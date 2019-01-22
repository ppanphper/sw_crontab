<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\helpers\CommonHelper;
use app\helpers\RequestAgentHelper;
use app\models\Agents;
use Yii;
use app\config\Constants;
use yii\console\Controller;
use \Swoole\Server as SwooleServer;
use \Exception;

class MonitorController extends Controller
{
    const WORKER_NUM = 2;
    const TASK_WORKER_NUM = 10;

    // 扫描节点
    const WORKER_SCAN_NODES = 0;
    // 检测节点
    const WORKER_CHECK_NODES = 1;

    // 更新节点进程起始Id
    const TASK_WORKER_UPDATE_NODES_START = self::WORKER_NUM;
    // 更新节点进程的结束Id
    const TASK_WORKER_UPDATE_NODES_END = self::TASK_WORKER_NUM + self::WORKER_NUM - 1;

    private $_configPath;
    protected $_processNamePrefix = '';

    protected $_swServer;

    /**
     * Worker Start
     * @var array
     */
    public $_initWorkerMaps = [];


    public function init()
    {
        parent::init();
        $this->_configPath = Yii::$app->basePath . '/config/agent_node.php';
        $this->_processNamePrefix = Yii::$app->id;
    }

    public function actionIndex()
    {
        $this->_swServer = new SwooleServer('127.0.0.1', 8902, SWOOLE_BASE);
        $config = [
            'worker_num'      => self::WORKER_NUM,
            'task_worker_num' => self::TASK_WORKER_NUM,
        ];
        $this->_swServer->set($config);
        $this->_swServer->on('Start', function() {
            $this->setProcessName('MonitorMaster');
        });
        $this->_swServer->on('ManagerStart', function() {
            $this->setProcessName('MonitorManager');
        });
        $this->_swServer->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->_swServer->on('Receive', function ($server, $fd, $from_id, $data) {});
        $this->_swServer->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->_swServer->on('Task', function ($server, $task_id, $from_id, $data) {});
        $this->_swServer->on('Finish', function ($server, $task_id, $from_id) {});

        $this->_initWorkerMaps = [
            self::WORKER_SCAN_NODES => [$this, 'scanNodes'],
            self::WORKER_CHECK_NODES => [$this, 'checkNodes'],
        ];

        $this->_swServer->start();
    }

    /**
     * Worker 和 Task进程启动的时候触发
     *
     * @param SwooleServer $server
     * @param $worker_id
     */
    public function onWorkerStart(SwooleServer $server, $worker_id)
    {
        if ($server->taskworker) {
            $this->setProcessName("Task|updateNodes|{$worker_id}");
        } else {
            // 获取当前进程Id
            if (isset($this->_initWorkerMaps[$worker_id])) {
                // 执行对应的匿名函数
                call_user_func_array($this->_initWorkerMaps[$worker_id], [$server, $worker_id]);
            } else {
                $this->setProcessName("Worker|{$worker_id}");
            }
        }
    }

    /**
     * Worker 和 Task 进程间通信接收数据
     *
     * @param SwooleServer $server
     * @param $src_worker_id
     * @param $data
     */
    public function onPipeMessage(SwooleServer $server, $src_worker_id, $data)
    {
        $key = $data['ip'].':'.$data['port'];
        $offlineTime = Yii::$app->params['offlineTime'];
        $currentTime = time();
        try {
            $trn = Yii::$app->db->beginTransaction();
            try {
                // 检测是否已在数据库中记录，不存在就写入
                $model = Agents::find()->where([
                    'ip' => $data['ip'],
                    'port' => $data['port']
                ])->one();
                if(empty($model)) {
                    $model = new Agents();
                    $model->name = $data['ip'].':'.$data['port'];
                    $model->ip = $data['ip'];
                    $model->port = $data['port'];
                    $model->status = Agents::STATUS_ENABLED;
                }
                $model->last_report_time = $data['time'];
                $agentStatus = Agents::AGENT_STATUS_ONLINE;
                // 当前时间 减去 最近上报时间 >= 离线时间阀值，就认为节点离线
                if (($currentTime - $data['time']) >= $offlineTime) {
                    $agentStatus = Agents::AGENT_STATUS_OFFLINE;
                    // ping一下节点是否能通，防止Redis的问题误判
                    $bool = $this->pingAgent($data['ip'], $data['port']);
                    if($bool) {
                        $agentStatus = Agents::AGENT_STATUS_ONLINE_REPORT_FAILED;
                    }
                }
                $model->agent_status = $agentStatus;
                $bool = $model->save();
                // Commit changes
                $trn->commit();
                $this->formatOutput('update node = '.$key.'; save['.($bool ? 'success' : 'failed').']');
            } catch (Exception $e) {
                // Rollback transaction
                $trn->rollback();
                throw new Exception($e->getMessage(), $e->getCode(), $e);
            }
        } catch (Exception $e) {
            $this->formatOutput('update node = '.$key.'; rollback[failed] Error = '.$e->getMessage());
        }
    }

    /**
     * Ping 节点检测是否正常
     *
     * @param $ip
     * @param $port
     *
     * @return bool
     */
    protected function pingAgent($ip, $port) {
        $bool = false;
        $result = RequestAgentHelper::controlCommand('ping', [], $ip, $port);
        if ($result['code'] == Constants::STATUS_CODE_SUCCESS) {
            $bool = true;
        }
        return $bool;
    }

    /**
     * 扫描节点
     *
     * @param SwooleServer $server
     * @param integer $worker_id
     */
    public function scanNodes($server, $worker_id)
    {
        $functionName = __FUNCTION__;
        $this->setProcessName("Worker|{$functionName}");
        $interval = Yii::$app->params['monitorInterval'];
        $server->tick($interval * 1000, function () use ($server, $interval, $functionName) {
            // 加锁
            $locked = CommonHelper::getConcurrentLock($functionName, 1, $interval);
            // 如果加锁失败，并且Redis连接正常
            if(!$locked && Yii::$app->redis->getHandle()) {
                return;
            }

            $nodes = [];
            $msg = '';

            $allNodes = Yii::$app->redis->hgetall(Constants::REDIS_KEY_AGENT_SERVER_LIST);
            if ($allNodes) {
                $dstWorkerId = self::TASK_WORKER_UPDATE_NODES_START;
                foreach ($allNodes as $key => $item) {
                    if(!preg_match('#(?:\d+\.){3}\d+:\d+#', $key)) continue;
                    $node = explode(':', $key);
                    if(count($node) == 2) {
                        $nodes[$key] = [
                            'ip'   => $node[0],
                            'port' => $node[1],
                            'time' => $item['time']
                        ];
                        $item['ip'] = $node[0];
                        $item['port'] = $node[1];
                        // 轮询发送注册/更新节点信息(非阻塞)
                        $server->sendMessage($item, $dstWorkerId);
                        $dstWorkerId++;
                        if ($dstWorkerId > self::TASK_WORKER_UPDATE_NODES_END) {
                            $dstWorkerId = self::TASK_WORKER_UPDATE_NODES_START;
                        }
                    }
                }
            }

            if (is_array($nodes) && count($nodes) > 0) {
                $configString = var_export($nodes, true);
                $updateFile = true;
                $content = "<?php" . PHP_EOL . "//This is generaled by client monitor" . PHP_EOL . "return " . $configString . ";";
                if(file_exists($this->_configPath)) {
                    $configContent = file_get_contents($this->_configPath);
                    if(md5($configContent) === md5($content)) {
                        $updateFile = false;
                        $msg = '';
                    }
                }
                // 频繁写会导致在高并发读的情况下，因为内容被重置include返回1
                if($updateFile) {
                    $ret = file_put_contents($this->_configPath, $content);
                    $msg = 'General config file to:' . $this->_configPath;
                    if (!$ret) {
                        $msg = 'Error save the config to file...';
                    }
                }
            }
            $msg && $this->formatOutput($msg);
            CommonHelper::getConcurrentLock($functionName, 0);
        });
    }

    /**
     * 检测节点是否长时间没有上报
     *
     * @param SwooleServer $server
     * @param integer $worker_id
     */
    public function checkNodes($server, $worker_id)
    {
        $this->setProcessName("Worker|checkNodes");
        $offlineTime = Yii::$app->params['offlineTime'];
        // 需要与扫描节点写文件的程序间隔开，否则会有一定几率读取空内容
        $interval = Yii::$app->params['monitorInterval'] + 5;
        $server->tick($interval * 1000, function () use ($server, $offlineTime) {
            $currentTime = time();
            // 如果有离线的节点就发邮件告警
            $notifyData = Agents::findAll([
                'agent_status' => [Agents::AGENT_STATUS_OFFLINE, Agents::AGENT_STATUS_ONLINE_REPORT_FAILED],
            ]);
            if ($notifyData) {
                $data = [];
                foreach($notifyData as $key=>$item) {
                    $data[$key] = $item->toArray();
                    $data[$key]['offlineTime'] = $currentTime - $item['last_report_time'];
                }
                unset($item, $notifyData, $key);
                $retries = 3;
                do {
                    $bool = Yii::$app->mailer->compose('monitor/notify', ['data'=>$data])
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject(('['.Yii::t('app', ucfirst(YII_ENV)).']').Yii::t('app', 'Monitor agent node offline alarm'))
                        ->send();
                    $retries--;
                    if(!$bool) {
                        sleep(1);
                    }
                } while (!$bool && $retries > 0);
            }
        });
    }

    protected function formatOutput($msg)
    {
        echo "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL;
    }

    /**
     * 设置进程名称
     *
     * @param $name
     * @param string $separator
     */
    protected function setProcessName($name, $separator = '|')
    {
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }

        $processNewName = $this->_processNamePrefix . $separator . $name;
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($processNewName);
        }
        else {
            swoole_set_process_name($processNewName);
        }
    }
}

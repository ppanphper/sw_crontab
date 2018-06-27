<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Agents;
use Yii;
use app\config\Constants;
use yii\console\Controller;
use \Swoole\Server as SwooleServer;
use \Swoole\Process as SwooleProcess;
use \Exception;

class MonitorController extends Controller
{
    private $_configPath;
    protected $_processNamePrefix = '';

    public function init()
    {
        parent::init();
        $this->_configPath = Yii::$app->basePath . '/config/agent_node.php';
        $this->_processNamePrefix = Yii::$app->id;
    }

    public function actionIndex()
    {
        // MacOS 不支持
        swoole_set_process_name($this->getProcessName('MonitorMaster'));
        $server = new SwooleServer('127.0.0.1', 8902, SWOOLE_BASE);
        $server->on('Receive', function ($server, $fd, $from_id, $data) {});

        // 扫描节点
        $server->addProcess(new SwooleProcess([$this, 'scanNodes']));

        // 检测节点是否长时间没有上报
        $server->addProcess(new SwooleProcess([$this, 'checkNodes']));

        $server->start();
    }

    /**
     * 扫描节点
     *
     * @param SwooleProcess $process
     */
    public function scanNodes($process)
    {
        // MacOS 不支持
        $process->name($this->getProcessName('scanNodes'));
        $interval = Yii::$app->params['monitorInterval'];
        while (true) {
            $nodes = [];
            $msg = '';

            $allNodes = Yii::$app->redis->hgetall(Constants::REDIS_KEY_AGENT_SERVER_LIST);
            if ($allNodes) {
                foreach ($allNodes as $key => $item) {
                    if(!preg_match('#(?:\d+\.){3}\d+:\d+#', $key)) continue;
                    $node = explode(':', $key);
                    if(count($node) == 2) {
                        $nodes[$key] = [
                            'ip'   => $node[0],
                            'port' => $node[1],
                            'time' => $item['time']
                        ];
                        try {
                            // 检测是否已在数据库中记录，不存在就写入
                            $exists = Agents::find()->where([
                                'ip' => $node[0],
                                'port' => $node[1]
                            ])->count();
                            if(empty($exists)) {
                                $model = new Agents();
                                $model->name = '自动注册节点';
                                $model->ip = $node[0];
                                $model->port = $node[1];
                                $model->status = 1;
                                $model->isNewRecord = true;
                                $bool = $model->save();
                                $this->formatOutput('自动注册节点 = '.$key.'; 保存['.($bool ? '成功' : '失败').']');
                            }
                        } catch (Exception $e) {
                            $this->formatOutput('自动注册节点 = '.$key.'; 保存[失败] Error = '.$e->getMessage());
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
            Yii::$app->redis->close();
            //sleep 10 sec
            sleep($interval);
        }
    }

    /**
     * 检测节点是否长时间没有上报
     *
     * @param SwooleProcess $process
     */
    public function checkNodes($process)
    {
        // 检测与扫描要间隔开
        $interval = Yii::$app->params['monitorInterval'];
        sleep($interval);

        // MacOS 不支持
        $process->name($this->getProcessName('checkNodes'));
        $offlineTime = Yii::$app->params['offlineTime'];
        // 需要与扫描节点写文件的程序间隔开，否则会有一定几率读取空内容，因为file_put_contents会把内容置空
        $interval = Yii::$app->params['monitorInterval'] + 3;
        while (true) {
            $currentTime = time();
            $notifyData = [];
            $alarmContent = '';
            if (file_exists($this->_configPath)) {
                $config = include $this->_configPath;
                if (is_array($config)) {
                    foreach ($config as $key => $item) {
                        if (($item['offlineTime'] = $currentTime - $item['time']) >= $offlineTime) {
                            $notifyData[] = $item;
                        }
                    }
                } else {
                    $alarmContent = '配置文件内容不是数组; Config = ' . var_export($config, true);
                }
            } else {
                $alarmContent = '配置文件不存在; ConfigPath = ' . var_export($this->_configPath, true);
            }

            if ($notifyData) {
                $retries = 3;
                do {
                    $bool = Yii::$app->mailer->compose('monitor/notify', ['data'=>$notifyData])
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject('监控代理节点离线报警')
                        ->send();
                    $retries--;
                } while (!$bool && $retries > 0);
            }
            if ($alarmContent) {
                $retries = 3;
                do {
                    $bool = Yii::$app->mailer->compose()
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject('监控代理节点配置文件报警')
                        ->setHtmlBody($alarmContent)
                        ->send();
                    $retries--;
                } while (!$bool && $retries > 0);
            }
            sleep($interval);
        }
    }

    protected function formatOutput($msg)
    {
        echo "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL;
    }

    protected function getProcessName($name) {
        return $this->_processNamePrefix . '|'.$name;
    }
}

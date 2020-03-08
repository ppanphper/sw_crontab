<?php

namespace app\models\searchs;

use app\config\Constants;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Crontab as CrontabModel;
use app\models\ViaTable;
use yii\data\Sort;

/**
 * Crontab represents the model behind the search form about `app\models\Crontab`.
 */
class Crontab extends CrontabModel
{
    /**
     * 是否是搜索状态
     * @var bool
     */
    public $isSearched = false;

    /**
     * 节点信息
     * @var null
     */
    public $agentInfo = null;

    /**
     * 搜索的节点Id
     * @var
     */
    public $agentId;

    /**
     * 搜索的负责人Id
     * @var
     */
    public $ownerId;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'cid', 'concurrency', 'max_process_time', 'status', 'ownerId', 'agentId'], 'integer'],
            [['name', 'rule', 'command', 'run_user', 'create_time', 'update_time'], 'safe'],
            // 判断最大长度
            [['max_process_time', 'concurrency'], 'number', 'min' => 0, 'max' => 99999999],
            [['status'], 'number', 'min' => 0, 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = CrontabModel::find()->alias('a');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // 是否有查询条件
        $attributes = $this->getAttributes();
        foreach($attributes as $item) {
            if($item !== '' && $item !== null) {
                $this->isSearched = true;
                break;
            }
        }

        if ($this->status === '' || (is_string($this->status) && trim($this->status) === '')) {
            $query->where(['>', 'a.status', -1]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'a.cid' => $this->cid,
            'a.status' => $this->status,
            'c.bid' => $this->ownerId,
        ]);
        $joinWith = [];
        if($this->agentId) {

            // 查询出不在这个节点运行的任务，过滤掉
//            SELECT a.id, b.*
//            FROM `crontab` `a`
//            LEFT JOIN `via_table` `b` ON (`a`.`id` = `b`.`aid` AND `b`.`type`=2)
//                        AND (b.bid = '1' OR b.bid IS NULL)
//            LEFT JOIN via_table c ON (`a`.`id` = `c`.`aid` AND `c`.`type`=3 AND c.`bid` = '1')
//            WHERE (`a`.`status` > -1) AND  c.id IS NULL
            /** @see Crontab::getAgents() */
            $joinWith['agents'] = function ($query) {
                $query->andOnCondition('b.bid = :bid OR b.bid IS NULL', [':bid' => $this->agentId]);
            };
            /** @see Crontab::getNotInAgents() */
            $joinWith['notInAgents'] = function ($query) {
                $query->andOnCondition('d.bid = :bid', [':bid' => $this->agentId])->andWhere('d.id IS NULL');
            };

            $this->agentInfo = [];
            $rows = Agents::getData();
            if($rows) {
                foreach($rows as $row) {
                    // 找到这个节点的Id
                    if($row['id'] == $this->agentId) {
                        $field = $row['ip'].':'.$row['port'];
                        // 获取上报的信息
                        $this->agentInfo = Yii::$app->redis->hget(Constants::REDIS_KEY_AGENT_SERVER_LIST, $field);
                        break;
                    }
                }
            }
        }

        if($this->ownerId) {
            /** @see Crontab::getOwners() */
            $joinWith[] = 'owners';
        }

        if($joinWith) {
            $this->isSearched = true;
            $query->select([
                'a.*',
            ])->joinWith($joinWith, false);
        }

        if ($this->max_process_time) {
            $query->andWhere(['>=', 'a.max_process_time', $this->max_process_time]);
        }

        $query->andFilterWhere(['like', 'a.name', $this->name])
            ->andFilterWhere(['like', 'a.rule', $this->rule])
            ->andFilterWhere(['like', 'a.command', $this->command])
            ->andFilterWhere(['like', 'a.run_user', $this->run_user]);

        $query->orderBy([
            'update_time' => SORT_DESC,
            'status' => SORT_DESC
        ]);

        $dataProvider->totalCount = $query->count();

        return $dataProvider;
    }
}

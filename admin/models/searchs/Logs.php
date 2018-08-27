<?php

namespace app\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Logs as LogsModel;

/**
 * Logs represents the model behind the search form about `app\models\Logs`.
 */
class Logs extends LogsModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'task_id', 'run_id', 'code'], 'integer'],
            [['consume_time'], 'number', 'max'=> 9999999999.999999, 'min'=>0],
            [['title','created'], 'safe'],
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
        $query = LogsModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
//					'task_id' => SORT_DESC,
//					'run_id' => SORT_DESC,
                ]
            ], // 新增配置项 默认 id 倒序
        ]);
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'task_id' => $this->task_id,
            'run_id' => $this->run_id,
//            'created' => $this->created,
        ]);

        if($this->consume_time) {
            $query->andWhere("consume_time >= :consume_time", [':consume_time' => $this->consume_time]);
        }

        if ($this->created) {
            $createTime = strtotime($this->created);
            $createTimeEnd = $createTime + 24*3600;
            $query->andWhere("created BETWEEN {$createTime} AND {$createTimeEnd}");
        }

        if($this->code == 255) {
            $query->andWhere("code BETWEEN 1 and 255");
        }
        else {
            $query->andFilterWhere(['code'=>$this->code]);
        }

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'msg', $this->msg]);

        $searchBool = false;
        // 没有查询条件
        $noQueryCondition = true;
        foreach($this->attributes as $key=>$attribute) {
            if(!empty($attribute)) {
                if($key == 'run_id') {
                    $searchBool = true;
//					break;
                }
                // 有查询条件
                $noQueryCondition = false;
            }
        }
        if($searchBool) {
            $dataProvider->setSort([
                'defaultOrder' => [
                    'code' => SORT_DESC,
                ]
            ]);
        }
        // 没有查询条件
        if($noQueryCondition) {
            // count 优化
            $countQuery = clone $query;
            // SELECT id FROM `logs` ORDER BY id DESC LIMIT 1;
            $count = $countQuery->select([
                'id'
            ])->orderBy(['id' => SORT_DESC])->limit(1)->one();
            $dataProvider->setTotalCount($count['id']);
        }
        return $dataProvider;
    }
}

<?php

namespace app\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Crontab as CrontabModel;

/**
 * Crontab represents the model behind the search form about `app\models\Crontab`.
 */
class Crontab extends CrontabModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'cid', 'concurrency', 'status'], 'integer'],
            [['name', 'rule', 'command', 'run_user', 'owner', 'agents', 'create_time', 'update_time'], 'safe'],
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
        $query = CrontabModel::find()->where(['<>', 'status', -1]);

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

        // grid filtering conditions
        $query->andFilterWhere([
            'id'          => $this->id,
            'cid'         => $this->cid,
            'concurrency' => $this->concurrency,
            'status'      => $this->status,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'rule', $this->rule])
            ->andFilterWhere(['like', 'command', $this->command])
            ->andFilterWhere(['like', 'run_user', $this->run_user])
            ->andFilterWhere(['like', 'owner', $this->owner])
            ->andFilterWhere(['like', 'agents', $this->agents]);

        return $dataProvider;
    }
}

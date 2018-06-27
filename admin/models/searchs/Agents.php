<?php

namespace app\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Agents as AgentsModel;
use yii\data\DataProviderInterface;

/**
 * Agents represents the model behind the search form about `app\models\Agents`.
 */
class Agents extends AgentsModel
{
    public $categoryId;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'port', 'status', 'categoryId'], 'integer'],
            [['name', 'ip'], 'safe'],
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
     * @return DataProviderInterface
     */
    public function search($params)
    {
        $query = AgentsModel::find()->joinWith(['category']);

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
            'port'        => $this->port,
            'status'      => $this->status,
            'category.id' => $this->categoryId
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'ip', $this->ip]);

        $dataProvider->totalCount = $query->count('DISTINCT agents.id');

        return $dataProvider;
    }
}

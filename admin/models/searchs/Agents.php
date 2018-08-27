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
     * 是否是搜索状态
     * @var bool
     */
    public $isSearched = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'port', 'agent_status', 'categoryId'], 'integer'],
            [['name', 'ip'], 'safe'],
            // 1.0.0.0 ~ 255.255.255.255
            ['ip', 'match', 'pattern' => '/^(?:(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)$/'],
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
        $query = AgentsModel::find()->alias('a');

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
        foreach ($attributes as $item) {
            if ($item !== '' && $item !== null) {
                $this->isSearched = true;
                break;
            }
        }

        if($this->categoryId) {
            $this->isSearched = true;
        }

        $query->where(['<>', 'a.status', -1]);

        // grid filtering conditions
        $query->andFilterWhere([
            'a.id'           => $this->id,
            'a.port'         => $this->port,
            'a.status'       => $this->status,
            'a.agent_status' => $this->agent_status,
        ]);

        $query->select([
            'DISTINCT(a.id)',
            'a.*',
        ])->joinWith(['category']);
        $query->andFilterWhere([
            'b.bid' => $this->categoryId,
        ]);
        $dataProvider->totalCount = $query->count('DISTINCT a.id');

        $query->andFilterWhere(['like', 'a.name', $this->name])
            ->andFilterWhere(['like', 'a.ip', $this->ip]);

        return $dataProvider;
    }
}

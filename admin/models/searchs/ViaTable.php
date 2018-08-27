<?php

namespace app\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\ViaTable as ViaTableModel;

/**
 * ViaTable represents the model behind the search form about `app\models\ViaTable`.
 */
class ViaTable extends ViaTableModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'aid', 'bid', 'type'], 'integer'],
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
        $query = ViaTableModel::find();

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
            'id' => $this->id,
            'aid' => $this->aid,
            'bid' => $this->bid,
            'type' => $this->type,
        ]);

        return $dataProvider;
    }
}

<?php

namespace Ua0leg\Yii2ActionLog\models;

use Ua0leg\Yii2ActionLog\Module;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ActionLogSearch extends ActionLog
{
    public function rules(): array
    {
        return [
            [['id', 'id_model', 'id_user', 'ipv4'], 'integer'],
            [['table_name', 'action', 'before', 'after', 'created_at'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search($params): ActiveDataProvider
    {
        $query = ActionLog::find();

        foreach (static::$excludedUserIds as $userId) {
            $query->andWhere(['!=', 'id_user', $userId]);
        }

        $pageSize = 100;
        foreach (Yii::$app->modules as $module) {
            if ($module instanceof Module) {
                $pageSize = $module->pageSize;
                break;
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort'       => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'         => $this->id,
            'id_model'   => $this->id_model,
            'id_user'    => $this->id_user,
            'ipv4'       => $this->ipv4,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'table_name', $this->table_name])
            ->andFilterWhere(['like', 'action', $this->action])
            ->andFilterWhere(['like', 'before', $this->before])
            ->andFilterWhere(['like', 'after', $this->after]);

        return $dataProvider;
    }
}

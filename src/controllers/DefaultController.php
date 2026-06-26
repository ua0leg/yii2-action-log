<?php

namespace Ua0leg\Yii2ActionLog\controllers;

use Ua0leg\Yii2ActionLog\components\ActionLogRevert;
use Ua0leg\Yii2ActionLog\models\ActionLog;
use Ua0leg\Yii2ActionLog\models\ActionLogSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new ActionLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionStat()
    {
        $days = (int) Yii::$app->request->get('days', 30);
        if (!in_array($days, [0, 7, 30, 90, 365], true)) {
            $days = 30;
        }

        return $this->render('stat', [
            'days'  => $days,
            'stats' => ActionLog::getStatistics($days),
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
            'diff'  => ActionLog::diffRows($model->before, $model->after),
        ]);
    }

    public function actionRevert()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $logId = (int) Yii::$app->request->post('log_id');
        $direction = (string) Yii::$app->request->post('direction', '');
        $attribute = Yii::$app->request->post('attribute');
        $attribute = $attribute !== null && $attribute !== '' ? (string) $attribute : null;

        try {
            ActionLogRevert::apply($this->findModel($logId), $direction, $attribute);

            return [
                'success' => true,
                'message' => 'Reverted',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    protected function findModel($id): ActionLog
    {
        if (($model = ActionLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

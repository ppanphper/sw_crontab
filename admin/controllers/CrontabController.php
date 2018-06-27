<?php

namespace app\controllers;

use Yii;
use app\models\Crontab;
use app\models\searchs\Crontab as CrontabSearch;
use yii\db\Exception;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * CrontabController implements the CRUD actions for Crontab model.
 */
class CrontabController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
//                    'changeStatus' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Crontab models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CrontabSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Crontab model.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Crontab model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Crontab();
        $model->setScenario('create');

        $loadBoolean = $model->load(Yii::$app->request->post());
        //Ajax表单验证
        if (Yii::$app->request->isAjax && $loadBoolean) {

            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($loadBoolean && $model->save()) {
            return $this->redirect(['index']);
        } else {
            $model->setScenario($model::SCENARIO_DEFAULT);
            $model->rule = '* * * * *';
            $model->concurrency = 0;
            $model->max_process_time = 600;
            $model->owner = Yii::$app->user->getIdentity()->getId();
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Crontab model.
     * If update is successful, the browser will be redirected to the 'index' page.
     *
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->setScenario('update');

        $loadBoolean = $model->load(Yii::$app->request->post());
        //Ajax表单验证
        if (Yii::$app->request->isAjax && $loadBoolean) {

            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($loadBoolean && $model->save()) {
            return $this->redirect(['index']);
        } else {
            $model->setScenario($model::SCENARIO_DEFAULT);
            if ($model->owner && is_string($model->owner)) {
                $model->owner = explode(',', $model->owner);
            }
            if ($model->agents && is_string($model->agents)) {
                $model->agents = explode(',', $model->agents);
            }
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Copy an existing Crontab model.
     * @param string $id
     * @return mixed
     */
    public function actionCopy($id)
    {
        if(Yii::$app->request->getIsPost()) {
            $model = new Crontab();
            $model->setScenario('create');
            $loadBoolean = $model->load(Yii::$app->request->post());
            //Ajax表单验证
            if(Yii::$app->request->isAjax && $loadBoolean) {

                Yii::$app->response->format=Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if ($loadBoolean && $model->save()) {
                return $this->redirect(['index']);
            }
        }
        $model = $this->findModel($id);
        $model->setScenario($model::SCENARIO_DEFAULT);
        if($model->owner && is_string($model->owner)) {
            $model->owner = explode(',', $model->owner);
        }
        if($model->agents && is_string($model->agents)) {
            $model->agents = explode(',', $model->agents);
        }
        $model->isNewRecord = true;
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Crontab model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        // 标记为删除
        $model->status = -1;
        $model->update_time = time();
        $model->save(false, ['status', 'update_time']);

        return $this->redirect(['index']);
    }

    public function actionChangeStatus()
    {
        $result = [
            'status' => 0,
            'msg'    => Yii::t('app', 'Change the failure'),
            'data'   => []
        ];
        try {
            $id = Yii::$app->request->post('id');
            $model = $this->findModel($id);
            $status = intval(Yii::$app->request->post('status'));
            $model->status = !$status;
            $bool = $model->save(false, ['status']);
            if ($bool) {
                $result['status'] = 1;
                $result['msg'] = Yii::t('app', 'Change the success');
                $result['data'] = [
                    'label'  => Yii::t('app', ($model->status ? 'Enabled' : 'Disabled')),
                    'status' => intval($model->status),
                ];
            }
        } catch (Exception $e) {
        }
        return $this->asJson($result);
    }

    /**
     * Finds the Crontab model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param string $id
     *
     * @return Crontab the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Crontab::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

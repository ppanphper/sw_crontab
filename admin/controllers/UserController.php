<?php

namespace app\controllers;

use mdm\admin\components\Configs;
use Yii;
use app\models\User;
use app\models\searchs\User as UserSearch;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     *
     * @param integer $id
     *
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();
        $model->setScenario('create');

        $loadBoolean = $model->load(Yii::$app->request->post());
        //Ajax表单验证
        if (Yii::$app->request->isAjax && $loadBoolean) {

            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($loadBoolean && $model->save()) {
            return $this->redirect(['index']);
        }
        $model->setScenario($model::SCENARIO_DEFAULT);

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
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
        if ($loadBoolean) {
            $attributeNames = [
                'username',
                'nickname',
                'mobile',
                'email',
                'status',
                'update_time',
            ];
            // 输入了密码才修改
            if ($model->password !== '') {
                $attributeNames[] = 'password';
            }
            if ($model->save(true, $attributeNames)) {
                return $this->redirect(['index']);
            }
        }
        $model->setScenario($model::SCENARIO_DEFAULT);
        $model->password = '';
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        // 把权限记录也删除掉
        $model->on($model::EVENT_AFTER_DELETE, function ($event) {
            $userId = $event->data->id;
            $manager = Configs::authManager();
            $assignments = $manager->getAssignments($userId);
            if ($assignments) {
                foreach ($assignments as $name => $assignment) {
                    try {
                        $item = new \StdClass();
                        $item->name = $name;
                        $manager->revoke($item, $userId);
                    } catch (\Exception $exc) {
                        Yii::error($exc->getMessage(), __METHOD__);
                    }
                }
            }
        }, $model);
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}

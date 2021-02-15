<?php

namespace app\controllers;

use Yii;
use yii\db\Expression;
use app\models\Avisosusuarios;
use app\models\Articulostienda;
use app\models\ArticulostiendaSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Tiendas;
use app\models\TiendasSearch;
use app\models\Articulos;
use app\models\ArticulosSearch;
use yii\web\UploadedFile;
use app\models\Comentarios;
use app\models\ComentariosSearch;
use yii\filters\AccessControl;

/**
 * ArticulostiendaController implements the CRUD actions for Articulostienda model.
 */
class ArticulostiendaController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
	            'class' => AccessControl::className(),
	            'rules' => [
	                [
	                    'allow' => true,
	                    'actions' => ['index', 'view', 'update', 'bloqueo', 'quitabloqueo'],
	                    'roles' => ['admin','moderador', 'sysadmin'],
	                ],
	                [
	                    'allow' => true,
	                    'actions' => ['delete', 'create'],
	                    'roles' => ['admin', 'sysadmin'],
	                ],
	                [
	                    'allow' => true,
	                    'actions' => ['denuncia'],
	                    'roles' => ['admin', 'sysadmin','moderador', 'normal'],
	                ],
	            ],
            ],
        ];
    }

    /**
     * Lists all Articulostienda models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ArticulostiendaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Articulostienda model.
     * @param integer $id
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
     * Creates a new Articulostienda model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Articulostienda(['scenario'=>'crear']);
        $modelousuario = new Articulos();
		    $modelotienda= new Tiendas();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			
      			$model->imagen = UploadedFile::getInstance($model, 'imagen');
      					
      			
      			$model->save();

            if($model->imagen){
              $nombre=$model->tienda_id.'_'.$model->articulo_id;
              $model->imagen_id=$nombre.'.'.$model->imagen->extension;
              $model->save();
        			$model->imagen->saveAs('uploads/'.$model->imagen_id);

      			}
            return $this->redirect(['view', 'id' => $model->id]);
        }
		
		    $modelotienda = Tiendas::findOne($idtienda=Yii::$app->request->get('id_tienda'));
        $modeloart=Articulos::findOne($idarticulo=Yii::$app->request->get('id_articulo'));

        if ($modeloart === null || $modelotienda === null) {
            
            return $this->redirect(['tiendas/elegir_tienda','modo'=>2]);

        }

        $model->articulo_id= $idarticulo;
		    $model->tienda_id= $idtienda;
        
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Articulostienda model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
			
			$model->imagen = UploadedFile::getInstance($model, 'imagen');
			
			$model->save();

      if($model->imagen){
        $nombre=$model->tienda_id.'_'.$model->articulo_id;
        $model->imagen_id=$nombre.'.'.$model->imagen->extension;
        $model->save();
        $model->imagen->saveAs('uploads/'.$model->imagen_id);

      }
			
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Articulostienda model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model=$this->findModel($id);
		
		$tienda_id=$model->tienda_id;
		$articulo_id=$model->articulo_id;
		
		$model->delete();
		
		$modelC=Comentarios::findAll(['tienda_id'=>$tienda_id,'articulo_id'=>$articulo_id]);
		if($modelC!==null)
		{
			foreach($modelC as $coment)
			{
				$coment->delete();
			}
		}
		
		$modelT=Tiendas::findOne($tienda_id);
		if($modelT!==null){
			$modelT->actualizarVotos();
		}

        return $this->redirect(['index']);
    }

    /**
     * Finds the Articulostienda model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Articulostienda the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Articulostienda::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionBloqueo($id)
    {
        $model = $this->findModel($id);

        $model->scenario='bloqueo';

        if($model->bloqueado!=0){

              return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post())) {
            
            $model->bloqueado=2;
            $model->save();
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('bloqueos', [
            'model' => $model,
        ]);
    }

    public function actionDenuncia($id)
    {

        $model = $this->findModel($id);

        $aviso = new Avisosusuarios();

        if ($model->load(Yii::$app->request->post()) || $aviso->load(Yii::$app->request->post())) {

   

          $aviso->clase_aviso='D';
          $aviso->fecha_aviso=new Expression('NOW()');
          $aviso->tienda_id=$model->tienda_id;
          $aviso->articulo_id=$model->articulo_id;
          

          $model->num_denuncias=$model->num_denuncias+1;

          /*El numero maximo de denuncias es 10 */
          if($model->num_denuncias===10){

            //$model->num_denuncias=$model->num_denuncias+1;
            $model->bloqueado=1;
            $model->fecha_bloqueo=new Expression('NOW()');
          }

          

          if($model->num_denuncias===1){
			$model->fecha_denuncia1=new Expression('NOW()');
            $aviso->texto=$model->notas_denuncia;
          }
		  
		  $model->save();
          $aviso->save();

          return $this->goHome();
        }

        if($model->num_denuncias===0){

            return $this->render('denuncias', [
              'model' => $model, 'aviso' => $aviso
            ]);

        }else{
           return $this->render('denuncias2', [
              'model' => $model, 'aviso' => $aviso
            ]);
        }
    }

    public function actionQuitabloqueo($id)
    {
        $model = $this->findModel($id);

        if($model===NULL){
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if($model->bloqueado!=0){

            $model->bloqueado=0;
            $model->notas_denuncia=NULL;
            $model->num_denuncias=0;
            $model->notas_denuncia=NULL;
            $model->fecha_bloqueo=NULL;
            $model->notas_bloqueo=NULL;
            $model->save();
            return $this->redirect(['view', 'id' => $model->id]);
        };
    }
}

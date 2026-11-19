<?php

namespace app\controllers;

use app\models\Product;
use app\models\StockForm;
use app\models\StockMovement;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;

class ProductController extends ActiveController
{
    public $modelClass = Product::class;
    public $createScenario = Product::SCENARIO_CREATE;
    public $updateScenario = Product::SCENARIO_UPDATE;

    // Sertakan data paginasi di body: { items: [...], _meta: {...}, _links: {...} }
    public $serializer = [
        'class' => \yii\rest\Serializer::class,
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = ['class' => HttpBearerAuth::class];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareIndex'];
        return $actions;
    }

    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'low-stock' => ['GET', 'HEAD'],
            'stock-in' => ['POST'],
            'stock-out' => ['POST'],
            'movements' => ['GET', 'HEAD'],
        ]);
    }

    /**
     * GET /products?q=laptop&category=Elektronik&sort=-stock
     */
    public function prepareIndex(): ActiveDataProvider
    {
        $request = Yii::$app->request;
        $query = Product::find();

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->andWhere(['or', ['like', 'name', $q], ['like', 'sku', $q]]);
        }
        $category = trim((string) $request->get('category', ''));
        if ($category !== '') {
            $query->andWhere(['category' => $category]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['defaultPageSize' => 20, 'pageSizeLimit' => [1, 100]],
            'sort' => [
                'attributes' => ['id', 'sku', 'name', 'category', 'price', 'stock', 'updated_at'],
                'defaultOrder' => ['id' => SORT_ASC],
            ],
        ]);
    }

    /**
     * GET /products/low-stock — produk dengan stok <= stok minimum.
     */
    public function actionLowStock(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Product::find()->where('[[stock]] <= [[min_stock]]'),
            'pagination' => ['defaultPageSize' => 50, 'pageSizeLimit' => [1, 100]],
            'sort' => ['defaultOrder' => ['stock' => SORT_ASC, 'id' => SORT_ASC]],
        ]);
    }

    public function actionStockIn(int $id)
    {
        return $this->recordMovement($id, StockMovement::TYPE_IN);
    }

    public function actionStockOut(int $id)
    {
        return $this->recordMovement($id, StockMovement::TYPE_OUT);
    }

    /**
     * GET /products/{id}/movements — riwayat stok terbaru lebih dulu.
     */
    public function actionMovements(int $id): ActiveDataProvider
    {
        $product = $this->findProduct($id);
        return new ActiveDataProvider([
            'query' => $product->getMovements()->with('user'),
            'pagination' => ['defaultPageSize' => 20, 'pageSizeLimit' => [1, 100]],
            'sort' => false,
        ]);
    }

    private function recordMovement(int $id, string $type)
    {
        $product = $this->findProduct($id);
        $form = new StockForm();
        $form->load(Yii::$app->request->getBodyParams(), '');
        $movement = $form->apply($product, $type, Yii::$app->user->id);
        if ($movement === null) {
            return $form; // Serializer mengubahnya menjadi 422 + daftar error
        }
        Yii::$app->response->setStatusCode(201);
        return ['movement' => $movement, 'product' => $product];
    }

    private function findProduct(int $id): Product
    {
        $product = Product::findOne($id);
        if ($product === null) {
            throw new NotFoundHttpException("Produk #$id tidak ditemukan.");
        }
        return $product;
    }
}

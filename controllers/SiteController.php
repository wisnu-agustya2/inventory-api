<?php

namespace app\controllers;

use yii\rest\Controller;

class SiteController extends Controller
{
    public function actionHealth()
    {
        return ['status' => 'ok'];
    }
}

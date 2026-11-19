<?php

namespace app\controllers;

use app\models\LoginForm;
use Yii;
use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;

class AuthController extends Controller
{
    protected function verbs()
    {
        return ['login' => ['POST']];
    }

    /**
     * POST /auth/login  { "username": "...", "password": "..." }
     */
    public function actionLogin()
    {
        $form = new LoginForm();
        $form->load(Yii::$app->request->getBodyParams(), '');

        if (!$form->validate()) {
            if ($form->hasErrors('password') && !$form->hasErrors('username') && $form->password) {
                throw new UnauthorizedHttpException($form->getFirstError('password'));
            }
            return $form; // 422: field wajib belum diisi
        }

        $user = $form->getUser();
        $ttl = (int) Yii::$app->params['tokenTtl'];
        return [
            'token' => $user->issueToken($ttl),
            'token_type' => 'Bearer',
            'expires_at' => $user->token_expires_at,
            'user' => $user,
        ];
    }
}

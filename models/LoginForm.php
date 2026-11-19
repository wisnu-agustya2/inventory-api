<?php

namespace app\models;

use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;

    private ?User $_user = null;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            [['username', 'password'], 'string', 'max' => 128],
            [['password'], 'validateCredentials'],
        ];
    }

    public function validateCredentials($attribute)
    {
        if ($this->hasErrors()) {
            return;
        }
        $user = User::findOne(['username' => $this->username]);
        // Pesan sama untuk username dan password salah agar tidak membocorkan username yang terdaftar.
        if ($user === null || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Username atau password salah.');
            return;
        }
        $this->_user = $user;
    }

    public function getUser(): ?User
    {
        return $this->_user;
    }
}

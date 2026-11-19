<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $password_hash
 * @property string|null $access_token_hash
 * @property int|null $token_expires_at
 * @property int $created_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return '{{%user}}';
    }

    public function rules()
    {
        return [
            [['username'], 'required'],
            [['username'], 'string', 'min' => 3, 'max' => 64],
            [['username'], 'match', 'pattern' => '/^[a-zA-Z0-9_.-]+$/'],
            [['username'], 'unique'],
        ];
    }

    public function fields()
    {
        return ['id', 'username'];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Buat token baru. Yang disimpan di database hanya hash-nya,
     * jadi token tidak bocor walau database bocor.
     */
    public function issueToken(int $ttl): string
    {
        $token = Yii::$app->security->generateRandomString(40);
        $this->access_token_hash = hash('sha256', $token);
        $this->token_expires_at = time() + $ttl;
        $this->save(false, ['access_token_hash', 'token_expires_at']);
        return $token;
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (!is_string($token) || $token === '') {
            return null;
        }
        $user = static::findOne(['access_token_hash' => hash('sha256', $token)]);
        if ($user === null || $user->token_expires_at < time()) {
            return null;
        }
        return $user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }
}

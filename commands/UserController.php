<?php

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manajemen user API.
 */
class UserController extends Controller
{
    /**
     * Membuat user baru: php yii user/create <username> <password>
     */
    public function actionCreate(string $username, string $password): int
    {
        if (strlen($password) < 8) {
            $this->stderr("Password minimal 8 karakter.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $user = new User(['username' => $username]);
        $user->setPassword($password);
        if (!$user->save()) {
            foreach ($user->getFirstErrors() as $error) {
                $this->stderr($error . "\n", Console::FG_RED);
            }
            return ExitCode::DATAERR;
        }
        $this->stdout("User '{$user->username}' dibuat (id {$user->id}).\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}

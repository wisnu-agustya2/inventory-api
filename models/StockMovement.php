<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $user_id
 * @property string $type
 * @property int $quantity
 * @property int $stock_after
 * @property string|null $note
 * @property int $created_at
 */
class StockMovement extends ActiveRecord
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    public static function tableName()
    {
        return '{{%stock_movement}}';
    }

    public function fields()
    {
        return [
            'id',
            'product_id' => fn () => (int) $this->product_id,
            'type',
            'quantity' => fn () => (int) $this->quantity,
            'stock_after' => fn () => (int) $this->stock_after,
            'note',
            'user' => fn () => $this->user?->username,
            'created_at' => fn () => (int) $this->created_at,
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}

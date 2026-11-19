<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $category
 * @property string $unit
 * @property string $price
 * @property int $stock
 * @property int $min_stock
 * @property int $created_at
 * @property int $updated_at
 */
class Product extends ActiveRecord
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public static function tableName()
    {
        return '{{%product}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function scenarios()
    {
        $editable = ['sku', 'name', 'category', 'unit', 'price', 'min_stock'];
        return [
            self::SCENARIO_DEFAULT => array_merge($editable, ['stock']),
            // Stok awal hanya boleh diisi saat membuat produk.
            self::SCENARIO_CREATE => array_merge($editable, ['stock']),
            // Setelah itu stok hanya berubah lewat stock-in / stock-out agar riwayat tetap akurat.
            self::SCENARIO_UPDATE => $editable,
        ];
    }

    public function rules()
    {
        return [
            [['sku', 'name'], 'required'],
            [['sku', 'name', 'category', 'unit'], 'trim'],
            [['sku'], 'string', 'max' => 32],
            [['sku'], 'match', 'pattern' => '/^[A-Za-z0-9_-]+$/', 'message' => 'SKU hanya boleh huruf, angka, - dan _'],
            [['sku'], 'filter', 'filter' => 'strtoupper'],
            [['sku'], 'unique'],
            [['name'], 'string', 'max' => 150],
            [['category'], 'string', 'max' => 64],
            [['unit'], 'string', 'max' => 16],
            [['category'], 'default', 'value' => 'Umum'],
            [['unit'], 'default', 'value' => 'pcs'],
            [['price'], 'number', 'min' => 0],
            [['price'], 'default', 'value' => 0],
            [['stock', 'min_stock'], 'integer', 'min' => 0],
            [['stock', 'min_stock'], 'default', 'value' => 0],
        ];
    }

    public function fields()
    {
        return [
            'id',
            'sku',
            'name',
            'category',
            'unit',
            'price' => fn () => (float) $this->price,
            'stock' => fn () => (int) $this->stock,
            'min_stock' => fn () => (int) $this->min_stock,
            'is_low_stock' => fn () => $this->isLowStock(),
            'created_at',
            'updated_at',
        ];
    }

    public function isLowStock(): bool
    {
        return (int) $this->stock <= (int) $this->min_stock;
    }

    public function afterDelete()
    {
        // SQLite di project ini tidak memakai foreign key, jadi hapus riwayat secara eksplisit.
        StockMovement::deleteAll(['product_id' => $this->id]);
        parent::afterDelete();
    }

    public function getMovements()
    {
        return $this->hasMany(StockMovement::class, ['product_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }
}

<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Mencatat stok masuk/keluar secara atomik.
 */
class StockForm extends Model
{
    public $quantity;
    public $note;

    public function rules()
    {
        return [
            [['quantity'], 'required'],
            [['quantity'], 'integer', 'min' => 1, 'max' => 1000000],
            [['note'], 'trim'],
            [['note'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return StockMovement|null null jika validasi gagal (lihat getErrors()).
     */
    public function apply(Product $product, string $type, ?int $userId): ?StockMovement
    {
        if (!$this->validate()) {
            return null;
        }
        $qty = (int) $this->quantity;
        $db = Yii::$app->db;

        $transaction = $db->beginTransaction();
        try {
            // Satu UPDATE bersyarat: aman dari race condition dan tidak pernah membuat stok negatif.
            $condition = ['id' => $product->id];
            if ($type === StockMovement::TYPE_OUT) {
                $condition = ['and', $condition, ['>=', 'stock', $qty]];
            }
            $affected = Product::updateAllCounters(
                ['stock' => $type === StockMovement::TYPE_IN ? $qty : -$qty],
                $condition
            );
            if ($affected === 0) {
                $transaction->rollBack();
                $product->refresh();
                $this->addError('quantity', "Stok tidak cukup. Stok tersedia: {$product->stock}");
                return null;
            }
            Product::updateAll(['updated_at' => time()], ['id' => $product->id]);
            $product->refresh();

            $movement = new StockMovement([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $qty,
                'stock_after' => $product->stock,
                'note' => $this->note === '' ? null : $this->note,
                'created_at' => time(),
            ]);
            $movement->save(false);
            $transaction->commit();
            return $movement;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}

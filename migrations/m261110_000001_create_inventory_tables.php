<?php

use yii\db\Migration;

class m261110_000001_create_inventory_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(64)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'access_token_hash' => $this->string(64)->null()->unique(),
            'token_expires_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'sku' => $this->string(32)->notNull()->unique(),
            'name' => $this->string(150)->notNull(),
            'category' => $this->string(64)->notNull()->defaultValue('Umum'),
            'unit' => $this->string(16)->notNull()->defaultValue('pcs'),
            'price' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'stock' => $this->integer()->notNull()->defaultValue(0),
            'min_stock' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_product_category', '{{%product}}', 'category');

        $this->createTable('{{%stock_movement}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->null(),
            'type' => $this->string(3)->notNull(),
            'quantity' => $this->integer()->notNull(),
            'stock_after' => $this->integer()->notNull(),
            'note' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_movement_product', '{{%stock_movement}}', ['product_id', 'id']);

        // SQLite tidak mendukung ALTER TABLE ADD FOREIGN KEY
        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey('fk_movement_product', '{{%stock_movement}}', 'product_id', '{{%product}}', 'id', 'CASCADE');
            $this->addForeignKey('fk_movement_user', '{{%stock_movement}}', 'user_id', '{{%user}}', 'id', 'SET NULL');
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%stock_movement}}');
        $this->dropTable('{{%product}}');
        $this->dropTable('{{%user}}');
    }
}

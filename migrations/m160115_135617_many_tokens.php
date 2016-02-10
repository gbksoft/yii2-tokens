<?php

/**
 * Generate model in console:
 *
 * ./yii migrate
 * ./yii gii/model --tableName=user_token --modelClass=UserToken --ns=common\\models
 */

use yii\db\Migration;
use yii\base\Event;

class m160115_135617_many_tokens extends Migration
{
    const EVENT_BEFORE_MIGRATE_UP = 'beforeUserTokenMigrateUp';
    
    public $usersTableName = '{{%user}}';
    public $userIdField = null;
    
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        // Trigger events
        Yii::$app->trigger(self::EVENT_BEFORE_MIGRATE_UP, new Event([
            'sender' => $this,
        ]));
        
        $this->createTable('{{%user_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->userIdField ? $this->userIdField : $this->integer(),
            'token' => $this->string(128)->notNull(),
            'verify_ip' => $this->boolean()->defaultValue(false),
            
            // @link http://stackoverflow.com/a/20473371
            'user_ip' => $this->string(46),
            
            // @link http://stackoverflow.com/a/20746656
            'user_agent' => $this->text(),
            
            'frozen_expire'  => $this->boolean()->defaultValue(true),
            
            'created_at' => $this->dateTime(),
            'expired_at'  => $this->dateTime(),
        ], $tableOptions);
        
        $this->addForeignKey('fk_token_user', '{{%user_token}}', 'user_id', $this->usersTableName, 'id', 'CASCADE');
        $this->createIndex('i_user_token', '{{%user_token}}', 'token');
        $this->createIndex('i_user_token_expired', '{{%user_token}}', ['token', 'expired_at']);
    }

    public function down()
    {
        $this->dropIndex('i_user_token_expired', '{{%user_token}}');
        $this->dropIndex('i_user_token', '{{%user_token}}');
        $this->dropForeignKey('fk_token_user', '{{%user_token}}');
        $this->dropTable('{{%user_token}}');
        return true;
    }
}

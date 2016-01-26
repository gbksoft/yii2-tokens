<?php

namespace gbksoft\tokens\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user_token".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $token
 * @property integer $verify_ip
 * @property string $user_ip
 * @property boolean $frozen_expire
 * @property string $user_agent
 * @property string $created_at
 * @property string $expired_at
 *
 * @property User $user
 */
class UserToken extends ActiveRecord
{
    const SCENARIO_CREATE_FOR_USER = 'createForUser';
    
    /**
     * Default life time for user token
     */
    const EXPIRE_DEFAULT_SECONDS = 604800; // One week
    
    /**
     * User relation class name
     * @var \yii\web\IdentityInterface
     */
    public $userClass;
    
    public function init()
    {
        if (!$this->userClass) {
            $this->userClass = Yii::$app->controller->module->userClass;
        }
        return parent::init();
    }
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_token}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'token'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['token'], 'required', 'on' => self::SCENARIO_CREATE_FOR_USER],
            [['user_id'], 'integer'],
            [['user_agent'], 'string'],
            [['created_at', 'expired_at'], 'safe'],
            [['token'], 'string', 'max' => 128],
            [['user_ip'], 'string', 'max' => 46],
            [['frozen_expire', 'verify_ip'], 'boolean', 'skipOnEmpty' => true],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = [
            'id',
            'token',
            'expired_at',
            'frozen_expire',
            'verify_ip',
        ];
        
        return $fields;
    }
    
    /**
     * @inheritdoc
     */
    public function scenarios() {
        return \yii\helpers\ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE_FOR_USER => [],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'token' => 'Token',
            'verify_ip' => 'Verify IP',
            'user_ip' => 'User IP',
            'frozen_expire' => 'Frozen Expire',
            'user_agent' => 'User Agent',
            'created_at' => 'Created At',
            'expired_at' => 'Expired At',
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne($this->userClass, ['id' => 'user_id']);
    }
    
    /**
     * Set additional client info
     * @return boolean;
     */
    public function updateClientInfo()
    {
        $request = Yii::$app->getRequest();
        
        $this->user_ip = $request->getUserIP();
        $this->user_agent = $request->getUserAgent();
        
        return true;
    }
    
    /**
     * Generate new token for user
     * 
     * @param integer $userId
     * @param integer $expiredSeconds
     * @return boolean|common\models\UserToken
     */
    public static function createForUser(IdentityInterface $user, $seconds = 0, $remember = false, $verifyIP = false)
    {
        $seconds = intval($seconds);
        if (!$seconds) {
            $seconds = self::EXPIRE_DEFAULT_SECONDS;
        }
        
        $userToken = new self(['scenario' => self::SCENARIO_CREATE_FOR_USER]);
        
        $userToken->token = Yii::$app->getSecurity()->generateRandomString();
        $userToken->expired_at = new Expression('DATE_ADD(NOW(), INTERVAL ' . $seconds . ' SECOND)');
        $userToken->frozen_expire = !$remember;
        $userToken->verify_ip = (bool) $verifyIP;
        
        $userToken->updateClientInfo();
        
        if ($userToken->save()) {
            $userToken->link('user', $user);
            $userToken->refresh();
            
            return $userToken;
        } else {
            return false;
        }
    }
    
    /**
     * Extend time to expired_at fields
     * 
     * @param integer $seconds
     * @return boolean
     * @throws \yii\base\InvalidConfigException
     */
    public function extend($seconds = 0)
    {
        if ($this->frozen_expire) {
            throw new \yii\base\InvalidConfigException('Time expiration token can not be extended.');
        }
        
        $seconds = intval($seconds);
        if (!$seconds) {
            $seconds = self::EXPIRE_DEFAULT_SECONDS;
        }
        
        $this->expired_at = new Expression('DATE_ADD(NOW(), INTERVAL ' . $seconds . ' SECOND)');
        
        return $this->save();
    }
    
    /**
     * Match ip with current model ip
     * @param string $ip
     * @return boolean
     */
    public function matchIp($ip)
    {
        return $this->verify_ip ? $this->user_ip == $ip : true;
    }
    
    /**
     * Check token for expired
     * @return boolean
     */
    public function expired()
    {
        $expired_at_timestamp = (int) Yii::$app->formatter->asTimestamp($this->expired_at);
        $now = (int) Yii::$app->formatter->asTimestamp(time());
        return $now >= $expired_at_timestamp;
    }
}

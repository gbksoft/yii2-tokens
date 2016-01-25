<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace api\versions\v1\controllers\userToken;

use Yii;
use yii\web\ServerErrorHttpException;
use common\models\UserToken;
use yii\helpers\Url;

/**
 * Create new user token for current identity
 */
class CreateAction extends \yii\rest\CreateAction {
    /**
     * Field name of body parameter for remember
     * @var string
     */
    public $rememberField = 'remember';
    
    /**
     * Confirm value of body parameter for remember
     * @var array
     */
    public $rememberYesValue = 'yes';
    
    /**
     * Confirm value of body parameter for not remember
     * @var array
     */
    public $rememberNoValue = 'no';
    
    /**
     * Field name of body parameter for verify_ip
     * @var string
     */
    public $verifyIpField = 'verify_ip';
    
    /**
     * Confirm value of body parameter for verify_ip
     * @var array
     */
    public $verifyIpYesValue = 'yes';
    
    
    /**
     * If remember me expire time as seconds
     * @var integer
     */
    public $expireSecondsRemember = 604800; // One week
    /**
     * If not remember me expire time as seconds
     * @var integer
     */
    public $expireSecondsNotRemember = 3600; // One hour
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        /** @var $request \yii\web\Request */
        $request = Yii::$app->getRequest();
        
        $rememberValue = $request->getBodyParam($this->rememberField);
        $verifyIP = ($request->getBodyParam($this->verifyIpField) == $this->verifyIpYesValue);

        switch ($rememberValue) {
            case $this->rememberYesValue:
                $remember = true;
                $seconds = $this->expireSecondsRemember;
                break;
            case $this->rememberNoValue:
                $remember = false;
                $seconds = $this->expireSecondsNotRemember;
                break;
            default:
                $remember = false;
                $seconds = UserToken::EXPIRE_DEFAULT_SECONDS;
        }
        
        /* @var $model \common\models\UserToken */
        $model = UserToken::createForUser(Yii::$app->user->identity, $seconds, $remember, $verifyIP);
        
        if ($model) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create user token.');
        }

        return UserToken::findOne($model->primaryKey);
    }
}
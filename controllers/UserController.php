<?php
namespace gbksoft\modules\tokens\controllers;

use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use yii\rest\ActiveController;

/**
 * Class UserTokenController
 * 
 * @package gbksoft\modules\tokens\controllers
 */
class UserController extends ActiveController
{
    const EVENT_BEFORE_BEHAVIOR = 'beforeControllerBehavior';
    
    public $modelClass = 'gbksoft\modules\tokens\models\UserToken';
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $event = new \yii\base\Event();
        $event->data = [];
        $this->module->trigger(self::EVENT_BEFORE_BEHAVIOR, $event);
        
        return ArrayHelper::merge(parent::behaviors(), $event->data, [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['options'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'view', 'current', 'extend'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ]);
    }
    
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            
            // Create new user token for current identity
            'create' => [
                'class' => 'gbksoft\modules\tokens\controllers\user\CreateAction',
            ],
            
            // View by user token primaryKey
            'view' => [
                'class' => 'gbksoft\modules\tokens\controllers\user\ViewAction',
            ],
                    
            // Get current user token
            'current' => [
                'class' => 'gbksoft\modules\tokens\controllers\user\CurrentAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            
            // Extend user token expired time
            'extend' => [
                'class' => 'gbksoft\modules\tokens\controllers\user\ExtendAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            
        ]);
    }
}

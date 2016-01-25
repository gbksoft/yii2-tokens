<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace api\versions\v1\controllers\userToken;

use Yii;
use yii\helpers\Url;

/**
 * Display user token for current identity
 */
class CurrentAction extends \yii\rest\ViewAction {
    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully created.
     */
    public $viewAction = 'view';
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        return parent::run(null);
    }
    
    /**
     * @inheritdoc
     */
    public function findModel($id) {
        $model = Yii::$app->user->identity->userToken;
        
        $id = implode(',', array_values($model->getPrimaryKey(true)));
        Yii::$app->getResponse()->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        
        return $model;
    }
}
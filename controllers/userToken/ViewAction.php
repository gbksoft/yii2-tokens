<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace gbksoft\tokens\controllers\userToken;

use Yii;
use yii\web\ServerErrorHttpException;

/**
 * Display user token for current identity
 */
class ViewAction extends \yii\rest\ViewAction {
    /**
     * @inheritdoc
     */
    public function run($id)
    {
        $model = parent::findModel($id);
        
        // Check for current user
        if ($model->user->primaryKey != Yii::$app->user->identity->primaryKey) {
            throw new ServerErrorHttpException('View this object is forbidden.');
        }

        return $model;
    }
}
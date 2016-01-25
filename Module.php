<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace gbksoft\tokens;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\ForbiddenHttpException;

/**
 * This is the main module class for the Tokens module.
 *
 *
 * @author Hryhorii Furletov <littlefuntik@gmail.com>
 * @since 2.0
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'gbksoft\tokens\controllers';
    /**
     * Add default url rules to urlManager for module controllers
     * POST tokens/user-token         - create new userToken object for current identity
     * POST tokens/user-token/current - userToken object for current identity
     * POST tokens/user-token/extend  - add life to userToken for current identity
     * GET tokens/user-token/{id}     - view userToken object by pk for current user (!)
     */
    public $setUrlRules = true;
    /**
     * Class used in rules for this module.
     * Default value is "yii\rest\UrlRule"
     */
    public $urlRuleClass = 'yii\rest\UrlRule';
    
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if (!$this->setUrlRules) {
            $this->addUrlRules($app->getUrlManager());
        }
    }
    
    /**
     * Add default url rules of this module to custom urlManager
     * @param \yii\web\UrlManager $urlManager
     */
    public function addUrlRules(\yii\web\UrlManager $urlManager)
    {
        if ($app instanceof \yii\web\Application) {
            $urlManager->addRules([
                'class' => $this->urlRuleClass,
                'controller' => [
                    $this->id . '/user-token',
                ],
                'extraPatterns' => [
                    
                    // Get info of the current user token
                    'POST current' => 'current',
                    
                    // Extend user token expired time
                    'POST extend' => 'extend',
                    
                ],
                'pluralize' => false,
            ]);
            
            /**
             * Next commented code used for example:
             */
//            $urlManager->addRules([
//                $this->id => $this->id . '/default/index',
//                $this->id . '/<id:\w+>' => $this->id . '/default/view',
//                $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>' => $this->id . '/<controller>/<action>',
//            ], false);
        } elseif ($app instanceof \yii\console\Application) {
            /**
             * TODO: Add clear expired tokens as console controller
             * Next commented code used for example:
             */
//            $app->controllerMap[$this->id] = [
//                'class' => 'yii\gii\console\GenerateController',
//                'module' => $this,
//            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }
}

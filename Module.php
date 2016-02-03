<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace gbksoft\modules\tokens;

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
    const MODULE_ID = 'tokens';
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'gbksoft\modules\tokens\controllers';
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
    public $urlRulePrefix = '';
    /**
     * Class extended yii\web\IdentityInterface interface
     * Required option.
     * @var string
     */
    public $userClass = 'yii\web\User';
    
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($this->setUrlRules) {
            $this->addUrlRules($app);
        }
    }
    
    /**
     * Add default url rules of this module to custom urlManager of application
     * @param \yii\base\Application $app
     */
    public function addUrlRules(\yii\base\Application $app)
    {
        if ($app instanceof \yii\web\Application) {
            $app->getUrlManager()->addRules([
                [
                    'prefix' => $this->urlRulePrefix,
                    'class' => $this->urlRuleClass,
                    'controller' => [
                        $this->id . '/user',
                    ],
                    'extraPatterns' => [

                        // Get info of the current user token
                        'POST current' => 'current',

                        // Extend user token expired time
                        'POST extend' => 'extend',

                    ],
                    'pluralize' => false,
                ]
            ]);
        } elseif ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->id] = [
                'class' => 'gbksoft\modules\tokens\console\AppController',
                'module' => $this,
            ];
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

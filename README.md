Tokens Module for Yii 2
========================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require gbksoft/yii2-tokens
```

or add

```
"gbksoft/yii2-tokens": "~1.0.0"
```

to the require section of your `composer.json` file.

Usage
-----

Module configurations:

```php
...
    'modules' => [
        'tokens' => [
            'class' => 'gbksoft\modules\tokens\Module',
            'userClass' => common\models\User::class,
            'urlRulePrefix' => 'v1/',
            'on beforeControllerBehavior' => function($event) {
                // Update behaviors on module controller "User"
                $event->data = common\overrides\rest\ActiveController::getDefaultBehaviors();
            },
        ],
    ],
...
```

In IdentityInterface (User model)

```php
use yii\web\UnauthorizedHttpException;
use gbksoft\modules\tokens\models\UserToken;

...

class User extends ActiveRecord implements IdentityInterface
{
    ...

    public $userToken;

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        /** @var $userToken \common\models\UserToken */
        $userToken = UserToken::find()
            ->andWhere(['=', 'token', $token])
            // Additional conditions..
            // ->joinWith(['user' => function($query) {
            //     $query->onCondition(['=', 'user.status', self::STATUS_ACTIVE]);
            // }])
            ->one();
        
        if (!$userToken) {
            return $userToken;
        }
        
        if (!$userToken->matchIp(Yii::$app->getRequest()->userIP)) {
            throw new UnauthorizedHttpException('Differed user token IP-s');
        }
        
        if ($userToken->expired()) {
            throw new UnauthorizedHttpException('User token is expired');
        }
        
        if ($userToken) {
            $user = $userToken->user;
            $user->userToken = $userToken;
        } else {
            $user = $userToken; // null default
        }
        
        return $user;
    }

    ...
}
```

Login action example

```php

namespace api\versions\v1\controllers\user;

use Yii;
use yii\web\ServerErrorHttpException;
use gbksoft\modules\tokens\models\UserToken;

class LoginAction extends \yii\rest\CreateAction {
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
     * Creates a new model.
     * @return \yii\db\ActiveRecordInterface the model newly created
     * @throws ServerErrorHttpException if there is any error when creating the model
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        /** @var $model \common\models\User */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);

        /** @var $request \yii\web\Request */
        $request = Yii::$app->getRequest();
        
        $model->load($request->getBodyParams(), '');
        
        if ($model->login($model)) {
            
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
            
            /* @var $userToken \common\models\UserToken */
            $userToken = UserToken::createForUser(Yii::$app->user->identity, $seconds, $remember, $verifyIP);
            
            if ($userToken) {
                return $userToken;
            } else {
                throw new ServerErrorHttpException('Failed to create the object for user token.');
            }
            
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed login.');
        }
        
        return $model;
    }
}
```

Console command for clear expired tokens

```bash
./yii tokens/tokens/clear-expired
```
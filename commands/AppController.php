<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace gbksoft\tokens\commands;

use Yii;
use yii\console\Controller as Controller;
use gbksoft\tokens\models\UserToken;

/**
 * @author Hryhorii Furletov <littlefuntik@gmail.com>
 * @since 2.0
 */
class AppController extends Controller
{
    /**
     * Clear all expired data from current application
     */
    public function actionClearExpiredTokens()
    {
        // Die script every time
        // This condition for database connection
        $timerExitPeriod = 2 * 60 * 60; // 2 hours
        
        $startTimeProcess = microtime(true);
        
        $loop = \React\EventLoop\Factory::create();

        // Every hour clear expired user tokens
        $loop->addPeriodicTimer(3600, function () use ($timerExitPeriod, $startTimeProcess) {
            
            if (microtime(true) - $startTimeProcess >= $timerExitPeriod) {
                exit(self::EXIT_CODE_NORMAL);
            }
            
            $timeStart = microtime(true);
            $this->stdout('Start clearing expired tokens:' . PHP_EOL);
            $this->stdout(' -> Current memory usage: ' . number_format(memory_get_usage() / 1024, 3) . 'K.' . PHP_EOL);
            
            $countDeleted = UserToken::deleteAll('expired_at <= NOW()');
            
            $this->stdout('Finished clearing expired tokens:' . PHP_EOL);
            $this->stdout(' -> Deleted rows count ' . $countDeleted . '.' . PHP_EOL);
            $this->stdout(' -> Current memory usage: ' . number_format(memory_get_usage() / 1024, 3) . 'K.' . PHP_EOL);
            $this->stdout(' -> ' . (microtime(true) - $timeStart) . ' elapsed seconds.' . PHP_EOL);
        });

        $loop->run();
    }
}

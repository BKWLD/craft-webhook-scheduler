<?php
/**
 * craft-webhook-scheduler plugin for Craft CMS 3.x
 *
 * Craft Webhook Scheduler
 *
 * @link      https://bukwild.com
 * @copyright Copyright (c) 2022 Bukwild
 */

namespace Bkwld\WebhookScheduler\console\controllers;

use Bkwld\WebhookScheduler\Craftwebhookscheduler;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Runs Entries Scheduler
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return mixed
     */
    public function actionIndex()
    {
        Craftwebhookscheduler::getInstance()->runScheduler();
    }

}

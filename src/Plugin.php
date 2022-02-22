<?php
/**
 * craft-webhook-scheduler plugin for Craft CMS 3.x
 *
 * Craft Webhook Scheduler
 *
 * @link      https://bukwild.com
 * @copyright Copyright (c) 2022 Bukwild
 */

namespace Bkwld\WebhookScheduler;

use Bkwld\WebhookScheduler\models\Settings;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\elements\Entry;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

use DateTime;
use GuzzleHttp\Client;

use Yii;
use yii\base\Event;
use craft\events\ModelEvent;
use craft\services\Elements;
use craft\events\ElementEvent;
use craft\helpers\ElementHelper;

class Plugin extends \craft\base\Plugin
{

    public static $plugin;

    public $schemaVersion = '1.0.1';

    public $hasCpSettings = false;

    public $hasCpSection = true;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Checking if plugin is installed
        if (!$this->isInstalled) return;

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) $this->controllerNamespace = 'Bkwld\WebhookScheduler\console\controllers';

        // Register service fike
        $this->setComponents([
            'schedulerService' => services\SchedulerService::class,
            'webhookService' => services\WebhookService::class,
        ]);

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['craft-webhook-scheduler'] = 'craft-webhook-scheduler/default';
            }
        );

        // Register after save event. Saves a pending entry to the DB.
        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                if (
                    !ElementHelper::isDraft($event->sender) &&
                    ($event->sender->enabled && $event->sender->getEnabledForSite()) &&
                    !$event->sender->propagating &&
                    !ElementHelper::rootElement($event->sender)->isProvisionalDraft &&
                    !$event->sender->resaving &&
                    !ElementHelper::isRevision($event->sender)
                ) {
                    $this->schedulerService->savePendingEntry($event->sender, $event->sender->siteId);
                }
            }
        );

        // Log plugin loaded
        Craft::info(Craft::t('craft-webhook-scheduler', '{name} plugin loaded', ['name' => $this->name]), __METHOD__);
    }

}

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
use craft\base\Plugin;
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
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Bukwild
 * @package   Craftwebhookscheduler
 * @since     1.0.0
 *
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Craftwebhookscheduler extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Craftwebhookscheduler::$plugin
     *
     * @var Craftwebhookscheduler
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Craftwebhookscheduler::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Checking if plugin is installed
        if (!$this->isInstalled) return;

        // Checking if user is guest
        $userIsGuest = Craft::$app->user->isGuest;
        if ($userIsGuest) return;

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) $this->controllerNamespace = 'Bkwld\WebhookScheduler\console\controllers';

        // Register service fike
        $this->setComponents([
            'schedulerService' => \Bkwld\WebhookScheduler\services\SchedulerService::class,
            'webhookService' => \Bkwld\WebhookScheduler\services\WebhookService::class,
        ]);

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['craft-webhook-scheduler'] = 'craft-webhook-scheduler/default';
            }
        );

        Craft::info(
            Craft::t(
                'craft-webhook-scheduler',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function runScheduler()
    {
        $this->schedulerService->checkPendingEntries();
    }

}

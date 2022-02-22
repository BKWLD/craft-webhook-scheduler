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
        if (!$this->isInstalled) {
            return;
        }

        // Checking if user is guest
        $userIsGuest = Craft::$app->user->isGuest;
        if ($userIsGuest) {
            return;
        }

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'Bkwld\WebhookScheduler\console\controllers';
        }

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
        $this->checkPendingEntries();
    }

    public function checkPendingEntries()
    {
        // Check all webhooks
        $webhooks = (new Query())
            ->select([
                'webhooks.id as id',
                'webhooks.webhookUrl as webhookUrl',
                'webhooks.lastRun as lastRun',
                'sites.name as siteName',
                'sites.id as siteId',
            ])
            ->from(['{{%craftwebhookscheduler_webhooks}} as webhooks'])
            ->leftJoin('sites as sites', 'webhooks.siteId = sites.id')
            ->orderBy(['webhooks.id' => SORT_DESC])
            ->all();

        if (!$webhooks) {
            return;
        }

        // Check each webhook/site
        foreach ($webhooks as $webhook) {
            // Getting current date
            $newDate = date("Y-m-d H:i:s");

            // Getting pending entries of this siteId
            $pendingEntries = $this->getPendingEntries($webhook['siteId']);

            if (count($pendingEntries) > 0) {
                $entriesId = [];
                // Add pending entries to scheduled entries table
                foreach ($pendingEntries as $pendingEntry) {
                    $isPendingEntryAlreadyScheduled = (new Query())
                        ->select(['*'])
                        ->from(['{{%craftwebhookscheduler_scheduled_posts}}'])
                        ->where(['entryId' => $pendingEntry['id']])
                        ->exists();

                    if (!$isPendingEntryAlreadyScheduled) {
                        Db::insert('{{%craftwebhookscheduler_scheduled_posts}}', [
                            'siteId' => $webhook['siteId'],
                            'entryId' => $pendingEntry['id'],
                            'dateToPublish' => $pendingEntry['postDate']->format('Y-m-d H:i:s'),
                        ]);
                        $entriesId[] = $pendingEntry['id'];
                    }
                }
                if (count($entriesId) > 0) $this->log("Adding Pending Entries Ids: " . implode(',', $entriesId) . " to DB");
            } else {
                $this->log("No pending entries for this site: " . $webhook['siteName']);
            }

            // Check if there are entries that needs to be published(post to webhook)
            $pendingEntriesToPublish = (new Query())
                ->select(['*'])
                ->from(['{{%craftwebhookscheduler_scheduled_posts}}'])
                ->where(['isPublished' => false])
                ->andWhere(['<', 'dateToPublish', $newDate])
                ->andWhere(['siteId' => $webhook['siteId']])
                ->all();

            if (count($pendingEntriesToPublish) > 0) {
                $entriesId = array_column($pendingEntriesToPublish, 'entryId');

                $dbRes = Db::update('{{%craftwebhookscheduler_scheduled_posts}}', [
                    'isPublished' => true,
                ], [
                    'entryId' => $entriesId
                ], [], false);

                $webhookUrl = $webhook['webhookUrl'];
                $res = $this->postToWebhook($webhookUrl, $webhook['id']);
                $this->log($res ?? "Entry Ids: " . implode(',', $entriesId) . " to be posted by Webhook: $webhookUrl | Run Date: $newDate");

                // Clear GraphQL Caches
                $this->invalidateCaches();
            } else {
                $this->log("No pending entries to publish for this site: " . $webhook['siteName']);
            }

        }
    }

    public function getPendingEntries($siteId)
    {
        return Entry::find()->siteId($siteId)->status('pending')->all();
    }

    function postToWebhook($webhookUrl, $webhookId)
    {
        $newDate = date("Y-m-d H:i:s");
        try {
            $client = new Client;
            $response = $client->post($webhookUrl);

            // Update webhook with last run date
            $this->updateLastRunDate($webhookId, $newDate);
            return null;
        } catch (\Exception $exception) {
            return "Craft-Scheduler: Error while posting to Webhook. Run Date: $newDate";
        }
    }

    function updateLastRunDate($id, $date)
    {
        Db::update('{{%craftwebhookscheduler_webhooks}}', [
            'lastRun' => $date,
        ], [
            'id' => $id,
        ]);
    }

    function log($msgLog){
        Craft::info("Craft-Entries-Scheduler: $msgLog", 'Craft Webhook Scheduler');
        echo "Craft-Entries-Scheduler: $msgLog" ."\n";
    }

    function invalidateCaches(){
        // Clear GraphQL cache for these entries. See https://github.com/craftcms/cms/issues/7556#issuecomment-777898641
        try {
            Craft::$app->gql->invalidateCaches();
        }catch (\Exception $exception){
            $this->log("Error while invalidating caches");
        }
    }
}

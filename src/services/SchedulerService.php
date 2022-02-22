<?php
/**
 * craft-webhook-scheduler plugin for Craft CMS 3.x
 *
 * Craft Webhook Scheduler
 *
 * @link      https://bukwild.com
 * @copyright Copyright (c) 2022 Bukwild
 */

namespace Bkwld\WebhookScheduler\services;

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
use yii\base\Event;
use yii\base\Component;
use Bkwld\WebhookScheduler\Plugin;

class SchedulerService extends Component
{

    public function checkPendingEntries()
    {
        // Check all webhooks
        $webhooks = Plugin::getInstance()->webhookService->getWebhooks();

        // Return if empty
        if (empty($webhooks)) return;

        // Check each webhook/site for pending entries
        foreach ($webhooks as $webhook) {
            $this->publishPendingEntries($webhook);
        }
    }

    function savePendingEntries($webhook)
    {
        $pendingEntries = $this->getPendingEntries($webhook['siteId']);

        if (empty($pendingEntries)) {
            $this->log("No pending entries for this site: " . $webhook['siteName']);
            return;
        }

        // Add pending entries to scheduled entries table
        foreach ($pendingEntries as $pendingEntry) {
            $this->savePendingEntry($pendingEntry, $webhook['siteId']);
        }
    }

    function savePendingEntry($pendingEntry, $siteId){
        $isPendingEntryAlreadyScheduled = (new Query())
            ->select(['*'])
            ->from(['{{%craftwebhookscheduler_scheduled_posts}}'])
            ->where(['entryId' => $pendingEntry['id']])
            ->exists();

        if (!$isPendingEntryAlreadyScheduled) {
            Db::insert('{{%craftwebhookscheduler_scheduled_posts}}', [
                'siteId' => $siteId,
                'entryId' => $pendingEntry['id'],
                'dateToPublish' => $pendingEntry['postDate']->format('Y-m-d H:i:s'),
            ]);
            $this->log("Adding Pending Entry Id: " . $pendingEntry['id'] . " to DB");
        }
    }

    function publishPendingEntries($webhook)
    {
        $newDate = date("Y-m-d H:i:s");

        // Check if there are entries that needs to be published(post to webhook)
        $pendingEntriesToPublish = (new Query())
            ->select(['*'])
            ->from(['{{%craftwebhookscheduler_scheduled_posts}}'])
            ->where(['isPublished' => false])
            ->andWhere(['<', 'dateToPublish', $newDate])
            ->andWhere(['siteId' => $webhook['siteId']])
            ->all();

        if (empty($pendingEntriesToPublish)) {
            $this->log("No pending entries to publish for this site: " . $webhook['siteName']);
            return;
        }

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
    }

    function getPendingEntries($siteId)
    {
        return Entry::find()->siteId($siteId)->status('pending')->all();
    }

    function updateLastRunDate($id, $date)
    {
        Db::update('{{%craftwebhookscheduler_webhooks}}', [
            'lastRun' => $date,
        ], [
            'id' => $id,
        ]);
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

    function invalidateCaches()
    {
        // Clear GraphQL cache for these entries. See https://github.com/craftcms/cms/issues/7556#issuecomment-777898641
        try {
            Craft::$app->gql->invalidateCaches();
        } catch (\Exception $exception) {
            $this->log("Error while invalidating caches");
        }
    }

    function log($msgLog)
    {
        Craft::info($msgLog, 'Craft Webhook Scheduler');
    }
}
<?php

namespace Bkwld\WebhookScheduler\services;

use Craft;
use craft\db\Query;
use craft\helpers\Db;

class WebhookService
{

    function getWebhooks()
    {
        return (new Query())
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
    }

    function getSites()
    {
        return (new Query())
            ->select(['*'])
            ->from(['{{%sites}}'])
            ->orderBy(['id' => SORT_DESC])
            ->all();
    }

    function getSitesForSelectInput()
    {
        $sites = $this->getSites();

        $sitesArray = [
            ['value' => null, 'label' => Craft::t('craft-webhook-scheduler', 'Select Site')],
        ];

        foreach ($sites as $site) {
            $sitesArray[] = ['value' => $site['id'], 'label' => $site['name']];
        }
        return $sitesArray;
    }

    function saveWebhook($siteId, $webhookUrl)
    {
        Db::insert('{{%craftwebhookscheduler_webhooks}}', [
            'siteId' => $siteId,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    function deleteWebhook($id)
    {
        Db::delete('{{%craftwebhookscheduler_webhooks}}', ['id' => $id]);
    }
}
<?php
/**
 * craft-webhook-scheduler plugin for Craft CMS 3.x
 *
 * Craft Webhook Scheduler
 *
 * @link      https://bukwild.com
 * @copyright Copyright (c) 2022 Bukwild
 */

namespace Bkwld\WebhookScheduler\controllers;

use Bkwld\WebhookScheduler\assetbundles\indexcpsection\IndexCPSectionAsset;
use Bkwld\WebhookScheduler\Craftwebhookscheduler;

use Craft;
use craft\web\Controller;
use craft\db\Paginator;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\twig\variables\Paginate;
use Symfony\Component\Translation\Dumper\IniFileDumper;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'save', 'delete'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/craft-webhook-scheduler/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
        Craft::$app->getView()->registerAssetBundle(IndexCPSectionAsset::class);

        // Webhooks
        $results = (new Query())
            ->select([
                'webhooks.id as id',
                'webhooks.webhookUrl as webhookUrl',
                'webhooks.lastRun as lastRun',
                'sites.name as siteName',
            ])
            ->from(['{{%craftwebhookscheduler_webhooks}} as webhooks'])
            ->leftJoin('sites as sites','webhooks.siteId = sites.id')
            ->orderBy(['webhooks.id' => SORT_DESC])
            ->all();

        // Sites (ex. Prod, UAT, Dev, Canada, etc...)
        $sites = (new Query())
            ->select(['*'])
            ->from(['{{%sites}}'])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $sitesArray = [
            ['value' => null, 'label' => Craft::t('craft-webhook-scheduler', 'Select Site')],
        ];

        foreach ($sites as $site) {
            $sitesArray[] = ['value' => $site['id'], 'label' => $site['name']];
        }

        return $this->renderTemplate('craft-webhook-scheduler/_manage/index', [
            'webhooks' => $results,
            'sites' => $sitesArray,
        ]);
    }

    public function actionSave(){
        $this->requirePostRequest();

        $siteId = $this->request->getBodyParam('siteId');
        $webhookUrl = $this->request->getRequiredBodyParam('webhookUrl');

        if (!$siteId || !$webhookUrl){
            return $this->redirect('craft-webhook-scheduler/?error=true');
        }

        Db::insert('{{%craftwebhookscheduler_webhooks}}', [
            'siteId' => $siteId,
            'webhookUrl' => $webhookUrl,
        ]);

        return $this->redirect('craft-webhook-scheduler/');
    }

    public function actionDelete(){
        $this->requirePostRequest();

        $id = $this->request->getBodyParam('id');

        if (!$id){
            return $this->asJson([
                'success' => false,
            ]);
        }
        Db::delete('{{%craftwebhookscheduler_webhooks}}', ['id' => $id]);

        return $this->asJson([
            'success' => true,
        ]);
    }

}

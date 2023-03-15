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
use Bkwld\WebhookScheduler\Plugin;

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
    protected array|int|bool $allowAnonymous = ['index', 'save', 'delete'];

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
        $results = Plugin::getInstance()->webhookService->getWebhooks();

        // Sites (ex. Prod, UAT, Dev, Canada, etc...)
        $sites = Plugin::getInstance()->webhookService->getSitesForSelectInput();

        return $this->renderTemplate('craft-webhook-scheduler/_manage/index', [
            'webhooks' => $results,
            'sites' => $sites,
        ]);
    }

    public function actionSave(){
        $this->requirePostRequest();

        $siteId = $this->request->getBodyParam('siteId');
        $webhookUrl = $this->request->getRequiredBodyParam('webhookUrl');

        if (!$siteId || !$webhookUrl) return $this->redirect('craft-webhook-scheduler/?error=true');

        Plugin::getInstance()->webhookService->saveWebhook($siteId, $webhookUrl);

        return $this->redirect('craft-webhook-scheduler/');
    }

    public function actionDelete(){
        $this->requirePostRequest();

        $id = $this->request->getBodyParam('id');

        if (!$id) return $this->asJson(['success' => false]);

        Plugin::getInstance()->webhookService->deleteWebhook($id);

        return $this->asJson(['success' => true]);
    }

}

<?php
/**
 * craft-entries-scheduler plugin for Craft CMS 3.x
 *
 * Craft Entries Scheduler
 *
 * @link      https://bukwild.com
 * @copyright Copyright (c) 2022 Bukwild
 */

namespace bukwild\craftentriesscheduler\controllers;

use bukwild\craftentriesscheduler\assetbundles\indexcpsection\IndexCPSectionAsset;
use bukwild\craftentriesscheduler\Craftentriesscheduler;

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

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Bukwild
 * @package   Craftentriesscheduler
 * @since     1.0.0
 */
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
     * e.g.: actions/craft-entries-scheduler/default
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
            ->from(['{{%craftentriesscheduler_webhooks}} as webhooks'])
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
            ['value' => null, 'label' => Craft::t('craft-entries-scheduler', 'Select Site')],
        ];

        foreach ($sites as $site) {
            $sitesArray[] = ['value' => $site['id'], 'label' => $site['name']];
        }

        return $this->renderTemplate('craft-entries-scheduler/_manage/index', [
            'webhooks' => $results,
            'sites' => $sitesArray,
        ]);
    }

    public function actionSave(){
        $this->requirePostRequest();

        $siteId = $this->request->getBodyParam('siteId');
        $webhookUrl = $this->request->getRequiredBodyParam('webhookUrl');

        if (!$siteId || !$webhookUrl){
            return $this->redirect('craft-entries-scheduler/?error=true');
        }

        Db::insert('{{%craftentriesscheduler_webhooks}}', [
            'siteId' => $siteId,
            'webhookUrl' => $webhookUrl,
        ]);

        return $this->redirect('craft-entries-scheduler/');
    }

    public function actionDelete(){
        $this->requirePostRequest();

        $id = $this->request->getBodyParam('id');

        if (!$id){
            return $this->asJson([
                'success' => false,
            ]);
        }
        Db::delete('{{%craftentriesscheduler_webhooks}}', ['id' => $id]);

        return $this->asJson([
            'success' => true,
        ]);
    }

}

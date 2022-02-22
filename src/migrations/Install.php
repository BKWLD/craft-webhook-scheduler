<?php
/**
 * craft-webhook-scheduler plugin for Craft CMS 3.x
 *
 * Craft Webhook Scheduler
 *
 * @link      https://bukwild.com
 * @copyright Copyright (c) 2022 Bukwild
 */

namespace bkwld\craftwebhookscheduler\migrations;

use bkwld\craftwebhookscheduler\Craftwebhookscheduler;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

    // craftwebhookscheduler_craftwebhookschedulerrecord table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%craftwebhookscheduler_webhooks}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%craftwebhookscheduler_webhooks}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                // Custom columns in the table
                    'siteId' => $this->integer()->notNull(),
                    'webhookUrl' => $this->string(255)->notNull()->defaultValue(''),
                    'lastRun' => $this->dateTime()->null(),
                ]
            );

            $this->createTable(
                '{{%craftwebhookscheduler_scheduled_posts}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    // Custom columns in the table
                    'siteId' => $this->integer()->notNull(),
                    'entryId' => $this->integer()->notNull(),
                    'dateToPublish' => $this->dateTime()->null(),
                    'isPublished' => $this->boolean()->defaultValue(false),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {

    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%craftwebhookscheduler_scheduled_posts}}', 'siteId'),
            '{{%craftwebhookscheduler_scheduled_posts}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%craftwebhookscheduler_webhooks}}', 'siteId'),
            '{{%craftwebhookscheduler_webhooks}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        MigrationHelper::dropForeignKeyIfExists('craftwebhookscheduler_scheduled_posts','siteId');
        MigrationHelper::dropForeignKeyIfExists('craftwebhookscheduler_webhooks','siteId');
        $this->dropTableIfExists('{{%craftwebhookscheduler_scheduled_posts}}');
        $this->dropTableIfExists('{{%craftwebhookscheduler_webhooks}}');
    }
}

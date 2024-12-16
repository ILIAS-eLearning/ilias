<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Cron\Schedule\CronJobScheduleType;

/**
* Class for indexing hmtl ,pdf, txt files and htlm Learning modules.
* This indexer is called by cron.php
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* @package ServicesSearch
*/
class ilLuceneIndexer extends ilCronJob
{
    protected int $timeout = 60;
    protected ilSetting $setting;

    public function init(): void
    {
        global $DIC;
        $this->setting = $DIC->settings();
    }

    public function getId(): string
    {
        return "src_lucene_indexer";
    }

    public function getTitle(): string
    {
        return $this->lng->txt("cron_lucene_index");
    }

    public function getDescription(): string
    {
        return $this->lng->txt("cron_lucene_index_info");
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return null;
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    public function run(): ilCronJobResult
    {
        $status = ilCronJobResult::STATUS_NO_ACTION;
        $error_message = null;

        try {
            ilRpcClientFactory::factory('RPCIndexHandler', 60)->index(
                CLIENT_ID . '_' . $this->setting->get('inst_id', "0"),
                true
            );
        } catch (Exception $e) {
            $error_message = $e->getMessage();

            if ($e instanceof ilRpcClientException && $e->getCode() == 28) {
                ilLoggerFactory::getLogger('src')->info('Connection timed out after ' . $this->timeout . ' seconds. ' .
                    'Indexing will continoue without a proper return message. View ilServer log if you think there are problems while indexing.');
                $error_message = null;
            }
        }

        $result = new ilCronJobResult();
        if ($error_message) {
            // #16035 - currently no way to discern the severity of the exception
            $result->setMessage($error_message);
            $status = ilCronJobResult::STATUS_FAIL;
        } else {
            $status = ilCronJobResult::STATUS_OK;
        }
        $result->setStatus($status);
        return $result;
    }


    /**
     * Update lucene index
     * @param int[] $a_obj_ids
     * @return bool
     */
    public static function updateLuceneIndex(array $a_obj_ids): bool
    {
        global $DIC;

        $ilSetting = $DIC['ilSetting'];
        if (!ilSearchSettings::getInstance()->isLuceneUserSearchEnabled()) {
            return false;
        }

        try {
            ilLoggerFactory::getLogger('src')->info('Lucene update index call BEGIN --- ');

            ilRpcClientFactory::factory('RPCIndexHandler', 1)->indexObjects(
                CLIENT_ID . '_' . $ilSetting->get('inst_id', "0"),
                $a_obj_ids
            );
            ilLoggerFactory::getLogger('src')->info('Lucene update index call --- END');
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            ilLoggerFactory::getLogger('src')->error($error_message);
            return false;
        }

        return true;
    }
}

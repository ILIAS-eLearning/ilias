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

use ILIAS\Cron\Schedule\CronJobScheduleType;

/**
 * Class ilCronEcsTaskScheduler
 *
 * Start execution of ecs tasks.
 *
 */
class ilCronEcsTaskScheduler extends \ilCronJob
{
    public const ID = 'ecs_task_handler';
    public const DEFAULT_SCHEDULE_VALUE = 1;

    private ilLogger $logger;
    private ilCronJobResult $result;

    public function init(): void
    {
        $this->lng->loadLanguageModule('ecs');

        global $DIC;
        $this->logger = $DIC->logger()->wsrv();
        $this->result = new \ilCronJobResult();
    }

    public function getTitle(): string
    {
        return $this->lng->txt('ecs_cron_task_scheduler');
    }

    public function getDescription(): string
    {
        return $this->lng->txt('ecs_cron_task_scheduler_info');
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return self::DEFAULT_SCHEDULE_VALUE;
    }

    public function run(): ilCronJobResult
    {
        $this->logger->debug('Starting ecs task scheduler...');

        $servers = \ilECSServerSettings::getInstance();

        foreach ($servers->getServers(ilECSServerSettings::ACTIVE_SERVER) as $server) {
            try {
                $this->logger->info('Starting task execution for ecs server: ' . $server->getTitle());
                $scheduler = \ilECSTaskScheduler::_getInstanceByServerId($server->getServerId());
                $scheduler->startTaskExecution();
            } catch (\Exception $e) {
                $this->result->setStatus(\ilCronJobResult::STATUS_CRASHED);
                $this->result->setMessage($e->getMessage());
                $this->logger->warning('ECS task execution failed with message: ' . $e->getMessage());
                return $this->result;
            }
        }
        $this->result->setStatus(\ilCronJobResult::STATUS_OK);
        return $this->result;
    }
}

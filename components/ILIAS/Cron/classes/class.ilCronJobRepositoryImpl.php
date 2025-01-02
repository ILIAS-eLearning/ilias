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
use ILIAS\Cron\Registry;

class ilCronJobRepositoryImpl implements ilCronJobRepository
{
    private const TYPE_PLUGINS = 'Plugins';

    public function __construct(
        private readonly Registry $registry,
        private readonly ilDBInterface $db,
        private readonly ilSetting $setting,
        private readonly ilLogger $logger,
        private readonly ilComponentRepository $componentRepository,
        private readonly ilComponentFactory $componentFactory,
        private readonly ILIAS\Language\Language $lng,
        private readonly ILIAS\Logging\LoggerFactory $logger_factory
    ) {
    }

    public function getJobInstanceById(string $id): ?ilCronJob
    {

        $jobs_data = $this->getCronJobData($id);
        if ($jobs_data !== [] && $jobs_data[0]['job_id'] === $id) {
            return $this->getJobInstance(
                $jobs_data[0]['job_id'],
                $jobs_data[0]['component'],
                $jobs_data[0]['class']
            );
        }

        $this->logger->info('CRON - job ' . $id . ' seems invalid or is inactive');

        return null;
    }

    public function getJobInstance(
        string $a_id,
        string $a_component,
        string $a_class
    ): ?ilCronJob {

        if (class_exists($a_class)) {
            $job = new $a_class(
                $a_component,
                $this->lng,
                $this->logger_factory
            );

            if ($job instanceof ilCronJob && $job->getId() === $a_id) {
                return $job;
            }
        }

        return null;
    }

    public function getCronJobData($id = null, bool $withInactiveJobsIncluded = true): array
    {
        $jobData = [];

        if ($id && !is_array($id)) {
            $id = [$id];
        }

        $query = "SELECT * FROM cron_job";
        $where = [];
        if ($id) {
            $where[] = $this->db->in('job_id', $id, false, ilDBConstants::T_TEXT);
        } else {
            $where[] = 'class != ' . $this->db->quote(self::TYPE_PLUGINS, ilDBConstants::T_TEXT);
        }
        if (!$withInactiveJobsIncluded) {
            $where[] = 'job_status = ' . $this->db->quote(1, ilDBConstants::T_INTEGER);
        }
        if ($where !== []) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        // :TODO: discuss job execution order
        $query .= ' ORDER BY job_id';

        $res = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($res)) {
            $jobData[] = $row;
        }

        return $jobData;
    }

    public function registerJob(
        string $a_component,
        string $a_id,
        string $a_class,
        ?string $a_path
    ): void {
        if (!$this->db->tableExists('cron_job')) {
            return;
        }

        //$job = $this->getJobInstance($a_id, $a_component, $a_class, true);
        $job = $this->getJobInstance($a_id, $a_component, $a_class);
        if ($job) {
            $this->createDefaultEntry($job, $a_component, $a_class, $a_path);
        }
    }

    public function unregisterAllJobs(): void
    {
        $query = 'TRUNCATE cron_job;';
        $res = $this->db->manipulate($query);
    }

    public function unregisterJob(string $a_component, array $a_xml_job_ids): void
    {
        if (!$this->db->tableExists('cron_job')) {
            return;
        }

        $jobs = [];
        $query = 'SELECT job_id FROM cron_job WHERE component = ' . $this->db->quote($a_component, 'text');
        $res = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($res)) {
            $jobs[] = $row['job_id'];
        }

        if ($jobs !== []) {
            if ($a_xml_job_ids !== []) {
                foreach ($jobs as $job_id) {
                    if (!in_array($job_id, $a_xml_job_ids, true)) {
                        $this->db->manipulate(
                            'DELETE FROM cron_job' .
                            ' WHERE component = ' . $this->db->quote($a_component, 'text') .
                            ' AND job_id = ' . $this->db->quote($job_id, 'text')
                        );
                    }
                }
            } else {
                $this->db->manipulate(
                    'DELETE FROM cron_job WHERE component = ' . $this->db->quote($a_component, 'text')
                );
            }
        }
    }

    public function createDefaultEntry(
        ilCronJob $job,
        string $component,
        string $class,
        ?string $path
    ): void {
        $query = "SELECT job_id, schedule_type, component, class, path FROM cron_job" .
            " WHERE job_id = " . $this->db->quote($job->getId(), "text");
        $res = $this->db->query($query);
        $row = $this->db->fetchAssoc($res);
        $job_id = $row['job_id'] ?? null;
        $job_exists = ($job_id === $job->getId());
        $schedule_type_value = $row['schedule_type'] ?? null;
        $schedule_type = is_numeric($schedule_type_value) ? CronJobScheduleType::tryFrom((int) $schedule_type_value) : null;

        if (
            $job_exists && (
                $row['component'] !== $component ||
                $row['class'] !== $class ||
                $row['path'] !== $path
            )
        ) {
            $this->db->manipulateF(
                'UPDATE cron_job SET component = %s, class = %s, path = %s WHERE job_id = %s',
                ['text', 'text', 'text', 'text'],
                [$component, $class, $path, $job->getId()]
            );
        }

        // new job
        if (!$job_exists) {
            $query = 'INSERT INTO cron_job (job_id, component, class, path)' .
                ' VALUES (' . $this->db->quote($job->getId(), 'text') . ', ' .
                $this->db->quote($component, 'text') . ', ' .
                $this->db->quote($class, 'text') . ', ' .
                $this->db->quote($path, 'text') . ')';
            $this->db->manipulate($query);

            $this->logger->info('Cron XML - Job ' . $job->getId() . ' in class ' . $class . ' added.');

            // only if flexible
            $this->updateJobSchedule(
                $job,
                $job->getDefaultScheduleType(),
                $job->getDefaultScheduleValue()
            );

            if ($job->hasAutoActivation()) {
                $this->activateJob($job, new DateTimeImmutable('@' . time()));
                $job->activationWasToggled($this->db, $this->setting, true);
            } else {
                // to overwrite dependent settings
                $job->activationWasToggled($this->db, $this->setting, false);
            }
        } elseif ($schedule_type === null && $job->hasFlexibleSchedule()) {
            // existing job - but schedule is flexible now
            $this->updateJobSchedule(
                $job,
                $job->getDefaultScheduleType(),
                $job->getDefaultScheduleValue()
            );
        } elseif ($schedule_type !== null && !$job->hasFlexibleSchedule()) {
            // existing job - but schedule is not flexible anymore
            $this->updateJobSchedule($job, null, null);
        }
    }

    /**
     * @return array<int, array{0: ilCronJob, 1: array<string, mixed>}>
     */
    public function getPluginJobs(bool $withOnlyActive = false): array
    {
        $res = [];
        foreach ($this->componentRepository->getPlugins() as $pl) {
            if (!$pl->isActive()) {
                continue;
            }

            $plugin = $this->componentFactory->getPlugin($pl->getId());

            if (!$plugin instanceof ilCronJobProvider) {
                continue;
            }

            foreach ($plugin->getCronJobInstances() as $job) {
                $jobs_data = $this->getCronJobData($job->getId());
                $job_data = $jobs_data[0] ?? null;
                if (!is_array($job_data) || $job_data === []) {
                    // as job is not "imported" from xml
                    $this->createDefaultEntry($job, $plugin->getPluginName(), self::TYPE_PLUGINS, '');
                }

                $jobs_data = $this->getCronJobData($job->getId());
                $job_data = $jobs_data[0];

                // #17941
                if (!$withOnlyActive || (int) $job_data['job_status'] === 1) {
                    $res[$job->getId()] = [$job, $job_data];
                }
            }
        }

        return $res;
    }

    public function resetJob(ilCronJob $job): void
    {
        $this->db->manipulate(
            'UPDATE cron_job' .
            ' SET running_ts = ' . $this->db->quote(0, 'integer') .
            ' , alive_ts = ' . $this->db->quote(0, 'integer') .
            ' , job_result_ts = ' . $this->db->quote(0, 'integer') .
            ' WHERE job_id = ' . $this->db->quote($job->getId(), 'text')
        );
    }

    public function updateJobResult(
        ilCronJob $job,
        DateTimeImmutable $when,
        ilObjUser $actor,
        ilCronJobResult $result,
        bool $wasManualExecution = false
    ): void {
        $user_id = $wasManualExecution ? $actor->getId() : 0;

        $query = 'UPDATE cron_job SET ' .
            ' job_result_status = ' . $this->db->quote($result->getStatus(), 'integer') .
            ' , job_result_user_id = ' . $this->db->quote($user_id, 'integer') .
            ' , job_result_code = ' . $this->db->quote($result->getCode(), 'text') .
            ' , job_result_message = ' . $this->db->quote($result->getMessage(), 'text') .
            ' , job_result_type = ' . $this->db->quote((int) $wasManualExecution, 'integer') .
            ' , job_result_ts = ' . $this->db->quote($when->getTimestamp(), 'integer') .
            ' , job_result_dur = ' . $this->db->quote($result->getDuration() * 1000, 'integer') .
            ' WHERE job_id = ' . $this->db->quote($job->getId(), 'text');
        $this->db->manipulate($query);
    }

    public function updateRunInformation(string $jobId, int $runningTimestamp, int $aliveTimestamp): void
    {
        $this->db->manipulate(
            'UPDATE cron_job SET' .
            ' running_ts = ' . $this->db->quote($runningTimestamp, 'integer') .
            ' , alive_ts = ' . $this->db->quote($aliveTimestamp, 'integer') .
            ' WHERE job_id = ' . $this->db->quote($jobId, 'text')
        );
    }

    public function updateJobSchedule(ilCronJob $job, ?CronJobScheduleType $scheduleType, ?int $scheduleValue): void
    {
        if (
            $scheduleType === null ||
            ($job->hasFlexibleSchedule() && in_array($scheduleType, $job->getValidScheduleTypes(), true))
        ) {
            $query = 'UPDATE cron_job SET ' .
                ' schedule_type = ' . $this->db->quote($scheduleType?->value, 'integer') .
                ' , schedule_value = ' . $this->db->quote($scheduleValue, 'integer') .
                ' WHERE job_id = ' . $this->db->quote($job->getId(), 'text');
            $this->db->manipulate($query);
        }
    }

    public function activateJob(
        ilCronJob $job,
        DateTimeImmutable $when,
        ?ilObjUser $actor = null,
        bool $wasManuallyExecuted = false
    ): void {
        $usrId = 0;
        if ($wasManuallyExecuted && $actor instanceof ilObjUser) {
            $usrId = $actor->getId();
        }

        $query = 'UPDATE cron_job SET ' .
            ' job_status = ' . $this->db->quote(1, 'integer') .
            ' , job_status_user_id = ' . $this->db->quote($usrId, 'integer') .
            ' , job_status_type = ' . $this->db->quote($wasManuallyExecuted, 'integer') .
            ' , job_status_ts = ' . $this->db->quote($when->getTimestamp(), 'integer') .
            ' WHERE job_id = ' . $this->db->quote($job->getId(), 'text');
        $this->db->manipulate($query);
    }

    public function deactivateJob(
        ilCronJob $job,
        DateTimeImmutable $when,
        ilObjUser $actor,
        bool $wasManuallyExecuted = false
    ): void {
        $usrId = $wasManuallyExecuted ? $actor->getId() : 0;

        $query = 'UPDATE cron_job SET ' .
            ' job_status = ' . $this->db->quote(0, 'integer') .
            ' , job_result_status = ' . $this->db->quote(null, 'text') .
            ' , job_result_message = ' . $this->db->quote(null, 'text') .
            ' , job_result_type = ' . $this->db->quote(null, 'text') .
            ' , job_result_code = ' . $this->db->quote(null, 'text') .
            ' , job_status_user_id = ' . $this->db->quote($usrId, 'integer') .
            ' , job_status_type = ' . $this->db->quote($wasManuallyExecuted, 'integer') .
            ' , job_status_ts = ' . $this->db->quote($when->getTimestamp(), 'integer') .
            ' WHERE job_id = ' . $this->db->quote($job->getId(), 'text');
        $this->db->manipulate($query);
    }

    public function findAll(): ilCronJobCollection
    {
        $collection = new ilCronJobEntities();

        foreach ($this->registry->getAllJobs() as $job) {
            $job_data = $this->getCronJobData($job->getId());
            $entity = new ilCronJobEntity($job, array_shift($job_data));
            $collection->add($entity);
        }
        return $collection;
    }
}

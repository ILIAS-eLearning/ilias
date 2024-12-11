<?php

declare(strict_types=1);

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
 ********************************************************************
 */

use ILIAS\Setup;

class ilCronJobsMetricsCollectedObjective extends Setup\Metrics\CollectedObjective
{
    /**
     * @inheritDoc
     */
    protected function getTentativePreconditions(Setup\Environment $environment): array
    {
        return [
            new ilIniFilesLoadedObjective(),
            new ilDatabaseInitializedObjective(),
            new ilComponentRepositoryExistsObjective(),
            new ilComponentFactoryExistsObjective()
        ];
    }

    /**
     * @inheritDoc
     */
    protected function collectFrom(Setup\Environment $environment, Setup\Metrics\Storage $storage): void
    {
        $db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $component_repository = $environment->getResource(Setup\Environment::RESOURCE_COMPONENT_REPOSITORY);
        $component_factory = $environment->getResource(Setup\Environment::RESOURCE_COMPONENT_FACTORY);
        $settings_factory = $environment->getResource(Setup\Environment::RESOURCE_SETTINGS_FACTORY);

        $mock_lng = new class () implements \ILIAS\Language\Language {
            public function txt(string $a_topic, string $a_default_lang_fallback_mod = ""): string
            {
                return '';
            }
            public function loadLanguageModule(string $a_module): void
            {
            }
        };

        $repo = new ilCronJobRepositoryImpl(
            $db,
            $settings_factory->settingsFor(),
            new ILIAS\components\Logging\NullLogger(),
            $component_repository,
            $component_factory,
            $mock_lng
        );

        //@var ilCronJobEntity[]
        $collection = $repo->findAll()->toArray();
        $cron_jobs = [];
        foreach ($collection as $entity) {
            $active = new Setup\Metrics\Metric(
                Setup\Metrics\Metric::STABILITY_VOLATILE,
                Setup\Metrics\Metric::TYPE_BOOL,
                (bool) $entity->getJobStatus(),
                "Is the job active?"
            );
            $component = new Setup\Metrics\Metric(
                Setup\Metrics\Metric::STABILITY_STABLE,
                Setup\Metrics\Metric::TYPE_TEXT,
                $entity->getComponent()
            );
            $cron_jobs[$entity->getJobId()] = new Setup\Metrics\Metric(
                Setup\Metrics\Metric::STABILITY_MIXED,
                Setup\Metrics\Metric::TYPE_COLLECTION,
                [
                    "component" => $component,
                    "active" => $active
                ]
            );
        }
        $cron_jobs = new Setup\Metrics\Metric(
            Setup\Metrics\Metric::STABILITY_MIXED,
            Setup\Metrics\Metric::TYPE_COLLECTION,
            $cron_jobs
        );

        $cron_jobs_count = new Setup\Metrics\Metric(
            Setup\Metrics\Metric::STABILITY_STABLE,
            Setup\Metrics\Metric::TYPE_GAUGE,
            count($collection)
        );
        $storage->store(
            "number of cron jobs",
            $cron_jobs_count
        );
        $storage->store(
            "cron jobs",
            $cron_jobs
        );
    }
}

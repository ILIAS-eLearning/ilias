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

use ILIAS\Setup;
use ILIAS\Refinery;

class ilLearningSequenceSetupAgent implements Setup\Agent
{
    use Setup\Agent\HasNoNamedObjective;

    /**
     * @inheritdoc
     */
    public function hasConfig(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getArrayToConfigTransformation(): Refinery\Transformation
    {
        throw new \LogicException("Agent has no config.");
    }

    /**
     * @inheritdoc
     */
    public function getInstallObjective(Setup\Config $config = null): Setup\Objective
    {
        return new ilFileSystemComponentDataDirectoryCreatedObjective(
            ilLearningSequenceFilesystem::PATH_PRE,
            ilFileSystemComponentDataDirectoryCreatedObjective::WEBDIR
        );
    }

    /**
     * @inheritdoc
     */
    public function getUpdateObjective(Setup\Config $config = null): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            'Database is updated for Module/LearningSequence',
            false,
            new ilDatabaseUpdateStepsExecutedObjective(
                new ilLearningSequenceRectifyPostConditionsTableDBUpdateSteps()
            ),
            new ilDatabaseUpdateStepsExecutedObjective(
                new ilLearningSequenceRegisterNotificationType()
            ),
            new ilDatabaseUpdateStepsExecutedObjective(
                new ilLearningSequenceRegisterPageType()
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getBuildArtifactObjective(): Setup\Objective
    {
        return new Setup\Objective\NullObjective();
    }

    /**
     * @inheritdoc
     */
    public function getStatusObjective(Setup\Metrics\Storage $storage): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            'Component LearningSequence',
            true,
            new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilLearningSequenceRectifyPostConditionsTableDBUpdateSteps()),
            new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilLearningSequenceRegisterNotificationType())
        );
    }

    /**
     * @inheritDoc
     */
    public function getMigrations(): array
    {
        return [];
    }
}

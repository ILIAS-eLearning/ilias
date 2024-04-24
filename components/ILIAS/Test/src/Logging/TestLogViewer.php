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

namespace ILIAS\Test\Logging;

use ILIAS\TestQuestionPool\Questions\GeneralQuestionPropertiesRepository;

use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;

class TestLogViewer
{
    private DataFactory $data_factory;

    public function __construct(
        private readonly TestLoggingRepository $logging_repository,
        private readonly TestLogger $logger,
        private readonly GeneralQuestionPropertiesRepository $question_repo,
        private readonly ServerRequestInterface $request,
        private readonly \ilUIService $ui_service,
        private readonly UIFactory $ui_factory,
        private readonly \ilLanguage $lng
    ) {
        $this->data_factory = new DataFactory();
    }

    public function getLogTable(
        URLBuilder $url_builder,
        URLBuilderToken $action_parameter_token,
        URLBuilderToken $row_id_token,
        int $ref_id = null
    ): array {
        $log_table = new LogTable(
            $this->logging_repository,
            $this->logger,
            $this->question_repo,
            $this->ui_factory,
            $this->data_factory,
            $this->lng,
            $url_builder,
            $action_parameter_token,
            $row_id_token,
            $ref_id
        );

        $filter = $log_table->getFilter($this->ui_service);
        $filter_data = $this->ui_service->filter()->getData($filter);
        return [
            $filter,
            $log_table->getTable()->withRequest($this->request)->withFilter($filter_data)
        ];
    }

    public function getLegacyLogTableForObjId(\ilObjectGUI $parent_gui, int $obj_id): \ilAssessmentFolderLogTableGUI
    {
        $table_gui = new \ilAssessmentFolderLogTableGUI($parent_gui, 'logs');
        $log_output = $this->logging_repository->getLegacyLogsForObjId($obj_id);

        array_walk($log_output, static function (&$row) use ($parent_gui) {
            $row['location_href'] = '';
            $row['location_txt'] = '';
            if (is_numeric($row['ref_id']) && $row['ref_id'] > 0) {
                $row['location_href'] = ilLink::_getLink((int) $row['ref_id'], 'tst');
                $row['location_txt'] = $parent_gui->lng->txt("perma_link");
            }
        });

        $table_gui->setData($log_output);
        return $table_gui;
    }

}

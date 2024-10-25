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

use ILIAS\Test\Scoring\Manual\TestScoring;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\ResourceStorage\Services as IRSS;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\DI\UIServices as UIServices;
use ILIAS\Test\ExportImport\Factory as ExportImportFactory;
use ILIAS\Test\ExportImport\Types as ExportImportTypes;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Export User Interface Class
 *
 * @author       Michael Jansen <mjansen@databay.de>
 * @author       Maximilian Becker <mbecker@databay.de>
 * @ilCtrl_Calls ilTestExportGUI: ilExportGUI
 */
class ilTestExportGUI extends ilExportGUI
{
    protected UIServices $ui;

    public function __construct(
        ilObjTestGUI $parent_gui,
        private readonly ilDBInterface $db,
        private readonly ExportImportFactory $export_factory,
        private readonly ilObjectDataCache $obj_cache,
        private readonly ilObjUser $user,
        private readonly UIFactory $ui_factory,
        private readonly UIRenderer $ui_renderer,
        private readonly IRSS $irss,
        private readonly ServerRequestInterface $request,
        private readonly ilTestParticipantAccessFilterFactory $participant_access_filter_factory,
        private readonly ilTestHTMLGenerator $html_generator,
        private readonly array $selected_files,
    ) {
        parent::__construct($parent_gui, null);
    }

    /**
     * @return ilTestExportTableGUI
     */
    protected function buildExportTableGUI(): ilTestExportTableGUI
    {
        $table = new ilTestExportTableGUI($this, 'listExportFiles', $this->obj);
        return $table;
    }

    /**
     * Create test export file
     */
    public function createTestExportWithResults()
    {
        $test_exp = $this->export_factory->getExporter($this->obj, ExportImportTypes::XML_WITH_RESULTS);
        $test_exp->write();
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('exp_file_created'), true);
        $this->ctrl->redirectByClass('iltestexportgui');
    }

    public function createTestArchiveExport()
    {
        if ($this->access->checkAccess('write', '', $this->obj->getRefId())) {
            // prepare generation before contents are processed (for mathjax)

            $evaluation = new ilTestEvaluation($this->db, $this->obj->getTestId());
            $allActivesPasses = $evaluation->getAllActivesPasses();
            $participantData = new ilTestParticipantData($this->db, $this->lng);
            $participantData->setActiveIdsFilter(array_keys($allActivesPasses));
            $participantData->load($this->obj->getTestId());

            $archiveService = new ilTestArchiveService(
                $this->obj,
                $this->lng,
                $this->db,
                $this->user,
                $this->ui_factory,
                $this->ui_renderer,
                $this->irss,
                $this->request,
                $this->obj_cache,
                $this->participant_access_filter_factory,
                $this->html_generator
            );
            $archiveService->setParticipantData($participantData);
            $archiveService->archivePassesByActives($allActivesPasses);

            $test_id = $this->obj->getId();
            $test_ref = $this->obj->getRefId();
            $archive_exp = new ilTestArchiver(
                $this->lng,
                $this->db,
                $this->user,
                $this->ui_factory,
                $this->ui_renderer,
                $this->irss,
                $this->request,
                $this->obj_cache,
                $this->participant_access_filter_factory,
                $test_id,
                $test_ref
            );

            $scoring = new TestScoring($this->obj, $this->user, $this->db, $this->lng);
            $best_solution = $scoring->calculateBestSolutionForTest();

            $tmpFileName = ilFileUtils::ilTempnam();
            if (!is_dir($tmpFileName)) {
                ilFileUtils::makeDirParents($tmpFileName);
            }

            $archive_exp->handInTestBestSolution($best_solution);

            $archive_exp->updateTestArchive();
            $archive_exp->compressTestArchive();
        } else {
            $this->tpl->setOnScreenMessage('info', 'cannot_export_archive', true);
        }
        $this->ctrl->redirectByClass('iltestexportgui');
    }

    public function listExportFiles(): void
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));

        if (count($this->getFormats()) > 1) {
            foreach ($this->getFormats() as $f) {
                $options[$f['key']] = $f['txt'];
            }
            $si = new ilSelectInputGUI($this->lng->txt('type'), 'format');
            $si->setOptions($options);
            $this->toolbar->addInputItem($si, true);
            $this->toolbar->addFormButton($this->lng->txt('exp_create_file'), 'createExportFile');
        } else {
            $format = $this->getFormats()[0];
            $this->toolbar->addFormButton(
                $this->lng->txt('exp_create_file')
                . ' (' . $format['txt'] . ')',
                'create_' . $format['key']
            );
        }

        $archiver = new ilTestArchiver(
            $this->lng,
            $this->db,
            $this->user,
            $this->ui_factory,
            $this->ui_renderer,
            $this->irss,
            $this->request,
            $this->obj_cache,
            $this->participant_access_filter_factory,
            $this->getParentGUI()->getTestObject()->getId()
        );
        $archive_dir = $archiver->getZipExportDirectory();
        $archive_files = [];

        if (file_exists($archive_dir) && is_dir($archive_dir)) {
            $archive_files = scandir($archive_dir);
        }

        $export_dir = $this->obj->getExportDirectory();
        $export_files = $this->obj->getExportFiles($export_dir);
        $data = [];
        if (count($export_files) > 0) {
            foreach ($export_files as $exp_file) {
                $file_arr = explode('__', $exp_file);
                if ($file_arr[0] == $exp_file) {
                    continue;
                }

                array_push(
                    $data,
                    [
                        'file' => $exp_file,
                        'size' => filesize($export_dir . '/' . $exp_file),
                        'timestamp' => $file_arr[0],
                        'type' => 'ZIP'
                    ]
                );
            }
        }

        if (count($archive_files) > 0) {
            foreach ($archive_files as $exp_file) {
                if ($exp_file == '.' || $exp_file == '..') {
                    continue;
                }
                $file_arr = explode('_', $exp_file);

                $data[] = [
                    'file' => $exp_file,
                    'size' => filesize($archive_dir . '/' . $exp_file),
                    'timestamp' => $file_arr[4],
                    'type' => 'ZIP'
                ];
            }
        }

        $table = $this->buildExportTableGUI();
        $table->setSelectAllCheckbox('file');
        foreach ($this->getCustomColumns() as $c) {
            $table->addCustomColumn($c['txt'], $c['obj'], $c['func']);
        }

        foreach ($this->getCustomMultiCommands() as $c) {
            $table->addCustomMultiCommand($c['txt'], 'multi_' . $c['func']);
        }

        $table->resetFormats();
        foreach ($this->formats as $format) {
            $table->addFormat($format['key']);
        }

        $table->setData($data);
        $this->tpl->setOnScreenMessage('info', $this->lng->txt('no_manual_feedback_export_info'), true);
        $this->tpl->setContent($table->getHTML());
    }

    public function download(): void
    {
        if ($this->selected_files === []) {
            $this->tpl->setOnScreenMessage('info', $this->lng->txt('no_checkbox'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }

        if (count($this->selected_files) > 1) {
            $this->tpl->setOnScreenMessage('info', $this->lng->txt('select_max_one_item'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }

        $archiver = new ilTestArchiver(
            $this->lng,
            $this->db,
            $this->user,
            $this->ui_factory,
            $this->ui_renderer,
            $this->irss,
            $this->request,
            $this->obj_cache,
            $this->participant_access_filter_factory,
            $this->getParentGUI()->getTestObject()->getId()
        );
        $filename = basename($this->selected_files[0]);
        $exportFile = $this->obj->getExportDirectory() . '/' . $filename;
        $archiveFile = $archiver->getZipExportDirectory() . '/' . $filename;

        if (file_exists($exportFile)) {
            ilFileDelivery::deliverFileLegacy($exportFile, $filename);
        }

        if (file_exists($archiveFile)) {
            ilFileDelivery::deliverFileLegacy($archiveFile, $filename);
        }

        $this->ctrl->redirect($this, 'listExportFiles');
    }

    public function delete(): void
    {
        $archiver = new ilTestArchiver(
            $this->lng,
            $this->db,
            $this->user,
            $this->ui_factory,
            $this->ui_renderer,
            $this->irss,
            $this->request,
            $this->obj_cache,
            $this->participant_access_filter_factory,
            $this->getParentGUI()->getTestObject()->getId()
        );
        $archiveDir = $archiver->getZipExportDirectory();

        $export_dir = $this->obj->getExportDirectory();
        foreach ($this->selected_files as $file) {
            $file = basename($file);
            $dir = substr($file, 0, strlen($file) - 4);

            if (!strlen($file) || !strlen($dir)) {
                continue;
            }

            $exp_file = $export_dir . '/' . $file;
            $arc_file = $archiveDir . '/' . $file;
            $exp_dir = $export_dir . '/' . $dir;
            if (@is_file($exp_file)) {
                unlink($exp_file);
            }
            if (@is_file($arc_file)) {
                unlink($arc_file);
            }
            if (@is_dir($exp_dir)) {
                ilFileUtils::delDir($exp_dir);
            }
        }
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('msg_deleted_export_files'), true);
        $this->ctrl->redirect($this, 'listExportFiles');
    }
}

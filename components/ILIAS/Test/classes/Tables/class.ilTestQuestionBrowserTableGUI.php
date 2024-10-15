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

use ILIAS\Test\Utilities\TitleColumnsBuilder;
use ILIAS\Test\RequestDataCollector;
use ILIAS\Test\Logging\TestLogger;
use ILIAS\TestQuestionPool\Questions\GeneralQuestionPropertiesRepository;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\Taxonomy\DomainService as TaxonomyService;
use ILIAS\Test\Questions\QuestionsBrowserFilter;
use ILIAS\Test\Questions\QuestionsBrowserTable;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\UI\Component\Link\Standard as StandardLink;

/**
 * @ilCtrl_Calls ilTestQuestionBrowserTableGUI: ilFormPropertyDispatchGUI
 */
class ilTestQuestionBrowserTableGUI
{
    public const REPOSITORY_ROOT_NODE_ID = 1;

    public const MODE_PARAMETER = 'question_browse_mode';
    public const MODE_BROWSE_POOLS = 'modeBrowsePools';
    public const MODE_BROWSE_TESTS = 'modeBrowseTests';

    public const CMD_BROWSE_QUESTIONS = 'browseQuestions';
    public const CMD_INSERT_QUESTIONS = 'insertQuestions';

    public function __construct(
        private readonly ilTabsGUI $tabs,
        private readonly ilTree $tree,
        private readonly ilDBInterface $db,
        private readonly TestLogger $logger,
        private readonly ilComponentRepository $component_repository,
        private readonly ilObjTest $test_obj,
        private readonly ilAccessHandler $access,
        private readonly GlobalHttpState $http_state,
        private readonly Refinery $refinery,
        private readonly UIFactory $ui_factory,
        private readonly UIRenderer $ui_renderer,
        private readonly RequestDataCollector $testrequest,
        private readonly GeneralQuestionPropertiesRepository $questionrepository,
        private readonly ilLanguage $lng,
        private readonly ilCtrl $ctrl,
        private readonly ilGlobalTemplateInterface $main_tpl,
        private readonly ilUIService $ui_service,
        private readonly DataFactory $data_factory,
        private readonly TaxonomyService $taxonomy,
        private readonly TitleColumnsBuilder $title_builder,
    ) {
    }

    public function executeCommand(): bool
    {
        $this->handleWriteAccess();
        $this->handleTabs();

        switch (strtolower((string) $this->ctrl->getNextClass($this))) {
            case strtolower(self::class):
            case '':
                $cmd = $this->ctrl->getCmd() . 'Cmd';
                return $this->$cmd();

            default:
                return $this->browseQuestionsCmd();
        }
    }

    /**
     * @throws ilCtrlException
     */
    private function handleWriteAccess(): void
    {
        if (!$this->access->checkAccess('write', '', $this->test_obj->getRefId())) {
            $this->ctrl->redirectByClass(ilObjTestGUI::class, ilObjTestGUI::SHOW_QUESTIONS_CMD);
        }
    }

    private function browseQuestionsCmd(): bool
    {
        $this->ctrl->setParameter($this, self::MODE_PARAMETER, $this->testrequest->raw(self::MODE_PARAMETER));
        $action = $this->ctrl->getLinkTarget($this, self::CMD_BROWSE_QUESTIONS);

        $mode = $this->ctrl->getParameterArrayByClass(self::class)[self::MODE_PARAMETER];
        $parent_title = ($mode === self::MODE_BROWSE_TESTS ? 'test_title' : 'tst_source_question_pool');

        $filter = (new QuestionsBrowserFilter(
            $this->ui_service,
            $this->lng,
            $this->ui_factory,
            'question_browser_filter',
            $parent_title
        ))->getComponent($action, $this->http_state->request());

        $this->main_tpl->setContent(
            $this->ui_renderer->render([
                $filter,
                (new QuestionsBrowserTable(
                    (string) $this->test_obj->getId(),
                    $this->ui_factory,
                    $this->ui_renderer,
                    $this->lng,
                    $this->ctrl,
                    $this->data_factory,
                    new ilAssQuestionList($this->db, $this->lng, $this->refinery, $this->component_repository),
                    $this->test_obj,
                    $this->tree,
                    $this->testrequest,
                    $this->taxonomy,
                    $this->questionPoolLinkBuilder,
                    $parent_title
                ))->getComponent(
                    $this->http_state->request(),
                    $this->ui_service->filter()->getData($filter)
                )
            ])
        );

        return true;
    }

    private function insertQuestionsCmd(): void
    {
        $selected_array = $this->http_state->wrapper()->query()->retrieve(
            'qlist_q_id',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()),
                $this->refinery->always([])
            ])
        );

        if ($selected_array === []) {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt('tst_insert_missing_question'), true);
            $this->ctrl->redirect($this, self::CMD_BROWSE_QUESTIONS);
        }

        $test_question_set_config = $this->buildTestQuestionSetConfig();
        array_map(
            fn(int $v): int => $this->test_obj->insertQuestion($v),
            $selected_array
        );

        $this->test_obj->saveCompleteStatus($test_question_set_config);

        $this->main_tpl->setOnScreenMessage('success', $this->lng->txt('tst_questions_inserted'), true);

        $this->ctrl->redirectByClass($this->getBackTargetCmdClass(), $this->getBackTargetCommand());
    }

    private function handleParameters(): void
    {
        if ($this->testrequest->isset(self::CONTEXT_PARAMETER)) {
            $this->ctrl->setParameterByClass(
                self::class,
                self::CONTEXT_PARAMETER,
                $this->testrequest->raw(self::CONTEXT_PARAMETER)
            );
            $this->addHiddenInput(self::CONTEXT_PARAMETER, $this->testrequest->raw(self::CONTEXT_PARAMETER));
        }

        if ($this->testrequest->isset(self::MODE_PARAMETER)) {
            $this->ctrl->setParameterByClass(
                self::class,
                self::MODE_PARAMETER,
                $this->testrequest->raw(self::MODE_PARAMETER)
            );
            $this->addHiddenInput(self::MODE_PARAMETER, $this->testrequest->raw(self::MODE_PARAMETER));
        }
    }

    private function handleTabs(): void
    {
        $this->tabs->clearTargets();
        $this->tabs->clearSubTabs();

        $this->tabs->setBackTarget(
            $this->lng->txt('backtocallingtest'),
            $this->ctrl->getLinkTargetByClass(ilObjTestGUI::class, ilObjTestGUI::SHOW_QUESTIONS_CMD)
        );

        $browseQuestionsTabLabel = match ($this->testrequest->raw(self::MODE_PARAMETER)) {
            self::MODE_BROWSE_POOLS => $this->lng->txt('tst_browse_for_qpl_questions'),
            self::MODE_BROWSE_TESTS => $this->lng->txt('tst_browse_for_tst_questions'),
            default => ''
        };

        $this->tabs->addTab(
            self::CMD_BROWSE_QUESTIONS,
            $browseQuestionsTabLabel,
            $this->ctrl->getLinkTarget($this, self::CMD_BROWSE_QUESTIONS)
        );
        $this->tabs->activateTab('browseQuestions');
    }

    private function getBackTargetLabel(): string
    {
        return $this->lng->txt('backtocallingtest');
    }

    private function getBackTargetUrl(): string
    {
        return $this->ctrl->getLinkTargetByClass(
            $this->getBackTargetCmdClass(),
            $this->getBackTargetCommand()
        );
    }

    private function getBackTargetCmdClass(): string
    {
        switch ($this->fetchContextParameter()) {
            case self::CONTEXT_PAGE_VIEW:

                return 'ilTestExpressPageObjectGUI';
            case self::CONTEXT_LIST_VIEW:
            default:
                return 'ilObjTestGUI';
        }
    }

    private function getBackTargetCommand(): string
    {
        switch ($this->fetchContextParameter()) {
            case self::CONTEXT_LIST_VIEW:
                return ilObjTestGUI::SHOW_QUESTIONS_CMD;

            case self::CONTEXT_PAGE_VIEW:
                return 'showPage';
        }

        return '';
    }

    private function getBrowseQuestionsTabLabel(): string
    {
        switch ($this->fetchModeParameter()) {
            case self::MODE_BROWSE_POOLS:

                return $this->lng->txt('tst_browse_for_qpl_questions');

            case self::MODE_BROWSE_TESTS:

                return $this->lng->txt('tst_browse_for_tst_questions');
        }

        return '';
    }

    private function getBrowseQuestionsTabUrl(): string
    {
        return $this->ctrl->getLinkTarget($this, self::CMD_BROWSE_QUESTIONS);
    }

    public function initFilter(): void
    {
        $ti = new ilTextInputGUI($this->lng->txt("tst_qbt_filter_question_title"), "title");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["title"] = $ti->getValue();

        $ti = new ilTextInputGUI($this->lng->txt("description"), "description");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["description"] = $ti->getValue();

        $ti = new ilTextInputGUI($this->lng->txt("author"), "author");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $this->addFilterItem($ti);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $ti->readFromSession();
        $this->filter["author"] = $ti->getValue();

        $lifecycleOptions = array_merge(
            ['' => $this->lng->txt('qst_lifecycle_filter_all')],
            ilAssQuestionLifecycle::getDraftInstance()->getSelectOptions($this->lng)
        );
        $lifecycleInp = new ilSelectInputGUI($this->lng->txt('qst_lifecycle'), 'lifecycle');
        $lifecycleInp->setOptions($lifecycleOptions);
        $this->addFilterItem($lifecycleInp);
        $lifecycleInp->readFromSession();
        $this->filter['lifecycle'] = $lifecycleInp->getValue();

        $types = ilObjQuestionPool::_getQuestionTypes();
        $options = [];
        $options[""] = $this->lng->txt('filter_all_question_types');
        foreach ($types as $translation => $row) {
            $options[$row['type_tag']] = $translation;
        }

        $si = new ilSelectInputGUI($this->lng->txt("question_type"), "type");
        $si->setOptions($options);
        $this->addFilterItem($si);
        $si->readFromSession();
        $this->filter["type"] = $si->getValue();

        $ti = new ilTextInputGUI($this->getParentObjectLabel(), 'parent_title');
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter['parent_title'] = $ti->getValue();

        $ri = new ilRepositorySelectorInputGUI($this->lng->txt('repository'), 'repository_root_node');
        $ri->setHeaderMessage($this->lng->txt('question_browse_area_info'));
        if ($this->fetchModeParameter() === self::MODE_BROWSE_TESTS) {
            $ri->setClickableTypes(['tst']);
        } else {
            $ri->setClickableTypes(['qpl']);
        }
        $this->addFilterItem($ri);
        $ri->readFromSession();
        $this->filter['repository_root_node'] = $ri->getValue();
    }

    private function getParentObjectLabel(): string
    {
        switch ($this->fetchModeParameter()) {
            case self::MODE_BROWSE_POOLS:

                return $this->lng->txt('qpl');

            case self::MODE_BROWSE_TESTS:

                return $this->lng->txt('tst');
        }

        return '';
    }

    protected function getTranslatedLifecycle(?string $lifecycle): string
    {
        try {
            return ilAssQuestionLifecycle::getInstance($lifecycle)->getTranslation($this->lng);
        } catch (ilTestQuestionPoolInvalidArgumentException $e) {
            return '';
        }
    }

    public function fillRow(array $a_set): void
    {
        $this->tpl->setVariable('QUESTION_ID', $a_set['question_id']);
        $this->tpl->setVariable('QUESTION_TITLE', $a_set['title']);
        $this->tpl->setVariable('QUESTION_COMMENT', $a_set['description']);
        $this->tpl->setVariable('QUESTION_TYPE', $this->questionrepository->getForQuestionId($a_set['question_id'])->getTypeName($this->lng));
        $this->tpl->setVariable('QUESTION_AUTHOR', $a_set['author']);
        $this->tpl->setVariable('QUESTION_LIFECYCLE', $this->getTranslatedLifecycle($a_set['lifecycle']));
        $this->tpl->setVariable(
            'QUESTION_CREATED',
            ilDatePresentation::formatDate(new ilDate($a_set['created'], IL_CAL_UNIX))
        );
        $this->tpl->setVariable(
            'QUESTION_UPDATED',
            ilDatePresentation::formatDate(new ilDate($a_set['tstamp'], IL_CAL_UNIX))
        );
        $this->tpl->setVariable(
            'QUESTION_POOL_OR_TEST_TITLE',
            $this->ui_renderer->render(
                $this->buildPossiblyLinkedQuestonPoolOrTestTitle(
                    (int) $a_set['obj_fi'],
                    $a_set['parent_title']
                )
            )
        );
    }

    private function buildPossiblyLinkedQuestonPoolOrTestTitle(int $obj_id, string $parent_title): StandardLink
    {
        switch ($this->fetchModeParameter()) {
            case self::MODE_BROWSE_POOLS:
                return $this->title_builder->buildAccessCheckedQuestionpoolTitleAsLink(
                    $obj_id,
                    $parent_title
                );

            case self::MODE_BROWSE_TESTS:
                return $this->title_builder->buildAccessCheckedTestTitleAsLinkForObjId(
                    $obj_id,
                    $parent_title
                );
        }

        return '';
    }

    private function buildTestQuestionSetConfig(): ilTestQuestionSetConfig
    {
        return (new ilTestQuestionSetConfigFactory(
            $this->tree,
            $this->db,
            $this->lng,
            $this->logger,
            $this->component_repository,
            $this->test_obj,
            $this->questionrepository
        ))->getQuestionSetConfig();
    }
}

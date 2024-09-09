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

/**
 * The assOrderingHorizontalGUI class encapsulates the GUI representation for horizontal ordering questions.
 *
 * @author	Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author	Björn Heyser <bheyser@databay.de>
 * @author	Maximilian Becker <mbecker@databay.de>
 *
 * @version	$Id$
 *
 * @ingroup components\ILIASTestQuestionPool
 *
 * @ilctrl_iscalledby assOrderingHorizontalGUI: ilObjQuestionPoolGUI
 * @ilCtrl_Calls assOrderingHorizontalGUI: ilPropertyFormGUI, ilFormPropertyDispatchGUI
 */
class assOrderingHorizontalGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable
{
    /**
    * assOrderingHorizontalGUI constructor
    *
    * The constructor takes possible arguments an creates an instance of the assOrderingHorizontalGUI object.
    *
    * @param integer $id The database id of a single choice question object
    * @access public
    */
    public function __construct($id = -1)
    {
        parent::__construct();
        $this->object = new assOrderingHorizontal();
        $this->setErrorMessage($this->lng->txt("msg_form_save_error"));
        if ($id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    public function getCommand($cmd)
    {
        return $cmd;
    }

    /**
     * {@inheritdoc}
     */
    protected function writePostData(bool $always = false): int
    {
        $hasErrors = (!$always) ? $this->editQuestion(true) : false;
        if (!$hasErrors) {
            $this->writeQuestionGenericPostData();
            $this->writeQuestionSpecificPostData(new ilPropertyFormGUI());
            $this->saveTaxonomyAssignments();
            return 0;
        }
        return 1;
    }

    public function editQuestion(
        bool $checkonly = false,
        ?bool $is_save_cmd = null
    ): bool {
        $save = $is_save_cmd ?? $this->isSaveCommand();

        $form = new ilPropertyFormGUI();
        $this->editForm = $form;

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->outQuestionType());
        $form->setMultipart(false);
        $form->setTableWidth("100%");
        $form->setId("orderinghorizontal");

        $this->addBasicQuestionFormProperties($form);
        $this->populateQuestionSpecificFormPart($form);


        $this->populateTaxonomyFormSection($form);

        $this->addQuestionFormCommandButtons($form);

        $errors = false;

        if ($save) {
            $form->setValuesByPost();
            $errors = !$form->checkInput();
            $form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
            if ($errors) {
                $checkonly = false;
            }
        }

        if (!$checkonly) {
            $this->renderEditForm($form);
        }
        return $errors;
    }

    public function getSolutionOutput(
        int $active_id,
        ?int $pass = null,
        bool $graphical_output = false,
        bool $result_output = false,
        bool $show_question_only = true,
        bool $show_feedback = false,
        bool $show_correct_solution = false,
        bool $show_manual_scoring = false,
        bool $show_question_text = true,
        bool $show_inline_feedback = true
    ): string {
        // get the solution of the user for the active pass or from the last pass if allowed
        $template = new ilTemplate("tpl.il_as_qpl_orderinghorizontal_output_solution.html", true, true, "components/ILIAS/TestQuestionPool");

        if (($active_id > 0) && (!$show_correct_solution)) {
            $elements = [];
            $solutions = $this->object->getSolutionValues($active_id, $pass);

            if (count($solutions) && strlen($solutions[0]["value1"])) {
                $elements = explode("{::}", $solutions[0]["value1"]);
            }

            if (!count($elements)) {
                $elements = $this->object->getRandomOrderingElements();
            }

            foreach ($elements as $id => $element) {
                $template->setCurrentBlock("element");
                $template->setVariable("ELEMENT_ID", "sol_e_" . $this->object->getId() . "_$id");
                $template->setVariable("ELEMENT_VALUE", ilLegacyFormElementsUtil::prepareFormOutput($element));
                $template->parseCurrentBlock();
            }
        } else {
            $elements = $this->object->getOrderingElements();
            foreach ($elements as $id => $element) {
                $template->setCurrentBlock("element");
                $template->setVariable("ELEMENT_ID", "sol_e_" . $this->object->getId() . "_$id");
                $template->setVariable("ELEMENT_VALUE", ilLegacyFormElementsUtil::prepareFormOutput($element));
                $template->parseCurrentBlock();
            }
        }

        if (($active_id > 0) && (!$show_correct_solution)) {
            if ($this->object->getStep() === null) {
                $reached_points = $this->object->getReachedPoints($active_id, $pass);
            } else {
                $reached_points = $this->object->calculateReachedPoints($active_id, $pass);
            }
            if ($graphical_output) {
                $correctness_icon = $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_NOT_OK);
                if ($reached_points == $this->object->getMaximumPoints()) {
                    $correctness_icon = $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_OK);
                } elseif ($reached_points > 0) {
                    $correctness_icon = $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_MOSTLY_OK);
                }
                $template->setCurrentBlock("icon_ok");
                $template->setVariable("ICON_OK", $correctness_icon);
                $template->parseCurrentBlock();
            }
        } else {
            $reached_points = $this->object->getPoints();
        }

        if ($result_output) {
            $resulttext = ($reached_points == 1) ? "(%s " . $this->lng->txt("point") . ")" : "(%s " . $this->lng->txt("points") . ")";
            $template->setVariable("RESULT_OUTPUT", sprintf($resulttext, $reached_points));
        }
        if ($show_question_text == true) {
            $template->setVariable("QUESTIONTEXT", $this->object->getQuestionForHTMLOutput());
        }
        //		$template->setVariable("SOLUTION_TEXT", ilUtil::prepareFormOutput($solutionvalue));
        if ($this->object->getTextSize() >= 10) {
            $template->setVariable("STYLE", " style=\"font-size: " . $this->object->getTextSize() . "%;\"");
        }

        $questionoutput = $template->get();
        $solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", true, true, "components/ILIAS/TestQuestionPool");
        $solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);


        $feedback = '';
        if ($show_feedback) {
            if (!$this->isTestPresentationContext()) {
                $fb = $this->getGenericFeedbackOutput((int) $active_id, $pass);
                $feedback .= strlen($fb) ? $fb : '';
            }
        }
        if (strlen($feedback)) {
            $cssClass = (
                $this->hasCorrectSolution($active_id, $pass) ?
                ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT : ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG
            );

            $solutiontemplate->setVariable("ILC_FB_CSS_CLASS", $cssClass);
            $solutiontemplate->setVariable("FEEDBACK", ilLegacyFormElementsUtil::prepareTextareaOutput($feedback, true));
        }
        $solutionoutput = $solutiontemplate->get();
        if (!$show_question_only) {
            // get page object output
            $solutionoutput = $this->getILIASPage($solutionoutput);
        }
        return $solutionoutput;
    }


    public function getPreview(
        bool $show_question_only = false,
        bool $show_inline_feedback = false
    ): string {
        $template = new ilTemplate('tpl.il_as_qpl_orderinghorizontal_output.html', true, true, 'components/ILIAS/TestQuestionPool');
        $this->initializePlayerJS();

        if ($this->getPreviewSession() !== null
            && $this->getPreviewSession()->getParticipantsSolution() !== null) {
            $elements = $this->object->splitAndTrimOrderElementText(
                (string) $this->getPreviewSession()->getParticipantsSolution(),
                $this->object->getAnswerSeparator()
            );
        } else {
            $elements = $this->object->getRandomOrderingElements();
        }

        foreach ($elements as $id => $element) {
            $template->setCurrentBlock('element');
            $template->setVariable('ELEMENT_ID', "e_{$this->object->getId()}_{$id}");
            $template->setVariable('ELEMENT_VALUE', ilLegacyFormElementsUtil::prepareFormOutput($element));
            $template->parseCurrentBlock();
        }
        $template->setVariable('QUESTION_ID', $this->object->getId());
        $template->setVariable('VALUE_ORDERRESULT', ' value="' . join('{::}', $elements) . '"');
        if ($this->object->getTextSize() >= 10) {
            $template->setVariable('STYLE', ' style="font-size: ' . $this->object->getTextSize() . '%;"');
        }
        $template->setVariable('QUESTIONTEXT', $this->object->getQuestionForHTMLOutput());
        if ($show_question_only) {
            return $template->get();
        }
        return $this->getILIASPage($template->get());
    }

    public function getTestOutput(
        int $active_id,
        int $pass,
        bool $is_question_postponed = false,
        array|bool $user_post_solutions = false,
        bool $show_specific_inline_feedback = false
    ): string {
        $template = new ilTemplate('tpl.il_as_qpl_orderinghorizontal_output.html', true, true, 'components/ILIAS/TestQuestionPool');
        $this->initializePlayerJS();

        $elements = $this->object->getRandomOrderingElements();
        if ($active_id) {
            $solutions = $this->object->getTestOutputSolutions($active_id, $pass);
            if (count($solutions) == 1) {
                $elements = explode('{::}', $solutions[0]['value1']);
            }
        }
        if (!is_array($solutions) || count($solutions) == 0) {
            ilSession::set('qst_ordering_horizontal_elements', $elements);
        } else {
            ilSession::clear('qst_ordering_horizontal_elements');
        }
        foreach ($elements as $id => $element) {
            $template->setCurrentBlock('element');
            $template->setVariable('ELEMENT_ID', "e_{$this->object->getId()}_{$id}");
            $template->setVariable('ELEMENT_VALUE', ilLegacyFormElementsUtil::prepareFormOutput($element));
            $template->parseCurrentBlock();
        }
        $template->setVariable('QUESTION_ID', $this->object->getId());
        if ($this->object->getTextSize() >= 10) {
            $template->setVariable('STYLE', ' style="font-size: ' . $this->object->getTextSize() . '%;"');
        }
        $template->setVariable('VALUE_ORDERRESULT', ' value="' . join('{::}', $elements) . '"');
        $template->setVariable('QUESTIONTEXT', $this->object->getQuestionForHTMLOutput());
        return $this->outQuestionPage("", $is_question_postponed, $active_id, $template->get());
    }

    public function getSpecificFeedbackOutput(array $userSolution): string
    {
        return '';
    }

    public function writeQuestionSpecificPostData(ilPropertyFormGUI $form): void
    {
        $this->object->setTextSize((float) str_replace(',', '.', $this->request_data_collector->raw('textsize') ?? '0.0'));
        $this->object->setOrderText($this->request_data_collector->raw('ordertext'));
        $this->object->setPoints((float) str_replace(',', '.', $this->request_data_collector->raw('points')));
    }

    /**
     * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
     * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
     * make sense in the given context.
     *
     * E.g. array('cloze_type', 'image_filename')
     *
     * @return string[]
     */
    public function getAfterParticipationSuppressionQuestionPostVars(): array
    {
        return [];
    }

    public function populateQuestionSpecificFormPart(\ilPropertyFormGUI $form): ilPropertyFormGUI
    {
        // ordertext
        $ordertext = new ilTextAreaInputGUI($this->lng->txt("ordertext"), "ordertext");
        $ordertext->setValue((string) self::prepareTextareaOutput($this->object->getOrderText(), false, true));
        $ordertext->setRequired(true);
        $ordertext->setInfo(sprintf($this->lng->txt("ordertext_info"), $this->object->getSeparator()));
        $ordertext->setRows(10);
        $ordertext->setCols(80);
        $form->addItem($ordertext);
        // textsize
        $textsize = new ilNumberInputGUI($this->lng->txt("textsize"), "textsize");
        $textsize->setValue($this->object->getTextSize());
        $textsize->setInfo($this->lng->txt("textsize_info"));
        $textsize->setSize(6);
        $textsize->setMinValue(10);
        $textsize->setRequired(false);
        $form->addItem($textsize);
        // points
        $points = new ilNumberInputGUI($this->lng->txt("points"), "points");

        $points->allowDecimals(true);
        // mbecker: Fix for mantis bug 7866: Predefined values schould make sense.
        // This implements a default value of "1" for this question type.
        if ($this->object->getPoints() == null) {
            $points->setValue("1");
        } else {
            $points->setValue($this->object->getPoints());
        }
        $points->setRequired(true);
        $points->setSize(3);
        $points->setMinValue(0.0);
        $points->setMinvalueShouldBeGreater(true);
        $form->addItem($points);
        return $form;
    }

    /**
     * Returns an html string containing a question specific representation of the answers so far
     * given in the test for use in the right column in the scoring adjustment user interface.
     * @param array $relevant_answers
     * @return string
     */
    public function getAggregatedAnswersView(array $relevant_answers): string
    {
        return  $this->renderAggregateView(
            $this->aggregateAnswers($relevant_answers, $this->object->getOrderText())
        )->get();
    }

    public function aggregateAnswers($relevant_answers_chosen, $answer_defined_on_question): array
    {
        $aggregate = [];
        foreach ($relevant_answers_chosen as $answer) {
            $answer = str_replace($this->object->getAnswerSeparator(), '&nbsp;&nbsp;-&nbsp;&nbsp;', $answer);
            if (in_array($answer['value1'], $aggregate)) {
                $aggregate[$answer['value1']] = $aggregate[$answer['value1']] + 1;
            } else {
                $aggregate[$answer['value1']] = 1;
            }
        }

        return $aggregate;
    }

    /**
     * @param $aggregate
     *
     * @return ilTemplate
     */
    public function renderAggregateView($aggregate): ilTemplate
    {
        $tpl = new ilTemplate('tpl.il_as_aggregated_answers_table.html', true, true, "components/ILIAS/TestQuestionPool");

        foreach ($aggregate as $key => $line_data) {
            $tpl->setCurrentBlock('aggregaterow');
            $tpl->setVariable('COUNT', $line_data);
            $tpl->setVariable('OPTION', $key);
            $tpl->parseCurrentBlock();
        }
        return $tpl;
    }

    public function getAnswersFrequency($relevantAnswers, $questionIndex): array
    {
        $answers = [];

        foreach ($relevantAnswers as $ans) {
            $md5 = md5($ans['value1']);

            if (!isset($answers[$md5])) {
                $answer = str_replace(
                    $this->object->getAnswerSeparator(),
                    '&nbsp;&nbsp;-&nbsp;&nbsp;',
                    $ans['value1']
                );

                $answers[$md5] = [
                    'answer' => $answer, 'frequency' => 0
                ];
            }

            $answers[$md5]['frequency']++;
        }

        return $answers;
    }

    public function populateCorrectionsFormProperties(ilPropertyFormGUI $form): void
    {
        // points
        $points = new ilNumberInputGUI($this->lng->txt("points"), "points");

        $points->allowDecimals(true);
        $points->setValue($this->object->getPoints());
        $points->setRequired(true);
        $points->setSize(3);
        $points->setMinValue(0.0);
        $points->setMinvalueShouldBeGreater(true);
        $form->addItem($points);
    }

    public function saveCorrectionsFormProperties(ilPropertyFormGUI $form): void
    {
        $this->object->setPoints((float) str_replace(',', '.', $form->getInput('points')));
    }

    private function initializePlayerJS(): void
    {
        $this->tpl->addJavascript('assets/js/orderinghorizontal.js');
        $this->tpl->addOnLoadCode(
            "il.test.orderinghorizontal.init(document.querySelector('#horizontal_{$this->object->getId()}'));"
        );
    }
}

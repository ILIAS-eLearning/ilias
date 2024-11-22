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
 * Multiple choice question GUI representation
 *
 * The assTextSubsetGUI class encapsulates the GUI representation
 * for multiple choice questions.
 *
 * @author	Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author	Björn Heyser <bheyser@databay.de>
 * @author	Maximilian Becker <mbecker@databay.de>
 *
 * @version	$Id$
 *
 * @ingroup components\ILIASTestQuestionPool
 * @ilCtrl_Calls assTextSubsetGUI: ilFormPropertyDispatchGUI
 */
class assTextSubsetGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable
{
    private $answers_from_post;

    /**
     * assTextSubsetGUI constructor
     *
     * The constructor takes possible arguments an creates an instance of the assTextSubsetGUI object.
     *
     * @param integer $id The database id of a text subset question object
     */
    public function __construct($id = -1)
    {
        parent::__construct();
        $this->object = new assTextSubset();
        if ($id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function writePostData(bool $always = false): int
    {
        /*
         * sk 26.09.22: This is horrific but I don't see a better way right now,
         * without needing to check most questions for side-effects.
         */
        $answers = $this->request_data_collector->rawArray('answers');
        $this->answers_from_post = $answers['answer'] ?? [];

        if (!(!$always && $this->editQuestion(true))) {
            $this->writeQuestionGenericPostData();
            $this->writeQuestionSpecificPostData(new ilPropertyFormGUI());
            $this->writeAnswerSpecificPostData(new ilPropertyFormGUI());
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
        $form->setId("asstextsubset");

        $this->addBasicQuestionFormProperties($form);
        $this->populateQuestionSpecificFormPart($form);
        $this->populateAnswerSpecificFormPart($form);
        $this->populateTaxonomyFormSection($form);
        $this->addQuestionFormCommandButtons($form);

        $errors = false;
        if ($save) {
            $form->setValuesByPost();
            $points = $form->getItemByPostVar('points');
            $points->setValue($this->object->getMaximumPoints());
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

    public function addanswers(): void
    {
        $this->setAdditionalContentEditingModeFromPost();
        $this->writePostData(true);
        $cmd = $this->request_data_collector->raw('cmd') ?? [];
        $add_answers = in_array('addanswers', $cmd) && is_array($cmd['addanswers']) ? $cmd['addanswers'] : [];
        $this->object->addAnswer('', 0, key($add_answers) + 1);
        $this->editQuestion();
    }

    public function removeanswers(): void
    {
        $this->setAdditionalContentEditingModeFromPost();
        $this->writePostData(true);
        $cmd = $this->request_data_collector->raw('cmd') ?? [];
        $remove_answers = in_array('removeanswers', $cmd) && is_array($cmd['removeanswers']) ? $cmd['removeanswers'] : [];
        $this->object->deleteAnswer(key($remove_answers));
        $this->editQuestion();
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
        $solutions = [];
        if (($active_id > 0) && (!$show_correct_solution)) {
            $solutions = $this->object->getSolutionValues($active_id, $pass);
        } else {
            $rank = [];
            foreach ($this->object->answers as $answer) {
                $points_string_for_key = (string) $answer->getPoints();
                if ($answer->getPoints() > 0) {
                    if (!array_key_exists($points_string_for_key, $rank)) {
                        $rank[$points_string_for_key] = [];
                    }
                    array_push($rank[$points_string_for_key], $answer->getAnswertext());
                }
            }
            krsort($rank, SORT_NUMERIC);
            foreach ($rank as $index => $bestsolutions) {
                array_push($solutions, ["value1" => join(",", $bestsolutions), "points" => $index]);
            }
        }

        return $this->renderSolutionOutput(
            $solutions,
            $active_id,
            $pass,
            $graphical_output,
            $result_output,
            $show_question_only,
            $show_feedback,
            $show_correct_solution,
            $show_manual_scoring,
            $show_question_text,
            false,
            $show_inline_feedback,
        );
    }

    public function renderSolutionOutput(
        mixed $user_solutions,
        int $active_id,
        int $pass,
        bool $graphical_output = false,
        bool $result_output = false,
        bool $show_question_only = true,
        bool $show_feedback = false,
        bool $show_correct_solution = false,
        bool $show_manual_scoring = false,
        bool $show_question_text = true,
        bool $show_autosave_title = false,
        bool $show_inline_feedback = false,
    ): ?string {
        $template = new ilTemplate("tpl.il_as_qpl_textsubset_output_solution.html", true, true, "components/ILIAS/TestQuestionPool");
        $solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", true, true, "components/ILIAS/TestQuestionPool");

        $available_answers = &$this->object->getAvailableAnswers();
        for ($i = 0; $i < $this->object->getCorrectAnswers(); $i++) {
            if (!array_key_exists($i, $user_solutions) || (strcmp($user_solutions[$i]["value1"], "") == 0)) {
            } else {
                if (($active_id > 0) && (!$show_correct_solution)) {
                    if ($graphical_output) {
                        // output of ok/not ok icons for user entered solutions
                        $index = $this->object->isAnswerCorrect($available_answers, $user_solutions[$i]["value1"]);
                        $correct = false;
                        if ($index !== false) {
                            unset($available_answers[$index]);
                            $correct = true;
                        }

                        $correctness_icon = $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_NOT_OK);
                        if ($correct) {
                            $correctness_icon = $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_OK);
                        }
                        $template->setCurrentBlock("icon_ok");
                        $template->setVariable("ICON_OK", $correctness_icon);
                        $template->parseCurrentBlock();
                    }
                }
                $template->setCurrentBlock("textsubset_row");
                $template->setVariable("SOLUTION", $this->escapeTemplatePlaceholders($user_solutions[$i]["value1"]));
                $template->setVariable("COUNTER", $i + 1);
                if ($result_output) {
                    $points = $user_solutions[$i]["points"];
                    $resulttext = ($points == 1) ? "(%s " . $this->lng->txt("point") . ")" : "(%s " . $this->lng->txt("points") . ")";
                    $template->setVariable("RESULT_OUTPUT", sprintf($resulttext, $points));
                }
                $template->parseCurrentBlock();
            }
        }
        if ($show_question_text == true) {
            $template->setVariable("QUESTIONTEXT", $this->object->getQuestionForHTMLOutput());
        }
        $questionoutput = $template->get();
        $feedback = ($show_feedback && !$this->isTestPresentationContext()) ? $this->getGenericFeedbackOutput((int) $active_id, $pass) : "";
        if (strlen($feedback)) {
            $cssClass = (
                $this->hasCorrectSolution($active_id, $pass) ?
                ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT : ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG
            );

            $solutiontemplate->setVariable("ILC_FB_CSS_CLASS", $cssClass);
            $solutiontemplate->setVariable("FEEDBACK", ilLegacyFormElementsUtil::prepareTextareaOutput($feedback, true));
        }
        $solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

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
        $solutions = is_object($this->getPreviewSession()) ? (array) $this->getPreviewSession()->getParticipantsSolution() : [];
        $template = new ilTemplate("tpl.il_as_qpl_textsubset_output.html", true, true, "components/ILIAS/TestQuestionPool");
        $width = $this->object->getMaxTextboxWidth();
        for ($i = 0; $i < $this->object->getCorrectAnswers(); $i++) {
            $template->setCurrentBlock("textsubset_row");
            foreach ($solutions as $idx => $solution_value) {
                if ($idx == $i) {
                    $template->setVariable("TEXTFIELD_VALUE", " value=\""
                        . $this->escapeTemplatePlaceholders($solution_value)
                        . "\"");
                }
            }
            $template->setVariable("COUNTER", $i + 1);
            $template->setVariable("TEXTFIELD_ID", $i);
            $template->setVariable("TEXTFIELD_SIZE", $width);
            $template->parseCurrentBlock();
        }
        $template->setVariable("QUESTIONTEXT", $this->object->getQuestionForHTMLOutput());
        $questionoutput = $template->get();
        if (!$show_question_only) {
            // get page object output
            $questionoutput = $this->getILIASPage($questionoutput);
        }
        return $questionoutput;
    }

    public function getTestOutput(
        int $active_id,
        int $pass,
        bool $is_question_postponed = false,
        array|bool $user_post_solutions = false,
        bool $show_specific_inline_feedback = false
    ): string {
        if ($active_id) {
            $solutions = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass);
        }

        $template = new ilTemplate("tpl.il_as_qpl_textsubset_output.html", true, true, "components/ILIAS/TestQuestionPool");
        $width = $this->object->getMaxTextboxWidth();
        for ($i = 0; $i < $this->object->getCorrectAnswers(); $i++) {
            $template->setCurrentBlock("textsubset_row");
            foreach ($solutions as $idx => $solution_value) {
                if ($idx == $i) {
                    $template->setVariable("TEXTFIELD_VALUE", " value=\""
                        . $this->escapeTemplatePlaceholders($solution_value["value1"])
                        . "\"");
                }
            }
            $template->setVariable("COUNTER", $i + 1);
            $template->setVariable("TEXTFIELD_ID", $i);
            $template->setVariable("TEXTFIELD_SIZE", $width);
            $template->parseCurrentBlock();
        }
        $template->setVariable("QUESTIONTEXT", $this->object->getQuestionForHTMLOutput());
        $questionoutput = $template->get();
        $pageoutput = $this->outQuestionPage("", $is_question_postponed, $active_id, $questionoutput);
        return $pageoutput;
    }

    public function getSpecificFeedbackOutput(array $user_solution): string
    {
        $output = "";
        return ilLegacyFormElementsUtil::prepareTextareaOutput($output, true);
    }

    public function writeQuestionSpecificPostData(ilPropertyFormGUI $form): void
    {
        $this->object->setCorrectAnswers($this->request_data_collector->int('correctanswers'));
        $this->object->setTextRating($this->request_data_collector->string('text_rating'));
    }

    public function writeAnswerSpecificPostData(ilPropertyFormGUI $form): void
    {
        // Delete all existing answers and create new answers from the form data
        $this->object->flushAnswers();

        $answers = $this->request_data_collector->floatArray('answers', 3);
        $points = $answers['points'] ?? [];

        foreach ($this->answers_from_post as $index => $answertext) {
            $this->object->addAnswer(htmlentities(assQuestion::extendedTrim($answertext)), $points[$index], $index);
        }
    }

    public function populateQuestionSpecificFormPart(\ilPropertyFormGUI $form): ilPropertyFormGUI
    {
        // number of requested answers
        $correctanswers = new ilNumberInputGUI($this->lng->txt("nr_of_correct_answers"), "correctanswers");
        $correctanswers->setMinValue(1);
        $correctanswers->setDecimals(0);
        $correctanswers->setSize(3);
        $correctanswers->setValue($this->object->getCorrectAnswers());
        $correctanswers->setRequired(true);
        $form->addItem($correctanswers);

        // maximum available points
        $points = new ilNumberInputGUI($this->lng->txt("maximum_points"), "points");
        $points->setMinValue(0.0);
        $points->setMinvalueShouldBeGreater(true);
        $points->setSize(6);
        $points->setDisabled(true);
        $points->allowDecimals(true);
        $points->setValue($this->object->getMaximumPoints());
        $points->setRequired(false);
        $form->addItem($points);

        // text rating
        $textrating = new ilSelectInputGUI($this->lng->txt("text_rating"), "text_rating");
        $text_options = [
            "ci" => $this->lng->txt("cloze_textgap_case_insensitive"),
            "cs" => $this->lng->txt("cloze_textgap_case_sensitive")
        ];
        if (!$this->object->getSelfAssessmentEditingMode()) {
            $text_options["l1"] = sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "1");
            $text_options["l2"] = sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "2");
            $text_options["l3"] = sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "3");
            $text_options["l4"] = sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "4");
            $text_options["l5"] = sprintf($this->lng->txt("cloze_textgap_levenshtein_of"), "5");
        }
        $textrating->setOptions($text_options);
        $textrating->setValue($this->object->getTextRating());
        $form->addItem($textrating);
        return $form;
    }

    public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form): ilPropertyFormGUI
    {
        $choices = new ilAnswerWizardInputGUI($this->lng->txt("answers"), "answers");
        $choices->setRequired(true);
        $choices->setQuestionObject($this->object);
        $choices->setSingleline(true);
        $choices->setAllowMove(false);
        $choices->setMinValue(0.0);
        if ($this->object->getAnswerCount() == 0) {
            $this->object->addAnswer("", 0, 0);
        }
        $choices->setValues(array_map(
            function (ASS_AnswerBinaryStateImage $value) {
                $value->setAnswerText(html_entity_decode($value->getAnswerText()));
                return $value;
            },
            $this->object->getAnswers()
        ));
        $form->addItem($choices);
        return $form;
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
    public function getAfterParticipationSuppressionAnswerPostVars(): array
    {
        return [];
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

    /**
     * Returns an html string containing a question specific representation of the answers so far
     * given in the test for use in the right column in the scoring adjustment user interface.
     * @param array $relevant_answers
     * @return string
     */
    public function getAggregatedAnswersView(array $relevant_answers): string
    {
        return  $this->renderAggregateView(
            $this->aggregateAnswers($relevant_answers)
        )->get();
    }

    public function aggregateAnswers($relevant_answers_chosen): array
    {
        $aggregate = [];

        foreach ($relevant_answers_chosen as $relevant_answer) {
            if (array_key_exists($relevant_answer['value1'], $aggregate)) {
                $aggregate[$relevant_answer['value1']]++;
            } else {
                $aggregate[$relevant_answer['value1']] = 1;
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

        foreach ($aggregate as $key => $value) {
            $tpl->setCurrentBlock('aggregaterow');
            $tpl->setVariable('OPTION', $key);
            $tpl->setVariable('COUNT', $value);
            $tpl->parseCurrentBlock();
        }
        return $tpl;
    }

    public function getAnswersFrequency($relevantAnswers, $questionIndex): array
    {
        $answers = [];

        foreach ($relevantAnswers as $ans) {
            if (!isset($answers[$ans['value1']])) {
                $answers[$ans['value1']] = [
                    'answer' => $ans['value1'], 'frequency' => 0
                ];
            }

            $answers[$ans['value1']]['frequency']++;
        }
        $answers = $this->completeAddAnswerAction($answers, $questionIndex);
        return $answers;
    }

    protected function completeAddAnswerAction($answers, $questionIndex)
    {
        foreach ($answers as $key => $ans) {
            $found = false;

            foreach ($this->object->getAnswers() as $item) {
                if ($ans['answer'] !== $item->getAnswerText()) {
                    continue;
                }

                $found = true;
                break;
            }

            if (!$found) {
                $answers[$key]['addable'] = true;
            }
        }

        return $answers;
    }

    public function populateCorrectionsFormProperties(ilPropertyFormGUI $form): void
    {
        $choices = new ilAssAnswerCorrectionsInputGUI($this->lng->txt("answers"), "answers");
        $choices->setRequired(true);
        $choices->setQuestionObject($this->object);
        $choices->setValues($this->object->getAnswers());
        $form->addItem($choices);
    }

    /**
     * @param ilPropertyFormGUI $form
     */
    public function saveCorrectionsFormProperties(ilPropertyFormGUI $form): void
    {
        $input = $form->getItemByPostVar('answers');
        $values = $input->getValues();

        foreach ($this->object->getAnswers() as $index => $answer) {
            $points = (float) str_replace(',', '.', $values[$index]->getPoints());
            $answer->setPoints($points);
        }
    }
}

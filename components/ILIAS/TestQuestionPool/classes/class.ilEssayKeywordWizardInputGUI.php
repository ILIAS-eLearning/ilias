<?php

use ILIAS\TestQuestionPool\RequestDataCollector;

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

class ilEssayKeywordWizardInputGUI extends ilSingleChoiceWizardInputGUI
{
    protected readonly RequestDataCollector $request_data_collector;
    public function __construct($a_title = '', $a_postvar = '')
    {
        global $DIC;
        parent::__construct($a_title, $a_postvar);

        $this->request_data_collector = new RequestDataCollector($this->http, $this->refinery, $DIC->upload());
    }

    public function setValue($a_value): void
    {
        $this->values = [];
        if (is_array($a_value)) {
            if (is_array($a_value['answer'])) {
                foreach ($a_value['answer'] as $index => $value) {
                    if (isset($a_value['points'])) {
                        $pvalue = $a_value['points'][$index];
                    } else {
                        $value = 0.0;
                    }
                    if (isset($a_value['points_unchecked'])) {
                        $value_unchecked = $a_value['points_unchecked'][$index];
                    } else {
                        $value_unchecked = 0.0;
                    }
                    $answer = new ASS_AnswerMultipleResponseImage($value, (float) $pvalue, $index, $value_unchecked);
                    $this->values[] = $answer;
                }
            }
        }
    }

    /**
     * Check input, strip slashes etc. set alert, if input is not ok.
     * @return    boolean        Input ok, true/false
     */
    public function checkInput(): bool
    {
        global $DIC;
        $lng = $DIC['lng'];

        $post_var = $this->request_data_collector->retrieveArrayOfStringsFromPost($this->getPostVar());

        $found_values = is_array($post_var)
            ? ilArrayUtil::stripSlashesRecursive(
                $post_var,
                false,
                ilObjAdvancedEditing::_getUsedHTMLTagsAsString('assessment')
            )
            : $post_var;

        if (is_array($found_values)) {
            // check answers
            if (is_array($found_values['answer'])) {
                foreach ($found_values['answer'] as $aidx => $answervalue) {
                    if ($answervalue === '' && (!isset($found_values['imagename']) || $found_values['imagename'][$aidx] === '')) {
                        $this->setAlert($lng->txt('msg_input_is_required'));
                        return false;
                    }

                    if (mb_strlen($answervalue) > $this->getMaxLength()) {
                        $this->setAlert($lng->txt("msg_input_char_limit_max"));
                        return false;
                    }
                }
            }
            // check points
            $max = 0;
            if (is_array($found_values['points'])) {
                foreach ($found_values['points'] as $points) {
                    $max = max($max, $points);
                    if ($points === '' || (!is_numeric($points))) {
                        $this->setAlert($lng->txt('form_msg_numeric_value_required'));
                        return false;
                    }
                }
            }
            if ($max === 0) {
                $this->setAlert($lng->txt('enter_enough_positive_points'));
                return false;
            }
        } else {
            $this->setAlert($lng->txt('msg_input_is_required'));
            return false;
        }

        return $this->checkSubItemsInput();
    }

    /**
     * Insert property html
     * @return    void    Size
     */
    public function insert(ilTemplate $a_tpl): void
    {
        global $DIC;
        $lng = $DIC['lng'];

        $tpl = new ilTemplate("tpl.prop_essaykeywordswizardinput.html", true, true, "components/ILIAS/TestQuestionPool");
        $i = 0;
        foreach ($this->values as $value) {
            if ($this->getSingleline()) {
                if (is_object($value)) {
                    $tpl->setCurrentBlock("prop_text_propval");
                    $tpl->setVariable(
                        "PROPERTY_VALUE",
                        ilLegacyFormElementsUtil::prepareFormOutput($value->getAnswertext())
                    );
                    $tpl->parseCurrentBlock();
                    $tpl->setCurrentBlock("prop_points_propval");
                    $tpl->setVariable(
                        "PROPERTY_VALUE",
                        ilLegacyFormElementsUtil::prepareFormOutput($value->getPointsChecked())
                    );
                    $tpl->parseCurrentBlock();
                }
                $tpl->setCurrentBlock('singleline');
                $tpl->setVariable("SIZE", $this->getSize());
                $tpl->setVariable("SINGLELINE_ID", $this->getPostVar() . "[answer][$i]");
                $tpl->setVariable("SINGLELINE_ROW_NUMBER", $i);
                $tpl->setVariable("SINGLELINE_POST_VAR", $this->getPostVar());
                $tpl->setVariable("MAXLENGTH", $this->getMaxLength());
                if ($this->getDisabled()) {
                    $tpl->setVariable("DISABLED_SINGLELINE", " disabled=\"disabled\"");
                }
                $tpl->parseCurrentBlock();
            } else {
                if (!$this->getSingleline()) {
                    if (is_object($value)) {
                        $tpl->setCurrentBlock("prop_points_propval");
                        $tpl->setVariable(
                            "PROPERTY_VALUE",
                            ilLegacyFormElementsUtil::prepareFormOutput($value->getPoints())
                        );
                        $tpl->parseCurrentBlock();
                    }
                }
            }

            $tpl->setCurrentBlock("row");
            $tpl->setVariable("POST_VAR", $this->getPostVar());
            $tpl->setVariable("ROW_NUMBER", $i);
            $tpl->setVariable("ID", $this->getPostVar() . "[answer][$i]");
            $tpl->setVariable("POINTS_ID", $this->getPostVar() . "[points][$i]");
            if ($this->getDisabled()) {
                $tpl->setVariable("DISABLED_POINTS", " disabled=\"disabled\"");
            }
            $tpl->setVariable("ADD_BUTTON", $this->renderer->render(
                $this->glyph_factory->add()->withAction('#')
            ));
            $tpl->setVariable("REMOVE_BUTTON", $this->renderer->render(
                $this->glyph_factory->remove()->withAction('#')
            ));
            $tpl->parseCurrentBlock();
            $i++;
        }

        $tpl->setVariable("ELEMENT_ID", $this->getPostVar());
        $tpl->setVariable("TEXT_YES", $lng->txt('yes'));
        $tpl->setVariable("TEXT_NO", $lng->txt('no'));
        $tpl->setVariable("DELETE_IMAGE_HEADER", $lng->txt('delete_image_header'));
        $tpl->setVariable("DELETE_IMAGE_QUESTION", $lng->txt('delete_image_question'));
        $tpl->setVariable("ANSWER_TEXT", $lng->txt('answer_text'));
        $tpl->setVariable("POINTS_TEXT", $lng->txt('points'));
        $tpl->setVariable("COMMANDS_TEXT", $lng->txt('actions'));
        $tpl->setVariable("POINTS_CHECKED_TEXT", $lng->txt('checkbox_checked'));

        $a_tpl->setCurrentBlock("prop_generic");
        $a_tpl->setVariable("PROP_GENERIC", $tpl->get());
        $a_tpl->parseCurrentBlock();

        global $DIC;
        $tpl = $DIC['tpl'];
        $tpl->addJavascript("assets/js/answerwizardinput.js");
        $tpl->addJavascript("assets/js/essaykeywordwizard.js");
    }
}

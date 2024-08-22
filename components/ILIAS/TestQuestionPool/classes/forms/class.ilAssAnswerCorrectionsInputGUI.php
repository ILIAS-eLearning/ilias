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
 * Class ilTextSubsetCorrectionsInputGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/TestQuestionPool
 */
class ilAssAnswerCorrectionsInputGUI extends ilAnswerWizardInputGUI
{
    /**
     * @var bool
     */
    protected bool $hidePointsEnabled = false;

    /**
     * @return bool
     */
    public function isHidePointsEnabled(): bool
    {
        return $this->hidePointsEnabled;
    }

    /**
     * @param bool $hidePointsEnabled
     */
    public function setHidePointsEnabled(bool $hidePointsEnabled): void
    {
        $this->hidePointsEnabled = $hidePointsEnabled;
    }

    public function setValue($a_value): void
    {
        if (is_array($a_value) && is_array($a_value['points'])) {
            foreach ($a_value['points'] as $index => $value) {
                $this->values[$index]->setPoints($a_value['points'][$index]);
            }
        }
    }

    public function checkInput(): bool
    {
        global $DIC;
        $lng = $DIC['lng'];

        $found_values = $this->http->wrapper()->post()->retrieve(
            $this->getPostVar(),
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->string()),
                $this->refinery->always(null)
            ])
        );

        if ($this->isHidePointsEnabled()) {
            return true;
        }

        if (is_array($found_values)) {
            // check points
            $max = 0;
            foreach ($found_values['points'] ?? [] as $points) {
                $points = str_replace(',', '.', $points);
                $max = max($max, $points);
                if ($points === '' || !is_numeric($points)) {
                    $this->setAlert($lng->txt('form_msg_numeric_value_required'));
                    return false;
                }

                if ($this->minvalueShouldBeGreater()) {
                    if (
                        trim($points) !== ''
                        && $this->getMinValue() !== false
                        && $points <= $this->getMinValue()
                    ) {
                        $this->setAlert($lng->txt('form_msg_value_too_low'));
                        return false;
                    }
                } elseif (
                    trim($points) !== ''
                    && $this->getMinValue() !== false
                    && $points < $this->getMinValue()
                ) {
                    $this->setAlert($lng->txt('form_msg_value_too_low'));
                    return false;
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
     * @throws ilTemplateException
     */
    public function insert(ilTemplate $a_tpl): void
    {
        global $DIC;
        $lng = $DIC['lng'];

        $tpl = new ilTemplate('tpl.prop_textsubsetcorrection_input.html', true, true, 'components/ILIAS/TestQuestionPool');
        $i = 0;
        foreach ($this->values as $value) {
            if (!$this->isHidePointsEnabled()) {
                $tpl->setCurrentBlock('points');
                $tpl->setVariable('POST_VAR', $this->getPostVar());
                $tpl->setVariable('ROW_NUMBER', $i);
                $tpl->setVariable('POINTS_ID', $this->getPostVar() . "[points][$i]");
                $tpl->setVariable('POINTS', ilLegacyFormElementsUtil::prepareFormOutput($value->getPoints()));
                $tpl->parseCurrentBlock();
            }

            $tpl->setCurrentBlock('row');
            $tpl->setVariable('ANSWER', ilLegacyFormElementsUtil::prepareFormOutput($value->getAnswertext()));
            $tpl->parseCurrentBlock();
            $i++;
        }

        $tpl->setVariable('ELEMENT_ID', $this->getPostVar());
        $tpl->setVariable('ANSWER_TEXT', $this->getTextInputLabel($lng));

        if (!$this->isHidePointsEnabled()) {
            $tpl->setVariable('POINTS_TEXT', $this->getPointsInputLabel($lng));
        }

        $a_tpl->setCurrentBlock('prop_generic');
        $a_tpl->setVariable('PROP_GENERIC', $tpl->get());
        $a_tpl->parseCurrentBlock();
    }
}

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

use ILIAS\TestQuestionPool\RequestDataCollector;

/**
 * Class ilAssClozeTestCombinationVariantsInputGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/Test(QuestionPool)
 */
class ilAssClozeTestCombinationVariantsInputGUI extends ilAnswerWizardInputGUI
{
    private RequestDataCollector $request_data_collector;

    public function __construct(string $a_title = '', string $a_postvar = '')
    {
        parent::__construct($a_title, $a_postvar);
        global $DIC;
        $this->request_data_collector = new RequestDataCollector($DIC->http(), $DIC->refinery(), $DIC->upload());
    }

    public function setValue($a_value): void
    {
        if (is_array($a_value) && is_array($a_value['points'])) {
            foreach ($a_value['points'] as $idx => $term) {
                $this->values[$idx]['points'] = $a_value['points'][$idx];
            }
        }
    }

    public function checkInput(): bool
    {
        global $DIC;
        $lng = $DIC->language();

        $values = $this->request_data_collector->retrieveFloatArrayOrIntArrayFromPost($this->getPostVar());

        $max = 0;
        foreach ($values['points'] ?? [] as $points) {
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

        return true;
    }

    /**
     * @throws ilTemplateException
     */
    public function insert(ilTemplate $a_tpl): void
    {
        $tpl = new ilTemplate('tpl.prop_gap_combi_answers_input.html', true, true, 'components/ILIAS/TestQuestionPool');
        $gaps = [];

        foreach ($this->values as $variant) {
            foreach ($variant['gaps'] as $gapIndex => $answer) {
                $gaps[$gapIndex] = $gapIndex;

                $tpl->setCurrentBlock('gap_answer');
                $tpl->setVariable('GAP_ANSWER', $answer);
                $tpl->parseCurrentBlock();
            }

            $tpl->setCurrentBlock('variant');
            $tpl->setVariable('POSTVAR', $this->getPostVar());
            $tpl->setVariable('POINTS', $variant['points']);
            $tpl->parseCurrentBlock();
        }

        foreach ($gaps as $gapIndex) {
            $tpl->setCurrentBlock('gap_header');
            $tpl->setVariable('GAP_HEADER', 'Gap ' . ($gapIndex + 1));
            $tpl->parseCurrentBlock();
        }

        $tpl->setVariable('POINTS_HEADER', 'Points');

        $a_tpl->setCurrentBlock('prop_generic');
        $a_tpl->setVariable('PROP_GENERIC', $tpl->get());
        $a_tpl->parseCurrentBlock();
    }
}

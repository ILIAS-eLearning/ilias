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
 * Class ilImagemapCorrectionsInputGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/TestQuestionPool
 */
class ilImagemapCorrectionsInputGUI extends ilImagemapFileInputGUI
{
    private readonly RequestDataCollector $request_data_collector;

    public function __construct(string $a_title = '', string $a_postvar = '')
    {
        parent::__construct($a_title, $a_postvar);
        $this->request_data_collector = new RequestDataCollector($this->http, $this->refinery, $this->upload_service);
    }

    public function setValueByArray(array $a_values): void
    {
        $this->setAreasByArray($a_values[$this->getPostVar()]['coords']);
    }

    public function setAreasByArray($a_areas): void
    {
        if (is_array($a_areas['points'])) {
            foreach ($this->areas as $idx => $name) {
                if (isset($a_areas['points_unchecked']) && $this->getPointsUncheckedFieldEnabled()) {
                    $this->areas[$idx]->setPointsUnchecked($a_areas['points_unchecked'][$idx]);
                } else {
                    $this->areas[$idx]->setPointsUnchecked(0);
                }

                $this->areas[$idx]->setPoints($a_areas['points'][$idx]);
            }
        }
    }

    public function checkInput(): bool
    {
        global $DIC;
        $lng = $DIC['lng'];

        $post_var = $this->request_data_collector->retrieveNestedArraysOfStrings($this->getPostVar(), 2);
        $post_var = is_array($post_var) ? ilArrayUtil::stripSlashesRecursive($post_var) : $post_var;

        $max = 0;
        if (is_array($post_var['coords']['points'])) {
            foreach ($post_var['coords']['points'] as $idx => $name) {
                if ($post_var['coords']['points'][$idx] === '' && $this->getRequired()) {
                    $this->setAlert($lng->txt('form_msg_area_missing_points'));
                    return false;
                }

                if (!is_numeric($post_var['coords']['points'][$idx])) {
                    $this->setAlert($lng->txt('form_msg_numeric_value_required'));
                    return false;
                }

                if ($post_var['coords']['points'][$idx] > 0) {
                    $max = (int) $post_var['coords']['points'][$idx];
                }
            }
        }

        if ($max === 0) {
            $this->setAlert($lng->txt('enter_enough_positive_points'));
            return false;
        }

        return true;
    }

    public function insert(ilTemplate $a_tpl): void
    {
        global $DIC;
        $lng = $DIC['lng'];

        $template = new ilTemplate('tpl.prop_imagemapquestioncorrection_input.html', true, true, 'components/ILIAS/TestQuestionPool');

        if ($this->getImage() !== '') {
            $template->setCurrentBlock('image');
            if (count($this->getAreas())) {
                $preview = new ilImagemapPreview($this->getImagePath() . $this->getValue());
                foreach ($this->getAreas() as $index => $area) {
                    $preview->addArea($index, $area->getArea(), $area->getCoords(), $area->getAnswertext(), '', '', true, $this->getLineColor());
                }
                $preview->createPreview();
                $imagepath = $this->getImagePathWeb() . $preview->getPreviewFilename($this->getImagePath(), $this->getValue()) . '?img=' . time();
                $template->setVariable('SRC_IMAGE', $imagepath);
            } else {
                $template->setVariable('SRC_IMAGE', $this->getImage());
            }
            $template->setVariable('ALT_IMAGE', $this->getAlt());
            $template->setVariable('POST_VAR_D', $this->getPostVar());
            $template->parseCurrentBlock();
        }

        if (is_array($this->getAreas()) && $this->getAreas()) {
            $counter = 0;
            foreach ($this->getAreas() as $area) {
                if ($area->getPoints() !== '') {
                    $template->setCurrentBlock('area_points_value');
                    $template->setVariable('VALUE_POINTS', $area->getPoints());
                    $template->parseCurrentBlock();
                }
                if ($this->getPointsUncheckedFieldEnabled()) {
                    if ($area->getPointsUnchecked() !== '') {
                        $template->setCurrentBlock('area_points_unchecked_value');
                        $template->setVariable('VALUE_POINTS_UNCHECKED', $area->getPointsUnchecked());
                        $template->parseCurrentBlock();
                    }

                    $template->setCurrentBlock('area_points_unchecked_field');
                    $template->parseCurrentBlock();
                }
                $template->setCurrentBlock('row');
                if ($area->getAnswertext() !== '') {
                    $template->setVariable('ANSWER_AREA', $area->getAnswertext());
                }
                $template->setVariable('POST_VAR_R', $this->getPostVar());
                $template->setVariable('TEXT_SHAPE', strtoupper($area->getArea()));
                $template->setVariable('VALUE_SHAPE', $area->getArea());
                $coords = preg_replace("/(\d+,\d+,)/", "\$1 ", $area->getCoords());
                $template->setVariable('VALUE_COORDINATES', $area->getCoords());
                $template->setVariable('TEXT_COORDINATES', $coords);
                $template->setVariable('COUNTER', $counter);
                $template->parseCurrentBlock();
                $counter++;
            }
            $template->setCurrentBlock('areas');
            $template->setVariable('TEXT_NAME', $lng->txt('ass_imap_hint'));
            if ($this->getPointsUncheckedFieldEnabled()) {
                $template->setVariable('TEXT_POINTS', $lng->txt('points_checked'));

                $template->setCurrentBlock('area_points_unchecked_head');
                $template->setVariable('TEXT_POINTS_UNCHECKED', $lng->txt('points_unchecked'));
                $template->parseCurrentBlock();
            } else {
                $template->setVariable('TEXT_POINTS', $lng->txt('points'));
            }
            $template->setVariable('TEXT_SHAPE', $lng->txt('shape'));
            $template->setVariable('TEXT_COORDINATES', $lng->txt('coordinates'));
            $template->setVariable('TEXT_COMMANDS', $lng->txt('actions'));
            $template->parseCurrentBlock();
        }

        $template->setVariable('POST_VAR', $this->getPostVar());
        $template->setVariable('ID', $this->getFieldId());
        $template->setVariable('TXT_BROWSE', $lng->txt('select_file'));
        $template->setVariable('TXT_MAX_SIZE', $lng->txt('file_notice') . ' ' .
            $this->getMaxFileSizeString());

        $a_tpl->setCurrentBlock('prop_generic');
        $a_tpl->setVariable('PROP_GENERIC', $template->get());
        $a_tpl->parseCurrentBlock();

        #global $DIC;
        #$tpl = $DIC['tpl'];
        #$tpl->addJavascript('assets/js/ServiceFormWizardInput.js');
        #$tpl->addJavascript(assets/js/imagemap.js');
    }
}

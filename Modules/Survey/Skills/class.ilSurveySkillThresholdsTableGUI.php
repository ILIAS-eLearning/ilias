<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

/**
 * TableGUI class for competence thresholds
 * @author Alexander Killing <killing@leifos.de>
 */
class ilSurveySkillThresholdsTableGUI extends ilTable2GUI
{
    /** @var int[] */
    protected array $question_ids;
    protected array $thresholds;
    protected int $tref_id = 0;
    protected ilObjSurvey $object;
    protected int $scale_sum;

    public function __construct(
        object $a_parent_obj,
        string $a_parent_cmd,
        ilObjSurvey $a_survey,
        int $a_base_skill_id,
        int $a_tref_id
    ) {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();
        
        $this->object = $a_survey;
        $this->base_skill_id = $a_base_skill_id;
        $this->tref_id = $a_tref_id;
        
        
        $this->determineMaxScalesAndQuestions();

        ilUtil::sendInfo(
            $lng->txt("survey_skill_nr_q") . ": " . count($this->question_ids) .
            ", " . $lng->txt("survey_skill_max_scale_points") . ": " . $this->scale_sum
        );
        
        $this->skill_thres = new ilSurveySkillThresholds($this->object);
        $this->thresholds = $this->skill_thres->getThresholds();
        
        parent::__construct($a_parent_obj, $a_parent_cmd);
        
        $this->skill_survey = new ilSurveySkill($a_survey);
        $this->setData($this->getLevels());
        $this->setTitle(ilBasicSkill::_lookupTitle($this->base_skill_id, $this->tref_id));
        
        $this->addColumn($this->lng->txt("survey_skill_level"));
        $this->addColumn($this->lng->txt("survey_up_to_x_points"));

        $this->setRowTemplate("tpl.svy_skill_threshold_row.html", "Modules/Survey");
        
        //		$this->addMultiCommand("saveThresholds", $lng->txt("save"));
        $this->addCommandButton("saveThresholds", $lng->txt("save"));
        $this->setFormAction($ilCtrl->getFormAction($this->parent_obj));
    }
    
    public function determineMaxScalesAndQuestions() : void
    {
        $ssk = new ilSurveySkill($this->object);
        $this->question_ids = $ssk->getQuestionsForSkill(
            $this->base_skill_id,
            $this->tref_id
        );
        $this->scale_sum = $ssk->determineMaxScale(
            $this->base_skill_id,
            $this->tref_id
        );
    }
    
    
    /**
     * Get levels
     *
     * @param
     * @return
     */
    public function getLevels()
    {
        $bs = new ilBasicSkill($this->base_skill_id);
        return $bs->getLevelData();
    }
    
    protected function fillRow($a_set)
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;
    
        $this->tpl->setVariable("LEVEL", $a_set["title"]);
        $this->tpl->setVariable("LEVEL_ID", $a_set["id"]);
        
        $tr = $this->thresholds[$a_set["id"]][$this->tref_id];
        if ((int) $tr != 0) {
            $this->tpl->setVariable("THRESHOLD", (int) $tr);
        } else {
            $this->tpl->setVariable("THRESHOLD", "");
        }
    }
}

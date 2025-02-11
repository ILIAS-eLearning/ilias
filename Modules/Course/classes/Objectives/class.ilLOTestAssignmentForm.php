<?php

declare(strict_types=0);
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
 * LO test assignment form creator
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @package ModulesCourse
 */
class ilLOTestAssignmentForm
{
    public const TEST_NEW = 1;
    public const TEST_ASSIGN = 2;

    private ilLanguage $lng;
    private ilCtrlInterface $ctrl;
    private ilTree $tree;

    private ilObject $container;
    private object $gui;
    private ilLOSettings $settings;
    private int $type = 0;

    /**
     * Constructor
     */
    public function __construct(object $gui, ilObject $a_container_obj, int $a_type)
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->tree = $DIC->repositoryTree();
        $this->gui = $gui;
        $this->container = $a_container_obj;
        $this->settings = ilLOSettings::getInstanceByObjId($this->getContainer()->getId());
        $this->type = $a_type;
    }

    public function getContainer(): ilObject
    {
        return $this->container;
    }

    public function getGUI(): object
    {
        return $this->gui;
    }

    public function getSettings(): ilLOSettings
    {
        return $this->settings;
    }

    public function getTestType(): int
    {
        return $this->type;
    }

    public function initForm(bool $a_as_multi_assignment = false): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->lng->txt('crs_loc_tst_assignment'));
        $form->setFormAction($this->ctrl->getFormAction($this->getGUI()));

        if ($a_as_multi_assignment) {
            $form->addCommandButton('saveMultiTestAssignment', $this->lng->txt('save'));
        } else {
            $form->addCommandButton('saveTest', $this->lng->txt('save'));
        }

        switch ($this->getTestType()) {
            case ilLOSettings::TYPE_TEST_INITIAL:
                $form->setTitle($this->lng->txt('crs_loc_settings_itest_tbl'));
                break;

            case ilLOSettings::TYPE_TEST_QUALIFIED:
                $form->setTitle($this->lng->txt('crs_loc_settings_qtest_tbl'));
                break;
        }

        $assignable = $this->getAssignableTests();

        $cr_mode = new ilRadioGroupInputGUI($this->lng->txt('crs_loc_form_assign_it'), 'mode');
        $cr_mode->setRequired(true);
        $cr_mode->setValue((string) self::TEST_NEW);

        $new = new ilRadioOption($this->lng->txt('crs_loc_form_tst_new'), (string) self::TEST_NEW);

        switch ($this->getTestType()) {
            case ilLOSettings::TYPE_TEST_INITIAL:
                $new->setInfo($this->lng->txt("crs_loc_form_tst_new_initial_info"));
                break;

            case ilLOSettings::TYPE_TEST_QUALIFIED:
                $new->setInfo($this->lng->txt("crs_loc_form_tst_new_qualified_info"));
                break;
        }

        // title
        $ti = new ilTextInputGUI($this->lng->txt("title"), "title");
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $new->addSubItem($ti);

        // description
        $ta = new ilTextAreaInputGUI($this->lng->txt("description"), "desc");
        $ta->setCols(40);
        $ta->setRows(2);
        $new->addSubItem($ta);

        // Question assignment type
        $this->lng->loadLanguageModule('assessment');
        $qst = new ilRadioGroupInputGUI($this->lng->txt('tst_question_set_type'), 'qtype');
        $qst->setRequired(true);

        $random = new ilRadioOption(
            $this->lng->txt('tst_question_set_type_random'),
            ilObjTest::QUESTION_SET_TYPE_RANDOM
        );
        $qst->addOption($random);

        $fixed = new ilRadioOption(
            $this->lng->txt('tst_question_set_type_fixed'),
            ilObjTest::QUESTION_SET_TYPE_FIXED
        );
        $qst->addOption($fixed);
        $new->addSubItem($qst);
        $cr_mode->addOption($new);

        // assign existing
        $existing = new ilRadioOption($this->lng->txt('crs_loc_form_assign'), (string) self::TEST_ASSIGN);

        switch ($this->getTestType()) {
            case ilLOSettings::TYPE_TEST_INITIAL:
                $existing->setInfo($this->lng->txt("crs_loc_form_assign_initial_info"));
                break;

            case ilLOSettings::TYPE_TEST_QUALIFIED:
                $existing->setInfo($this->lng->txt("crs_loc_form_assign_qualified_info"));
                break;
        }

        if ($assignable === []) {
            $existing->setDisabled(true);
        }
        $cr_mode->addOption($existing);

        $options = array();
        $options[''] = $this->lng->txt('select_one');
        foreach ($assignable as $tst_ref_id) {
            $tst_obj_id = ilObject::_lookupObjId($tst_ref_id);
            $options[$tst_ref_id] = ilObject::_lookupTitle($tst_obj_id);
        }
        $selectable = new ilSelectInputGUI($this->lng->txt('crs_loc_form_available_tsts'), 'tst');
        $selectable->setRequired(true);
        $selectable->setOptions($options);
        $existing->addSubItem($selectable);
        $form->addItem($cr_mode);
        if ($a_as_multi_assignment) {
            $assignments = ilLOTestAssignments::getInstance($this->getContainer()->getId());

            $objective_ids = ilCourseObjective::_getObjectiveIds($this->getContainer()->getId(), false);

            $options = array();
            $options[''] = $this->lng->txt('select_one');
            foreach ($objective_ids as $oid) {
                $already_assigned_tst = $assignments->getTestByObjective($oid, $this->getTestType());
                if (!$already_assigned_tst) {
                    $options[$oid] = ilCourseObjective::lookupObjectiveTitle($oid);
                }
            }

            $objective = new ilSelectInputGUI($this->lng->txt('crs_objectives'), 'objective');
            $objective->setRequired(true);
            $objective->setOptions($options);
            $form->addItem($objective);
        }
        return $form;
    }

    protected function getAssignableTests(): array
    {
        $assignments = ilLOTestAssignments::getInstance($this->getContainer()->getId());

        $tests = array();
        foreach ($this->tree->getChildsByType($this->getContainer()->getRefId(), 'tst') as $tree_node) {
            if (!in_array($tree_node['child'], $assignments->getTests())) {
                $tests[] = $tree_node['child'];
            }
        }
        return $tests;
    }
}

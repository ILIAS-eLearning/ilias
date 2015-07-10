<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionSkillAssignmentsGUI.php';

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/TestQuestionPool
 * 
 * @ilCtrl_Calls ilQuestionPoolSkillAdministrationGUI: ilAssQuestionSkillAssignmentsGUI
 */
class ilQuestionPoolSkillAdministrationGUI
{
	/**
	 * @var ILIAS
	 */
	private $ilias;

	/**
	 * @var ilCtrl
	 */
	private $ctrl;

	/**
	 * @var ilAccessHandler
	 */
	private $access;

	/**
	 * @var ilTabsGUI
	 */
	private $tabs;

	/**
	 * @var ilTemplate
	 */
	private $tpl;

	/**
	 * @var ilLanguage
	 */
	private $lng;

	/**
	 * @var ilDB
	 */
	private $db;

	/**
	 * @var ilPluginAdmin
	 */
	private $pluginAdmin;

	/**
	 * @var ilObjQuestionPool
	 */
	private $poolOBJ;
	
	
	public function __construct(ILIAS $ilias, ilCtrl $ctrl, ilAccessHandler $access, ilTabsGUI $tabs, ilTemplate $tpl, ilLanguage $lng, ilDB $db, ilPluginAdmin $pluginAdmin, ilObjQuestionPool $poolOBJ, $refId)
	{
		$this->ilias = $ilias;
		$this->ctrl = $ctrl;
		$this->access = $access;
		$this->tabs = $tabs;
		$this->tpl = $tpl;
		$this->lng = $lng;
		$this->db = $db;
		$this->pluginAdmin = $pluginAdmin;
		$this->poolOBJ = $poolOBJ;
		$this->refId = $refId;
	}

	private function isAccessDenied()
	{
		if( !$this->poolOBJ->isSkillServiceEnabled() )
		{
			return true;
		}

		if( !ilObjQuestionPool::isSkillManagementGloballyActivated() )
		{
			return true;
		}

		if( ! $this->access->checkAccess('write', '', $this->refId) )
		{
			return true;
		}

		return false;
	}

	public function manageTabs($activeSubTabId)
	{
		$link = $this->ctrl->getLinkTargetByClass(
			'ilAssQuestionSkillAssignmentsGUI', ilAssQuestionSkillAssignmentsGUI::CMD_SHOW_SKILL_QUEST_ASSIGNS
		);
		$this->tabs->addSubTab(
			'ilAssQuestionSkillAssignmentsGUI', $this->lng->txt('tst_skl_sub_tab_quest_assign'), $link

		);

		$this->tabs->activateTab('qpl_tab_competences');
		$this->tabs->activateSubTab($activeSubTabId);
	}

	public function executeCommand()
	{
		if( $this->isAccessDenied() )
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"), $this->ilias->error_obj->MESSAGE);
		}

		$nextClass = $this->ctrl->getNextClass();

		$this->manageTabs($nextClass);

		switch($nextClass)
		{
			case 'ilassquestionskillassignmentsgui':

				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionList.php';
				$questionList = new ilAssQuestionList($this->db, $this->lng, $this->pluginAdmin, $this->poolOBJ->getId());
				$questionList->setQuestionInstanceTypeFilter(ilAssQuestionList::QUESTION_INSTANCE_TYPE_ORIGINALS);
				$questionList->load();

				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionSkillAssignmentsGUI.php';
				$gui = new ilAssQuestionSkillAssignmentsGUI($this->ctrl, $this->tpl, $this->lng, $this->db);
				$gui->setParentObjId($this->poolOBJ->getId());
				$gui->setQuestionList($questionList);

				$this->ctrl->forwardCommand($gui);
				
				break;
		}
	}
}
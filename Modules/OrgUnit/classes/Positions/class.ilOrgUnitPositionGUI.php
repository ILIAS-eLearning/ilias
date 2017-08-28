<?php

use ILIAS\Modules\OrgUnit\ARHelper\BaseCommands;

/**
 * Class ilOrgUnitPositionGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilOrgUnitPositionGUI extends BaseCommands {

	const SUBTAB_SETTINGS = 'settings';
	const SUBTAB_PERMISSIONS = 'obj_orgunit_positions';
	const CMD_CONFIRM_DELETION = 'confirmDeletion';
	const CMD_CONFIRM_DELETION_AND_ASSIGN = 'confirmDeletionAndAssign';


	/**
	 * @return array
	 */
	protected function getPossibleNextClasses() {
		return array(
			ilOrgUnitDefaultPermissionGUI::class,
			ilOrgUnitUserAssignmentGUI::class,
		);
	}


	/**
	 * @return string
	 */
	protected function getActiveTabId() {
		return ilObjOrgUnitGUI::TAB_POSITIONS;
	}


	protected function index() {
		self::initAuthoritiesRenderer();
		$b = ilLinkButton::getInstance();
		$b->setUrl($this->ctrl()->getLinkTarget($this, self::CMD_ADD));
		$b->setCaption(self::CMD_ADD);
		$this->dic()->toolbar()->addButtonInstance($b);

		$table = new ilOrgUnitPositionTableGUI($this, self::CMD_INDEX);
		$this->setContent($table->getHTML());
	}


	protected function add() {
		$form = new ilOrgUnitPositionFormGUI($this, new ilOrgUnitPosition());
		$this->tpl()->setContent($form->getHTML());
	}


	protected function create() {
		$form = new ilOrgUnitPositionFormGUI($this, new ilOrgUnitPosition());
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_position_created'), true);
			$this->ctrl()->redirect($this, self::CMD_INDEX);
		}

		$this->tpl()->setContent($form->getHTML());
	}


	protected function edit() {
		$this->addSubTabs();
		$this->activeSubTab(self::SUBTAB_SETTINGS);
		$position = $this->getPositionFromRequest();
		$form = new ilOrgUnitPositionFormGUI($this, $position);
		$form->fillForm();
		$this->tpl()->setContent($form->getHTML());
	}


	protected function update() {
		$position = $this->getPositionFromRequest();
		$form = new ilOrgUnitPositionFormGUI($this, $position);
		$form->setValuesByPost();
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_position_udpated'), true);
			$this->ctrl()->redirect($this, self::CMD_INDEX);
		}

		$this->tpl()->setContent($form->getHTML());
	}


	protected function confirm() {
		$position = $this->getPositionFromRequest();
		if ($position->isCorePosition()) {
			$this->cancel();
		}

		$this->dic()->language()->loadLanguageModule('orgu');
		$user_string = $this->dic()->language()->txt("user_assignments") . ": ";
		$ilOrgUnitUserAssignmentQueries = ilOrgUnitUserAssignmentQueries::getInstance();

		$confirmation = new ilConfirmationGUI();
		$confirmation->setFormAction($this->ctrl()->getFormAction($this));
		$confirmation->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
		$confirmation->setConfirm($this->txt('confirm_deletion_and_assign'), self::CMD_CONFIRM_DELETION_AND_ASSIGN);
		$confirmation->addButton($this->txt('confirm_deletion_button'), self::CMD_CONFIRM_DELETION);
		$confirmation->setHeaderText($this->txt('msg_confirm_d_ua'));

		$confirmation->addHiddenItem(self::AR_ID, $position->getId());

		// Amount uf user-assignments
		$userIdsOfPosition = $ilOrgUnitUserAssignmentQueries->getUserIdsOfPosition($position->getId());
		$confirmation->addItem('users', true, $user_string . count($userIdsOfPosition));

		$this->tpl()->setContent($confirmation->getHTML());
	}


	protected function confirmDeletionAndAssign() {
		$position = $this->getPositionFromRequest();
		if ($position->isCorePosition()) {
			$this->cancel();
		}
		$ilOrgUnitUserAssignmentQueries = ilOrgUnitUserAssignmentQueries::getInstance();
		$assignments = $ilOrgUnitUserAssignmentQueries->getUserAssignmentsOfPosition($position->getId());

		$employee_position = ilOrgUnitPosition::getCorePosition(ilOrgUnitPosition::CORE_POSITION_EMPLOYEE);

		foreach ($assignments as $assignment) {
			ilOrgUnitUserAssignment::findOrCreateAssignment($assignment->getUserId(), $employee_position->getId(), $assignment->getOrguId());
			$assignment->delete();
		}

		$this->confirmDeletion();
	}


	protected function confirmDeletion() {
		$position = $this->getPositionFromRequest();
		if ($position->isCorePosition()) {
			$this->cancel();
		}
		self::initAuthoritiesRenderer();
		$this->dic()->language()->loadLanguageModule('orgu');
		$position_string = $this->dic()->language()->txt("position") . ": ";
		$authority_string = $this->dic()->language()->txt("authorities") . ": ";
		$user_string = $this->dic()->language()->txt("user_assignments") . ": ";
		$ilOrgUnitUserAssignmentQueries = ilOrgUnitUserAssignmentQueries::getInstance();

		$confirmation = new ilConfirmationGUI();
		$confirmation->setFormAction($this->ctrl()->getFormAction($this));
		$confirmation->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
		$confirmation->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE);
		$confirmation->setHeaderText($this->txt('msg_confirm_deletion'));
		$confirmation->addItem(self::AR_ID, $position->getId(), $position_string
		                                                        . $position->getTitle());

		// Authorities
		$authority_string .= implode(", ", $position->getAuthorities());
		$confirmation->addItem('authorities', true, $authority_string);

		// Amount uf user-assignments
		$userIdsOfPosition = $ilOrgUnitUserAssignmentQueries->getUserIdsOfPosition($position->getId());
		$count = count($userIdsOfPosition);
		if ($count) {
			$confirmation->addItem('users', true, $user_string . $count);
		}

		$this->tpl()->setContent($confirmation->getHTML());
	}


	protected function delete() {
		$position = $this->getPositionFromRequest();
		$position->deleteWithAllDependencies();
		ilUtil::sendSuccess($this->txt('msg_deleted'), true);
		$this->ctrl()->redirect($this, self::CMD_INDEX);
	}


	protected function cancel() {
		$this->ctrl()->redirect($this, self::CMD_INDEX);
	}


	/**
	 * @return mixed
	 */
	protected function getARIdFromRequest() {
		$get = $this->dic()->http()->request()->getQueryParams()[self::AR_ID];
		$post = $this->dic()->http()->request()->getParsedBody()[self::AR_ID];

		return $post ? $post : $get;
	}


	/**
	 * @return \ilOrgUnitPosition
	 */
	protected function getPositionFromRequest() {
		return ilOrgUnitPosition::find($this->getARIdFromRequest());
	}


	public static function initAuthoritiesRenderer() {
		$lang = $GLOBALS['DIC']->language();
		$lang->loadLanguageModule('orgu');
		$lang_keys = array(
			'in',
			'scope_' . ilOrgUnitAuthority::SCOPE_SAME_ORGU,
			'scope_' . ilOrgUnitAuthority::SCOPE_SUBSEQUENT_ORGUS,
			'over_' . ilOrgUnitAuthority::OVER_EVERYONE,
		);
		$t = array();
		foreach ($lang_keys as $key) {
			$t[$key] = $lang->txt($key);
		}

		ilOrgUnitAuthority::replaceNameRenderer(function ($id) use ($t) {
			/**
			 * @var $ilOrgUnitAuthority ilOrgUnitAuthority
			 */
			$ilOrgUnitAuthority = ilOrgUnitAuthority::find($id);

			switch ($ilOrgUnitAuthority->getScope()) {
				case ilOrgUnitAuthority::SCOPE_SAME_ORGU:
				case ilOrgUnitAuthority::SCOPE_ALL_ORGUS:
				case ilOrgUnitAuthority::SCOPE_SUBSEQUENT_ORGUS:
				default:
					$in_txt = $t["scope_" . $ilOrgUnitAuthority->getScope()];
					break;
			}

			switch ($ilOrgUnitAuthority->getOver()) {
				case ilOrgUnitAuthority::OVER_EVERYONE:
					$over_txt = $t["over_" . $ilOrgUnitAuthority->getOver()];
					break;
				default:
					$over_txt = ilOrgUnitPosition::findOrGetInstance($ilOrgUnitAuthority->getOver())
					                             ->getTitle();
					break;
			}

			return " " . $t["over"] . " " . $over_txt . " " . $t["in"] . " " . $in_txt;
		});
	}


	public function addSubTabs() {
		$this->ctrl()->saveParameter($this, 'arid');
		$this->ctrl()->saveParameterByClass(ilOrgUnitDefaultPermissionGUI::class, 'arid');
		$this->pushSubTab(self::SUBTAB_SETTINGS, $this->ctrl()
		                                              ->getLinkTarget($this, self::CMD_INDEX));
		$this->pushSubTab(self::SUBTAB_PERMISSIONS, $this->ctrl()
		                                                 ->getLinkTargetByClass(ilOrgUnitDefaultPermissionGUI::class, self::CMD_INDEX));
	}
}

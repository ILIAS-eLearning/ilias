<?php

use ILIAS\Modules\OrgUnit\ARHelper\BaseCommands;

/**
 * Class ilOrgUnitPermissionGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrlStructureCalls(
 *		parents={
 *			"ilOrgUnitPositionGUI",
 *		}
 * )
 */
class ilOrgUnitPermissionGUI extends BaseCommands
{
    protected function index()
    {
        $table = new ilOrgUnitPermissionTableGUI($this, self::CMD_INDEX, $this->getParentRefId());
    }
}

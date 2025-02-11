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

declare(strict_types=1);

/**
 * Class ilOrgUnitExportGUI
 * @author: Oskar Truffer <ot@studer-raimann.ch>
 * @author: Martin Studer <ms@studer-raimann.ch>
 */
class ilOrgUnitExportGUI extends ilExportGUI
{
    protected ilToolbarGUI $toolbar;
    protected ilLanguage $lng;
    protected ilCtrlInterface $ctrl;
    protected ilObject $ilObjOrgUnit;
    protected \ILIAS\UI\Factory $ui_factory;

    public function __construct(ilObjOrgUnitGUI $a_parent_gui, /*null|ilObject|ilObjOrgUnit*/ ?ilObject $a_main_obj = null)
    {
        parent::__construct($a_parent_gui, $a_main_obj);

        global $DIC;
        $ilToolbar = $DIC->toolbar();
        $lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();

        $this->toolbar = $DIC->toolbar();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->ui_factory = $DIC['ui.factory'];

        $this->ilObjOrgUnit = $a_parent_gui->getObject();

        if ($this->ilObjOrgUnit->getRefId() === ilObjOrgUnit::getRootOrgRefId()) {
            //Simple XML and Simple XLS Export should only be available in the root orgunit folder as it always exports the whole tree
            $this->extendExportGUI();
        }
    }

    public function listExportFiles(): void
    {
        if ($this->ilObjOrgUnit->getRefId() != ilObjOrgUnit::getRootOrgRefId()) {
            parent::listExportFiles();
        }
    }

    private function extendExportGUI(): void
    {
        $this->toolbar->addComponent(
            $this->ui_factory->link()->standard(
                $this->lng->txt('simple_xml'),
                $this->ctrl->getLinkTarget($this, "simpleExport")
            )
        );
        $this->toolbar->addComponent(
            $this->ui_factory->link()->standard(
                $this->lng->txt('simple_xls'),
                $this->ctrl->getLinkTarget($this, "simpleExportExcel")
            )
        );
    }

    public function simpleExport(): void
    {
        $ilOrgUnitExporter = new ilOrgUnitExporter();
        $ilOrgUnitExporter->sendAndCreateSimpleExportFile();
    }

    public function simpleExportExcel(): void
    {
        $ilOrgUnitExporter = new ilOrgUnitExporter();
        $ilOrgUnitExporter->simpleExportExcel(ilObjOrgUnit::getRootOrgRefId());
    }
}

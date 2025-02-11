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

use ILIAS\DI\UIServices;
use ILIAS\Badge\Tile;

/**
 * TableGUI class for badge listing
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
class ilBadgeTableGUI extends ilTable2GUI
{
    protected string $parent_type;
    protected array $filter = [];
    private readonly Tile $tile;
    private readonly UIServices $ui;

    public function __construct(
        object $a_parent_obj,
        string $a_parent_cmd = "",
        int $a_parent_obj_id = 0,
        protected bool $has_write = false
    ) {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->ui = $DIC->ui();
        $this->tile = new Tile($DIC);
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->setId("bdgbdg");
        $this->parent_type = ilObject::_lookupType($a_parent_obj_id);

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setLimit(9999);

        $this->setTitle($lng->txt("obj_bdga"));

        if ($this->has_write) {
            $this->addColumn("", "", 1);
        }

        $this->addColumn($lng->txt("title"), "title");
        $this->addColumn($lng->txt("type"), "type");
        $this->addColumn($lng->txt("active"), "active");

        if ($this->has_write) {
            $this->addColumn($lng->txt("action"), "");

            $lng->loadLanguageModule("content");
            $this->addMultiCommand("copyBadges", $lng->txt("cont_copy_to_clipboard"));
            $this->addMultiCommand("activateBadges", $lng->txt("activate"));
            $this->addMultiCommand("deactivateBadges", $lng->txt("deactivate"));
            $this->addMultiCommand("confirmDeleteBadges", $lng->txt("delete"));
            $this->setSelectAllCheckbox("id");
        }

        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.badge_row.html", "Services/Badge");
        $this->setDefaultOrderField("title");

        $this->setFilterCommand("applyBadgeFilter");
        $this->setResetCommand("resetBadgeFilter");

        $this->initFilter();

        $this->getItems($a_parent_obj_id);
    }

    public function initFilter(): void
    {
        $lng = $this->lng;

        $title = $this->addFilterItemByMetaType("title", self::FILTER_TEXT, false, $lng->txt("title"));
        $this->filter["title"] = $title->getValue();

        $handler = ilBadgeHandler::getInstance();
        $valid_types = $handler->getAvailableTypesForObjType($this->parent_type);
        if ($valid_types &&
            count($valid_types) > 1) {
            $lng->loadLanguageModule("search");

            $options = array("" => $lng->txt("search_any"));
            foreach ($valid_types as $id => $type) {
                $options[$id] = ilBadge::getExtendedTypeCaption($type);
            }
            asort($options);

            $type = $this->addFilterItemByMetaType("type", self::FILTER_SELECT, false, $lng->txt("type"));
            $type->setOptions($options);
            $this->filter["type"] = $type->getValue();
        }
    }

    public function getItems(int $a_parent_obj_id): void
    {
        $data = array();

        foreach (ilBadge::getInstancesByParentId($a_parent_obj_id, $this->filter) as $badge) {
            $data[] = array(
                "id" => $badge->getId(),
                "title" => $badge->getTitle(),
                "active" => $badge->isActive(),
                "type" => ($this->parent_type !== "bdga")
                    ? ilBadge::getExtendedTypeCaption($badge->getTypeInstance())
                    : $badge->getTypeInstance()->getCaption(),
                "manual" => (!$badge->getTypeInstance() instanceof ilBadgeAuto),
                "renderer" => fn() => $this->tile->asTitle($this->tile->modalContent($badge)),
            );
        }

        $this->setData($data);
    }

    protected function fillRow(array $a_set): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        if ($this->has_write) {
            $this->tpl->setVariable("VAL_ID", $a_set["id"]);
        }

        $this->tpl->setVariable("PREVIEW", $this->ui->renderer()->render($a_set["renderer"]()));
        $this->tpl->setVariable("TXT_TYPE", $a_set["type"]);
        $this->tpl->setVariable("TXT_ACTIVE", $a_set["active"]
            ? $lng->txt("yes")
            : $lng->txt("no"));

        if ($this->has_write) {
            $buttons = [];

            if ($a_set["manual"] && $a_set["active"]) {
                $ilCtrl->setParameter($this->getParentObject(), "bid", $a_set["id"]);
                $ilCtrl->setParameter($this->getParentObject(), "tgt", "bdgl");
                $url = $ilCtrl->getLinkTarget($this->getParentObject(), "awardBadgeUserSelection");
                $ilCtrl->setParameter($this->getParentObject(), "bid", "");
                $ilCtrl->setParameter($this->getParentObject(), "tgt", "");

                $buttons[] = $this->ui->factory()->button()->shy($lng->txt("badge_award_badge"), $url);
            }

            $ilCtrl->setParameter($this->getParentObject(), "bid", $a_set["id"]);
            $url = $ilCtrl->getLinkTarget($this->getParentObject(), "editBadge");
            $ilCtrl->setParameter($this->getParentObject(), "bid", "");

            $buttons[] = $this->ui->factory()->button()->shy($lng->txt("edit"), $url);
            $actions = $this->ui->factory()->dropdown()->standard($buttons)->withLabel($lng->txt("actions"));

            $this->tpl->setVariable("ACTIONS", $this->ui->renderer()->render($actions));
        }
    }
}

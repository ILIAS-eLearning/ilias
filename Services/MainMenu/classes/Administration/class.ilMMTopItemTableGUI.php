<?php

declare(strict_types=1);

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

use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;

/**
 * Class ilMMTopItemTableGUI
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilMMTopItemTableGUI extends ilTable2GUI
{
    use Hasher;

    private ilMMItemRepository $item_repository;

    private ilObjMainMenuAccess $access;

    /**
     * ilMMTopItemTableGUI constructor.
     * @param ilMMTopItemGUI      $a_parent_obj
     * @param ilMMItemRepository  $item_repository
     * @param ilObjMainMenuAccess $access
     */
    public function __construct(ilMMTopItemGUI $a_parent_obj, ilMMItemRepository $item_repository, ilObjMainMenuAccess $access)
    {
        $this->access = $access;
        $this->setId(self::class);
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        parent::__construct($a_parent_obj);
        $this->item_repository = $item_repository;
        $this->lng = $this->parent_obj->lng;
        $this->setData($this->resolveData());
        $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
        if ($this->access->hasUserPermissionTo('write')) {
            $this->addCommandButton(ilMMTopItemGUI::CMD_SAVE_TABLE, $this->lng->txt('button_save'));
        }
        $this->initColumns();
        $this->setRowTemplate('tpl.top_items.html', 'Services/MainMenu');
    }

    private function initColumns(): void
    {
        $this->addColumn($this->lng->txt('topitem_position'), '', '30px');
        $this->addColumn($this->lng->txt('topitem_title'));
        $this->addColumn($this->lng->txt('topitem_active'));
        $this->addColumn($this->lng->txt('topitem_subentries'));
        $this->addColumn($this->lng->txt('topitem_css_id'));
        $this->addColumn($this->lng->txt('topitem_type'));
        $this->addColumn($this->lng->txt('topitem_provider'));
        $this->addColumn($this->lng->txt('topitem_actions'));
    }

    /**
     * @inheritDoc
     */
    protected function fillRow(array $a_set): void
    {
        static $position;
        $position++;
        global $DIC;
        $renderer = $DIC->ui()->renderer();
        $factory = $DIC->ui()->factory();

        $item_facade = $this->item_repository->repository()->getItemFacade($DIC->globalScreen()->identification()->fromSerializedIdentification($a_set['identification']));

        $this->tpl->setVariable('IDENTIFIER', ilMMAbstractItemGUI::IDENTIFIER);
        $this->tpl->setVariable('ID', $this->hash($item_facade->getId()));
        $this->tpl->setVariable('NATIVE_ID', $item_facade->getId());
        $this->tpl->setVariable('TITLE', $item_facade->getDefaultTitle());
        $this->tpl->setVariable('SUBENTRIES', $item_facade->getAmountOfChildren());
        $this->tpl->setVariable('TYPE', $item_facade->getTypeForPresentation());
        $this->tpl->setVariable('CSS_ID', "mm_" . $item_facade->identification()->getInternalIdentifier());
        $this->tpl->setVariable('POSITION', $position * 10);
        $this->tpl->setVariable('NATIVE_POSITION', $item_facade->getRawItem()->getPosition());
        $this->tpl->setVariable('SAVED_POSITION', $item_facade->getFilteredItem()->getPosition());
        if ($item_facade->isActivated()) {
            $this->tpl->touchBlock('is_active');
        }
        if ($item_facade->isAlwaysAvailable() || !$item_facade->isAvailable()) {
            $this->tpl->touchBlock('is_active_blocked');
        }
        $this->tpl->setVariable('PROVIDER', $item_facade->getProviderNameForPresentation());

        $this->ctrl->setParameterByClass(ilMMTopItemGUI::class, ilMMAbstractItemGUI::IDENTIFIER, $this->hash($a_set['identification']));
        $this->ctrl->setParameterByClass(ilMMItemTranslationGUI::class, ilMMItemTranslationGUI::IDENTIFIER, $this->hash($a_set['identification']));

        if ($this->access->hasUserPermissionTo('write')) {
            $items = [];
            if ($item_facade->isEditable()) {
                $items[] = $factory->button()->shy($this->lng->txt(ilMMTopItemGUI::CMD_EDIT), $this->ctrl->getLinkTargetByClass(ilMMTopItemGUI::class, ilMMTopItemGUI::CMD_EDIT));
                $items[] = $factory->button()
                                   ->shy($this->lng->txt(ilMMTopItemGUI::CMD_TRANSLATE), $this->ctrl->getLinkTargetByClass(ilMMItemTranslationGUI::class, ilMMItemTranslationGUI::CMD_DEFAULT));
            }

            $rendered_modal = "";
            if ($item_facade->isDeletable()) {
                $ditem = $factory->modal()->interruptiveItem()->standard($this->hash($a_set['identification']), $item_facade->getDefaultTitle());
                $action = $this->ctrl->getFormActionByClass(ilMMSubItemGUI::class, ilMMSubItemGUI::CMD_DELETE);
                $m = $factory->modal()
                                  ->interruptive($this->lng->txt(ilMMTopItemGUI::CMD_DELETE), $this->lng->txt(ilMMTopItemGUI::CMD_CONFIRM_DELETE), $action)
                                  ->withAffectedItems([$ditem]);

                $items[] = $shy = $factory->button()->shy($this->lng->txt(ilMMTopItemGUI::CMD_DELETE), "")->withOnClick($m->getShowSignal());
                // $items[] = $factory->button()->shy($this->lng->txt(ilMMSubItemGUI::CMD_DELETE), $this->ctrl->getLinkTargetByClass(ilMMSubItemGUI::class, ilMMSubItemGUI::CMD_CONFIRM_DELETE));
                $rendered_modal = $renderer->render([$m]);
            }
            if ($item_facade->isInterchangeable()) {
                $items[] = $factory->button()->shy($this->lng->txt(ilMMTopItemGUI::CMD_MOVE . '_to_item'), $this->ctrl->getLinkTargetByClass(ilMMTopItemGUI::class, ilMMTopItemGUI::CMD_SELECT_PARENT));
            }
            $this->tpl->setVariable('ACTIONS', $rendered_modal . $renderer->render([$factory->dropdown()->standard($items)->withLabel($this->lng->txt('sub_actions'))]));
        }
    }

    /**
     * @return array
     */
    private function resolveData(): array
    {
        return $this->item_repository->getTopItems();
    }
}

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

use ILIAS\UI\Implementation\Component\Button\Shy;

class ilDclFieldListGUI
{
    protected \ILIAS\UI\Factory $ui_factory;
    protected \ILIAS\UI\Renderer $renderer;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilToolbarGUI $toolbar;
    protected ilGlobalTemplateInterface $tpl;
    protected ILIAS\HTTP\Services $http;
    protected ILIAS\Refinery\Factory $refinery;
    protected ilDclTable $table;
    protected ilDclTableListGUI $parent_obj;

    public function __construct(ilDclTableListGUI $a_parent_obj)
    {
        global $DIC;

        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->parent_obj = $a_parent_obj;
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('dcl');
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();
        $this->ui_factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->table = ilDclCache::getTableCache($this->http->wrapper()->query()->retrieve('table_id', $this->refinery->kindlyTo()->int()));

        $DIC->help()->setScreenId('dcl_fields');

        $this->ctrl->saveParameterByClass(ilDclTableEditGUI::class, 'table_id');
        $DIC['ilLocator']->addItem($this->table->getTitle(), $this->ctrl->getLinkTargetByClass(ilDclTableEditGUI::class, 'edit'));
        $this->tpl->setLocator();

        if (!$this->checkAccess()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(ilDclRecordListGUI::class, 'listRecords');
        }
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd('listFields');
        $this->$cmd();
    }

    public function saveOrder(): void
    {
        $order = $this->http->wrapper()->post()->retrieve(
            'order',
            $this->refinery->kindlyTo()->dictOf($this->refinery->kindlyTo()->string())
        );
        $val = 10;
        foreach ($order as $field_id) {
            $order[$field_id] = $val;
            $val += 10;
        }

        foreach ($this->table->getFields() as $field) {
            $field->setOrder($order[$field->getId()]);
            $field->doUpdate();
        }
    }

    public function addToExport(): void
    {
        $field = $this->table->getField(
            $this->http->wrapper()->query()->retrieve('field_id', $this->refinery->kindlyTo()->string())
        );
        $field->setExportable(true);
        $field->doUpdate();
        $this->listFields();
    }

    public function RemoveFromExport(): void
    {
        $field = $this->table->getField(
            $this->http->wrapper()->query()->retrieve('field_id', $this->refinery->kindlyTo()->string())
        );
        $field->setExportable(false);
        $field->doUpdate();
        $this->listFields();
    }

    public function listFields(): void
    {
        $add_new = $this->ui_factory->button()->primary(
            $this->lng->txt('dcl_add_new_field'),
            $this->ctrl->getLinkTargetByClass(ilDclFieldEditGUI::class, 'create')
        );
        $this->toolbar->addStickyItem($add_new);

        $switcher = new ilDclSwitcher($this->toolbar, $this->ui_factory, $this->ctrl, $this->lng);
        $switcher->addTableSwitcherToToolbar(
            $this->parent_obj->getDataCollectionObject()->getTables(),
            self::class,
            'listFields'
        );

        $this->tpl->setContent(
            $this->renderer->render(
                $this->ui_factory->panel()->listing()->standard(
                    sprintf($this->lng->txt('dcl_fields_of_X'), $this->table->getTitle()),
                    [$this->ui_factory->item()->group('', $this->getItems())]
                )
            )
        );
    }

    protected function getItems(): array
    {
        $items = [];

        foreach ($this->table->getFields() as $field) {
            $field_id = $field->getId();
            $endpoint = $this->ctrl->getLinkTargetByClass(ilDclFieldListGUI::class, 'saveOrder');
            $this->ctrl->setParameterByClass(ilObjDataCollectionGUI::class, 'field_id', $field_id);
            $item = $this->ui_factory->item()->standard($field->getTitle())
                ->withMainAction(
                    $this->ui_factory->button()->standard(
                        $this->renderer->render($this->ui_factory->symbol()->glyph()->sort()),
                        ''
                    )->withOnLoadCode(
                        static function (string $id) use ($field_id, $endpoint): string {
                            return "dcl.addSorting($id, '$field_id', '$endpoint')";
                        }
                    )
                )
                ->withDescription($field->getDescription())
                ->withProperties($this->getProperties($field))
                ->withActions($this->ui_factory->dropdown()->standard($this->getActions($field)))
            ;
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    protected function getProperties(ilDclBaseFieldModel $field): array
    {
        $properties = [];
        $properties[$this->lng->txt('dcl_in_export')] = $this->lng->txt($field->getExportable() ? 'yes' : 'no');
        $properties[$this->lng->txt('dcl_field_datatype')] = $field->getPresentationTitle();

        return $properties;
    }

    /**
     * @return Shy[]
     */
    protected function getActions(ilDclBaseFieldModel $field): array
    {
        $actions = [];
        if ($field->getExportable()) {
            $actions[] = $this->ui_factory->button()->shy(
                $this->lng->txt('remove_from_export'),
                $this->ctrl->getLinkTargetByClass(ilDclFieldListGUI::class, 'removeFromExport')
            );
        } else {
            $actions[] = $this->ui_factory->button()->shy(
                $this->lng->txt('add_to_export'),
                $this->ctrl->getLinkTargetByClass(ilDclFieldListGUI::class, 'addToExport')
            );
        }

        if (!$field->isStandardField()) {
            $actions[] = $this->ui_factory->button()->shy(
                $this->lng->txt('edit'),
                $this->ctrl->getLinkTargetByClass(ilDclFieldEditGUI::class, 'edit')
            );
            $actions[] = $this->ui_factory->button()->shy(
                $this->lng->txt('delete'),
                $this->ctrl->getLinkTargetByClass(ilDclFieldEditGUI::class, 'confirmDelete')
            );
        }

        return $actions;
    }

    protected function checkAccess(): bool
    {
        return ilObjDataCollectionAccess::hasAccessToEditTable(
            $this->parent_obj->getDataCollectionObject()->getRefId(),
            $this->table->getId()
        );
    }
}

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

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer;

/**
 * TableGUI class for LTI consumer listing
 * @author Jesús López <lopez@leifos.com>
 */
class ilUserRoleStartingPointTableGUI extends ilTable2GUI
{
    private const TABLE_POSITION_USER_CHOOSES = -1;
    private const TABLE_POSITION_DEFAULT = 9999;

    public function __construct(
        object $parent_obj,
        private ilStartingPoint $starting_point,
        private ilRbacReview $rbac_review,
        private UIFactory $ui_factory,
        private Renderer $ui_renderer,
    ) {
        $this->setId('usrrolesp');

        parent::__construct($parent_obj);

        $this->getItems();

        $this->setLimit(9999);
        $this->setTitle($this->lng->txt('user_role_starting_point'));

        $this->addColumn($this->lng->txt('user_order'));
        $this->addColumn($this->lng->txt('criteria'));
        $this->addColumn($this->lng->txt('starting_page'));
        $this->addColumn($this->lng->txt('actions'));
        $this->setFormAction($this->ctrl->getFormAction($parent_obj));
        $this->setRowTemplate('tpl.user_role_starting_point_row.html', 'Services/User');
        $this->addCommandButton('saveOrder', $this->lng->txt('save_order'));

        $this->setExternalSorting(true);
    }

    /**
     * Get data
     */
    public function getItems(): void
    {
        $dc = new ilObjectDataCache();

        $valid_points = ilUserUtil::getPossibleStartingPoints();

        $status = (ilUserUtil::hasPersonalStartingPoint() ? $this->lng->txt('yes') : $this->lng->txt('no'));

        $starting_points = [];
        $starting_points[] = [
            'id' => 'user',
            'criteria' => $this->lng->txt('user_chooses_starting_page'),
            'starting_page' => $status,
            'starting_position' => self::TABLE_POSITION_USER_CHOOSES
        ];

        $available_starting_points = $this->starting_point->getStartingPoints();

        foreach ($available_starting_points as $available_starting_point) {
            $starting_point = $available_starting_point->getStartingPoint();
            $position = $available_starting_point->getPosition();
            $sp_text = $valid_points[$starting_point] ?? '';

            if ($starting_point === ilUserUtil::START_REPOSITORY_OBJ
                && $available_starting_point->getStartingObject() !== null) {
                $starting_object = $available_starting_point->getStartingObject();
                $object_id = ilObject::_lookupObjId($starting_object);
                $type = $dc->lookupType($object_id);
                $title = $dc->lookupTitle($object_id);
                $sp_text = $this->lng->txt('obj_' . $type)
                    . ' <i>"' . $title . '"</i> '
                    . '[' . (string) $starting_object . ']';
            }

            if ($available_starting_point->isRoleBasedStartingPoint()) {
                $options = unserialize($available_starting_point->getRuleOptions(), ['allowed_classes' => false]);

                $role_obj = ilObjectFactory::getInstanceByObjId((int) $options['role_id'], false);
                if (!($role_obj instanceof \ilObjRole)) {
                    continue;
                }

                $starting_points[] = [
                    'id' => $available_starting_point->getId(),
                    'criteria' => $role_obj->getTitle(),
                    'starting_page' => $sp_text,
                    'starting_position' => $position,
                    'role_id' => $role_obj->getId()
                ];
            }
        }

        $default_sp = ilUserUtil::getStartingPoint();
        $starting_point = $valid_points[$default_sp];
        if ($default_sp == ilUserUtil::START_REPOSITORY_OBJ) {
            $reference_id = ilUserUtil::getStartingObject();

            $object_id = ilObject::_lookupObjId($reference_id);
            $type = $dc->lookupType($object_id);
            $title = $dc->lookupTitle($object_id);
            $starting_point = $this->lng->txt('obj_' . $type) . ' ' . '<i>"' . $title . '" (' . $reference_id . ')</i>';
        }

        $starting_points[] = [
            'id' => 'default',
            'criteria' => $this->lng->txt('default'),
            'starting_page' => $starting_point,
            'starting_position' => self::TABLE_POSITION_DEFAULT
        ];

        $sorted_starting_points = $this->starting_point->reArrangePositions(
            ilArrayUtil::sortArray($starting_points, 'starting_position', 'asc', true)
        );

        $this->setData($sorted_starting_points);
    }

    /**
     *
     * @param array<string|int> $row_data
     */
    protected function fillRow(array $row_data): void
    {
        $id = (string) $row_data['id'];
        $role_id = $row_data['role_id'] ?? null;
        $this->ctrl->setParameter($this->getParentObject(), 'spid', $id);

        if ($this->isSortingHidden($id)) {
            $this->tpl->setVariable('HIDDEN', 'hidden');
        } else {
            $this->tpl->setVariable('VAL_ID', 'position[' . $id . ']');
            $this->tpl->setVariable('VAL_POS', $row_data['starting_position']);
        }

        $this->tpl->setVariable(
            'TXT_TITLE',
            $this->getTitleForCriterium(
                $id,
                $role_id,
                $row_data['criteria']
            )
        );

        $actions = $this->getActions($id, $role_id);

        $this->tpl->setVariable('TXT_PAGE', $row_data['starting_page']);

        $list = $this->ui_factory->dropdown()->standard($actions)->withLabel(
            $this->lng->txt('actions')
        );

        $this->tpl->setVariable('ACTION', $this->ui_renderer->render($list));
    }

    /**
     * @return array<strings>
     */
    private function getActions(string $id, ?int $role_id): array
    {
        $actions = [];

        $edit_link = $this->getEditLink($id, $role_id);

        $actions[] = $this->ui_factory->link()->standard(
            $this->lng->txt('edit'),
            $edit_link
        );

        if (!in_array($id, ['0', 'default', 'user'])) {
            $delete_url = $this->ctrl->getLinkTarget(
                $this->getParentObject(),
                'confirmDeleteStartingPoint'
            );
            $actions[] = $this->ui_factory->link()->standard(
                $this->lng->txt('delete'),
                $delete_url
            );
        }

        return $actions;
    }

    private function getEditLink(string $id, ?int $role_id): string
    {
        $cmd = 'initRoleStartingPointForm';
        $rolid = $role_id;
        if ($id === '0' || $id === 'user') {
            $cmd = 'initUserStartingPointForm';
            $rolid = 'user';
        } elseif ($id === 'default') {
            $rolid = 'default';
        }

        $this->ctrl->setParameter($this->getParentObject(), 'rolid', $rolid);
        return $this->ctrl->getLinkTargetByClass(
            get_class($this->getParentObject()),
            $cmd
        );
    }

    private function isSortingHidden(string $id): bool
    {
        if (in_array($id, ['0', 'default', 'user'])) {
            return true;
        }
        return false;
    }

    private function getTitleForCriterium(
        string $id,
        ?int $role_id,
        string $criterium
    ): string {
        if (in_array($id, ['0', 'default', 'user'])) {
            return $criterium;
        }

        $parent_title = '';
        if ($role_id !== null && ilObject::_lookupType($role_id) === 'role') {
            $ref_id = $this->rbac_review->getObjectReferenceOfRole($role_id);
            if ($ref_id !== ROLE_FOLDER_ID) {
                $parent_title = ' (' . ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id)) . ')';
            }
        }
        return $this->lng->txt('has_role') . ': '
            . ilObjRole::_getTranslation($criterium)
            . $parent_title;
    }
}

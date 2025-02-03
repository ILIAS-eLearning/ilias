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
 * Class ilCalendarRecurrenceTableGUI
 */
class ilCalendarRecurrenceTableGUI extends ilTable2GUI
{
    private const REC_TABLE_ID = 'recurrence_table';

    private ilCalendarEntry $entry;

    public function __construct(
        ilCalendarEntry $entry,
        object $a_parent_obj,
        string $a_parent_cmd = "",
        string $a_template_context = ""
    ) {
        $this->setId(self::REC_TABLE_ID);
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->entry = $entry;
    }

    public function init(): void
    {
        $this->setFormAction($this->ctrl->getFormAction($this->getParentObject(), $this->getParentCmd()));
        $this->setFormName('appointments');
        $this->addColumn('', 'f', "1");
        $this->addColumn($this->lng->txt('title'));
        $this->setRowTemplate("tpl.show_recurrence_row.html", 'components/ILIAS/Calendar');

        $this->setSelectAllCheckbox('recurrence_ids');
        $this->addMultiCommand(
            'deleteExclude',
            $this->lng->txt('delete')
        );
        $this->addCommandButton(
            'delete',
            $this->lng->txt('cal_delete_recurrence_rule')
        );
        $this->addCommandButton(
            'cancel',
            $this->lng->txt('cancel')
        );
        $this->setShowRowsSelector(false);
    }

    protected function fillRow(array $a_set): void
    {
        $this->tpl->setVariable('VAL_ID', $a_set['id']);
        $this->tpl->setVariable('TITLE', $a_set['title']);
    }

    public function parse(): void
    {
        $calculator = new ilCalendarRecurrenceCalculator(
            $this->entry,
            ilCalendarRecurrences::_getFirstRecurrence($this->entry->getEntryId())
        );

        $end = clone $this->entry->getStart();
        $end->increment(IL_CAL_YEAR, 10);

        $appointments = $calculator->calculateDateList(
            $this->entry->getStart(),
            $end
        );
        $rows = [];
        foreach ($appointments as $recurrence_date) {
            $row = [];
            $row['id'] = (int) $recurrence_date->get(IL_CAL_UNIX);
            $row['title'] = $this->entry->getTitle() . ' ( ' . ilDatePresentation::formatDate($recurrence_date) . ' ) ';
            $rows[] = $row;
        }
        $this->setData($rows);
    }
}

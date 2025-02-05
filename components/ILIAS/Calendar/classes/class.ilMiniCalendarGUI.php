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
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @ingroup ServicesCalendar
 */
class ilMiniCalendarGUI
{
    public const PRESENTATION_CALENDAR = 1;

    protected ilDate $seed;
    protected ilCalendarUserSettings $user_settings;
    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrlInterface $ctrl;
    protected ilLanguage $lng;
    protected ilObjUser $user;
    protected object $parentobject;

    public function __construct(ilDate $seed, object $a_par_obj)
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        $lng = $DIC['lng'];

        $this->user = $DIC->user();
        $this->user_settings = ilCalendarUserSettings::_getInstanceByUserId($this->user->getId());
        $this->ctrl = $DIC->ctrl();
        $this->lng = $lng;
        $this->lng->loadLanguageModule('dateplaner');
        $this->seed = $seed;
        $this->setParentObject($a_par_obj);
    }

    public function setParentObject(object $a_parentobject): void
    {
        $this->parentobject = $a_parentobject;
    }

    public function getParentObject(): object
    {
        return $this->parentobject;
    }

    /**
     * Get HTML for calendar
     */
    public function getHTML(): string
    {
        $ftpl = new ilTemplate(
            "tpl.calendar_block_frame.html",
            true,
            true,
            "components/ILIAS/Calendar"
        );

        $tpl = new ilTemplate(
            "tpl.calendar_block.html",
            true,
            true,
            "components/ILIAS/Calendar"
        );
        $this->addMiniMonth($tpl);

        $ftpl->setVariable("BLOCK_TITLE", $this->lng->txt("calendar"));
        $ftpl->setVariable("CONTENT", $tpl->get());
        return $ftpl->get();
    }

    /**
     * Add mini version of monthly overview
     * (Maybe extracted to another class, if used in pd calendar tab
     */
    public function addMiniMonth(ilTemplate $a_tpl): void
    {
        // weekdays
        $a_tpl->setCurrentBlock('month_header_col');
        $a_tpl->setVariable('TXT_WEEKDAY', $this->lng->txt("cal_week_abbrev"));
        $a_tpl->parseCurrentBlock();
        for ($i = $this->user_settings->getWeekStart(); $i < (7 + $this->user_settings->getWeekStart()); $i++) {
            $a_tpl->setCurrentBlock('month_header_col');
            $a_tpl->setVariable('TXT_WEEKDAY', ilCalendarUtil::_numericDayToString($i, false));
            $a_tpl->parseCurrentBlock();
        }

        $scheduler = new ilCalendarSchedule($this->seed, ilCalendarSchedule::TYPE_MONTH);
        $scheduler->calculate();

        $counter = 0;
        foreach (ilCalendarUtil::_buildMonthDayList(
            (int) $this->seed->get(IL_CAL_FKT_DATE, 'm'),
            (int) $this->seed->get(IL_CAL_FKT_DATE, 'Y'),
            $this->user_settings->getWeekStart()
        )->get() as $date) {
            $counter++;
            //$this->showEvents($date);

            $a_tpl->setCurrentBlock('month_col');

            if (count($scheduler->getByDay($date, $this->user->getTimeZone()))) {
                $a_tpl->setVariable('DAY_CLASS', 'calminiapp');
                #$a_tpl->setVariable('TD_CLASS','calminiapp');
            }

            if (ilCalendarUtil::_isToday($date)) {
                $a_tpl->setVariable('TD_CLASS', 'calminitoday');
            } elseif (ilDateTime::_equals($date, $this->seed, IL_CAL_MONTH)) {
                $a_tpl->setVariable('TD_CLASS', 'calministd');
            } elseif (ilDateTime::_before($date, $this->seed, IL_CAL_MONTH)) {
                $a_tpl->setVariable('TD_CLASS', 'calminiprev');
            } else {
                $a_tpl->setVariable('TD_CLASS', 'calmininext');
            }

            $day = $date->get(IL_CAL_FKT_DATE, 'j');
            $month = $date->get(IL_CAL_FKT_DATE, 'n');

            $month_day = $day;

            $this->ctrl->clearParametersByClass('ilcalendardaygui');
            $this->ctrl->setParameterByClass('ilcalendardaygui', 'seed', $date->get(IL_CAL_DATE));
            $a_tpl->setVariable('OPEN_DAY_VIEW', $this->ctrl->getLinkTargetByClass('ilcalendardaygui', ''));
            $this->ctrl->clearParametersByClass('ilcalendardaygui');

            $a_tpl->setVariable('MONTH_DAY', $month_day);
            $a_tpl->parseCurrentBlock();

            if ($counter and !($counter % 7)) {
                $a_tpl->setCurrentBlock('week');
                $a_tpl->setVariable(
                    'WEEK',
                    $date->get(IL_CAL_FKT_DATE, 'W')
                );
                $a_tpl->parseCurrentBlock();

                $a_tpl->setCurrentBlock('month_row');
                $this->ctrl->clearParametersByClass('ilcalendarweekgui');
                $this->ctrl->setParameterByClass('ilcalendarweekgui', 'seed', $date->get(IL_CAL_DATE));
                $this->ctrl->clearParametersByClass('ilcalendarweekgui');
                $a_tpl->setVariable('TD_CLASS', 'calminiweek');
                $a_tpl->parseCurrentBlock();
            }
        }
        $a_tpl->setCurrentBlock('mini_month');
        //$a_tpl->setVariable('TXT_MONTH_OVERVIEW', $lng->txt("cal_month_overview"));
        $a_tpl->setVariable(
            'TXT_MONTH',
            $this->lng->txt('month_' . $this->seed->get(IL_CAL_FKT_DATE, 'm') . '_long') .
            ' ' . $this->seed->get(IL_CAL_FKT_DATE, 'Y')
        );
        $myseed = clone($this->seed);
        $this->ctrl->setParameterByClass('ilcalendarmonthgui', 'seed', $myseed->get(IL_CAL_DATE));
        $a_tpl->setVariable('OPEN_MONTH_VIEW', $this->ctrl->getLinkTargetByClass('ilcalendarmonthgui', ''));

        $myseed->increment(ilDateTime::MONTH, -1);
        $this->ctrl->setParameter($this->getParentObject(), 'seed', $myseed->get(IL_CAL_DATE));

        $a_tpl->setVariable(
            'PREV_MONTH',
            $this->ctrl->getLinkTarget($this->getParentObject(), "")
        );

        $myseed->increment(ilDateTime::MONTH, 2);
        $this->ctrl->setParameter($this->getParentObject(), 'seed', $myseed->get(IL_CAL_DATE));
        $a_tpl->setVariable(
            'NEXT_MONTH',
            $this->ctrl->getLinkTarget($this->getParentObject(), "")
        );

        $this->ctrl->setParameter($this->getParentObject(), 'seed', "");
        $a_tpl->parseCurrentBlock();
    }
}

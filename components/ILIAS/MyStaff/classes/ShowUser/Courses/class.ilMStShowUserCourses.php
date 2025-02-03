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

namespace ILIAS\MyStaff\Courses\ShowUser;

use ILIAS\MyStaff\ListCourses\ilMStListCourses;

/**
 * Class ilMStShowUserCourses
 * @author Martin Studer <ms@studer-raimann.ch>
 */
class ilMStShowUserCourses extends ilMStListCourses
{
    /**
     * @param array  $arr_filter
     * @return string
     */
    protected function createWhereStatement(array $arr_filter): string
    {
        global $DIC;

        if (!$arr_filter['usr_id']) {
            return '';
        }

        $where = parent::createWhereStatement($arr_filter);
        $usr_filter = "a_table.usr_id = " . $DIC->database()->quote($arr_filter['usr_id'], 'integer');

        if (empty($where)) {
            return ' WHERE ' . $usr_filter;
        } else {
            return $where . ' AND ' . $usr_filter;
        }
    }
}

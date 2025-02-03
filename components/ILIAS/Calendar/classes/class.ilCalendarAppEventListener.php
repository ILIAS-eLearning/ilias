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

/** @noinspection ALL */
declare(strict_types=1);
/**
 * Handles events (create,update,delete) for autmatic generated calendar
 * events from course, groups, ...
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @ingroup ServicesCalendar
 */
class ilCalendarAppEventListener implements ilAppEventListener
{
    /**
     * Handle events like create, update, delete
     * @access public
     * @param string $a_component component, e.g. "components/ILIAS/Forum" or "components/ILIAS/User"
     * @param string $a_event     event e.g. "createUser", "updateUser", "deleteUser", ...
     * @param array  $a_parameter parameter array (assoc), array("name" => ..., "phone_office" => ...)     *
     * @static
     */
    public static function handleEvent(string $a_component, string $a_event, array $a_parameter): void
    {
        global $DIC;

        $logger = $DIC->logger()->cal();
        $ilUser = $DIC->user();

        $delete_cache = false;
        switch ($a_component) {
            case 'components/ILIAS/Session':
            case 'components/ILIAS/Group':
            case 'components/ILIAS/Course':
            case 'components/ILIAS/EmployeeTalk':
                switch ($a_event) {
                    case 'create':
                        $logger->debug('Handling create event');
                        self::createCategory($a_parameter['object']);
                        self::createAppointments($a_parameter['object'], $a_parameter['appointments']);
                        $delete_cache = true;
                        break;

                    case 'update':
                        $logger->debug('Handling update event');
                        self::updateCategory($a_parameter['object']);
                        self::deleteAppointments($a_parameter['obj_id']);
                        self::createAppointments($a_parameter['object'], $a_parameter['appointments']);
                        $delete_cache = true;
                        break;

                    case 'delete':
                        $logger->debug('Handling delete event');
                        self::deleteCategory($a_parameter['obj_id']);
                        $delete_cache = true;
                        break;
                }
                break;

            case 'components/ILIAS/Booking':
                switch ($a_event) {
                    case 'create':
                    case 'delete':
                    case 'update':
                        break;
                }
                break;

            case 'components/ILIAS/Exercise':
                switch ($a_event) {
                    case 'createAssignment':
                        $logger->debug('Handling create event (exercise assignment)');
                        self::createCategory($a_parameter['object'], true); // exercise category could already exist
                        self::createAppointments($a_parameter['object'], $a_parameter['appointments']);
                        $delete_cache = true;
                        break;

                    case 'updateAssignment':
                        $logger->debug('Handling update event (exercise assignment)');
                        self::createCategory($a_parameter['object'], true); // different life-cycle than ilObject
                        self::deleteAppointments($a_parameter['obj_id'], $a_parameter['context_ids']);
                        self::createAppointments($a_parameter['object'], $a_parameter['appointments']);
                        $delete_cache = true;
                        break;

                    case 'deleteAssignment':
                        $logger->debug('Handling delete event (exercise assignment)');
                        self::deleteAppointments($a_parameter['obj_id'], $a_parameter['context_ids']);
                        $delete_cache = true;
                        break;

                    case 'delete':
                        $logger->debug(':Handling delete event');
                        self::deleteCategory($a_parameter['obj_id']);
                        $delete_cache = true;
                        break;
                }
                break;
        }

        if ($delete_cache) {
            ilCalendarCategories::deletePDItemsCache($ilUser->getId());
            ilCalendarCategories::deleteRepositoryCache($ilUser->getId());
        }
    }

    /**
     * Create a category for a new object (crs,grp, ...)
     */
    public static function createCategory(ilObject $a_obj, bool $a_check_existing = false): int
    {
        global $DIC;

        $lng = $DIC->language();

        // already existing?  do update instead
        if ($a_check_existing &&
            ilCalendarCategory::_getInstanceByObjId($a_obj->getId())) {
            return self::updateCategory($a_obj);
        }
        $cat = new ilCalendarCategory();
        $cat->setTitle($a_obj->getTitle() ? $a_obj->getTitle() : $lng->txt('obj_' . $a_obj->getType()));
        $cat->setType(ilCalendarCategory::TYPE_OBJ);
        $cat->setColor(ilCalendarAppointmentColors::_getRandomColorByType($a_obj->getType()));
        $cat->setObjId($a_obj->getId());
        return $cat->add();
    }

    /**
     * Update category for a object (crs,grp, ...)
     */
    public static function updateCategory(ilObject $a_obj): int
    {
        if ($cat = ilCalendarCategory::_getInstanceByObjId($a_obj->getId())) {
            $cat->setTitle($a_obj->getTitle());
            $cat->update();
            return $cat->getCategoryID();
        }
        return 0;
    }

    /**
     * Create appointments
     * @param ilObject
     * @param ilCalendarAppointmentTemplate[]
     * @return void
     */
    public static function createAppointments(ilObject $a_obj, array $a_appointments): void
    {
        global $DIC;

        $logger = $DIC->logger()->cal();

        if (!$cat_id = ilCalendarCategories::_lookupCategoryIdByObjId($a_obj->getId())) {
            $logger->warning('Cannot find calendar category for obj_id ' . $a_obj->getId());
            $cat_id = self::createCategory($a_obj);
        }

        foreach ($a_appointments as $app_templ) {
            $app = new ilCalendarEntry();
            $app->setContextId($app_templ->getContextId());
            $app->setContextInfo($app_templ->getContextInfo());
            $app->setTitle($app_templ->getTitle());
            $app->setSubtitle($app_templ->getSubtitle());
            $app->setDescription($app_templ->getDescription());
            $app->setFurtherInformations($app_templ->getInformation());
            $app->setLocation($app_templ->getLocation());
            $app->setStart($app_templ->getStart());
            $app->setEnd($app_templ->getEnd());
            $app->setFullday($app_templ->isFullday());
            $app->setAutoGenerated(true);
            $app->setTranslationType($app_templ->getTranslationType());
            $app->save();

            $ass = new ilCalendarCategoryAssignments($app->getEntryId());
            $ass->addAssignment($cat_id);
        }
    }

    /**
     * Delete automatic generated appointments
     */
    public static function deleteAppointments(int $a_obj_id, ?array $a_context_ids = null)
    {
        foreach (ilCalendarCategoryAssignments::_getAutoGeneratedAppointmentsByObjId($a_obj_id) as $app_id) {
            // delete only selected entries
            if (is_array($a_context_ids)) {
                $entry = new ilCalendarEntry($app_id);
                if (!in_array($entry->getContextId(), $a_context_ids)) {
                    continue;
                }
            }
            ilCalendarCategoryAssignments::_deleteByAppointmentId($app_id);
            ilCalendarEntry::_delete($app_id);
        }
    }

    /**
     * delete category
     */
    public static function deleteCategory(int $a_obj_id): void
    {
        global $DIC;

        $logger = $DIC->logger()->cal();

        if (!$cat_id = ilCalendarCategories::_lookupCategoryIdByObjId($a_obj_id)) {
            $logger->warning('Cannot find calendar category for obj_id ' . $a_obj_id);
            return;
        }

        $category = new ilCalendarCategory($cat_id);
        $category->delete();
    }
}

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

declare(strict_types=0);

/**
 * Handles course mail placeholders
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @package ModulesCourse
 */
class ilCourseMailTemplateTutorContext extends ilMailTemplateContext
{
    public const ID = 'crs_context_tutor_manual';

    protected static array $periodInfoByObjIdCache = [];

    public function getId(): string
    {
        return self::ID;
    }

    public function getTitle(): string
    {
        global $DIC;

        $lng = $DIC['lng'];

        $lng->loadLanguageModule('crs');

        return $lng->txt('crs_mail_context_tutor_title');
    }

    public function getDescription(): string
    {
        global $DIC;

        $lng = $DIC['lng'];

        $lng->loadLanguageModule('crs');

        return $lng->txt('crs_mail_context_tutor_info');
    }

    /**
     * Return an array of placeholders
     */
    public function getSpecificPlaceholders(): array
    {
        /**
         * @var $lng ilLanguage
         */
        global $DIC;

        $lng = $DIC['lng'];

        $lng->loadLanguageModule('crs');
        $lng->loadLanguageModule('trac');

        // tracking settings
        $tracking = new ilObjUserTracking();

        $placeholders = array();

        $placeholders['course_title'] = array(
            'placeholder' => 'COURSE_TITLE',
            'label' => $lng->txt('crs_title')
        );

        $placeholders['course_period_start'] = array(
            'placeholder' => 'COURSE_PERIOD_START',
            'label' => $lng->txt('crs_period_start_mail_placeholder')
        );

        $placeholders['course_period_end'] = array(
            'placeholder' => 'COURSE_PERIOD_END',
            'label' => $lng->txt('crs_period_end_mail_placeholder')
        );

        $placeholders['course_status'] = array(
            'placeholder' => 'COURSE_STATUS',
            'label' => $lng->txt('trac_status')
        );

        $placeholders['course_mark'] = array(
            'placeholder' => 'COURSE_MARK',
            'label' => $lng->txt('trac_mark')
        );

        if ($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_SPENT_SECONDS)) {
            $placeholders['course_time_spent'] = array(
                'placeholder' => 'COURSE_TIME_SPENT',
                'label' => $lng->txt('trac_spent_seconds')
            );
        }

        if ($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_LAST_ACCESS)) {
            $placeholders['course_first_access'] = array(
                'placeholder' => 'COURSE_FIRST_ACCESS',
                'label' => $lng->txt('trac_first_access')
            );

            $placeholders['course_last_access'] = array(
                'placeholder' => 'COURSE_LAST_ACCESS',
                'label' => $lng->txt('trac_last_access')
            );
        }

        $placeholders['course_link'] = array(
            'placeholder' => 'COURSE_LINK',
            'label' => $lng->txt('crs_mail_permanent_link')
        );

        return $placeholders;
    }

    private function getCachedPeriodByObjId(int $objId): ?array
    {
        if (!array_key_exists($objId, self::$periodInfoByObjIdCache)) {
            self::$periodInfoByObjIdCache[$objId] = ilObjCourseAccess::lookupPeriodInfo($objId);
        }

        return self::$periodInfoByObjIdCache[$objId];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveSpecificPlaceholder(
        string $placeholder_id,
        array $context_parameters,
        ilObjUser $recipient = null
    ): string {
        /**
         * @var $ilObjDataCache ilObjectDataCache
         */
        global $DIC;

        $ilObjDataCache = $DIC['ilObjDataCache'];

        if (!in_array($placeholder_id, array(
            'course_title',
            'course_period_start',
            'course_period_end',
            'course_link',
            'course_status',
            'course_mark',
            'course_time_spent',
            'course_first_access',
            'course_last_access'
        ))) {
            return '';
        }

        $obj_id = $ilObjDataCache->lookupObjId((int) $context_parameters['ref_id']);

        $tracking = new ilObjUserTracking();

        $this->getLanguage()->loadLanguageModule('trac');
        $this->getLanguage()->loadLanguageModule('crs');

        switch ($placeholder_id) {
            case 'course_title':
                return $ilObjDataCache->lookupTitle($obj_id);

            case 'course_period_start':
                $periodInfo = $this->getCachedPeriodByObjId((int) $ilObjDataCache->lookupObjId((int) $context_parameters['ref_id']));
                if ($periodInfo) {
                    $useRelativeDates = ilDatePresentation::useRelativeDates();
                    ilDatePresentation::setUseRelativeDates(false);
                    $formattedDate = ilDatePresentation::formatDate($periodInfo['crs_start']);
                    ilDatePresentation::setUseRelativeDates($useRelativeDates);

                    return $formattedDate;
                }

                return '';

            case 'course_period_end':
                $periodInfo = $this->getCachedPeriodByObjId($ilObjDataCache->lookupObjId((int) $context_parameters['ref_id']));
                if ($periodInfo) {
                    $useRelativeDates = ilDatePresentation::useRelativeDates();
                    ilDatePresentation::setUseRelativeDates(false);
                    $formattedDate = ilDatePresentation::formatDate($periodInfo['crs_end']);
                    ilDatePresentation::setUseRelativeDates($useRelativeDates);

                    return $formattedDate;
                }

                return '';

            case 'course_link':
                return ilLink::_getLink($context_parameters['ref_id'], 'crs');

            case 'course_status':
                if ($recipient === null) {
                    return '';
                }

                $status = ilLPStatus::_lookupStatus($obj_id, $recipient->getId());
                if (!$status) {
                    $status = ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
                }
                return ilLearningProgressBaseGUI::_getStatusText($status, $this->getLanguage());

            case 'course_mark':
                if ($recipient === null) {
                    return '';
                }

                $mark = ilLPMarks::_lookupMark($recipient->getId(), $obj_id);
                return (is_string($mark) && strlen(trim($mark))) ? $mark : '-';

            case 'course_time_spent':
                if ($recipient === null) {
                    return '';
                }

                if ($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_SPENT_SECONDS)) {
                    $progress = ilLearningProgress::_getProgress($recipient->getId(), $obj_id);
                    if (isset($progress['spent_seconds'])) {
                        return ilDatePresentation::secondsToString(
                            $progress['spent_seconds'],
                            false,
                            $this->getLanguage()
                        );
                    }
                }
                break;

            case 'course_first_access':
                if ($recipient === null) {
                    return '';
                }

                if ($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_LAST_ACCESS)) {
                    $progress = ilLearningProgress::_getProgress($recipient->getId(), $obj_id);
                    if (isset($progress['access_time_min'])) {
                        return ilDatePresentation::formatDate(new ilDateTime(
                            $progress['access_time_min'],
                            IL_CAL_UNIX
                        ));
                    }
                }
                break;

            case 'course_last_access':
                if ($recipient === null) {
                    return '';
                }

                if ($tracking->hasExtendedData(ilObjUserTracking::EXTENDED_DATA_LAST_ACCESS)) {
                    $progress = ilLearningProgress::_getProgress($recipient->getId(), $obj_id);
                    if (isset($progress['access_time'])) {
                        return ilDatePresentation::formatDate(new ilDateTime($progress['access_time'], IL_CAL_UNIX));
                    }
                }
                break;
        }

        return '';
    }
}

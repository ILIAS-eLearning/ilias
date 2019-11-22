<?php

use ILIAS\Membership\Changelog\Infrastructure\AR\EventAR;

/**
 * Class ilChangelogCronDeleteOldEvents
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class ilChangelogCronDeleteOldLogs extends ilCronJob
{

    const CLEAR_OLDER_THAN = 'clear_older_than';
    /**
     * @var ilLanguage
     */
    protected $lng;
    /**
     * @var ilDBInterface
     */
    protected $database;
    /**
     * @var ilSetting
     */
    protected $settings;


    /**
     * ilChangelogCronDeleteOldLogs constructor.
     */
    public function __construct()
    {
        global $DIC;
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule("changelog");
        $this->database = $DIC->database();
        $this->settings = new ilSetting('changelog');
    }


    /**
     * @inheritdoc
     */
    public function getId()
    {
        return 'changelog_delete_old_logs';
    }


    /**
     * @inheritdoc
     */
    public function hasAutoActivation()
    {
        return false;
    }


    /**
     * @inheritdoc
     */
    public function hasFlexibleSchedule()
    {
        return true;
    }


    /**
     * @inheritdoc
     */
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_IN_DAYS;
    }


    /**
     * @inheritdoc
     */
    function getDefaultScheduleValue()
    {
        return 10;
    }


    /**
     * @inheritdoc
     */
    public function hasCustomSettings()
    {
        return true;
    }


    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->lng->txt("delete_old_logs_title");
    }


    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->lng->txt("delete_old_logs_info");
    }


    /**
     * @inheritdoc
     */
    public function run()
    {
        $ilCronJobResult = new ilCronJobResult();
        $timespan_in_days = $this->settings->get(self::CLEAR_OLDER_THAN);
        if (!$timespan_in_days) {
            $ilCronJobResult->setStatus(ilCronJobResult::STATUS_INVALID_CONFIGURATION);

            return $ilCronJobResult;
        }

        $threshold_unix = time() - ($timespan_in_days * 24 * 60 * 60);
        $threshold = date('Y-m-d H:i:s', $threshold_unix);
        $res = $this->database->query('SELECT event_id FROM ' . EventAR::TABLE_NAME . ' WHERE timestamp < ' . $this->database->quote($threshold, 'timestamp'));
        if ($res->numRows() == 0) {
            $ilCronJobResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);
        } else {
            $all = $this->database->fetchAll($res, ilDBConstants::FETCHMODE_ASSOC);
            $all = array_map(function ($element) {
                return $element['event_id'];
            }, $all);

            $this->database->query(
                'DELETE FROM ' . EventAR::TABLE_NAME
                . ' WHERE event_id IN (\'' . implode('\',\'', $all) . '\')'
            );
            $ilCronJobResult->setMessage(count($all) . ' entrie(s) deleted.');
            $ilCronJobResult->setStatus(ilCronJobResult::STATUS_OK);
        }

        return $ilCronJobResult;
    }


    /**
     * @inheritdoc
     */
    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form)
    {
        $clear_older_then = new ilTextInputGUI($this->lng->txt('frm_' . self::CLEAR_OLDER_THAN), self::CLEAR_OLDER_THAN);
        $clear_older_then->setRequired(true);
        $clear_older_then->setValue($this->settings->get(self::CLEAR_OLDER_THAN));
        $clear_older_then->setInfo($this->lng->txt('frm_' . self::CLEAR_OLDER_THAN . '_info'));

        $a_form->addItem($clear_older_then);
    }


    /**
     * @inheritdoc
     */
    public function saveCustomSettings(ilPropertyFormGUI $a_form)
    {
        $this->settings->set(self::CLEAR_OLDER_THAN, $a_form->getInput(self::CLEAR_OLDER_THAN));

        return true;
    }
}
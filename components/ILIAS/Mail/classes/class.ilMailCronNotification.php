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

use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Cron\Schedule\CronJobScheduleType;

/**
 * Mail notifications
 * @author Nadia Ahmad <nahmad@databay.de>
 */
class ilMailCronNotification extends ilCronJob
{
    private GlobalHttpState $http;
    protected ilSetting $settings;
    protected bool $initDone = false;

    public function init(): void
    {
        $this->lng->loadLanguageModule('mail');

        global $DIC;

        if (!$this->initDone) {
            $this->settings = $DIC->settings();
            $this->http = $DIC->http();

            $this->initDone = true;
        }
    }

    public function getId(): string
    {
        return 'mail_notification';
    }

    public function getTitle(): string
    {
        return $this->lng->txt('cron_mail_notification');
    }

    public function getDescription(): string
    {
        return  sprintf(
            $this->lng->txt('cron_mail_notification_desc'),
            $this->lng->txt('mail_allow_external')
        );
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return null;
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return false;
    }

    public function hasCustomSettings(): bool
    {
        return true;
    }

    public function usesLegacyForms(): bool
    {
        return false;
    }

    public function getCustomConfigurationInput(
        \ILIAS\UI\Factory $ui_factory,
        \ILIAS\Refinery\Factory $factory,
        ilLanguage $lng
    ): \ILIAS\UI\Component\Input\Container\Form\FormInput {
        $status = $ui_factory
            ->input()
            ->field()
            ->checkbox($this->lng->txt('cron_mail_notification_message'))
            ->withByline($this->lng->txt('cron_mail_notification_message_info'))
            ->withValue((bool) $this->settings->get('mail_notification_message', '0'))
            ->withDedicatedName('mail_notification_message');

        return $status;
    }

    public function saveCustomConfiguration(mixed $form_data): void
    {
        $this->init();
        $this->settings->set(
            'mail_notification_message',
            (string) ((int) $form_data)
        );
    }

    public function run(): ilCronJobResult
    {
        $msn = new ilMailSummaryNotification();
        $msn->send();

        $result = new ilCronJobResult();
        $result->setStatus(ilCronJobResult::STATUS_OK);
        return $result;
    }

    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form): void
    {
        throw new RuntimeException('Not implemented');
    }

    public function saveCustomSettings(ilPropertyFormGUI $a_form): bool
    {
        throw new RuntimeException('Not implemented');
    }

    public function activationWasToggled(ilDBInterface $db, ilSetting $setting, bool $a_currently_active): void
    {
        $setting->set('mail_notification', (string) ((int) $a_currently_active));
    }
}

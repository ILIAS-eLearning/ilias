<?php declare(strict_types=1);

use ilCtrlException;
use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ilObjectGUI;
use ilPermissionGUI;
use ilSetting;

/******************************************************************************
 *
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
 *     https://www.ilias.de
 *     https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/

/**
 * @author Ingmar Szmais <iszmais@databay.de>
 *
 * @ilCtrl_IsCalledBy ilObjNotificationAdminGUI: ilAdministrationGUI
 * @ilCtrl_Calls      ilObjNotificationAdminGUI: ilPermissionGUI
 */
class ilObjNotificationAdminGUI extends ilObjectGUI
{
    protected Container $dic;

    /**
     * @inheritDoc
     */
    public function __construct($a_data, $a_id = 0, $a_call_by_reference = true, $a_prepare_output = true)
    {
        global $DIC;
        $this->dic = $DIC;

        $this->type = "nota";
        parent::__construct($a_data, $a_id, $a_call_by_reference, false);
        $this->lng->loadLanguageModule('notification_adm');
    }
    
    public function executeCommand() : void
    {
        $this->prepareOutput();

        switch (strtolower($this->ctrl->getNextClass())) {
            case strtolower(ilPermissionGUI::class):
                $perm_gui = new ilPermissionGUI($this);
                $this->ctrl->forwardCommand($perm_gui);
                break;
            default:
                switch ($this->ctrl->getCmd()) {
                    case 'saveGeneralSettings':
                        $this->saveGeneralSettings();
                        break;
                    default:
                        $this->showGeneralSettings();
                }
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function showGeneralSettings(?Form $form = null) : void
    {
        if ($form === null) {
            $settings = new ilSetting('notifications');
            $value = [];
            if ($settings->get('enable_osd') === '0' || $settings->get('enable_osd') === null) {
                $value['enable_osd'] = null;
            } else {
                $value['enable_osd'] = [
                    'osd_interval' => (int) $settings->get('osd_interval'),
                    'osd_vanish' => (int) $settings->get('osd_vanish'),
                    'osd_delay' => (int) $settings->get('osd_delay'),
                    'play_sound' => (bool) $settings->get('play_sound'),
                ];
            }
            $form = $this->getForm($value);
        }

        $this->tpl->setContent($this->dic->ui()->renderer()->render($form));
    }

    /**
     * @throws ilCtrlException
     */
    public function saveGeneralSettings() : void
    {
        $settings = new ilSetting('notifications');

        $form = $this->getForm()->withRequest($this->dic->http()->request());
        $data = $form->getData();
        if ($data && is_array($data['osd'])) {
            if ($data['osd']['enable_osd'] === null) {
                $settings->deleteAll();
                $settings->set('enable_osd', '0');
            } else {
                $settings->set('enable_osd', '1');
                $settings->set('osd_interval', ((string) $data['osd']['enable_osd']['osd_interval']));
                $settings->set('osd_vanish', ((string) $data['osd']['enable_osd']['osd_vanish']));
                $settings->set('osd_delay', ((string) $data['osd']['enable_osd']['osd_delay']));
                $settings->set('play_sound', ($data['osd']['enable_osd']['play_sound']) ? '1' : '0');
            }
        }
        $this->showGeneralSettings($form);
    }

    /**
     * @throws ilCtrlException
     */
    protected function getForm(array $value = null) : Form
    {
        $enable_osd = $this->dic->ui()->factory()->input()->field()->optionalGroup(
            [
                'osd_interval' => $this->dic->ui()->factory()->input()->field()->numeric(
                    $this->lng->txt('osd_interval'),
                    $this->lng->txt('osd_interval_desc')
                )->withRequired(true),
                'osd_vanish' => $this->dic->ui()->factory()->input()->field()->numeric(
                    $this->lng->txt('osd_vanish'),
                    $this->lng->txt('osd_vanish_desc')
                )->withRequired(true),
                'osd_delay' => $this->dic->ui()->factory()->input()->field()->numeric(
                    $this->lng->txt('osd_delay'),
                    $this->lng->txt('osd_delay_desc')
                )->withRequired(true),
                'play_sound' => $this->dic->ui()->factory()->input()->field()->checkbox(
                    $this->lng->txt('play_sound'),
                    $this->lng->txt('play_sound_desc')
                )
            ],
            $this->lng->txt('enable_osd')
        )->withByline(
            $this->lng->txt('enable_osd_desc')
        );

        if ($value !== null) {
            $enable_osd = $enable_osd->withValue($value['enable_osd'] ?? null);
        }

        return $this->dic->ui()->factory()->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'saveGeneralSettings'),
            [
                'osd' => $this->dic->ui()->factory()->input()->field()->section(
                    ['enable_osd' => $enable_osd],
                    $this->lng->txt('general_settings')
                )
            ]
        );
    }
}

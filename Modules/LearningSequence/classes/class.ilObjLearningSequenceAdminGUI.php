<?php declare(strict_types=1);

use ILIAS\UI\Component\Input;

/**
 * LearningSequence Administration Settings
 *
 * @ilCtrl_Calls ilObjLearningSequenceAdminGUI: ilPermissionGUI
 */
class ilObjLearningSequenceAdminGUI extends ilObjectGUI
{
    const CMD_VIEW = 'view';
    const CMD_EDIT = 'edit';
    const CMD_SAVE = 'save';
    const F_POLL_INTERVAL = 'polling';

    public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
    {
        $this->type = 'lsos';

        global $DIC;
        $this->ctrl = $DIC['ilCtrl'];
        $this->rbacsystem = $DIC['rbacsystem'];
        $this->ilSetting = $DIC['ilSetting'];
        $this->ui_factory = $DIC['ui.factory'];
        $this->ui_renderer = $DIC['ui.renderer'];
        $this->request = $DIC->http()->request();
        parent::__construct($a_data, $a_id, $a_call_by_reference, $a_prepare_output);
        $this->validation_factory = new \ILIAS\Validation\Factory(
            new \ILIAS\Data\Factory(),
            $this->lng
        );
    }

    public function getAdminTabs()
    {
        $this->tabs_gui->addTarget('settings', $this->ctrl->getLinkTargetByClass(self::class, self::CMD_EDIT));
        if ($this->rbacsystem->checkAccess('edit_permission', $this->object->getRefId())) {
            $this->tabs_gui->addTarget('perm_settings', $this->ctrl->getLinkTargetByClass('ilpermissiongui', 'perm'), array(), 'ilpermissiongui');
        }
    }

    public function executeCommand()
    {
        $this->checkPermission('read');
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();
        $this->prepareOutput();
        
        switch ($next_class) {
            case 'ilpermissiongui':
                $this->tabs_gui->setTabActive('perm_settings');
                $perm_gui = new ilPermissionGUI($this);
                $this->ctrl->forwardCommand($perm_gui);
                break;

            default:
                switch ($cmd) {
                    case self::CMD_VIEW:
                    case self::CMD_EDIT:
                        $this->edit();
                        break;
                    case self::CMD_SAVE:
                        $this->save();
                        break;
                    default:
                        throw new Exception(__METHOD__ . " :: Unknown command " . $cmd);
                }
        }
    }

    protected function getForm(array $values = []) : Input\Container\Form\Form
    {
        $target = $this->ctrl->getLinkTargetByClass(self::class, self::CMD_SAVE);
        $poll_interval = $this->ui_factory->input()->field()->numeric(
            $this->lng->txt("lso_admin_interval_label"),
            $this->lng->txt("lso_admin_interval_byline")
        )
        ->withAdditionalConstraint($this->validation_factory->greaterThan(0));

        if (isset($values[self::F_POLL_INTERVAL])) {
            $poll_interval = $poll_interval->withValue($values[self::F_POLL_INTERVAL]);
        }

        $section = $this->ui_factory->input()->field()->section(
            [self::F_POLL_INTERVAL => $poll_interval],
            $this->lng->txt("lso_admin_form_title"),
            $this->lng->txt("lso_admin_form_byline")
        );
        $form = $this->ui_factory->input()->container()->form()->standard($target, [$section]);
        return $form;
    }

    protected function show(Input\Container\Form\Form $form) : void
    {
        $this->tpl->setContent(
            $this->ui_renderer->render($form)
        );
    }

    protected function getCurrentPollingInterval() : float
    {
        $interval = $this->ilSetting->get(\ilObjLearningSequenceAdmin::SETTING_POLL_INTERVAL);
        if (!$interval) {
            $interval = \ilObjLearningSequenceAdmin::POLL_INTERVAL_DEFAULT;
        }
        return (float) $interval;
    }

    protected function edit() : void
    {
        $values = [
            self::F_POLL_INTERVAL => $this->getCurrentPollingInterval()
        ];
        $form = $this->getForm($values);
        $this->show($form);
    }

    protected function save() : void
    {
        $form = $this->getForm()->withRequest($this->request);
        $data = $form->getData();
        if ($data) {
            $data = array_shift($data);
            $interval = $data[self::F_POLL_INTERVAL];
            $this->ilSetting->set(
                \ilObjLearningSequenceAdmin::SETTING_POLL_INTERVAL,
                $interval
            );
        }
        $this->show($form);
    }
}

<?php declare(strict_types=1);

use \ILIAS\UI\Factory;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\Refinery\Factory as Refinery;
use \ILIAS\UI\Renderer;

class ilSystemStyleOverviewGUI
{
    protected ilCtrl $ctrl;
    protected ilToolbarGUI $toolbar;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    protected ilSkinFactory $skin_factory;
    protected ilFileSystemHelper $file_system;
    protected ilSkinStyleContainer $style_container;
    protected ilSystemStyleMessageStack $message_stack;
    protected Factory $ui_factory;
    protected Renderer $renderer;
    protected WrapperFactory $request_wrapper;
    protected Refinery $refinery;
    protected ilSystemStyleConfig $config;
    protected ilTabsGUI $tabs;
    protected ilHelpGUI $help;

    protected string $ref_id;
    protected bool $read_only = true;
    protected bool $management_enabled = false;

    protected string $style_id;

    public function __construct(
        ilCtrl $ctrl,
        ilLanguage $lng,
        ilGlobalTemplateInterface $tpl,
        Factory $ui_factory,
        Renderer $renderer,
        WrapperFactory $request_wrapper,
        ilToolbarGUI $toolbar,
        Refinery $refinery,
        ilSkinFactory $skin_factory,
        ilTabsGUI $tabs,
        ilHelpGUI $help,
        string $skin_id,
        string $style_id,
        string $ref_id,
        bool $read_only,
        bool $management_enabled
    ) {
        $this->ctrl = $ctrl;
        $this->lng = $lng;
        $this->tpl = $tpl;
        $this->ui_factory = $ui_factory;
        $this->renderer = $renderer;
        $this->request_wrapper = $request_wrapper;
        $this->toolbar = $toolbar;
        $this->refinery = $refinery;
        $this->tabs = $tabs;
        $this->style_id = $style_id;
        $this->message_stack = new ilSystemStyleMessageStack();
        $this->skin_factory = $skin_factory;
        $this->style_container = $this->skin_factory->skinStyleContainerFromId($skin_id, $this->message_stack);
        $this->help = $help;
        $this->ref_id = $ref_id;

        $this->setReadOnly($read_only);
        $this->setManagementEnabled($management_enabled);
    }

    public function executeCommand() : void
    {
        $cmd = $this->ctrl->getCmd();

        if ($cmd == "") {
            $cmd = $this->isReadOnly() ? "view" : "edit";
        }

        switch ($cmd) {
            case "addSystemStyle":
            case "addSubStyle":
            case "saveNewSystemStyle":
            case "saveNewSubStyle":
            case "copyStyle":
            case "importStyle":
            case "deleteStyles":
            case "deleteStyle":
            case "confirmDelete":
                if (!$this->isManagementEnabled()) {
                    throw new ilObjectException($this->lng->txt("permission_denied"));
                }
                $this->$cmd();
                return;
            case "cancel":
            case "edit":
            case "export":
            case "moveUserStyles":
            case "saveStyleSettings":
                if ($this->isReadOnly()) {
                    throw new ilObjectException($this->lng->txt("permission_denied"));
                }
                $this->$cmd();
                return;
            case "view":
                $this->$cmd();
                return;

        }
    }

    protected function view() : void
    {
        $table = new ilSystemStylesTableGUI($this, "edit");
        $this->tpl->setContent($table->getHTML());
    }

    protected function cancel()
    {
        $this->edit();
    }

    /**
     * Edit
     */
    public function edit()
    {
        if ($this->isManagementEnabled()) {
            // Add Button for adding skins
            $this->toolbar->addComponent($this->ui_factory->button()->standard($this->lng->txt("add_system_style"),
                $this->ctrl->getLinkTarget($this, 'addSystemStyle')));

            // Add Button for adding sub styles
            $add_sub = $this->ui_factory->button()->standard($this->lng->txt("add_substyle"),
                $this->ctrl->getLinkTarget($this, 'addSubStyle'));
            if (count(ilStyleDefinition::getAllSkins()) == 1) {
                $add_sub = $add_sub->withUnavailableAction();
            }
            $this->toolbar->addComponent($add_sub);
            $this->toolbar->addSeparator();
        }

        // from styles selector
        $si = new ilSelectInputGUI($this->lng->txt("sty_move_user_styles") . ": " . $this->lng->txt("sty_from"),
            "from_style");

        $options = array();
        foreach (ilStyleDefinition::getAllSkinStyles() as $id => $skin_style) {
            if (!$skin_style['substyle_of']) {
                $options[$id] = $skin_style['title'];
            }
        }
        $si->setOptions($options + array("other" => $this->lng->txt("other")));

        $this->toolbar->addInputItem($si, true);

        $si = new ilSelectInputGUI($this->lng->txt("sty_to"), "to_style");
        $si->setOptions($options);
        $this->toolbar->addInputItem($si, true);
        $this->toolbar->addComponent($this->ui_factory->button()->standard($this->lng->txt("sty_move_style"), ""));

        $this->toolbar->setFormAction($this->ctrl->getLinkTarget($this, 'moveUserStyles'));

        $table = new ilSystemStylesTableGUI($this, "edit");
        $table->addActions($this->isManagementEnabled());
        $this->tpl->setContent($table->getHTML());
    }

    public function moveUserStyles() : void
    {
        $to = $this->request_wrapper->post()->retrieve('to_style', $this->refinery->string()->splitString(":"));

        $from_style = $this->request_wrapper->post()->retrieve('from_style', $this->refinery->kindlyTo()->string());

        if ($from_style == "other") {
            // get all user assigned styles
            $all_user_styles = ilObjUser::_getAllUserAssignedStyles();

            // move users that are not assigned to
            // currently existing style
            foreach ($all_user_styles as $style) {
                if (!ilStyleDefinition::styleExists($style)) {
                    $style_arr = explode(":", $style);
                    ilObjUser::_moveUsersToStyle($style_arr[0], $style_arr[1], $to[0], $to[1]);
                }
            }
        } else {
            $from = explode(":", $from_style);
            ilObjUser::_moveUsersToStyle($from[0], $from[1], $to[0], $to[1]);
        }

        $this->message_stack->addMessage(new ilSystemStyleMessage($this->lng->txt("msg_obj_modified")));
        $this->edit();
    }

    public function saveStyleSettings() : void
    {
        $message_stack = new ilSystemStyleMessageStack();

        $to_array = $this->refinery->custom()->transformation(function ($v){
            return $v;
        });
        $active_styles = $this->request_wrapper->post()->retrieve("st_act", $this->refinery->to()->);
        var_dump($active_styles);
        exit;
        if ($this->checkStyleSettings($message_stack)) {
            $all_styles = ilStyleDefinition::getAllSkinStyles();
            foreach ($all_styles as $st) {
                if (!isset($_POST["st_act"][$st["id"]])) {
                    ilSystemStyleSettings::_deactivateStyle($st["template_id"], $st["style_id"]);
                } else {
                    ilSystemStyleSettings::_activateStyle($st["template_id"], $st["style_id"]);
                }
            }

            //set default skin and style
            if ($_POST["default_skin_style"] != "") {
                $sknst = explode(":", $_POST["default_skin_style"]);
                ilSystemStyleSettings::setCurrentDefaultStyle($sknst[0], $sknst[1]);
            }
            $message_stack->addMessage(new ilSystemStyleMessage($this->lng->txt("msg_obj_modified"),
                ilSystemStyleMessage::TYPE_SUCCESS));
        }
        $message_stack->getUIComponentsMessages($this->f);
        $this->ctrl->redirect($this, "edit");
    }

    protected function checkStyleSettings(ilSystemStyleMessageStack $message_stack) : bool
    {
        $passed = true;

        if (count($_POST["st_act"]) < 1) {
            $passed = false;
            $message_stack->addMessage(new ilSystemStyleMessage($this->lng->txt("at_least_one_style"),
                ilSystemStyleMessage::TYPE_ERROR));
        }

        if (!isset($_POST["st_act"][$_POST["default_skin_style"]])) {
            $passed = false;
            $message_stack->addMessage(new ilSystemStyleMessage($this->lng->txt("cant_deactivate_default_style"),
                ilSystemStyleMessage::TYPE_ERROR));
        }

        // check if a style should be deactivated, that still has
        // a user assigned to
        $all_styles = ilStyleDefinition::getAllSkinStyles();

        foreach ($all_styles as $st) {
            if (!isset($_POST["st_act"][$st["id"]])) {
                if (ilObjUser::_getNumberOfUsersForStyle($st["template_id"], $st["style_id"]) > 0) {
                    $passed = false;
                    $message_stack->addMessage(new ilSystemStyleMessage($st["style_name"] . ": " . $this->lng->txt("cant_deactivate_if_users_assigned"),
                        ilSystemStyleMessage::TYPE_ERROR));
                }
            }
        }
        return $passed;
    }

    protected function addSystemStyle() : void
    {
        $this->addSystemStyleForms();
    }

    protected function saveNewSystemStyle() : void
    {
        $form = $this->createSystemStyleForm();

        if ($form->checkInput()) {
            $skin_id = $this->request_wrapper->post()->retrieve('skin_id', $this->refinery->kindlyTo()->string());
            $style_id = $this->request_wrapper->post()->retrieve('style_id', $this->refinery->kindlyTo()->string());

            $skin_name = $this->request_wrapper->post()->retrieve('skin_name', $this->refinery->kindlyTo()->string());
            $style_name = $this->request_wrapper->post()->retrieve('style_name', $this->refinery->kindlyTo()->string());

            $message_stack = new ilSystemStyleMessageStack();
            if (ilStyleDefinition::skinExists($skin_id)) {
                ilUtil::sendFailure($this->lng->txt("skin_id_exists"));
            } else {
                try {
                    $skin = new ilSkin($skin_id, $skin_name);
                    $style = new ilSkinStyle($style_id, $style_name);
                    $skin->addStyle($style);
                    $container = new ilSkinStyleContainer($skin);
                    $container->create($message_stack);
                    $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'skin_id', $skin->getId());
                    $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'style_id', $style->getId());
                    if ($message_stack->hasMessages()) {
                        $message_stack->getUIComponentsMessages($this->f);
                    } else {
                        ilUtil::sendSuccess($this->lng->txt("msg_sys_style_created"), true);
                    }
                    $this->ctrl->redirectByClass("ilSystemStyleSettingsGUI");
                } catch (ilSystemStyleException $e) {
                    $message_stack->addMessage(new ilSystemStyleMessage($e->getMessage(),
                        ilSystemStyleMessage::TYPE_ERROR));
                }
            }
            $message_stack->getUIComponentsMessages($this->f);
        }

        // display only this form to correct input
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    protected function addSystemStyleForms() : void
    {
        $this->tabs->clearTargets();
        /**
         * Since clearTargets also clears the help screen ids
         */
        $this->help->setScreenIdComponent("sty");
        $this->help->setScreenId("system_styles");
        $this->help->setSubScreenId("create");

        $forms = array();

        $forms[] = $this->createSystemStyleForm();
        $forms[] = $this->importSystemStyleForm();
        $forms[] = $this->cloneSystemStyleForm();

        $this->tpl->setContent($this->getCreationFormsHTML($forms));
    }

    protected function createSystemStyleForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("sty_create_new_system_style"));

        $ti = new ilTextInputGUI($this->lng->txt("skin_id"), "skin_id");
        $ti->setInfo($this->lng->txt("skin_id_description"));
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);

        $ti = new ilTextInputGUI($this->lng->txt("skin_name"), "skin_name");
        $ti->setInfo($this->lng->txt("skin_name_description"));
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);

        $ti = new ilTextInputGUI($this->lng->txt("style_id"), "style_id");
        $ti->setInfo($this->lng->txt("style_id_description"));
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);

        $ti = new ilTextInputGUI($this->lng->txt("style_name"), "style_name");
        $ti->setInfo($this->lng->txt("style_name_description"));
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);

        $form->addCommandButton("saveNewSystemStyle", $this->lng->txt("save"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));

        return $form;
    }

    protected function importSystemStyleForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("sty_import_system_style"));

        // title
        $file_input = new ilFileInputGUI($this->lng->txt("import_file"), "importfile");
        $file_input->setRequired(true);
        $file_input->setSuffixes(array("zip"));
        $form->addItem($file_input);

        $form->addCommandButton("importStyle", $this->lng->txt("import"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));

        return $form;
    }

    protected function cloneSystemStyleForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("sty_copy_other_system_style"));

        // source
        $ti = new ilSelectInputGUI($this->lng->txt("sty_source"), "source_style");
        $ti->setRequired(true);
        $styles = ilStyleDefinition::getAllSkinStyles();
        $options = array();
        foreach ($styles as $id => $style) {
            $system_style_conf = new ilSystemStyleConfig();
            if ($style["skin_id"] != $system_style_conf->getDefaultSkinId()) {
                $options[$id] = $style['title'];
            }
        }
        $ti->setOptions($options);

        $form->addItem($ti);

        $form->addCommandButton("copyStyle", $this->lng->txt("copy"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));

        return $form;
    }

    protected function getCreationFormsHTML(array $a_forms) : string
    {
        include_once("./Services/Accordion/classes/class.ilAccordionGUI.php");

        $acc = new ilAccordionGUI();
        $acc->setBehaviour(ilAccordionGUI::FIRST_OPEN);
        $cnt = 1;
        foreach ($a_forms as $form_type => $cf) {
            /**
             * @var ilPropertyFormGUI $cf
             */
            $htpl = new ilTemplate("tpl.creation_acc_head.html", true, true, "Services/Object");

            // using custom form titles (used for repository plugins)
            $form_title = "";
            if (method_exists($this, "getCreationFormTitle")) {
                $form_title = $this->getCreationFormTitle($form_type);
            }
            if (!$form_title) {
                $form_title = $cf->getTitle();
            }

            // move title from form to accordion
            $htpl->setVariable("TITLE", $this->lng->txt("option") . " " . $cnt . ": " .
                $form_title);
            $cf->setTitle("");
            $cf->setTitleIcon("");
            $cf->setTableWidth("100%");

            $acc->addItem($htpl->get(), $cf->getHTML());

            $cnt++;
        }

        return "<div class='ilCreationFormSection'>" . $acc->getHTML() . "</div>";
    }

    protected function copyStyle()
    {
        $message_stack = new ilSystemStyleMessageStack();

        $imploded_skin_style_id = explode(":", $_POST['source_style']);
        $skin_id = $imploded_skin_style_id[0];
        $style_id = $imploded_skin_style_id[1];

        try {
            $container = $this->skin_factory->skinStyleContainerFromId($skin_id, $message_stack);
            $new_container = $this->skin_factory->copyFromSkinStyleContainer($container, $this->file_system,
                $this->lng->txt("sty_acopy"));
            $message_stack->prependMessage(new ilSystemStyleMessage($this->lng->txt("style_copied"),
                ilSystemStyleMessage::TYPE_SUCCESS));
            $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'skin_id', $new_container->getSkin()->getId());
            $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'style_id',
                $new_container->getSkin()->getStyle($style_id)->getId());
            $message_stack->addMessage(new ilSystemStyleMessage($this->lng->txt("directory_created") . " " . $new_container->getSkinDirectory(),
                ilSystemStyleMessage::TYPE_SUCCESS));
        } catch (Exception $e) {
            $message_stack->addMessage(new ilSystemStyleMessage($e->getMessage(), ilSystemStyleMessage::TYPE_ERROR));
        }

        $message_stack->getUIComponentsMessages($this->f);
        $this->ctrl->redirectByClass("ilSystemStyleSettingsGUI");
    }

    protected function deleteStyle() : void
    {
        $skin_id = $this->style_container->getSkin()->getId();
        $style_id = $this->style_id;
        $message_stack = new ilSystemStyleMessageStack();

        if ($this->checkDeletable($skin_id, $style_id, $message_stack)) {
            $delete_form_table = new ilSystemStyleDeleteGUI($this->lng, $this->ctrl);
            $container = $this->skin_factory->skinStyleContainerFromId($skin_id);
            $delete_form_table->addStyle($container->getSkin(), $container->getSkin()->getStyle($style_id),
                $container->getImagesSkinPath($style_id));
            $this->tpl->setContent($delete_form_table->getDeleteStyleFormHTML());
        } else {
            $message_stack->prependMessage(new ilSystemStyleMessage($this->lng->txt("style_not_deleted"),
                ilSystemStyleMessage::TYPE_ERROR));
            $message_stack->getUIComponentsMessages($this->f);
            $this->edit();
        }
    }

    protected function deleteStyles() : void
    {
        $delete_form_table = new ilSystemStyleDeleteGUI($this->lng, $this->ctrl);
        $message_stack = new ilSystemStyleMessageStack();

        $all_deletable = true;
        foreach ($_POST['id'] as $skin_style_id) {
            $imploded_skin_style_id = explode(":", $skin_style_id);
            $skin_id = $imploded_skin_style_id[0];
            $style_id = $imploded_skin_style_id[1];
            if (!$this->checkDeletable($skin_id, $style_id, $message_stack)) {
                $all_deletable = false;
            }
        }
        if ($all_deletable) {
            foreach ($_POST['id'] as $skin_style_id) {
                $imploded_skin_style_id = explode(":", $skin_style_id);
                $skin_id = $imploded_skin_style_id[0];
                $style_id = $imploded_skin_style_id[1];
                $container = $this->skin_factory->skinStyleContainerFromId($skin_id);
                $delete_form_table->addStyle($container->getSkin(), $container->getSkin()->getStyle($style_id),
                    $container->getImagesSkinPath($style_id));
            }
            $this->tpl->setContent($delete_form_table->getDeleteStyleFormHTML());
        } else {
            $message_stack->prependMessage(new ilSystemStyleMessage($this->lng->txt("styles_not_deleted"),
                ilSystemStyleMessage::TYPE_ERROR));
            $message_stack->getUIComponentsMessages($this->f);
            $this->edit();
        }
    }

    protected function checkDeletable(
        string $skin_id,
        string $style_id,
        ilSystemStyleMessageStack $message_stack
    ) : bool {
        $passed = true;
        if (ilObjUser::_getNumberOfUsersForStyle($skin_id, $style_id) > 0) {
            $message_stack->addMessage(new ilSystemStyleMessage($style_id . ": " . $this->lng->txt("cant_delete_if_users_assigned"),
                ilSystemStyleMessage::TYPE_ERROR));
            $passed = false;
        }
        if (ilSystemStyleSettings::_lookupActivatedStyle($skin_id, $style_id) > 0) {
            $message_stack->addMessage(new ilSystemStyleMessage($style_id . ": " . $this->lng->txt("cant_delete_activated_style"),
                ilSystemStyleMessage::TYPE_ERROR));
            $passed = false;
        }
        if (ilSystemStyleSettings::getCurrentDefaultSkin() == $skin_id && ilSystemStyleSettings::getCurrentDefaultSkin() == $style_id) {
            $message_stack->addMessage(new ilSystemStyleMessage($style_id . ": " . $this->lng->txt("cant_delete_default_style"),
                ilSystemStyleMessage::TYPE_ERROR));
            $passed = false;
        }

        if ($this->skin_factory->skinStyleContainerFromId($skin_id)->getSkin()->getSubstylesOfStyle($style_id)) {
            $message_stack->addMessage(new ilSystemStyleMessage($style_id . ": " . $this->lng->txt("cant_delete_style_with_substyles"),
                ilSystemStyleMessage::TYPE_ERROR));
            $passed = false;
        }
        return $passed;
    }

    protected function confirmDelete() : void
    {
        $message_stack = new ilSystemStyleMessageStack();

        foreach ($_POST as $key => $skin_style_id) {
            if (is_string($skin_style_id) && strpos($key, 'style') !== false) {
                try {
                    $imploded_skin_style_id = explode(":", $skin_style_id);
                    $container = $this->skin_factory->skinStyleContainerFromId($imploded_skin_style_id[0],
                        $message_stack);
                    $syle = $container->getSkin()->getStyle($imploded_skin_style_id[1]);
                    $container->deleteStyle($syle);
                    if (!$container->getSkin()->hasStyles()) {
                        $container->delete();
                    }
                } catch (Exception $e) {
                    $message_stack->addMessage(new ilSystemStyleMessage($e->getMessage(),
                        ilSystemStyleMessage::TYPE_ERROR));
                }
            }
        }
        $message_stack->getUIComponentsMessages($this->f);
        $this->ctrl->redirect($this);
    }

    /**
     *
     */
    protected function importStyle()
    {
        $form = $this->importSystemStyleForm();

        if ($form->checkInput()) {
            $message_stack = new ilSystemStyleMessageStack();
            $imported_container = $this->skin_factory->skinStyleContainerFromZip($_POST['importfile']['tmp_name'],
                $_POST['importfile']['name'], $message_stack);
            $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'skin_id',
                $imported_container->getSkin()->getId());
            $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'style_id',
                $imported_container->getSkin()->getDefaultStyle()->getId());
            $message_stack->addMessage(new ilSystemStyleMessage($this->lng->txt("style_imported") . " " . $imported_container->getSkinDirectory(),
                ilSystemStyleMessage::TYPE_SUCCESS));

            $message_stack->getUIComponentsMessages($this->f);
            $this->ctrl->redirectByClass("ilSystemStyleSettingsGUI");
        }

        // display only this form to correct input
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    protected function export() : void
    {
        $skin_id = $_GET["skin_id"];
        $container = $this->skin_factory->skinStyleContainerFromId($skin_id);
        try {
            $container->export();
        } catch (Exception $e) {
            ilUtil::sendFailure($this->lng->txt("zip_export_failed") . " " . $e->getMessage());
        }
    }

    /**
     *
     */
    protected function addSubStyle() : void
    {
        $this->tabs->clearTargets();
        /**
         * Since clearTargets also clears the help screen ids
         */
        $this->help->setScreenIdComponent("sty");
        $this->help->setScreenId("system_styles");
        $this->help->setSubScreenId("create_sub");

        $form = $this->addSubStyleForms();

        $this->tpl->setContent($form->getHTML());
    }

    protected function addSubStyleForms() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("sty_create_new_system_sub_style"));

        $ti = new ilTextInputGUI($this->lng->txt("sub_style_id"), "sub_style_id");
        $ti->setInfo($this->lng->txt("sub_style_id_description"));
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);

        $ti = new ilTextInputGUI($this->lng->txt("sub_style_name"), "sub_style_name");
        $ti->setInfo($this->lng->txt("sub_style_name_description"));
        $ti->setMaxLength(128);
        $ti->setSize(40);
        $ti->setRequired(true);
        $form->addItem($ti);

        // source
        $ti = new ilSelectInputGUI($this->lng->txt("parent"), "parent_style");
        $ti->setRequired(true);
        $ti->setInfo($this->lng->txt("sub_style_parent_style_description"));
        $styles = ilStyleDefinition::getAllSkinStyles();
        $options = array();
        foreach ($styles as $id => $style) {
            $system_style_conf = new ilSystemStyleConfig();
            if ($style["skin_id"] != $system_style_conf->getDefaultSkinId() && !$style["substyle_of"]) {
                $options[$id] = $style['title'];
            }
        }
        $ti->setOptions($options);

        $form->addItem($ti);
        $form->addCommandButton("saveNewSubStyle", $this->lng->txt("save"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));

        return $form;
    }

    protected function saveNewSubStyle() : void
    {
        $form = $this->addSubStyleForms();

        if ($form->checkInput()) {
            try {
                $imploded_parent_skin_style_id = explode(":", $_POST['parent_style']);
                $parent_skin_id = $imploded_parent_skin_style_id[0];
                $parent_style_id = $imploded_parent_skin_style_id[1];

                $container = $this->skin_factory->skinStyleContainerFromId($parent_skin_id);

                if (array_key_exists($_POST['sub_style_id'],
                    $container->getSkin()->getSubstylesOfStyle($parent_style_id))) {
                    throw new ilSystemStyleException(ilSystemStyleException::SUBSTYLE_ASSIGNMENT_EXISTS,
                        $_POST['sub_style_id']);
                }

                $sub_style_id = $_POST['sub_style_id'];

                $style = new ilSkinStyle($_POST['sub_style_id'], $_POST['sub_style_name']);
                $style->setSubstyleOf($parent_style_id);
                $container->addStyle($style);

                $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'skin_id', $parent_skin_id);
                $this->ctrl->setParameterByClass('ilSystemStyleSettingsGUI', 'style_id', $sub_style_id);
                ilUtil::sendSuccess($this->lng->txt("msg_sub_style_created"), true);
                $this->ctrl->redirectByClass("ilSystemStyleSettingsGUI");
            } catch (ilSystemStyleException $e) {
                ilUtil::sendFailure($e->getMessage(), true);
            }
        }

        // display only this form to correct input
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    public function isReadOnly() : bool
    {
        return $this->read_only;
    }

    public function setReadOnly($read_only) : void
    {
        $this->read_only = $read_only;
    }

    public function isManagementEnabled() : bool
    {
        return $this->management_enabled;
    }

    public function setManagementEnabled(bool $management_enabled)
    {
        $this->management_enabled = $management_enabled;
    }
}

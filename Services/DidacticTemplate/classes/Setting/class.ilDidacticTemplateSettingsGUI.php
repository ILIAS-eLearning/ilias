<?php declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use Psr\Http\Message\RequestInterface;
use ILIAS\DI\Container;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Refinery\Factory;
use ILIAS\FileUpload\FileUpload;

/**
 * Settings for a single didactic template
 * @author            Stefan Meyer <meyer@leifos.com>
 * @ingroup           ServicesDidacticTemplate
 * @ilCtrl_IsCalledBy ilDidacticTemplateSettingsGUI: ilObjRoleFolderGUI
 * @ilCtrl_Calls      ilDidacticTemplateSettingsGUI: ilMultilingualismGUI, ilPropertyFormGUI
 */
class ilDidacticTemplateSettingsGUI
{
    private ilLogger $logger;
    private ilObjectGUI $parent_object;
    private ?ilDidacticTemplateSetting $setting = null;
    private Container $dic;
    private ilLanguage $lng;
    private ilRbacSystem $rbacsystem;
    private ilCtrl $ctrl;
    private ilAccessHandler $access;
    private ilToolbarGUI $toolbar;
    private ilObjectDefinition $objDefinition;
    private RequestInterface $request;
    private GlobalHttpState $http;
    private Factory $refinery;
    private ilGlobalTemplateInterface $tpl;
    private ilTabsGUI $tabs;
    private FileUpload $upload;

    private int $ref_id;

    public function __construct(ilObjectGUI $a_parent_obj)
    {
        global $DIC;
        $this->parent_object = $a_parent_obj;
        $this->lng = $DIC->language();
        $this->rbacsystem = $DIC->rbac()->system();
        $this->ctrl = $DIC->ctrl();
        $this->objDefinition = $DIC['objDefinition'];
        $this->request = $DIC->http()->request();
        $this->access = $DIC->access();
        $this->toolbar = $DIC->toolbar();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->logger = $DIC->logger()->otpl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        $this->upload = $DIC->upload();

    }

    protected function initReferenceFromRequest() : void
    {
        if ($this->http->wrapper()->query()->has('ref_id')) {
            $this->ref_id = $this->http->wrapper()->query()->retrieve(
                'ref_id',
                $this->refinery->kindlyTo()->int()
            );
        }
    }

    /**
     * transforms selected tpls from post to SplFixedArray
     */
    protected function initTemplatesFromRequest() : SplFixedArray
    {
        if ($this->http->wrapper()->post()->has('tpls')) {
            return SplFixedArray::fromArray(
                $this->http->wrapper()->post()->retrieve(
                    'tpls',
                    $this->refinery->kindlyTo()->listOf(
                        $this->refinery->kindlyTo()->int()
                    )
                )
            );
        }
        return new SplFixedArray(0);
    }

    /**
     * @return ilDidacticTemplateSetting
     */
    protected function initTemplateFromRequest() : ?ilDidacticTemplateSetting
    {
        if ($this->http->wrapper()->query()->has('tplid')) {
            $tpl_id = $this->http->wrapper()->query()->retrieve(
                'tplid',
                $this->refinery->kindlyTo()->int()
            );
            return $this->setting = new ilDidacticTemplateSetting($tpl_id);
        }
        return null;
    }

    public function executeCommand() : string
    {
        $this->initReferenceFromRequest();

        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case "ilpropertyformgui":
                $setting = $this->initTemplateFromRequest();
                if (!$setting instanceof ilDidacticTemplateSetting) {
                    $setting = new ilDidacticTemplateSetting();
                }
                $form = $this->initEditTemplate($setting);
                $this->ctrl->forwardCommand($form);
            // no break
            case 'ilmultilingualismgui':
                $setting = $this->initTemplateFromRequest();
                if (
                    !$this->access->checkAccess('write', '', $this->ref_id) ||
                    !$setting instanceof ilDidacticTemplateSetting ||
                    $setting->isAutoGenerated()) {
                    $this->ctrl->redirect($this, "overview");
                }
                $this->setEditTabs("settings_trans");
                $transgui = new ilMultilingualismGUI($this->setting->getId(), 'dtpl');
                $defaultl = $this->setting->getTranslationObject()->getDefaultLanguage();
                $transgui->setStartValues(
                    $this->setting->getPresentationTitle($defaultl),
                    $this->setting->getPresentationDescription($defaultl)
                );
                $this->ctrl->forwardCommand($transgui);
                break;
            default:
                if (!$cmd) {
                    $cmd = 'overview';
                }
                $this->$cmd();
                break;
        }
        return '';
    }

    protected function overview() : void
    {
        if ($this->rbacsystem->checkAccess('write', $this->ref_id)) {
            $this->toolbar->addButton(
                $this->lng->txt('didactic_import_btn'),
                $this->ctrl->getLinkTarget($this, 'showImportForm')
            );
        }

        $filter = new ilDidacticTemplateSettingsTableFilter($this->ctrl->getFormAction($this, 'overview'));
        $filter->init();

        $table = new ilDidacticTemplateSettingsTableGUI($this, 'overview', $this->ref_id);
        $table->init();
        $table->parse($filter);

        $this->tpl->setContent(
            $filter->render() . '' . $table->getHTML()
        );
    }

    public function applyFilter() : void
    {
        $table = new ilDidacticTemplateSettingsTableGUI($this, 'overview', $this->ref_id);
        $table->init();
        $table->resetOffset();
        $table->writeFilterToSession();
        $this->overview();
    }

    public function resetFilter() : void
    {
        $table = new ilDidacticTemplateSettingsTableGUI($this, 'overview', $this->ref_id);
        $table->init();
        $table->resetOffset();
        $table->resetFilter();
        $this->overview();
    }

    protected function showImportForm(ilPropertyFormGUI $form = null) : void
    {
        $setting = $this->initTemplateFromRequest();
        if ($setting instanceof ilDidacticTemplateSetting) {
            $this->setEditTabs('import');
        } else {
            $this->tabs->clearTargets();
            $this->tabs->setBackTarget(
                $this->lng->txt('didactic_back_to_overview'),
                $this->ctrl->getLinkTarget($this, 'overview')
            );
        }

        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->createImportForm();
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function createImportForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setShowTopButtons(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('didactic_import_table_title'));
        $form->addCommandButton('importTemplate', $this->lng->txt('import'));
        $form->addCommandButton('overview', $this->lng->txt('cancel'));

        $file = new ilFileInputGUI($this->lng->txt('import_file'), 'file');
        $file->setSuffixes(['xml']);
        $file->setRequired(true);
        $form->addItem($file);

        $icon = new ilImageFileInputGUI($this->lng->txt('icon'), 'icon');
        $icon->setAllowDeletion(false);
        $icon->setSuffixes(['svg']);
        $icon->setInfo($this->lng->txt('didactic_icon_info'));
        $form->addItem($icon);

        $created = true;

        return $form;
    }

    protected function importTemplate() : void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }

        $setting = $this->initTemplateFromRequest();
        if ($setting instanceof ilDidacticTemplateSetting) {
            $form = $this->editImportForm();
        } else {
            $form = $this->createImportForm();
        }

        if (!$form->checkInput()) {
            ilUtil::sendFailure($this->lng->txt('err_check_input'));
            $form->setValuesByPost();

            if ($setting instanceof ilDidacticTemplateSetting) {
                $this->showEditImportForm($form);
            } else {
                $this->showImportForm($form);
            }
            return;
        }

        // Do import
        $import = new ilDidacticTemplateImport(ilDidacticTemplateImport::IMPORT_FILE);

        $file = $form->getInput('file');
        $tmp = ilFileUtils::ilTempnam() . '.xml';

        // move uploaded file
        ilFileUtils::moveUploadedFile(
            $file['tmp_name'],
            $file['name'],
            $tmp
        );
        $import->setInputFile($tmp);

        try {
            $settings = $import->import();
            if ($setting instanceof ilDidacticTemplateSetting) {
                $this->editImport($settings);
            } else {
                if ($settings->hasIconSupport($this->objDefinition)) {
                    $settings->getIconHandler()->handleUpload($this->upload, $_FILES['icon']['tmp_name']);
                }
            }
        } catch (ilDidacticTemplateImportException $e) {
            $this->logger->error('Import failed with message: ' . $e->getMessage());
            ilUtil::sendFailure($this->lng->txt('didactic_import_failed') . ': ' . $e->getMessage());
        }

        ilUtil::sendSuccess($this->lng->txt('didactic_import_success'), true);

        if ($setting instanceof ilDidacticTemplateSetting) {
            $this->ctrl->redirect($this, 'editTemplate');
        } else {
            $this->ctrl->redirect($this, 'overview');
        }
    }

    protected function editTemplate(ilPropertyFormGUI $form = null) : void
    {
        $setting = $this->initTemplateFromRequest();
        if (!$setting instanceof ilDidacticTemplateSetting) {
            ilUtil::sendFailure($this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
        }
        $this->setEditTabs("edit");
        $this->ctrl->saveParameter($this, 'tplid');
        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->initEditTemplate($this->setting);
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function updateTemplate() : void
    {
        $setting = $this->initTemplateFromRequest();
        $this->ctrl->saveParameter($this, 'tplid');

        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }

        $form = $this->initEditTemplate($this->setting);

        if ($form->checkInput()) {
            $tmp_file = $_FILES['icon']['tmp_name'];
            $upload_element = $form->getItemByPostVar('icon');
            if (
                (strlen($tmp_file) || (!strlen($tmp_file) && $this->setting->getIconIdentifier())) &&
                !$this->objDefinition->isContainer($form->getInput('type')) &&
                !$upload_element->getDeletionFlag()
            ) {
                $form->getItemByPostVar('icon')->setAlert($this->lng->txt('didactic_icon_error'));
                $this->handleUpdateFailure($form);
                return;
            }
            //change default entries if translation is active
            if (count($lang = $this->setting->getTranslationObject()->getLanguages())) {
                $this->setting->getTranslationObject()->setDefaultTitle($form->getInput('title'));
                $this->setting->getTranslationObject()->setDefaultDescription($form->getInput('description'));
                $this->setting->getTranslationObject()->save();
            }

            if (!$this->setting->isAutoGenerated()) {
                $this->setting->setTitle($form->getInput('title'));
                $this->setting->setDescription($form->getInput('description'));
            }

            $this->setting->setInfo($form->getInput('info'));
            $this->setting->enable((bool) $form->getInput('enable'));

            if (!$this->setting->isAutoGenerated()) {
                $this->setting->setAssignments(array($form->getInput('type')));
            }

            if ($form->getInput('local_template') && count($form->getInput('effective_from')) > 0) {
                $this->setting->setEffectiveFrom($form->getInput('effective_from'));
            } else {
                $this->setting->setEffectiveFrom(array());
            }

            $this->setting->setExclusive((bool) $form->getInput('exclusive_template'));

            $this->setting->update();

            $upload = $form->getItemByPostVar('icon');
            if ($upload->getDeletionFlag()) {
                $this->setting->getIconHandler()->delete();
            }
            $this->setting->getIconHandler()->handleUpload($this->upload, $_FILES['icon']['tmp_name']);
            ilUtil::sendSuccess($this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'overview');
        }
        $this->handleUpdateFailure($form);
    }

    protected function handleUpdateFailure(ilPropertyFormGUI $form) : void
    {
        ilUtil::sendFailure($this->lng->txt('err_check_input'));
        $form->setValuesByPost();
        $this->editTemplate($form);
    }

    protected function initEditTemplate(ilDidacticTemplateSetting $set) : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setShowTopButtons(false);
        $form->setFormAction($this->ctrl->getFormAction($this, 'updateTemplate'));
        $form->setTitle($this->lng->txt('didactic_edit_tpl'));
        $form->addCommandButton('updateTemplate', $this->lng->txt('save'));
        $form->addCommandButton('overview', $this->lng->txt('cancel'));

        // title
        $title = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $title->setSize(40);
        $title->setMaxLength(64);
        $title->setRequired(true);
        //use presentation title if autogenerated is set
        $title->setDisabled($set->isAutoGenerated());

        $def = [];
        if (!$set->isAutoGenerated()) {
            $trans = $set->getTranslations();
            $def = $trans[0]; // default

            if (sizeof($trans) > 1) {
                $languages = ilMDLanguageItem::_getLanguages();
                $title->setInfo($this->lng->txt("language") . ": " . $languages[$def["lang_code"]] .
                    ' <a href="' . $this->ctrl->getLinkTargetByClass("ilmultilingualismgui", "listTranslations") .
                    '">&raquo; ' . $this->lng->txt("more_translations") . '</a>');
            }
        }

        if ($set->isAutoGenerated()) {
            $title->setValue($set->getPresentationTitle());
        } elseif (isset($def['title'])) {
            $title->setValue($def["title"]);
        }

        $form->addItem($title);

        // desc
        $desc = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
        //use presentation title if autogenerated is set
        if ($set->isAutoGenerated()) {
            $desc->setValue($set->getPresentationDescription());
        } elseif (isset($def['description'])) {
            $desc->setValue($def["description"]);
        }
        $desc->setRows(3);
        $desc->setDisabled($set->isAutoGenerated());
        $form->addItem($desc);

        $icon = new ilImageFileInputGUI($this->lng->txt('didactic_icon'), 'icon');
        $icon->setImage($set->getIconHandler()->getAbsolutePath());
        $icon->setInfo($this->lng->txt('didactic_icon_info'));
        $icon->setAllowDeletion(true);
        $icon->setSuffixes(['svg']);
        $form->addItem($icon);

        // info
        $info = new ilTextAreaInputGUI($this->lng->txt('didactic_install_info'), 'info');
        $info->setValue($set->getInfo());
        $info->setRows(6);
        $form->addItem($info);

        //activate
        $enable = new ilCheckboxInputGUI($this->lng->txt('active'), 'enable');
        $enable->setChecked($set->isEnabled());
        $enable->setRequired(true);
        $form->addItem($enable);

        // object type
        if (!$set->isAutoGenerated()) {
            $type = new ilSelectInputGUI($this->lng->txt('obj_type'), 'type');
            $type->setRequired(true);
            $type->setInfo($this->lng->txt('dtpl_obj_type_info'));
            $assigned = $set->getAssignments();
            $type->setValue(isset($assigned[0]) ? $assigned[0] : '');
            $subs = $this->objDefinition->getSubObjectsRecursively('root', false);
            $options = array();
            foreach (array_merge($subs, array('fold' => 1)) as $obj => $null) {
                ilLoggerFactory::getLogger('root')->dump($null);
                if ($this->objDefinition->isPlugin($obj)) {
                    $options[$obj] = ilObjectPlugin::lookupTxtById($obj, "obj_" . $obj);
                } elseif ($this->objDefinition->isAllowedInRepository($obj)) {
                    $options[$obj] = $this->lng->txt('obj_' . $obj);
                }
            }
            asort($options);

            $type->setOptions($options);
            $form->addItem($type);

            $lokal_templates = new ilCheckboxInputGUI($this->lng->txt("activate_local_didactic_template"),
                "local_template");
            $lokal_templates->setChecked(count($set->getEffectiveFrom()) > 0);
            $lokal_templates->setInfo($this->lng->txt("activate_local_didactic_template_info"));

            //effective from (multinode)

            $effrom = new ilRepositorySelector2InputGUI($this->lng->txt("effective_form"), "effective_from", true);
            //$effrom->setMulti(true);
            $white_list = [];
            foreach ($this->objDefinition->getAllRepositoryTypes() as $type) {
                if ($this->objDefinition->isContainer($type)) {
                    $white_list[] = $type;
                }
            }
            $effrom->getExplorerGUI()->setTypeWhiteList($white_list);
            $effrom->setValue($set->getEffectiveFrom());

            $lokal_templates->addSubItem($effrom);
            $form->addItem($lokal_templates);

            $excl = new ilCheckboxInputGUI($this->lng->txt("activate_exclusive_template"), "exclusive_template");
            $excl->setInfo($this->lng->txt("activate_exclusive_template_info"));
            $excl->setChecked($set->isExclusive());

            $form->addItem($excl);
        }
        return $form;
    }

    protected function copyTemplate() : void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }

        $setting = $this->initTemplateFromRequest();
        if (!$setting instanceof ilDidacticTemplateSetting) {
            ilUtil::sendFailure($this->lng->txt('select_one'));
            $this->ctrl->redirect($this, 'overview');
            return;
        }

        $copier = new ilDidacticTemplateCopier($setting->getId());
        $copier->start();

        ilUtil::sendSuccess($this->lng->txt('didactic_copy_suc_message'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    protected function exportTemplate() : void
    {
        $setting = $this->initTemplateFromRequest();
        if (!$setting instanceof ilDidacticTemplateSetting) {
            ilUtil::sendFailure($this->lng->txt('select_one'));
            $this->ctrl->redirect($this, 'overview');
            return;
        }
        $writer = new ilDidacticTemplateXmlWriter($setting->getId());
        $writer->write();

        ilUtil::deliverData(
            $writer->xmlDumpMem(true),
            $writer->getSetting()->getTitle() . '.xml',
            'application/xml'
        );
    }

    protected function confirmDelete() : void
    {
        $templates = $this->initTemplatesFromRequest();
        if (!count($templates)) {
            ilUtil::sendFailure($this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
            return;
        }

        $confirm = new ilConfirmationGUI();
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setConfirm($this->lng->txt('delete'), 'deleteTemplates');
        $confirm->setCancel($this->lng->txt('cancel'), 'overview');

        $forbidden = array();

        foreach ($templates as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);

            if (!$tpl->isAutoGenerated()) {
                $confirm->addItem('tpls[]', (string) $tpl->getId(), $tpl->getPresentationTitle());
            } else {
                $forbidden[] = $tpl->getId();
            }
        }

        if (count($forbidden) > 0 && count($templates) == 1) {
            ilUtil::sendFailure($this->lng->txt('didactic_cannot_delete_auto_generated'), true);
            $this->ctrl->redirect($this, "overview");
        } elseif (count($forbidden) > 0 && count($templates) > 1) {
            ilUtil::sendInfo($this->lng->txt('didactic_cannot_delete_auto_generated_confirmation'));
        }

        ilUtil::sendQuestion($this->lng->txt('didactic_confirm_delete_msg'));
        $this->tpl->setContent($confirm->getHTML());
    }

    protected function deleteTemplates() : void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }
        $templates = $this->initTemplatesFromRequest();
        if (!$templates) {
            ilUtil::sendFailure($this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
            return;
        }

        foreach ($templates as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);
            $tpl->delete();
        }

        ilUtil::sendSuccess($this->lng->txt('didactic_delete_msg'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    protected function activateTemplates() : void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }
        $templates = $this->initTemplatesFromRequest();
        if (!count($templates)) {
            ilUtil::sendFailure($this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
            return;
        }

        foreach ($templates as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);
            $tpl->enable(true);
            $tpl->update();
        }

        ilUtil::sendSuccess($this->lng->txt('didactic_activated_msg'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    protected function deactivateTemplates() : void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }

        $templates = $this->initTemplatesFromRequest();
        if (!count($templates)) {
            ilUtil::sendFailure($this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
        }
        foreach ($templates as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);
            $tpl->enable(false);
            $tpl->update();
        }
        ilUtil::sendSuccess($this->lng->txt('didactic_deactivated_msg'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    protected function setEditTabs(string $a_tab_active = "edit") : void
    {
        $this->tabs->clearTargets();
        $this->tabs->setBackTarget(
            $this->lng->txt('didactic_back_to_overview'),
            $this->ctrl->getLinkTarget($this, 'overview')
        );
        $this->ctrl->saveParameter($this, "tplid");

        if (!$this->setting->isAutoGenerated()) {
            $this->tabs->addTab('edit', $this->lng->txt('settings'), $this->ctrl->getLinkTarget($this, 'editTemplate'));
            $this->tabs->addTab('import', $this->lng->txt('import'),
                $this->ctrl->getLinkTarget($this, 'showEditImportForm'));

            if (in_array($a_tab_active, array('edit', 'settings_trans'))) {
                $this->tabs->addSubTab('edit', $this->lng->txt('settings'),
                    $this->ctrl->getLinkTarget($this, 'editTemplate'));
                $this->tabs->addSubTab('settings_trans', $this->lng->txt("obj_multilinguality"),
                    $this->ctrl->getLinkTargetByClass(array("ilmultilingualismgui"), 'listTranslations'));
                $this->tabs->setTabActive('edit');
                $this->tabs->setSubTabActive($a_tab_active);
            } else {
                $this->tabs->setTabActive($a_tab_active);
            }
        }
    }

    public function showEditImportForm(ilPropertyFormGUI $form = null) : void
    {
        $this->initTemplateFromRequest();
        $this->setEditTabs("import");
        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->editImportForm();
        }
        $this->tpl->setContent($form->getHTML());
    }

    public function editImportForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setShowTopButtons(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('didactic_import_table_title'));
        $form->addCommandButton('importTemplate', $this->lng->txt('import'));
        $form->addCommandButton('overview', $this->lng->txt('cancel'));

        $file = new ilFileInputGUI($this->lng->txt('didactic_template_update_import'), 'file');
        $file->setRequired(true);
        $file->setSuffixes(['xml']);
        $file->setInfo($this->lng->txt('didactic_template_update_import_info'));
        $form->addItem($file);

        return $form;
    }

    public function editImport(ilDidacticTemplateSetting $a_settings) : void
    {
        ilDidacticTemplateObjSettings::transferAutoGenerateStatus($a_settings->getId(), $a_settings->getId());
        $assignments = ilDidacticTemplateObjSettings::getAssignmentsByTemplateID($a_settings->getId());
        $a_settings->delete();
        foreach ($assignments as $obj) {
            ilDidacticTemplateObjSettings::assignTemplate($obj["ref_id"], $obj["obj_id"], $a_settings->getId());
        }
        $this->ctrl->setParameter($this, "tplid", $a_settings->getId());
    }
}

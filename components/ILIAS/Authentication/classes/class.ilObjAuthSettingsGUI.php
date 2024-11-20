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

use ILIAS\Style\Content\GUIService;
use ILIAS\components\Authentication\Pages\AuthPageEditorContext;

/**
 * @ilCtrl_Calls ilObjAuthSettingsGUI: ilPermissionGUI, ilRegistrationSettingsGUI, ilLDAPSettingsGUI
 * @ilCtrl_Calls ilObjAuthSettingsGUI: ilAuthShibbolethSettingsGUI, ilCASSettingsGUI
 * @ilCtrl_Calls ilObjAuthSettingsGUI: ilSamlSettingsGUI, ilOpenIdConnectSettingsGUI
 * @ilCtrl_Calls ilObjAuthSettingsGUI: ilObjectContentStyleSettingsGUI
 */
class ilObjAuthSettingsGUI extends ilObjectGUI
{
    private const PROP_AUTH_MODE_KIND = 'kind';
    private const PROP_AUTH_MODE_SEQUENCE = 'sequence';

    private ilLogger $logger;
    private ILIAS\HTTP\GlobalHttpState $http;

    private GUIService $content_style_gui;

    public function __construct($a_data, int $a_id, bool $a_call_by_reference, bool $a_prepare_output = true)
    {
        $this->type = 'auth';
        parent::__construct($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

        global $DIC;
        $this->logger = $DIC->logger()->auth();

        $this->http = $DIC->http();

        $this->lng->loadLanguageModule('registration');
        $this->lng->loadLanguageModule('auth');
        $this->lng->loadLanguageModule('content');
        $this->content_style_gui = $DIC->contentStyle()->gui();
    }

    public function viewObject(): void
    {
        $this->authSettingsObject();
    }

    private function authSettingsObject(
        ?ILIAS\UI\Component\Input\Container\Form\Form $auth_mode_determination_form = null,
        ?ILIAS\UI\Component\Input\Container\Form\Form $registration_role_mapping_form = null
    ): void {
        if (!$this->rbac_system->checkAccess('visible,read', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        $this->tabs_gui->setTabActive('authentication_settings');
        $this->setSubTabs('authSettings');
        $this->tabs_gui->setSubTabActive('auth_settings');

        $generalSettingsTpl = new ilTemplate('tpl.auth_general.html', true, true, 'components/ILIAS/Authentication');

        $generalSettingsTpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this));

        $generalSettingsTpl->setVariable('TXT_AUTH_MODE', $this->lng->txt('auth_mode'));
        $generalSettingsTpl->setVariable('TXT_AUTH_DEFAULT', $this->lng->txt('default'));
        $generalSettingsTpl->setVariable('TXT_AUTH_ACTIVE', $this->lng->txt('active'));
        $generalSettingsTpl->setVariable('TXT_AUTH_NUM_USERS', $this->lng->txt('num_users'));

        $generalSettingsTpl->setVariable('TXT_LOCAL', $this->lng->txt('auth_local'));
        $generalSettingsTpl->setVariable('TXT_LDAP', $this->lng->txt('auth_ldap'));
        $generalSettingsTpl->setVariable('TXT_SHIB', $this->lng->txt('auth_shib'));

        $generalSettingsTpl->setVariable('TXT_CAS', $this->lng->txt('auth_cas'));

        $generalSettingsTpl->setVariable('TXT_SCRIPT', $this->lng->txt('auth_script'));

        $generalSettingsTpl->setVariable('TXT_APACHE', $this->lng->txt('auth_apache'));

        $auth_cnt = ilObjUser::_getNumberOfUsersPerAuthMode();
        $auth_modes = ilAuthUtils::_getAllAuthModes();
        $valid_modes = [
            ilAuthUtils::AUTH_LOCAL,
            ilAuthUtils::AUTH_LDAP,
            ilAuthUtils::AUTH_SHIBBOLETH,
            ilAuthUtils::AUTH_SAML,
            ilAuthUtils::AUTH_CAS,
            ilAuthUtils::AUTH_APACHE,
            ilAuthUtils::AUTH_OPENID_CONNECT
        ];

        $icon_ok = $this->ui_renderer->render(
            $this->ui_factory->symbol()->icon()->custom(
                ilUtil::getImagePath('standard/icon_ok.svg'),
                $this->lng->txt('enabled')
            )
        );
        $icon_not_ok = $this->ui_renderer->render(
            $this->ui_factory->symbol()->icon()->custom(
                ilUtil::getImagePath('standard/icon_not_ok.svg'),
                $this->lng->txt('disabled')
            )
        );

        $this->logger->debug(print_r($auth_modes, true));
        foreach ($auth_modes as $mode => $mode_name) {
            if (!in_array($mode, $valid_modes, true) && !ilLDAPServer::isAuthModeLDAP(
                (string) $mode
            ) && !ilSamlIdp::isAuthModeSaml((string) $mode)) {
                continue;
            }

            $generalSettingsTpl->setCurrentBlock('auth_mode');

            if (ilLDAPServer::isAuthModeLDAP((string) $mode)) {
                $server = ilLDAPServer::getInstanceByServerId(ilLDAPServer::getServerIdByAuthMode($mode));
                $generalSettingsTpl->setVariable('AUTH_NAME', $server->getName());
                $generalSettingsTpl->setVariable('AUTH_ACTIVE', $server->isActive() ? $icon_ok : $icon_not_ok);
            } elseif (ilSamlIdp::isAuthModeSaml((string) $mode)) {
                $idp = ilSamlIdp::getInstanceByIdpId(ilSamlIdp::getIdpIdByAuthMode($mode));
                $generalSettingsTpl->setVariable('AUTH_NAME', $idp->getEntityId());
                $generalSettingsTpl->setVariable('AUTH_ACTIVE', $idp->isActive() ? $icon_ok : $icon_not_ok);
            } elseif ($mode === ilAuthUtils::AUTH_OPENID_CONNECT) {
                $generalSettingsTpl->setVariable('AUTH_NAME', $this->lng->txt('auth_' . $mode_name));
                $generalSettingsTpl->setVariable(
                    'AUTH_ACTIVE',
                    ilOpenIdConnectSettings::getInstance()->getActive() ? $icon_ok : $icon_not_ok
                );
            } else {
                $generalSettingsTpl->setVariable('AUTH_NAME', $this->lng->txt('auth_' . $mode_name));
                $generalSettingsTpl->setVariable(
                    'AUTH_ACTIVE',
                    $this->ilias->getSetting(
                        $mode_name . '_active'
                    ) || (int) $mode === ilAuthUtils::AUTH_LOCAL ? $icon_ok : $icon_not_ok
                );
            }

            $auth_cnt_mode = $auth_cnt[$mode_name] ?? 0;
            if ($this->settings->get('auth_mode') === (string) $mode) {
                $generalSettingsTpl->setVariable('AUTH_CHECKED', 'checked="checked"');
                $auth_cnt_default = $auth_cnt['default'] ?? 0;
                $generalSettingsTpl->setVariable(
                    'AUTH_USER_NUM',
                    ((int) $auth_cnt_mode + $auth_cnt_default) . ' (' . $this->lng->txt('auth_per_default') .
                    ': ' . $auth_cnt_default . ')'
                );
            } else {
                $generalSettingsTpl->setVariable(
                    'AUTH_USER_NUM',
                    (int) $auth_cnt_mode
                );
            }
            $generalSettingsTpl->setVariable('AUTH_ID', $mode_name);
            $generalSettingsTpl->setVariable('AUTH_VAL', $mode);
            $generalSettingsTpl->parseCurrentBlock();
        }

        $generalSettingsTpl->setVariable('TXT_CONFIGURE', $this->lng->txt('auth_configure'));

        if ($this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $generalSettingsTpl->setVariable('TXT_AUTH_REMARK', $this->lng->txt('auth_remark_non_local_auth'));
            $generalSettingsTpl->setCurrentBlock('auth_mode_submit');
            $generalSettingsTpl->setVariable('TXT_SUBMIT', $this->lng->txt('save'));
            $generalSettingsTpl->setVariable('CMD_SUBMIT', 'setAuthMode');
            $generalSettingsTpl->parseCurrentBlock();
        }

        $page_content = [
            $this->ui_factory->panel()->standard(
                $this->lng->txt('auth_select'),
                $this->ui_factory->legacy($generalSettingsTpl->get()),
            )
        ];

        $auth_mode_determination_form = $auth_mode_determination_form ?? $this->buildAuthModeDeterminationForm();
        if ($auth_mode_determination_form !== null) {
            $page_content[] = $this->ui_factory->panel()->standard(
                $this->lng->txt('auth_auth_mode_determination'),
                $auth_mode_determination_form
            );
        }

        $page_content[] = $this->ui_factory->panel()->standard(
            $this->lng->txt('auth_active_roles'),
            $registration_role_mapping_form ?? $this->buildRegistrationRoleMappingForm()
        );

        $this->tpl->setContent(
            $this->ui_renderer->render($page_content)
        );
    }

    private function buildRegistrationRoleMappingForm(): ILIAS\UI\Component\Input\Container\Form\Form
    {
        $disabled_fields = !$this->rbac_system->checkAccess('write', $this->object->getRefId());

        $fields = [];
        $reg_roles = ilObjRole::_lookupRegisterAllowed();

        $excluded_auth_names = ['default', 'cas', 'saml', 'shibboleth', 'ldap', 'apache', 'ecs', 'openid'];
        // do not list auth modes with external login screen
        // even not default, because it can easily be set to
        // a non-working auth mode
        $active_auth_modes = array_filter(
            ilAuthUtils::_getActiveAuthModes(),
            static fn(string $auth_name): bool => in_array($auth_name, $excluded_auth_names, true),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($reg_roles as $role) {
            $options = [];
            $value = null;
            foreach ($active_auth_modes as $auth_name => $auth_key) {
                if ($auth_name === 'default') {
                    $name = $this->lng->txt('auth_' . $auth_name) . ' (' . $this->lng->txt(
                        'auth_' . ilAuthUtils::_getAuthModeName($auth_key)
                    ) . ')';
                } elseif ($id = ilLDAPServer::getServerIdByAuthMode((string) $auth_key)) {
                    $server = ilLDAPServer::getInstanceByServerId($id);
                    $name = $server->getName();
                } elseif ($id = ilSamlIdp::getIdpIdByAuthMode((string) $auth_key)) {
                    $idp = ilSamlIdp::getInstanceByIdpId($id);
                    $name = $idp->getEntityId();
                } else {
                    $name = $this->lng->txt('auth_' . $auth_name);
                }

                $options[$auth_name] = $name;

                if ($role['auth_mode'] === $auth_name) {
                    $value = $auth_name;
                }
            }

            if ($options === []) {
                continue;
            }

            $value = $value ?? ilAuthUtils::AUTH_LOCAL;

            $fields['r_' . $role['id']] = $this->ui_factory
                ->input()
                ->field()
                ->select(
                    $role['title'],
                    $options,
                    $this->lng->txt('auth_role_auth_mode')
                )
                ->withRequired(true)
                ->withValue($value)
                ->withDedicatedName('r_' . $role['id'])
                ->withDisabled($disabled_fields);
        }

        return $this->ui_factory
            ->input()
            ->container()
            ->form()
            ->standard($this->ctrl->getFormAction($this, 'updateRegistrationRoleMapping'), $fields)
            ->withDedicatedName('registration_role_mapping');
    }

    private function updateRegistrationRoleMappingObject(): void
    {
        if (!$this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        $form = $this->buildRegistrationRoleMappingForm();
        $form_valid = false;
        $form_data = null;
        if ($this->http->request()->getMethod() === 'POST') {
            $form = $form->withRequest($this->http->request());
            $form_data = $form->getData();
            $form_valid = $form_data !== null;
        }

        if (!$form_valid) {
            $this->tpl->setOnScreenMessage(
                $this->tpl::MESSAGE_TYPE_FAILURE,
                $this->lng->txt('err_wrong_login')
            );
            $this->authSettingsObject(null, $form);
            return;
        }

        $f_object = [];
        foreach ($form_data as $role_id => $auth_mode) {
            $f_object[substr($role_id, 2)] = $auth_mode;
        }
        ilObjRole::_updateAuthMode($f_object);

        $this->tpl->setOnScreenMessage(
            $this->tpl::MESSAGE_TYPE_SUCCESS,
            $this->lng->txt('auth_mode_roles_changed'),
            true
        );
        $this->ctrl->redirect($this, 'authSettings');
    }

    private function buildAuthModeDeterminationForm(): ?ILIAS\UI\Component\Input\Container\Form\Form
    {
        $det = ilAuthModeDetermination::_getInstance();
        if ($det->getCountActiveAuthModes() <= 1) {
            return null;
        }

        $disabled_fields = !$this->rbac_system->checkAccess('write', $this->object->getRefId());

        $automatic_options = [];
        $counter = 1;
        $auth_sequenced = $det->getAuthModeSequence();
        foreach ($auth_sequenced as $auth_mode) {
            $text = '';
            switch ($auth_mode) {
                case ilLDAPServer::isAuthModeLDAP((string) $auth_mode):
                    $auth_id = ilLDAPServer::getServerIdByAuthMode($auth_mode);
                    $server = ilLDAPServer::getInstanceByServerId($auth_id);
                    $text = $server->getName();
                    break;
                case ilAuthUtils::AUTH_LOCAL:
                    $text = $this->lng->txt('auth_local');
                    break;
                case ilAuthUtils::AUTH_SOAP:
                    $text = $this->lng->txt('auth_soap');
                    break;
                case ilAuthUtils::AUTH_APACHE:
                    $text = $this->lng->txt('auth_apache');
                    break;
                default:
                    foreach (ilAuthUtils::getAuthPlugins() as $pl) {
                        $option = $pl->getMultipleAuthModeOptions($auth_mode);
                        $text = $option[$auth_mode]['txt'];
                    }
                    break;
            }

            $automatic_options['m' . $auth_mode] = $this->ui_factory
                ->input()
                ->field()
                ->numeric($text)
                ->withDedicatedName('m' . $auth_mode)
                ->withValue($counter++)
                ->withDisabled($disabled_fields);
        }

        $options = [
            (string) ilAuthModeDetermination::TYPE_MANUAL => $this->ui_factory
                ->input()
                ->field()
                ->group(
                    [],
                    $this->lng->txt('auth_by_user')
                )
                ->withDedicatedName((string) ilAuthModeDetermination::TYPE_MANUAL)
                ->withDisabled($disabled_fields),
            (string) ilAuthModeDetermination::TYPE_AUTOMATIC => $this->ui_factory
                ->input()
                ->field()
                ->group(
                    $automatic_options,
                    $this->lng->txt('auth_automatic')
                )
                ->withDedicatedName((string) ilAuthModeDetermination::TYPE_AUTOMATIC)
                ->withDisabled($disabled_fields)
        ];

        $sections = [
            self::PROP_AUTH_MODE_KIND => $this->ui_factory
                ->input()
                ->field()
                ->switchableGroup(
                    $options,
                    $this->lng->txt('auth_kind_determination'),
                    $this->lng->txt('auth_mode_determination_info')
                )
                ->withDedicatedName(self::PROP_AUTH_MODE_KIND)
                ->withValue((string) $det->getKind())
                ->withDisabled($disabled_fields)
                ->withRequired(true)
        ];

        return $this->ui_factory
            ->input()
            ->container()
            ->form()
            ->standard($this->ctrl->getFormAction($this, 'updateAuthModeDetermination'), $sections)
            ->withDedicatedName('auth_mode_determination')
            ->withAdditionalTransformation(
                $this->refinery->custom()->transformation(function ($value): array {
                    $auth_mode_kind = (int) ($value[self::PROP_AUTH_MODE_KIND][0] ?? ilAuthModeDetermination::TYPE_MANUAL);
                    $sequence = [];
                    if ($auth_mode_kind === ilAuthModeDetermination::TYPE_AUTOMATIC) {
                        $sequence = (array) ($value[self::PROP_AUTH_MODE_KIND][1] ?? []);
                    }

                    $merged_values = array_merge(
                        [
                            self::PROP_AUTH_MODE_KIND => $auth_mode_kind,
                        ],
                        [
                            self::PROP_AUTH_MODE_SEQUENCE => $sequence
                        ]
                    );

                    return $merged_values;
                })
            );
    }

    private function updateAuthModeDeterminationObject(): void
    {
        if (!$this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        $form = $this->buildAuthModeDeterminationForm();
        if ($form === null) {
            $this->authSettingsObject();
            return;
        }

        $form_valid = false;
        $form_data = null;
        if ($this->http->request()->getMethod() === 'POST') {
            $form = $form->withRequest($this->http->request());
            $form_data = $form->getData();
            $form_valid = $form_data !== null;
        }

        if (!$form_valid) {
            $this->tpl->setOnScreenMessage(
                $this->tpl::MESSAGE_TYPE_FAILURE,
                $this->lng->txt('err_wrong_login')
            );
            $this->authSettingsObject($form);
            return;
        }

        $det = ilAuthModeDetermination::_getInstance();
        $kind = (int) $form_data[self::PROP_AUTH_MODE_KIND];
        $det->setKind($kind);
        if ($kind === ilAuthModeDetermination::TYPE_AUTOMATIC) {
            $sequence = $form_data[self::PROP_AUTH_MODE_SEQUENCE];
            $this->logger->debug('pos mode:' . print_r($sequence, true));
            asort($sequence, SORT_NUMERIC);
            $this->logger->debug('pos mode:' . print_r($sequence, true));
            $counter = 0;
            $position = [];
            foreach (array_keys($sequence) as $auth_mode) {
                $position[$counter++] = substr($auth_mode, 1);
            }
            $this->logger->debug('position mode:' . print_r($position, true));
            $det->setAuthModeSequence($position);
        }
        $det->save();

        $this->tpl->setOnScreenMessage(
            $this->tpl::MESSAGE_TYPE_SUCCESS,
            $this->lng->txt('settings_saved'),
            true
        );
        $this->ctrl->redirect($this, 'authSettings');
    }

    public function cancelObject(): void
    {
        $this->ctrl->redirect($this, 'authSettings');
    }

    public function setAuthModeObject(): void
    {
        if (!$this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }
        $this->logger->debug('auth mode available:' . $this->request_wrapper->has('auth_mode'));

        if (!$this->http->wrapper()->post()->has('auth_mode')) {
            $this->ilias->raiseError($this->lng->txt('auth_err_no_mode_selected'), $this->ilias->error_obj->MESSAGE);
        }
        $new_auth_mode = $this->http->wrapper()->post()->retrieve('auth_mode', $this->refinery->to()->string());
        $this->logger->debug('auth mode:' . $new_auth_mode);
        $current_auth_mode = $this->settings->get('auth_mode', '');
        if ($new_auth_mode === $current_auth_mode) {
            $this->tpl->setOnScreenMessage(
                'info',
                $this->lng->txt('auth_mode') . ': ' . $this->getAuthModeTitle() . ' ' . $this->lng->txt(
                    'auth_mode_not_changed'
                ),
                true
            );
            $this->ctrl->redirect($this, 'authSettings');
        }

        switch ((int) $new_auth_mode) {
            case ilAuthUtils::AUTH_SAML:
                break;

                // @fix changed from AUTH_SHIB > is not defined
            case ilAuthUtils::AUTH_SHIBBOLETH:
                if ($this->object->checkAuthSHIB() !== true) {
                    $this->tpl->setOnScreenMessage('failure', $this->lng->txt('auth_shib_not_configured'), true);
                    ilUtil::redirect(
                        $this->getReturnLocation(
                            'authSettings',
                            $this->ctrl->getLinkTargetByClass(
                                ilAuthShibbolethSettingsGUI::class,
                                'settings',
                                '',
                                false,
                                false
                            )
                        )
                    );
                }
                break;

            case ilAuthUtils::AUTH_SCRIPT:
                if ($this->object->checkAuthScript() !== true) {
                    $this->tpl->setOnScreenMessage('failure', $this->lng->txt('auth_script_not_configured'), true);
                    ilUtil::redirect(
                        $this->getReturnLocation(
                            'authSettings',
                            $this->ctrl->getLinkTarget($this, 'editScript', '', false, false)
                        )
                    );
                }
                break;
        }

        $this->ilias->setSetting('auth_mode', $new_auth_mode);

        $this->tpl->setOnScreenMessage(
            'success',
            $this->lng->txt('auth_default_mode_changed_to') . ' ' . $this->getAuthModeTitle(),
            true
        );
        $this->ctrl->redirect($this, 'authSettings');
    }

    private function buildSOAPForm(
        string $submit_action
    ): \ILIAS\UI\Component\Input\Container\Form\Form {
        $role_list = $this->rbac_review->getRolesByFilter(2, $this->object->getId());
        $roles = [];

        foreach ($role_list as $role) {
            $roles[$role['obj_id']] = $role['title'];
        }

        $active = $this->ui_factory
            ->input()
            ->field()
            ->checkbox($this->lng->txt('active'))
            ->withValue((bool) $this->settings->get('soap_auth_active', ''));

        $server = $this->ui_factory
            ->input()
            ->field()
            ->text(
                $this->lng->txt('server'),
                $this->lng->txt('auth_soap_server_desc')
            )
            ->withMaxLength(256)
            ->withRequired(true)
            ->withValue($this->settings->get('soap_auth_server', ''));

        $port = $this->ui_factory
            ->input()
            ->field()
            ->numeric(
                $this->lng->txt('port'),
                $this->lng->txt('auth_soap_port_desc')
            )
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
            ->withAdditionalTransformation(
                $this->refinery->int()->isLessThan(65536)
            )
            ->withValue((int) $this->settings->get('soap_auth_port', '0'));

        $use_https = $this->ui_factory
            ->input()
            ->field()
            ->checkbox($this->lng->txt('auth_soap_use_https'))
            ->withValue((bool) $this->settings->get('soap_auth_use_https', ''));

        $uri = $this->ui_factory
            ->input()
            ->field()
            ->text(
                $this->lng->txt('uri'),
                $this->lng->txt('auth_soap_uri_desc')
            )
            ->withMaxLength(256)
            ->withValue($this->settings->get('soap_auth_uri', ''));

        $namespace = $this->ui_factory
            ->input()
            ->field()
            ->text(
                $this->lng->txt('auth_soap_namespace'),
                $this->lng->txt('auth_soap_namespace_desc')
            )
            ->withMaxLength(256)
            ->withValue($this->settings->get('soap_auth_namespace', ''));

        $dotnet = $this->ui_factory
            ->input()
            ->field()
            ->checkbox($this->lng->txt('auth_soap_use_dotnet'))
            ->withValue((bool) $this->settings->get('soap_auth_use_dotnet', ''));

        $createuser = $this->ui_factory
            ->input()
            ->field()
            ->checkbox(
                $this->lng->txt('auth_create_users'),
                $this->lng->txt('auth_soap_create_users_desc')
            )
            ->withValue((bool) $this->settings->get('soap_auth_create_users', ''));

        $sendmail = $this->ui_factory
            ->input()
            ->field()
            ->checkbox(
                $this->lng->txt('user_send_new_account_mail'),
                $this->lng->txt('auth_new_account_mail_desc')
            )
            ->withValue((bool) $this->settings->get('soap_auth_account_mail', ''));

        $defaultrole = $this->ui_factory
            ->input()
            ->field()
            ->select(
                $this->lng->txt('auth_user_default_role'),
                $roles,
                $this->lng->txt('auth_soap_user_default_role_desc')
            )
            ->withValue($this->settings->get('soap_auth_user_default_role', '4'))
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0));

        $allowlocal = $this->ui_factory
            ->input()
            ->field()
            ->checkbox(
                $this->lng->txt('auth_allow_local'),
                $this->lng->txt('auth_soap_allow_local_desc')
            )
            ->withValue((bool) $this->settings->get('soap_auth_user_default_role', ''));

        $form = $this->ui_factory->input()->container()->form()->standard(
            $submit_action,
            [
                'active' => $active,
                'server' => $server,
                'port' => $port,
                'use_https' => $use_https,
                'uri' => $uri,
                'namespace' => $namespace,
                'dotnet' => $dotnet,
                'createuser' => $createuser,
                'sendmail' => $sendmail,
                'defaultrole' => $defaultrole,
                'allowlocal' => $allowlocal
            ]
        );
        return $form;
    }

    private function buildSOAPTestForm(
        string $submit_action
    ): \ILIAS\UI\Component\Input\Container\Form\Form {
        $ext_uid = $this->ui_factory->input()->field()->text(
            'ext_uid'
        );
        $soap_pw = $this->ui_factory->input()->field()->text(
            'soap_pw'
        );
        $new_user = $this->ui_factory->input()->field()
                                     ->checkbox('new_user');
        return $this->ui_factory->input()->container()->form()->standard(
            $submit_action,
            [
                'ext_uid' => $ext_uid,
                'soap_pw' => $soap_pw,
                'new_user' => $new_user
            ]
        )->withSubmitLabel('Send');
    }

    public function editSOAPObject(): void
    {
        if (!$this->rbac_system->checkAccess('read', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        $soap_form = $this->buildSOAPForm($this->ctrl->getFormAction($this, 'saveSOAP'));
        $test_form = $this->buildSOAPTestForm($this->ctrl->getFormAction($this, 'testSoapAuthConnection'));

        $this->tabs_gui->setTabActive('auth_soap');
        $panel = $this->ui_factory->panel()->standard('SOAP', [$soap_form, $test_form]);
        $this->tpl->setContent($this->ui_renderer->render($panel));
    }

    public function testSoapAuthConnectionObject(): void
    {
        if (!$this->rbac_system->checkAccess('read', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        $soap_form = $this->buildSOAPForm($this->ctrl->getFormAction($this, 'saveSOAP'));
        $test_form = $this->buildSOAPTestForm($this->ctrl->getFormAction($this, 'testSoapAuthConnection'));
        $panel_content = [$soap_form, $test_form];
        if ($this->request->getMethod() === 'POST') {
            $test_form = $test_form->withRequest($this->request);
            $result = $test_form->getData();
            if (!is_null($result)) {
                $panel_content[] = $this->ui_factory->legacy(
                    ilSOAPAuth::testConnection($result['ext_uid'], $result['soap_pw'], $result['new_user'])
                );
            }
        }
        $this->tabs_gui->setTabActive('auth_soap');
        $panel = $this->ui_factory->panel()->standard('SOAP', $panel_content);
        $this->tpl->setContent($this->ui_renderer->render($panel));
    }

    public function saveSOAPObject(): void
    {
        if (!$this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        $soap_form = $this->buildSOAPForm($this->ctrl->getFormAction($this, 'saveSOAP'));
        $test_form = $this->buildSOAPTestForm($this->ctrl->getFormAction($this, 'testSoapAuthConnection'));
        if ($this->request->getMethod() === 'POST') {
            $soap_form = $soap_form->withRequest($this->request);
            $result = $soap_form->getData();
            if (!is_null($result)) {
                $this->settings->set('soap_auth_active', (string) $result['active']);
                $this->settings->set('soap_auth_server', $result['server']);
                $this->settings->set('soap_auth_port', (string) $result['port']);
                $this->settings->set('soap_auth_use_https', (string) $result['use_https']);
                $this->settings->set('soap_auth_uri', $result['uri']);
                $this->settings->set('soap_auth_namespace', $result['namespace']);
                $this->settings->set('soap_auth_use_dotnet', (string) $result['dotnet']);
                $this->settings->set('soap_auth_create_users', (string) $result['createuser']);
                $this->settings->set('soap_auth_account_mail', (string) $result['sendmail']);
                $this->settings->set('soap_auth_user_default_role', (string) $result['defaultrole']);
                $this->settings->set('soap_auth_allow_local', (string) $result['allowlocal']);

                $this->tpl->setOnScreenMessage('success', $this->lng->txt('auth_soap_settings_saved'), true);
                $this->logger->info('data' . print_r($result, true));
                $this->ctrl->redirect($this, 'editSOAP');
            }
        }

        $this->tabs_gui->setTabActive('auth_soap');
        $panel = $this->ui_factory->panel()->standard('SOAP', [$soap_form, $test_form]);
        $this->tpl->setContent($this->ui_renderer->render($panel));
    }

    public function editScriptObject(): void
    {
        if (!$this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
        }

        if ($_SESSION['error_post_vars']) {
            $this->tpl->setVariable('AUTH_SCRIPT_NAME', $_SESSION['error_post_vars']['auth_script']['name']);
        } else {
            $settings = $this->ilias->getAllSettings();

            $this->tpl->setVariable('AUTH_SCRIPT_NAME', $settings['auth_script_name']);
        }

        $this->tabs_gui->setTabActive('auth_script');

        $this->tpl->addBlockFile(
            'ADM_CONTENT',
            'adm_content',
            'tpl.auth_script.html',
            'components/ILIAS/Authentication'
        );

        $this->tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this));
        $this->tpl->setVariable('COLSPAN', 3);
        $this->tpl->setVariable('TXT_AUTH_SCRIPT_TITLE', $this->lng->txt('auth_script_configure'));
        $this->tpl->setVariable('TXT_OPTIONS', $this->lng->txt('options'));
        $this->tpl->setVariable('TXT_AUTH_SCRIPT_NAME', $this->lng->txt('auth_script_name'));

        $this->tpl->setVariable('TXT_REQUIRED_FLD', $this->lng->txt('required_field'));
        $this->tpl->setVariable('TXT_CANCEL', $this->lng->txt('cancel'));
        $this->tpl->setVariable('TXT_SUBMIT', $this->lng->txt('save'));
        $this->tpl->setVariable('CMD_SUBMIT', 'saveScript');
    }

    public function saveScriptObject(): void
    {
        if (!$_POST['auth_script']['name']) {
            $this->ilias->raiseError($this->lng->txt('fill_out_all_required_fields'), $this->ilias->error_obj->MESSAGE);
        }

        $this->ilias->setSetting('auth_script_name', $_POST['auth_script']['name']);
        $this->ilias->setSetting('auth_mode', (string) ilAuthUtils::AUTH_SCRIPT);

        $this->tpl->setOnScreenMessage(
            'success',
            $this->lng->txt('auth_mode_changed_to') . ' ' . $this->getAuthModeTitle(),
            true
        );
        $this->ctrl->redirect($this, 'editScript');
    }

    private function getAuthModeTitle(): string
    {
        return match ((int) $this->ilias->getSetting('auth_mode')) {
            ilAuthUtils::AUTH_LOCAL => $this->lng->txt('auth_local'),
            ilAuthUtils::AUTH_LDAP => $this->lng->txt('auth_ldap'),
            ilAuthUtils::AUTH_SHIBBOLETH => $this->lng->txt('auth_shib'),
            ilAuthUtils::AUTH_SAML => $this->lng->txt('auth_saml'),
            ilAuthUtils::AUTH_SCRIPT => $this->lng->txt('auth_script'),
            ilAuthUtils::AUTH_APACHE => $this->lng->txt('auth_apache'),
            default => $this->lng->txt('unknown'),
        };
    }

    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass($this) ?? '';
        $cmd = $this->ctrl->getCmd() ?? '';
        $this->prepareOutput();

        if (!$this->rbac_system->checkAccess('visible,read', $this->object->getRefId())) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_read'), $this->error->WARNING);
        }

        switch ($next_class) {
            case 'ilopenidconnectsettingsgui':
                $this->tabs_gui->activateTab('auth_oidconnect');

                $oid = new ilOpenIdConnectSettingsGUI($this->object->getRefId());
                $this->ctrl->forwardCommand($oid);
                break;

            case 'ilsamlsettingsgui':
                $this->tabs_gui->setTabActive('auth_saml');

                $os = new ilSamlSettingsGUI($this->object->getRefId());
                $this->ctrl->forwardCommand($os);
                break;

            case 'ilregistrationsettingsgui':
                $this->tabs_gui->setTabActive('registration_settings');

                $registration_gui = new ilRegistrationSettingsGUI();
                $this->ctrl->forwardCommand($registration_gui);
                break;

            case 'ilpermissiongui':
                $this->tabs_gui->setTabActive('perm_settings');

                $perm_gui = new ilPermissionGUI($this);
                $this->ctrl->forwardCommand($perm_gui);
                break;

            case 'illdapsettingsgui':
                $this->tabs_gui->setTabActive('auth_ldap');

                $ldap_settings_gui = new ilLDAPSettingsGUI($this->object->getRefId());
                $this->ctrl->forwardCommand($ldap_settings_gui);
                break;

            case 'ilauthshibbolethsettingsgui':
                $this->tabs_gui->setTabActive('auth_shib');

                $shib_settings_gui = new ilAuthShibbolethSettingsGUI($this->object->getRefId());
                $this->ctrl->forwardCommand($shib_settings_gui);
                break;

            case 'ilcassettingsgui':
                $this->tabs_gui->setTabActive('auth_cas');

                $cas_settings = new ilCASSettingsGUI($this->object->getRefId());
                $this->ctrl->forwardCommand($cas_settings);
                break;

            case strtolower(ilAuthPageEditorGUI::class):
                $this->setSubTabs('authSettings');
                $this->tabs_gui->setTabActive('authentication_settings');
                $this->tabs_gui->setSubTabActive('auth_login_editor');

                $lpe = new ilAuthPageEditorGUI($this->object->getRefId());
                $this->ctrl->forwardCommand($lpe);
                break;

            case strtolower(ilObjectContentStyleSettingsGUI::class):
                $this->checkPermission('write');
                $this->setTitleAndDescription();
                $this->setSubTabs('authSettings');
                $this->tabs_gui->activateTab('authentication_settings');
                $this->tabs_gui->activateSubTab('style');

                $settings_gui = $this->content_style_gui
                    ->objectSettingsGUIForRefId(
                        null,
                        $this->object->getRefId()
                    );
                $this->ctrl->forwardCommand($settings_gui);
                break;

            case strtolower(ilAuthLogoutBehaviourGUI::class):
                $this->setSubTabs('authSettings');
                $this->tabs_gui->setTabActive('authentication_settings');
                $this->tabs_gui->setSubTabActive('logout_behaviour');

                $gui = new ilAuthLogoutBehaviourGUI();
                $this->ctrl->forwardCommand($gui);
                break;

            default:
                if (!$cmd) {
                    $cmd = 'authSettings';
                }
                $cmd .= 'Object';
                $this->$cmd();

                break;
        }
    }

    public function getAdminTabs(): void
    {
        $this->getTabs();
    }

    protected function getTabs(): void
    {
        $this->ctrl->setParameter($this, 'ref_id', $this->object->getRefId());

        if ($this->rbac_system->checkAccess('visible,read', $this->object->getRefId())) {
            $this->tabs_gui->addTarget(
                'authentication_settings',
                $this->ctrl->getLinkTarget($this, 'authSettings'),
                '',
                '',
                ''
            );

            $this->tabs_gui->addTarget(
                'registration_settings',
                $this->ctrl->getLinkTargetByClass('ilregistrationsettingsgui', 'view')
            );

            $this->tabs_gui->addTarget(
                'auth_ldap',
                $this->ctrl->getLinkTargetByClass('illdapsettingsgui', 'serverList'),
                '',
                '',
                ''
            );

            $this->tabs_gui->addTarget(
                'auth_shib',
                $this->ctrl->getLinkTargetByClass('ilauthshibbolethsettingsgui', 'settings')
            );

            $this->tabs_gui->addTarget(
                'auth_cas',
                $this->ctrl->getLinkTargetByClass('ilcassettingsgui', 'settings')
            );

            $this->tabs_gui->addTarget(
                'auth_soap',
                $this->ctrl->getLinkTarget($this, 'editSOAP'),
                '',
                '',
                ''
            );

            $this->tabs_gui->addTarget(
                'apache_auth_settings',
                $this->ctrl->getLinkTarget($this, 'apacheAuthSettings'),
                '',
                '',
                ''
            );

            $this->tabs_gui->addTarget(
                'auth_saml',
                $this->ctrl->getLinkTargetByClass('ilsamlsettingsgui', ilSamlSettingsGUI::DEFAULT_CMD),
                '',
                '',
                ''
            );

            $this->tabs_gui->addTab(
                'auth_oidconnect',
                $this->lng->txt('auth_oidconnect'),
                $this->ctrl->getLinkTargetByClass('ilopenidconnectsettingsgui')
            );
        }

        if ($this->rbac_system->checkAccess('edit_permission', $this->object->getRefId())) {
            $this->tabs_gui->addTarget(
                'perm_settings',
                $this->ctrl->getLinkTargetByClass([get_class($this), 'ilpermissiongui'], 'perm'),
                ['perm', 'info', 'owner'],
                'ilpermissiongui'
            );
        }
    }

    public function setSubTabs(string $a_tab): void
    {
        $this->lng->loadLanguageModule('auth');

        if ($a_tab === 'authSettings' && $this->access->checkAccess('write', '', $this->object->getRefId())) {
            $this->tabs_gui->addSubTabTarget(
                'auth_settings',
                $this->ctrl->getLinkTarget($this, 'authSettings'),
                ''
            );

            foreach (AuthPageEditorContext::cases() as $auth_ipe_context) {
                $this->ctrl->setParameterByClass(
                    ilAuthPageEditorGUI::class,
                    ilAuthPageEditorGUI::CONTEXT_HTTP_PARAM,
                    $auth_ipe_context->value
                );
                $this->tabs_gui->addSubTabTarget(
                    $auth_ipe_context->tabIdentifier(),
                    $this->ctrl->getLinkTargetByClass(
                        ilAuthPageEditorGUI::class,
                        ilAuthPageEditorGUI::DEFAULT_COMMAND
                    )
                );
                $this->ctrl->setParameterByClass(
                    ilAuthPageEditorGUI::class,
                    ilAuthPageEditorGUI::CONTEXT_HTTP_PARAM,
                    null
                );
            }

            $this->tabs_gui->addSubTabTarget(
                'logout_behaviour',
                $this->ctrl->getLinkTargetByClass(ilAuthLogoutBehaviourGUI::class, ''),
                ''
            );

            $this->tabs_gui->addSubTab(
                'style',
                $this->lng->txt('cont_style'),
                $this->ctrl->getLinkTargetByClass(ilObjectContentStyleSettingsGUI::class)
            );
        }
    }

    public function apacheAuthSettingsObject(?ilPropertyFormGUI $form = null): void
    {
        $this->tabs_gui->setTabActive('apache_auth_settings');

        if ($form === null) {
            $form = $this->getApacheAuthSettingsForm();

            $settings = new ilSetting('apache_auth');
            $settingsMap = $settings->getAll();

            $path = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/apache_auth_allowed_domains.txt';
            if (file_exists($path) && is_readable($path)) {
                $settingsMap['apache_auth_domains'] = file_get_contents($path);
            }

            $form->setValuesByArray($settingsMap);
        }
        $this->tpl->setVariable('ADM_CONTENT', $form->getHtml());
    }

    public function saveApacheSettingsObject(): void
    {
        $form = $this->getApacheAuthSettingsForm();
        $form->setValuesByPost();
        if ($form->checkInput()) {
            $settings = new ilSetting('apache_auth');
            $fields = [
                'apache_auth_indicator_name',
                'apache_auth_indicator_value',
                'apache_enable_auth',
                'apache_enable_local',
                'apache_local_autocreate',
                'apache_enable_ldap',
                'apache_auth_username_config_type',
                'apache_auth_username_direct_mapping_fieldname',
                'apache_default_role',
                'apache_auth_target_override_login_page',
                'apache_auth_enable_override_login_page',
                'apache_auth_authenticate_on_login_page',
                'apache_ldap_sid'
            ];

            foreach ($fields as $field) {
                $settings->set($field, (string) $form->getInput($field));
            }

            if ($form->getInput('apache_enable_auth')) {
                $this->ilias->setSetting('apache_active', '1');
            } else {
                $this->ilias->setSetting('apache_active', '0');
                global $DIC;

                $ilSetting = $DIC['ilSetting'];
                if ((int) $ilSetting->get('auth_mode', '0') === ilAuthUtils::AUTH_APACHE) {
                    $ilSetting->set('auth_mode', (string) ilAuthUtils::AUTH_LOCAL);
                }
            }

            $allowedDomains = $this->validateApacheAuthAllowedDomains((string) $form->getInput('apache_auth_domains'));
            file_put_contents(ILIAS_DATA_DIR . '/' . CLIENT_ID . '/apache_auth_allowed_domains.txt', $allowedDomains);

            $this->tpl->setOnScreenMessage('success', $this->lng->txt('apache_settings_changed_success'), true);
            $this->ctrl->redirect($this, 'apacheAuthSettings');
        } else {
            $this->apacheAuthSettingsObject($form);
        }
    }

    public function getApacheAuthSettingsForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('apache_settings'));

        $chb_enabled = new ilCheckboxInputGUI($this->lng->txt('apache_enable_auth'), 'apache_enable_auth');
        $chb_enabled->setValue('1');
        $form->addItem($chb_enabled);

        $chb_local_create_account = new ilCheckboxInputGUI(
            $this->lng->txt('apache_autocreate'),
            'apache_local_autocreate'
        );
        $chb_local_create_account->setValue('1');
        $chb_enabled->addSubitem($chb_local_create_account);

        $roles = $this->rbac_review->getGlobalRolesArray();
        $select = new ilSelectInputGUI($this->lng->txt('apache_default_role'), 'apache_default_role');
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role['obj_id']] = ilObject::_lookupTitle($role['obj_id']);
        }
        $select->setOptions($roleOptions);
        $select->setValue(4);

        $chb_local_create_account->addSubitem($select);

        $chb_local = new ilCheckboxInputGUI($this->lng->txt('apache_enable_local'), 'apache_enable_local');
        $chb_local->setValue('1');
        $form->addItem($chb_local);

        $chb_ldap = new ilCheckboxInputGUI($this->lng->txt('apache_enable_ldap'), 'apache_enable_ldap');
        $chb_local->setValue('1');

        $chb_ldap->setInfo($this->lng->txt('apache_ldap_hint_ldap_must_be_configured'));

        $this->lng->loadLanguageModule('auth');

        $servers = ilLDAPServer::getServerIds();
        if (count($servers)) {
            $ldap_server_select = new ilSelectInputGUI($this->lng->txt('auth_ldap_server_ds'), 'apache_ldap_sid');
            $options[0] = $this->lng->txt('select_one');
            foreach ($servers as $server_id) {
                $ldap_server = new ilLDAPServer($server_id);
                $options[$server_id] = $ldap_server->getName();
            }
            $ldap_server_select->setOptions($options);
            $ldap_server_select->setRequired(true);

            $ds = ilLDAPServer::getDataSource(ilAuthUtils::AUTH_APACHE);
            $ldap_server_select->setValue($ds);

            $chb_ldap->addSubItem($ldap_server_select);
        }
        $form->addItem($chb_ldap);

        $txt = new ilTextInputGUI($this->lng->txt('apache_auth_indicator_name'), 'apache_auth_indicator_name');
        $txt->setRequired(true);
        $form->addItem($txt);

        $txt = new ilTextInputGUI($this->lng->txt('apache_auth_indicator_value'), 'apache_auth_indicator_value');
        $txt->setRequired(true);
        $form->addItem($txt);

        $chb = new ilCheckboxInputGUI(
            $this->lng->txt('apache_auth_enable_override_login'),
            'apache_auth_enable_override_login_page'
        );
        $chb->setValue('1');
        $form->addItem($chb);

        $txt = new ilTextInputGUI(
            $this->lng->txt('apache_auth_target_override_login'),
            'apache_auth_target_override_login_page'
        );
        $txt->setRequired(true);
        $chb->addSubItem($txt);

        $chb = new ilCheckboxInputGUI(
            $this->lng->txt('apache_auth_authenticate_on_login_page'),
            'apache_auth_authenticate_on_login_page'
        );
        $chb->setValue('1');
        $form->addItem($chb);

        $sec = new ilFormSectionHeaderGUI();
        $sec->setTitle($this->lng->txt('apache_auth_username_config'));
        $form->addItem($sec);

        $rag = new ilRadioGroupInputGUI(
            $this->lng->txt('apache_auth_username_config_type'),
            'apache_auth_username_config_type'
        );
        $form->addItem($rag);

        $rao = new ilRadioOption($this->lng->txt('apache_auth_username_direct_mapping'), '1');
        $rag->addOption($rao);

        $txt = new ilTextInputGUI(
            $this->lng->txt('apache_auth_username_direct_mapping_fieldname'),
            'apache_auth_username_direct_mapping_fieldname'
        );
        $rao->addSubItem($txt);

        $rao = new ilRadioOption($this->lng->txt('apache_auth_username_extended_mapping'), '2');
        $rao->setDisabled(true);
        $rag->addOption($rao);

        $rao = new ilRadioOption($this->lng->txt('apache_auth_username_by_function'), '3');
        $rag->addOption($rao);

        $sec = new ilFormSectionHeaderGUI();
        $sec->setTitle($this->lng->txt('apache_auth_security'));
        $form->addItem($sec);

        $txt = new ilTextAreaInputGUI($this->lng->txt('apache_auth_domains'), 'apache_auth_domains');
        $txt->setInfo($this->lng->txt('apache_auth_domains_description'));

        $form->addItem($txt);

        if ($this->access->checkAccess('write', '', $this->ref_id)) {
            $form->addCommandButton('saveApacheSettings', $this->lng->txt('save'));
        }
        $form->addCommandButton('cancel', $this->lng->txt('cancel'));

        return $form;
    }

    private function validateApacheAuthAllowedDomains(string $text): string
    {
        return implode("\n", preg_split("/[\r\n]+/", $text));
    }

    public function registrationSettingsObject(): void
    {
        $registration_gui = new ilRegistrationSettingsGUI();
        $this->ctrl->redirect($registration_gui);
    }
}

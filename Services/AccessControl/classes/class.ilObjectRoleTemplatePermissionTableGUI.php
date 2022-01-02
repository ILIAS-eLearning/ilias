<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Table for object role permissions
*
* @author Stefan Meyer <meyer@leifos.com>
*
* @version $Id$
*
* @ingroup ServicesAccessControl
*/
class ilObjectRoleTemplatePermissionTableGUI extends ilTable2GUI
{
    private $ref_id = null;
    private $role_id = null;
    private $role_folder_id = 0;
    
    private $tpl_type = '';
    
    private $show_admin_permissions = false;
    private $show_change_existing_objects = true;
    
    private static $template_permissions = null;
    

    /**
     * Constructor
     * @return
     */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id, $a_role_id, $a_type, $a_show_admin_permissions = false)
    {
        global $DIC;

        $ilCtrl = $DIC['ilCtrl'];
        $rbacreview = $DIC['rbacreview'];
        $tpl = $DIC['tpl'];

        $this->tpl_type = $a_type;
        $this->show_admin_permissions = $a_show_admin_permissions;

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setId('role_template_' . $a_ref_id . '_' . $a_type);
        $this->setFormName('role_template_permissions');
        $this->setSelectAllCheckbox('template_perm[' . $this->getTemplateType() . ']');
        
        $this->lng->loadLanguageModule('rbac');
        
        $this->ref_id = $a_ref_id;
        $this->role_id = $a_role_id;
        
        $this->setRowTemplate("tpl.obj_role_template_perm_row.html", "Services/AccessControl");
        $this->setLimit(100);
        $this->setShowRowsSelector(false);
        $this->setDisableFilterHiding(true);
        $this->setNoEntriesText($this->lng->txt('msg_no_roles_of_type'));
        
        $this->setEnableHeader(false);
        $this->disable('sort');
        $this->disable('numinfo');
        $this->disable('form');
        
        $this->addColumn('', '', '0');
        $this->addColumn('', '', '100%');
        
        $this->initTemplatePermissions();
    }

    /**
     * @param bool $a_status
     */
    public function setShowChangeExistingObjects($a_status)
    {
        $this->show_change_existing_objects = $a_status;
    }

    /**
     * @return bool
     */
    public function getShowChangeExistingObjects()
    {
        return $this->show_change_existing_objects;
    }

    /**
     *
     * @return
     */
    protected function initTemplatePermissions()
    {
        global $DIC;

        $rbacreview = $DIC['rbacreview'];
        
        if (self::$template_permissions !== null) {
            return true;
        }
        self::$template_permissions = $rbacreview->getAllOperationsOfRole(
            $this->getRoleId(),
            $this->getRefId()
        );
    }
    
    /**
     * Get permissions by type
     * @param object $a_type
     * @return
     */
    protected function getPermissions($a_type)
    {
        return self::$template_permissions[$a_type] ? self::$template_permissions[$a_type] : array();
    }

    /**
     * Set object type for current template permission table
     * @param object $a_type
     * @return
     */
    public function initTemplateType($a_type)
    {
    }
    
    /**
     * get current tempalte type
     * @return
     */
    public function getTemplateType()
    {
        return $this->tpl_type;
    }
    
    /**
     * Get currrent role id
     * @return
     */
    public function getRoleId()
    {
        return $this->role_id;
    }
    
    /**
     * Get ref id of current object
     * @return
     */
    public function getRefId()
    {
        return $this->ref_id;
    }
    
    /**
     * Get obj id
     * @return
     */
    public function getObjId()
    {
        return ilObject::_lookupObjId($this->getRefId());
    }
    
    /**
     * get obj type
     * @return
     */
    public function getObjType()
    {
        return ilObject::_lookupType($this->getObjId());
    }
    
    /**
     * Fill row template
     * @return void
     */
    public function fillRow(array $a_set) : void
    {
        global $DIC;

        $objDefinition = $DIC['objDefinition'];
        
        if (isset($a_set['show_ce'])) {
            $this->tpl->setCurrentBlock('ce_td');
            $this->tpl->setVariable('CE_TYPE', $this->getTemplateType());
            $this->tpl->parseCurrentBlock();

            $this->tpl->setCurrentBlock('ce_desc_td');
            $this->tpl->setVariable('CE_DESC_TYPE', $this->getTemplateType());
            $this->tpl->setVariable('CE_LONG', $this->lng->txt('change_existing_object_type_desc'));

            if ($objDefinition->isSystemObject($this->getTemplateType())) {
                $this->tpl->setVariable(
                    "TXT_CE",
                    $this->lng->txt("change_existing_prefix_single") . " " .
                    $this->lng->txt("obj_" . $this->getTemplateType()) . " " .
                    $this->lng->txt("change_existing_suffix_single")
                );
            } else {
                $pl_txt = ($objDefinition->isPlugin($this->getTemplateType()))
                    ? ilObjectPlugin::lookupTxtById(
                        $this->getTemplateType(),
                        "objs_" . $this->getTemplateType()
                    )
                    : $this->lng->txt('objs_' . $this->getTemplateType());
                $this->tpl->setVariable(
                    'TXT_CE',
                    $this->lng->txt('change_existing_prefix') . ' ' .
                    $pl_txt . ' ' .
                    $this->lng->txt('change_existing_suffix')
                );
                $this->tpl->parseCurrentBlock();
            }
        } else {
            $this->tpl->setCurrentBlock('perm_td');
            $this->tpl->setVariable('OBJ_TYPE', $this->getTemplateType());
            $this->tpl->setVariable('PERM_PERM_ID', $a_set['ops_id']);
            $this->tpl->setVariable('PERM_CHECKED', $a_set['set'] ? 'checked="checked"' : '');
            
            if ($this->getRoleId() == SYSTEM_ROLE_ID) {
                $this->tpl->setVariable('PERM_DISABLED', 'disabled="disabled"');
            }
            
            $this->tpl->parseCurrentBlock();
            
            $this->tpl->setCurrentBlock('perm_desc_td');
            $this->tpl->setVariable('DESC_TYPE', $this->getTemplateType());
            $this->tpl->setVariable('DESC_PERM_ID', $a_set['ops_id']);

            if ($a_set["create_type"] != "" && $objDefinition->isPlugin($a_set['create_type'])) {
                $this->tpl->setVariable(
                    'TXT_PERMISSION',
                    ilObjectPlugin::lookupTxtById(
                        $a_set['create_type'],
                        $this->getTemplateType() . "_" . $a_set['name']
                    )
                );
            } elseif ($a_set["create_type"] == "" && $objDefinition->isPlugin($this->getTemplateType())) {
                $this->tpl->setVariable(
                    'TXT_PERMISSION',
                    ilObjectPlugin::lookupTxtById(
                        $this->getTemplateType(),
                        $this->getTemplateType() . "_" . $a_set['name']
                    )
                );
            } else {
                if (substr($a_set['name'], 0, 6) == 'create') {
                    #$perm = $this->lng->txt($this->getTemplateType().'_'.$row['name']);
                    $perm = $this->lng->txt('rbac' . '_' . $a_set['name']);
                } elseif ($this->lng->exists($this->getTemplateType() . '_' . $a_set['name'] . '_short')) {
                    $perm = $this->lng->txt($this->getTemplateType() . '_' . $a_set['name'] . '_short') . ': ' .
                        $this->lng->txt($this->getTemplateType() . '_' . $a_set['name']);
                } else {
                    $perm = $this->lng->txt($a_set['name']) . ': ' . $this->lng->txt($this->getTemplateType() . '_' . $a_set['name']);
                }
                
                $this->tpl->setVariable('TXT_PERMISSION', $perm);
            }
            $this->tpl->parseCurrentBlock();
        }
    }
    
    /**
     * Parse permissions
     * @return
     */
    public function parse()
    {
        global $DIC;

        $rbacreview = $DIC['rbacreview'];
        $objDefinition = $DIC['objDefinition'];
        
        $operations = $this->getPermissions($this->getTemplateType());


        // Object permissions
        $rows = array();
        foreach ($rbacreview->getOperationsByTypeAndClass($this->getTemplateType(), 'object') as $ops_id) {
            $operations = $this->getPermissions($this->getTemplateType());
            
            $operation = $rbacreview->getOperation($ops_id);

            $perm['ops_id'] = $ops_id;
            $perm['set'] = (in_array($ops_id, $operations) or $this->getRoleId() == SYSTEM_ROLE_ID);
            $perm['name'] = $operation['operation'];
            
            $rows[] = $perm;
        }
        
        // Get creatable objects
        $objects = $objDefinition->getCreatableSubObjects($this->getTemplateType());
        $ops_ids = ilRbacReview::lookupCreateOperationIds(array_keys($objects));

        foreach ($objects as $type => $info) {
            $ops_id = $ops_ids[$type];
            
            if (!$ops_id) {
                continue;
            }
            
            $perm['ops_id'] = $ops_id;
            $perm['set'] = (in_array($ops_id, $operations) or $this->getRoleId() == SYSTEM_ROLE_ID);
            
            $perm['name'] = 'create_' . $info['name'];
            $perm['create_type'] = $info['name'];
            
            $rows[] = $perm;
        }

        if (
            !$this->show_admin_permissions &&
            $this->getShowChangeExistingObjects()
        ) {
            $rows[] = array('show_ce' => 1);
        }

        $this->setData($rows);
    }
}

<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

use IMSGlobal\LTI\ToolProvider;

include_once './Services/Authentication/classes/Provider/class.ilAuthProvider.php';
include_once './Services/Authentication/interfaces/interface.ilAuthProviderInterface.php';
include_once './Services/LTI/classes/InternalProvider/class.ilLTIToolProvider.php';
require_once 'Services/LTI/classes/class.ilLTIDataConnector.php';
/**
 * OAuth based lti authentication
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de> 
 * @author Stefan Schneider
 *
 */
class ilAuthProviderLTI extends \ilAuthProvider implements \ilAuthProviderInterface
{
	const AUTH_MODE_PREFIX = 'lti';
	private $dataConnector = null;
	
	/**
	 * Do authentication
	 * @param \ilAuthStatus $status
	 */
	public function doAuthentication(\ilAuthStatus $status)
	{
		$this->dataConnector = new ilLTIDataConnector();
		require_once 'Services/LTI/classes/InternalProvider/class.ilLTIToolConsumer.php';
		$consumer = new ilLTIToolConsumer($_POST['oauth_consumer_key'],$this->dataConnector);
		$lti_provider = new ilLTIToolProvider($this->dataConnector);
		$lti_provider->handleRequest();

		//Bsp.: crs_67
		if (isset($_GET['target'])) {
			$context_ar = explode('_',$_GET['target']);
			if (count($context_ar) == 2) {
				$_SESSION['lti_context_id'] = $context_ar[1];
			}
		}
		if (isset($_POST['launch_presentation_css_url'])) {
			$_SESSION['lti_launch_css_url'] = $_POST['launch_presentation_css_url'];
			//Bsp.: 'http://192.168.0.74/lti_custom.css';
		}
		if (isset($_POST['launch_presentation_return_url']) && (strlen(trim($_POST['launch_presentation_return_url'])) > 0)) {
			$_SESSION['lti_launch_presentation_return_url'] = $_POST['launch_presentation_return_url'];
			// Bsp.: 'http://192.168.0.74/ilias51/ilias.php?ref_id=128&cmd=viewEmbed&cmdClass=ilobjexternalcontentgui&cmdNode=hx:gc&baseClass=ilObjPluginDispatchGUI';
		}
		
		$lti_id = $this->findAuthKeyId($_POST['oauth_consumer_key']);
		if(!$lti_id)
		{
			$status->setReason('lti_auth_failed_invalid_key');
			$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
			return false;
		}
		$prefix = $this->findAuthPrefix($lti_id);
		$internal_account = $this->findUserId($this->getCredentials()->getUsername(), $lti_id, $prefix);
	
		if($internal_account)
		{
			$this->updateUser($internal_account);
		}
		else
		{
			$internal_account = $this->createUser($lti_id,$prefix);
		}
		
		$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
		$status->setAuthenticatedUserId($internal_account);

		$lti_lis_person_name_full = "";
		if (isset($_POST['lis_person_name_given'])) {
			$_SESSION['lti_lis_person_name_given'] = $_POST['lis_person_name_given'];
			$lti_lis_person_name_full = $_POST['lis_person_name_given'] . ' ';
		}
		if (isset($_POST['lis_person_name_family'])) {
			$_SESSION['lti_lis_person_name_family'] = $_POST['lis_person_name_family'];
			$lti_lis_person_name_full .= $_POST['lis_person_name_family'];
		}
		if (isset($_POST['lis_person_name_full']) && (strlen(trim($_POST['lis_person_name_full'])) > 0)) {
			$_SESSION['lti_lis_person_name_full'] = $_POST['lis_person_name_full'];
		} else {
			$_SESSION['lti_lis_person_name_full'] = $lti_lis_person_name_full;
		}

		return true;
	}

	
	/**
	 * find consumer key id
	 * @global type $ilDB
	 * @param type $a_oauth_consumer_key
	 * @return type
	 */
	protected function findAuthKeyId($a_oauth_consumer_key)
	{
		global $ilDB;
		
		$query = 'SELECT consumer_pk from lti2_consumer where consumer_key256 = '.$ilDB->quote($a_oauth_consumer_key,'text');
		// $query = 'SELECT id from lti_ext_consumer where consumer_key = '.$ilDB->quote($a_oauth_consumer_key,'text');
		$this->getLogger()->debug($query);
		$res = $ilDB->query($query);
		
		$lti_id = 0;
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$lti_id = $row->consumer_pk;
			// $lti_id = $row->id;
		}
		$this->getLogger()->debug('External consumer key is: ' . (int) $lti_id);
		return $lti_id;
	}
	
	/**
	 * find lti id
	 * @param type $a_lti_id
	 */
	protected function findAuthPrefix($a_lti_id)
	{
		global $ilDB;
		
		$query = 'SELECT prefix from lti_ext_consumer where id = '.$ilDB->quote($a_lti_id,'integer');
		$this->getLogger()->debug($query);
		$res = $ilDB->query($query);
		
		// $prefix = 'lti'.$a_lti_id.'_';
		$prefix = '';
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$prefix = $row->prefix;
		}
		$this->getLogger()->debug('LTI prefix: ' . $prefix);
		return $prefix;
	}
	
	/**
	 * find global role of consumer
	 */
	protected function findGlobalRole($a_lti_id)
	{
		global $ilDB;
		
		$query = 'SELECT role from lti_ext_consumer where id = '.$ilDB->quote($a_lti_id,'integer');
		$this->getLogger()->debug($query);
		$res = $ilDB->query($query);
		
		$role = '';
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$role = $row->role;
		}
		$this->getLogger()->debug('LTI role: ' . $role);
		return $role;
		
	}
	
	/**
	 * Find user by auth mode and lti id
	 * @param type $a_oauth_user
	 * @param type $a_oauth_id
	 */
	protected function findUserId($a_oauth_user, $a_oauth_id, $a_user_prefix)
	{
		$user_name = ilObjUser::_checkExternalAuthAccount(
			self::AUTH_MODE_PREFIX.'_'.$a_oauth_id,
			$a_user_prefix.'_'.$a_oauth_user
		);
		$user_id = 0;
		if($user_name)
		{
			$user_id = ilObjUser::_lookupId($user_name);
		}
		$this->getLogger()->debug('Found user with auth mode lti_'.$a_oauth_id. ' with user_id: ' . $user_id);
		return $user_id;
	}
	
	/**
	 * create new user
	 *
	 * @access protected
	 */
	protected function createUser($a_lti_id, $a_prefix)
	{
		global $ilClientIniFile, $ilSetting, $rbacadmin;

		$userObj = new ilObjUser();

		include_once('./Services/Authentication/classes/class.ilAuthUtils.php');
		$local_user = ilAuthUtils::_generateLogin($a_prefix . '_' . $this->getCredentials()->getUsername());

		$newUser["login"] = $local_user;
		$newUser["firstname"] = $_POST['lis_person_name_given'];
		$newUser["lastname"] = $_POST['lis_person_name_family'];
		$newUser['email'] = $_POST['lis_person_contact_email_primary'];

		// set "plain md5" password (= no valid password)
		$newUser["passwd"] = "";
		$newUser["passwd_type"] = IL_PASSWD_CRYPTED;

		$newUser["auth_mode"] = 'lti_'.$a_lti_id;
		$newUser["profile_incomplete"] = 0;

		// system data
		$userObj->assignData($newUser);
		$userObj->setTitle($userObj->getFullname());
		$userObj->setDescription($userObj->getEmail());

		// set user language to system language
		$userObj->setLanguage($ilSetting->get("language"));

		// Time limit
		$userObj->setTimeLimitOwner(7);
		$userObj->setTimeLimitUnlimited(0);
		$userObj->setTimeLimitFrom(time() - 5);
		$userObj->setTimeLimitUntil(time() + $ilClientIniFile->readVariable("session", "expire"));


		// Create user in DB
		$userObj->setOwner(6);
		$userObj->create();
		$userObj->setActive(1);
		$userObj->updateOwner();
		$userObj->saveAsNew();
		$userObj->writePrefs();

		
		if($global_role = $this->findGlobalRole($a_lti_id))
		{
			$rbacadmin->assignUser($global_role, $userObj->getId(), true);
		}

		$this->getLogger()->info('Created new lti user with uid: ' . $userObj->getId(). ' and login: ' . $userObj->getLogin());
		return $userObj->getId();
	}
	
	/**
	 * update existing user
	 *
	 * @access protected
	 */
	protected function updateUser($a_local_user_id)
	{
		global $ilClientIniFile,$ilLog,$rbacadmin;
		
		$user_obj = new ilObjUser($a_local_user_id);
		$user_obj->setFirstname($_POST['lis_person_name_given']);
		$user_obj->setLastname($_POST['lis_person_name_family']);
		$user_obj->setEmail($_POST['lis_person_contact_email_primary']);
		$user_obj->setActive(true);
		
		$until = $user_obj->getTimeLimitUntil();

		if($until < (time() + $ilClientIniFile->readVariable('session','expire')))
		{		
			$user_obj->setTimeLimitFrom(time() - 60);
			$user_obj->setTimeLimitUntil(time() + $ilClientIniFile->readVariable("session","expire"));
		}
		$user_obj->update();
		$user_obj->refreshLogin();
		

		$this->getLogger()->info('Update of lti user with uid: ' . $user_obj->getId(). ' and login: ' . $user_obj->getLogin());
		return $user_obj->getId();
	}
	
	

}
?>
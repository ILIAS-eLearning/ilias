<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/**
* Class ilObjiLincClassroom
* 
* @author Sascha Hofmann <saschahofmann@gmx.de> 
* @version $Id$
*
* @extends ilObject
* @package iLinc
*/

require_once ('./classes/class.ilObject.php');
require_once ('class.ilnetucateXMLAPI.php');

class ilObjiLincClassroom extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjiLincClassroom($a_icla_id,$a_icrs_id)
	{
		global $ilErr,$ilias,$lng;
		
		$this->type = "icla";
		$this->id = $a_icla_id;		
		$this->parent = $a_icrs_id;
		$this->ilincAPI = new ilnetucateXMLAPI();

		$this->ilErr =& $ilErr;
		$this->ilias =& $ilias;
		$this->lng =& $lng;

		$this->max_title = MAXLENGTH_OBJ_TITLE;
		$this->max_desc = MAXLENGTH_OBJ_DESC;
		$this->add_dots = true;

		$this->referenced = false;
		$this->call_by_reference = false;

		if (!empty($this->id))
		{
			$this->read();
		}
		
		return $this;
	}
	
	function _lookupiCourseId($a_ref_id)
	{
		global $ilDB;

		$q = "SELECT course_id FROM ilinc_data ".
			 "LEFT JOIN object_reference ON object_reference.obj_id=ilinc_data.obj_id ".
			 "WHERE object_reference.ref_id = '".$a_ref_id."'";
		$obj_set = $ilDB->query($q);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);

		return $obj_rec["course_id"];
	}
	
	/**
	* 
	* @access private
	*/
	function read()
	{
		$this->ilincAPI->findClass($this->id);
		$response = $this->ilincAPI->sendRequest();

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_read_class";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		$this->setTitle($response->data['classes'][$this->id]['name']);
		$this->setDescription($response->data['classes'][$this->id]['description']);
		$this->setDocentId($response->data['classes'][$this->id]['instructoruserid']);
		$this->setStatus($response->data['classes'][$this->id]['alwaysopen']);
		
		// TODO: fetch instructor user if assigned

	}
	
	function joinClass(&$a_user_obj,$a_ilinc_class_id)
	{
		
		include_once ('class.ilObjiLincUser.php');
		$ilinc_user = new ilObjiLincUser($a_user_obj);
		
		$this->ilincAPI->joinClass($ilinc_user,$a_ilinc_class_id);
		$response = $this->ilincAPI->sendRequest("joinClass");
		
		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_join_classroom";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		// return URL to join class room
		return trim($response->data['url']['cdata']);
	}
	
	// not used yet
	function findUser(&$a_user_obj)
	{
		$this->ilincAPI->findUser($a_user_obj);
		$response = $this->ilincAPI->sendRequest();
		
		var_dump($response->data);
		exit;
	}

	/**
	* update object data
	*
	* @access	public
	* @return	boolean
	*/
	function update()
	{
		$this->ilincAPI->editClass($this->id,array("name" => $this->getTitle(),"description" => $this->getDescription(), "instructoruserid" => $this->getDocentId(), "alwaysopen" => $this->getStatus()));
		$response = $this->ilincAPI->sendRequest("editClass");

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_edit_classroom";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		$this->result_msg = $response->getResultMsg();

		return true;
	}
	
	/**
	* delete object and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{		
		$this->ilincAPI->removeClass($this->id);
		$response = $this->ilincAPI->sendRequest();

		if ($response->isError())
		{
			if (!$response->getErrorMsg())
			{
				$this->error_msg = "err_delete_classroom";
			}
			else
			{
				$this->error_msg = $response->getErrorMsg();
			}
			
			return false;
		}
		
		return true;
	}
	
	// returns array of docents of course
	function getDocentList()
	{
		$ilinc_crs_id = ilObjiLincClassroom::_lookupiCourseId($this->parent);
		
		$this->ilincAPI->findRegisteredUsersByRole($ilinc_crs_id,true);
		$response = $this->ilincAPI->sendRequest();
			
		if (is_array($response->data['users']))
		{
				return $response->data['users'];
		}
		
		return array();
	}
	
	function _getDocent($a_ilinc_user_id)
	{
		global $ilDB, $lng;
		
		$fullname = false;
		
		$q = "SELECT title,firstname,lastname FROM usr_data WHERE ilinc_id = '".$a_ilinc_user_id."' LIMIT 1";
		$r = $ilDB->query($q);
		
		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$fullname = ilObjiLincClassroom::_setFullname($row->title,$row->firstname,$row->lastname);
		}
		
		return $fullname;
	}
	
	function _setFullname($a_title = "",$a_firstname = "",$a_lastname = "")
	{
		$fullname = "";

		if ($a_title)
		{
			$fullname = $a_title." ";
		}

		if ($a_firstname)
		{
			$fullname .= $a_firstname." ";
		}

		if ($a_lastname)
		{
			return $fullname.$a_lastname;
		}
	}
	
	function setDocentId($a_ilinc_user_id)
	{
		$this->docent_id = $a_ilinc_user_id;
	}
	
	function getDocentName()
	{
		if (!$this->docent_name)
		{
			$this->docent_name = $this->_getDocent($this->docent_id);
		}
		
		return $this->docent_name;
	}
	
	function getDocentId()
	{
		return $this->docent_id;
	}
	
	function setStatus($a_status)
	{
		if ($a_status == "Wahr")
		{
			$this->status = true;
		}
		else
		{
			$this->status = false;
		}
	}
	
	function getStatus()
	{
		return $this->status;
	}
	
	function getErrorMsg()
	{
		$err_msg = $this->error_msg;
		$this->error_msg = "";

		return $err_msg;
	}
} // END class.ilObjiLincClassroom
?>

<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Modules\WebResource;

use ilXmlExporter;
use UnexpectedValueException;

/**
 * Class WebResourceExporter
 *
 * Booking definition
 *
 * @package ILIAS\Modules\WebResource
 *
 * @ingroup ServicesBooking
 *
 * @author  Stefan Meyer <meyer@leifos.com>
 */
class WebResourceExporter extends ilXmlExporter {

	private $writer = NULL;


	/**
	 * Constructor
	 */
	public function __construct()
	{
			
	}
	
	/**
	 * Init export
	 * @return 
	 */
	public function init()
	{
	}
	
	/**
	 * Get xml
	 * @param object $a_entity
	 * @param object $a_schema_version
	 * @param object $a_id
	 * @return 
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id) {
		try {
			$this->writer = new WebLinkXmlWriter(false);
			$this->writer->setObjId($a_id);
			$this->writer->write();
			return $this->writer->xmlDumpMem(false);
			
		}
		catch(UnexpectedValueException $e) 
		{
			$GLOBALS['DIC']->logger()->webr()->warning("Caught error: ".$e->getMessage());
			return '';
		}
	}
	
	/**
	 * Returns schema versions that the component can export to.
	 * ILIAS chooses the first one, that has min/max constraints which
	 * fit to the target release. Please put the newest on top.
	 *
	 * @return
	 */
	public function getValidSchemaVersions($a_entity)
	{
		return array (
			"4.1.0" => array(
				"namespace" => "http://www.ilias.de/Modules/WebResource/webr/4_1",
				"xsd_file" => "ilias_webr_4_1.xsd",
				"uses_dataset" => false,
				"min" => "4.1.0",
				"max" => "")
		);
	}
	
}
?>
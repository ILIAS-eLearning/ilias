<?php

namespace ILIAS\Services\AssessmentQuestion\PublicApi\Contracts;

/**
 * Interface QuestionIdContract
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  Adrian Lüthi <al@studer-raimann.ch>
 * @author  Björn Heyser <bh@bjoernheyser.de>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 *
 * @package ILIAS\Services\AssessmentQuestion\PublicApi\Contracts
 */
interface QuestionIdContract {

	/**
	 * @return string
	 */
	public function getId():string;

}
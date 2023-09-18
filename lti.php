<?php

declare(strict_types=1);

/**
 * LTI launch target script
 *
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 */
include_once './components/ILIAS/Context_/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_LTI_PROVIDER);

require_once("components/ILIAS/Init_/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

// authentication is done here ->
global $DIC;
$DIC->ctrl()->setCmd('doLTIAuthentication');
$DIC->ctrl()->setTargetScript('ilias.php');
$DIC->ctrl()->callBaseClass('ilStartUpGUI');

<?php

declare(strict_types=1);

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

/** @noRector */

chdir("../../../");

require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/Init_/classes/class.ilInitialisation.php");

ilContext::init(ilContext::CONTEXT_SCORM);
ilInitialisation::initILIAS();

if (!empty(ilObjLTIConsumer::verifyPrivateKey())) {
    echo "ERROR_OPEN_SSL_CONF";
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(ilObjLTIConsumer::getJwks(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

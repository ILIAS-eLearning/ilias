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

namespace ILIAS\GlobalScreen\Identification;

/**
 * Class CoreIdentification
 * @see    IdentificationFactory
 * This is a implementation of IdentificationInterface for usage in Core
 * components (they will get them through the factory). This a Serializable and
 * will be used to store in database and cache.
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class CoreIdentification extends AbstractIdentification implements IdentificationInterface
{
}

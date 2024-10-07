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

namespace ILIAS\MetaData\Repository\Search\Clauses\Properties;

use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Repository\Search\Clauses\Mode;
use ILIAS\MetaData\Paths\NullPath;

class NullBasicProperties implements BasicPropertiesInterface
{
    public function path(): PathInterface
    {
        return new NullPath();
    }

    public function mode(): Mode
    {
        return Mode::EQUALS;
    }

    public function isModeNegated(): bool
    {
        return false;
    }

    public function value(): string
    {
        return '';
    }
}

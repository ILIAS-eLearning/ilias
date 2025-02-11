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

namespace ILIAS\MetaData\Repository\Validation\Dictionary;

use ILIAS\MetaData\Structure\Dictionaries\Tags\Tag as BaseTag;

class Tag extends BaseTag implements TagInterface
{
    protected Restriction $restriction;
    protected string $value;

    public function __construct(
        Restriction $restriction,
        string $value,
        int ...$indices
    ) {
        $this->restriction = $restriction;
        $this->value = $value;
        parent::__construct(...$indices);
    }

    public function restriction(): Restriction
    {
        return $this->restriction;
    }

    public function value(): string
    {
        return $this->value;
    }
}

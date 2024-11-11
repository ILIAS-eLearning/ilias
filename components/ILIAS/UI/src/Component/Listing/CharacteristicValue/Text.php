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

namespace ILIAS\UI\Component\Listing\CharacteristicValue;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Layout\Alignment\Block;
use ILIAS\UI\Component\Navigation\Sequence\SegmentContent;

/**
 * Interface Text
 */
interface Text extends Component, Block, SegmentContent
{
    /**
     * Gets the items as array of key value pairs for the list.
     * Key is used as label for the value.
     *
     * @return array<string, string> $items
     */
    public function getItems(): array;
}

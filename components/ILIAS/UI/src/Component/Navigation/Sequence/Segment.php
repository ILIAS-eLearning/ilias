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

namespace ILIAS\UI\Component\Navigation\Sequence;

use ILIAS\UI\Component\Button;

/**
 * A segment is the content resulting from operating a sequence.
 */
interface Segment
{
    /**
     * A segment provides a title
     */
    public function getSegmentTitle(): string;

    /**
     * The actual "contents" of the displayed view when operating a sequence.
     */
    public function getSegmentContent(): string;

    /**
     * Segments MAY add actions to the sequence.
     * Those actions MUST target the actually displayed contents rather
     * than changing context entirely (i.e. breaking the sequence).
     * @return Button\Standard[]
     */
    public function getSegmentActions(): ?array;
}

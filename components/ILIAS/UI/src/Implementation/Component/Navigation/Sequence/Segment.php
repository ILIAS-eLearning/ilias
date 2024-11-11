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

namespace ILIAS\UI\Implementation\Component\Navigation\Sequence;

use ILIAS\UI\Component\Navigation\Sequence as ISequence;

class Segment implements ISequence\Segment
{
    protected ?array $actions = null;

    /**
     * @var ISequence\IsSegmentContent[]
     */
    protected array $contents;

    public function __construct(
        protected string $title,
        ISequence\IsSegmentContent ...$contents,
    ) {
        $this->contents = $contents;
    }

    public function getContents(): array
    {
        return $this->contents;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function withActions(...$actions): static
    {
        $clone = clone $this;
        $clone->actions = $actions;
        return $clone;
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }

}

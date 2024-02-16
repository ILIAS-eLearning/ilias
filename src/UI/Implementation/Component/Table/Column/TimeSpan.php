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

namespace ILIAS\UI\Implementation\Component\Table\Column;

use ILIAS\UI\Component\Table\Column as C;
use ILIAS\Data\DateFormat\DateFormat;

class TimeSpan extends Column implements C\TimeSpan
{
    public function __construct(
        \Closure $ordering_label_builder,
        string $title,
        protected DateFormat $format
    ) {
        parent::__construct($ordering_label_builder, $title);
    }

    public function getFormat(): DateFormat
    {
        return $this->format;
    }

    public function format($value): string
    {
        $this->checkArgListElements('value', $value, [\DateTimeImmutable::class]);
        return
            $value[0]->format($this->getFormat()->toString())
            . ' - ' .
            $value[1]->format($this->getFormat()->toString());
    }
}

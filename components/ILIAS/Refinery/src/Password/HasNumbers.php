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

namespace ILIAS\Refinery\Password;

use ILIAS\Refinery\Custom\Constraint;
use ILIAS\Data;

class HasNumbers extends Constraint
{
    public function __construct(Data\Factory $data_factory, \ILIAS\Language\Language $lng)
    {
        parent::__construct(
            static function (Data\Password $value): bool {
                return (bool) preg_match('/[0-9]/', $value->toString());
            },
            static function ($value): string {
                return "Password must contain numbers.";
            },
            $data_factory,
            $lng
        );
    }
}

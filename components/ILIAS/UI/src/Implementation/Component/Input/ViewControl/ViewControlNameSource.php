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

namespace ILIAS\UI\Implementation\Component\Input\ViewControl;

use ILIAS\UI\Implementation\Component\Input\NameSource;

/**
 * ViewControlNameSource returns fixed dedicated names w/o numerations
 */
class ViewControlNameSource implements NameSource
{
    private int $count = 0;
    /**
     * @inheritDoc
     */
    public function getNewName(): string
    {
        return 'vc' . $this->count++;
    }

    public function getNewDedicatedName(string $dedicated_name): string
    {
        return $dedicated_name;
    }
}

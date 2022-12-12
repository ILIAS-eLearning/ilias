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

namespace ILIAS\ResourceStorage\Information;

use DateTimeImmutable;

/**
 * Class Information
 * @author Fabian Schmid <fabian@sr.solutions.ch>
 */
interface Information
{
    public function getTitle(): string;

    public function getSuffix(): string;

    public function getMimeType(): string;

    public function getSize(): int;

    public function getCreationDate(): DateTimeImmutable;
}

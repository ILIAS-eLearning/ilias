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

namespace ILIAS\MetaData\Vocabularies\Factory;

use ILIAS\MetaData\Vocabularies\Slots\Identifier as SlotIdentifier;
use ILIAS\MetaData\Vocabularies\VocabularyInterface;

interface FactoryInterface
{
    public const STANDARD_SOURCE = 'LOMv1.0';
    public const COPYRIGHT_SOURCE = 'ILIAS';

    public function standard(SlotIdentifier $slot, string ...$values): BuilderInterface;

    public function controlledString(
        SlotIdentifier $slot,
        string $id,
        string $source,
        string ...$values
    ): BuilderInterface;

    public function controlledVocabValue(
        SlotIdentifier $slot,
        string $id,
        string $source,
        string ...$values
    ): BuilderInterface;

    public function copyright(string ...$values): BuilderInterface;

    public function null(): VocabularyInterface;
}

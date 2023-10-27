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

namespace ILIAS\LegalDocuments\test\Repository;

use ILIAS\LegalDocuments\test\ContainerMock;
use ILIAS\LegalDocuments\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use ILIAS\LegalDocuments\Repository\ReadOnlyDocumentRepository;

require_once __DIR__ . '/../ContainerMock.php';

class ReadOnlyDocumentRepositoryTest extends TestCase
{
    use ContainerMock;

    public function testConstruct(): void
    {
        $this->assertInstanceOf(ReadOnlyDocumentRepository::class, new ReadOnlyDocumentRepository($this->mock(DocumentRepository::class)));
    }
}

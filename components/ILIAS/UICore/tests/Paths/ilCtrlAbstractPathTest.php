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

require_once __DIR__ . '/ilCtrlPathTestBase.php';

/**
 * Class ilCtrlAbstractPathTest
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
class ilCtrlAbstractPathTest extends ilCtrlPathTestBase
{
    public function testAbstractPathGetCidPath(): void
    {
        $path = $this->getPath('a:b:c');
        $this->assertEquals('a:b:c', $path->getCidPath());

        $path = $this->getPath('0');
        $this->assertEquals('0', $path->getCidPath());

        $path = $this->getPath('');
        $this->assertNull($path->getCidPath());

        $path = $this->getPath();
        $this->assertNull($path->getCidPath());
    }

    public function testAbstractPathGetCurrentCid(): void
    {
        $path = $this->getPath('a:b:c');
        $this->assertEquals('c', $path->getCurrentCid());

        $path = $this->getPath('');
        $this->assertNull($path->getCurrentCid());

        $path = $this->getPath();
        $this->assertNull($path->getCurrentCid());
    }

    public function testAbstractPathGetNextCid(): void
    {
        $path = $this->getPath('1:2:3');
        $this->assertEquals('2', $path->getNextCid(ilCtrlBaseClass2TestGUI::class));
        $this->assertEquals('3', $path->getNextCid(ilCtrlCommandClass1TestGUI::class));
        $this->assertNull($path->getNextCid(ilCtrlCommandClass2TestGUI::class));

        $path = $this->getPath('');
        $this->assertNull($path->getNextCid(ilCtrlBaseClass1TestGUI::class));

        $path = $this->getPath();
        $this->assertNull($path->getNextCid(ilCtrlBaseClass1TestGUI::class));
    }

    public function testAbstractPathGetCidPaths(): void
    {
        $path = $this->getPath('0:1:2');
        $this->assertEquals(
            [
                '0',
                '0:1',
                '0:1:2',
            ],
            $path->getCidPaths(SORT_ASC)
        );

        $this->assertEquals(
            [
                '0:1:2',
                '0:1',
                '0',
            ],
            $path->getCidPaths()
        );

        $path = $this->getPath('');
        $this->assertEmpty($path->getCidPaths());
    }

    public function testAbstractPathGetCidArray(): void
    {
        $path = $this->getPath('a:b:c');
        $this->assertEquals(['c', 'b', 'a'], $path->getCidArray());
        $this->assertEquals(['a', 'b', 'c'], $path->getCidArray(SORT_ASC));

        $path = $this->getPath('');
        $this->assertEmpty($path->getCidArray());

        $path = $this->getPath();
        $this->assertEmpty($path->getCidArray());
    }
}

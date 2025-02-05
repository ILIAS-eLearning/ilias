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

use PHPUnit\Framework\TestCase;
use ILIAS\DI\Container;

/**
 * Class ilTimingAcceptedTest
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilTimingAcceptedTest extends TestCase
{
    protected Container $dic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDependencies();
    }

    public function testAcceptance(): void
    {
        $user_id = -1;
        $course_id = -2;
        $acc = new ilTimingAccepted($course_id, $user_id);
        $this->assertInstanceOf(ilTimingAccepted::class, $acc);
        $this->assertEquals($user_id, $acc->getUserId());
        $this->assertEquals($course_id, $acc->getCourseId());
        $this->assertFalse($acc->isAccepted());
        $this->assertFalse($acc->isVisible());
        $this->assertEmpty($acc->getRemark());

        $acc->setRemark('remark');
        $acc->setVisible(true);
        $acc->accept(true);

        $this->assertTrue($acc->isAccepted());
        $this->assertTrue($acc->isVisible());
        $this->assertEquals('remark', $acc->getRemark());
    }

    protected function setGlobalVariable(string $name, $value): void
    {
        global $DIC;

        $GLOBALS[$name] = $value;
        unset($DIC[$name]);
        $DIC[$name] = static function (\ILIAS\DI\Container $c) use ($value) {
            return $value;
        };
    }

    protected function initDependencies(): void
    {
        $this->dic = new Container();
        $GLOBALS['DIC'] = $this->dic;
        $this->setGlobalVariable('ilDB', $this->createMock(ilDBInterface::class));
    }
}

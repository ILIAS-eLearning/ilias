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

use PHPUnit\Framework\TestCase;

/**
 * Test clipboard repository
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class PWspStandardGUIRequestTest extends TestCase
{
    //protected $backupGlobals = false;

    protected function tearDown(): void
    {
    }

    protected function getRequest(array $get, array $post): \ILIAS\PersonalWorkspace\StandardGUIRequest
    {
        $http_mock = $this->createMock(ILIAS\HTTP\Services::class);
        $lng_mock = $this->createMock(ilLanguage::class);
        $data = new \ILIAS\Data\Factory();
        $refinery = new \ILIAS\Refinery\Factory($data, $lng_mock);
        return new \ILIAS\PersonalWorkspace\StandardGUIRequest(
            $http_mock,
            $refinery,
            $get,
            $post
        );
    }

    /**
     * Test ref id
     */
    public function testRefId(): void
    {
        $request = $this->getRequest(
            [
                "ref_id" => "5"
            ],
            []
        );

        $this->assertEquals(
            5,
            $request->getRefId()
        );
    }

    /**
     * Test no ref id
     */
    public function testNoRefId(): void
    {
        $request = $this->getRequest(
            [
            ],
            []
        );

        $this->assertEquals(
            0,
            $request->getRefId()
        );
    }

    /**
     * Test share id
     */
    public function testShareId(): void
    {
        $request = $this->getRequest(
            [
                "shr_id" => 16
            ],
            []
        );

        $this->assertEquals(
            16,
            $request->getShareId()
        );
    }

    /**
     * Test node
     */
    public function testNode(): void
    {
        $request = $this->getRequest(
            [
                "node" => 12
            ],
            []
        );

        $this->assertEquals(
            12,
            $request->getNode()
        );
    }
}

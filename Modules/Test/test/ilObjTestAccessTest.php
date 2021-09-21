<?php declare(strict_types=1);

/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilObjTestAccessTest
 * @author Marvin Beym <mbeym@databay.de>
 */
class ilObjTestAccessTest extends ilTestBaseTestCase
{
    private ilObjTestAccess $testObj;

    protected function setUp() : void
    {
        parent::setUp();

        $this->testObj = new ilObjTestAccess();
    }

    public function test_instantiateObject_shouldReturnInstance() : void
    {
        $this->assertInstanceOf(ilObjTestAccess::class, $this->testObj);
    }
}

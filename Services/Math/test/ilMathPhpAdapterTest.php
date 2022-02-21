<?php
require_once 'Services/Math/test/ilMathBaseAdapterTest.php';
/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
class ilMathPhpAdapterTest extends ilMathBaseAdapterTest
{
    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $this->mathAdapter = new ilMathPhpAdapter();
        parent::setUp();
    }
}

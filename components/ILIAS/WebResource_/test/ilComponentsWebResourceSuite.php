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

use PHPUnit\Framework\TestSuite;

class ilComponentsWebResourceSuite extends TestSuite
{
    public static function suite(): self
    {
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/WebResource_/test/ilWebResourceParameterTest.php");
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/WebResource_/test/ilWebResourceItemsContainerTest.php");
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/WebResource_/test/ilWebResourceDatabaseRepositoryTest.php");
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/WebResource_/test/ilWebResourceItemTest.php");
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/WebResource_/test/ilWebResourceItemInternalTest.php");
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/WebResource_/test/ilWebResourceItemExternalTest.php");

        $suite = new self();
        $suite->addTestSuite(ilWebResourceParameterTest::class);
        $suite->addTestSuite(ilWebResourceItemsContainerTest::class);
        $suite->addTestSuite(ilWebResourceDatabaseRepositoryTest::class);
        $suite->addTestSuite(ilWebResourceItemTest::class);
        $suite->addTestSuite(ilWebResourceItemInternalTest::class);
        $suite->addTestSuite(ilWebResourceItemExternalTest::class);
        return $suite;
    }
}

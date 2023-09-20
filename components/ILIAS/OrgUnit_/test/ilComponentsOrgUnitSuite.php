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
 ********************************************************************
 */

use PHPUnit\Framework\TestSuite;

/** @noRector */
require_once substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . '/vendor/composer/vendor/autoload.php';

class ilComponentsOrgUnitSuite extends TestSuite
{
    public static function suite(): self
    {
        $suite = new self();
        /** @noRector */
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitTest.php");
        $suite->addTestSuite("ilModulesOrgUnitTest");
        /** @noRector */
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilOrgUnitOperationContextRegisteredObjectiveTest.php");
        $suite->addTestSuite("ilOrgUnitOperationContextRegisteredObjectiveTest");
        /** @noRector */
        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilOrgUnitOperationRegisteredObjectiveTest.php");
        $suite->addTestSuite("ilOrgUnitOperationRegisteredObjectiveTest");

        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitAuthorityTest.php");
        $suite->addTestSuite("ilModulesOrgUnitAuthorityTest");

        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitPositionTest.php");
        $suite->addTestSuite("ilModulesOrgUnitPositionTest");

        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitUserAssignmentTest.php");
        $suite->addTestSuite("ilModulesOrgUnitUserAssignmentTest");

        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitOperationContextTest.php");
        $suite->addTestSuite("ilModulesOrgUnitOperationContextTest");

        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitOperationTest.php");
        $suite->addTestSuite("ilModulesOrgUnitOperationTest");

        require_once(substr(__FILE__, 0, strpos(__FILE__, "components/ILIAS")) . "/components/ILIAS/OrgUnit_/test/ilModulesOrgUnitPermissionTest.php");
        $suite->addTestSuite("ilModulesOrgUnitPermissionTest");

        return $suite;
    }
}

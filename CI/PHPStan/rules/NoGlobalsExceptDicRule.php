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

namespace ILIAS\CI\PHPStan\rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Type\ObjectType;
use ILIAS\DI\Container;

final class NoGlobalsExceptDicRule implements Rule
{
    public function getNodeType(): string
    {
        return ArrayDimFetch::class;
    }

    /**
     * @param Node\Stmt\Global_|ArrayDimFetch $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /*foreach ($node->vars as $variable) {
            if ($variable->name !== 'DIC') {
                return [
                    RuleErrorBuilder::message(
                        'You must not use global variables excecpt $DIC: ' . $variable->name
                    )->build()
                ];
            }
        }*/

        /*if ($node->var->name === 'GLOBALS' && $node->dim !== null) {
            $infered_type = $scope->getType($node->dim);
            if ($infered_type->isString() && $infered_type->getValue() !== 'DIC') {
                return [
                    RuleErrorBuilder::message(
                        'You must not use global variables except $DIC: ' . $infered_type->getValue()
                    )->build()
                ];
            }
        }*/

        return [];
    }
}

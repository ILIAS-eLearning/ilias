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

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\UI\Implementation\Component\Input\Input;
use ILIAS\UI\Implementation\Component\Input\InputData;
use ILIAS\UI\Implementation\Component\Input\NameSource;
use ILIAS\UI\Implementation\Component\Input\DynamicInputsNameSource;
use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\Triggerer;
use ILIAS\UI\Component\Signal;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\Result;
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Factory;
use ILIAS\Refinery\Transformation;
use LogicException;
use Generator;
use InvalidArgumentException;

abstract class Field extends Input implements InternalField
{
    protected bool $is_required = false;
    protected ?Constraint $requirement_constraint = null;


    /**
     * Applies the operations in this instance to the value.
     *
     * @param    mixed $res
     */
    protected function applyOperationsTo($res): Result
    {
        if ($res === null && !$this->isRequired()) {
            return $this->data_factory->ok($res);
        }

        $res = $this->data_factory->ok($res);
        foreach ($this->getOperations() as $op) {
            if ($res->isError()) {
                return $res;
            }

            $res = $op->applyTo($res);
        }

        return $res;
    }

    /**
     * Get the operations that should be performed on the input.
     *
     * @return Generator<Transformation>
     */
    private function getOperations(): Generator
    {
        if ($this->isRequired()) {
            $op = $this->getConstraintForRequirement();
            if ($op !== null) {
                yield $op;
            }
        }

        foreach ($this->operations as $op) {
            yield $op;
        }
    }
}

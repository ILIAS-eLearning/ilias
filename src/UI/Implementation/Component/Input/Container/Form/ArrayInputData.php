<?php declare(strict_types=1);

/* Copyright (c) 2021 Thibeau Fuhrer <thf@studer-raimann.ch> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Input\Container\Form;

use ILIAS\UI\Implementation\Component\Input\InputData;
use LogicException;

/**
 * @author  Thibeau Fuhrer <thf@studer-raimann.ch>
 */
class ArrayInputData implements InputData
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdocs
     */
    public function get($name)
    {
        if (!isset($this->data[$name])) {
            throw new LogicException("'$name' is not contained in provided data.");
        }

        return $this->data[$name];
    }

    /**
     * @inheritdocs
     */
    public function getOr($name, $default)
    {
        return $this->data[$name] ?? $default;
    }
}

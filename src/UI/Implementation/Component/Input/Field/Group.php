<?php

/* Copyright (c) 2017 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see
docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\Data\Result;
use ILIAS\UI\Component as C;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\Input\InputData;
use ILIAS\UI\Implementation\Component\Input\NameSource;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Validation\Factory as ValidationFactory;
use ILIAS\Transformation\Factory as TransformationFactory;

/**
 * This implements the group input.
 */
class Group extends Input implements C\Input\Field\Group {

	use GroupHelper;

	/**
	 * Group constructor.
	 *
	 * @param DataFactory           $data_factory
	 * @param ValidationFactory     $validation_factory
	 * @param TransformationFactory $transformation_factory
	 * @param                       $inputs
	 * @param                       $label
	 * @param                       $byline
	 */
	public function __construct(
		DataFactory $data_factory,
		ValidationFactory $validation_factory,
		TransformationFactory $transformation_factory,
		$inputs,
		$label,
		$byline
	) {
		parent::__construct($data_factory, $validation_factory, $transformation_factory, $label, $byline);
		$this->inputs = $inputs;
	}

	public function withDisabled($is_disabled) {
		$clone = parent::withDisabled($is_disabled);
		$inputs = [];
		foreach ($this->inputs as $key => $input)
		{
			$inputs[$key] = $input->withDisabled($is_disabled);
		}
		$clone->inputs = $inputs;
		return $clone;
	}

	public function withOnUpdate(Signal $signal) {
		$clone = parent::withOnUpdate($signal);
		$inputs = [];
		foreach ($this->inputs as $key => $input) {
			$inputs[$key] = $input->withOnUpdate($signal);
		}
		$clone->inputs = $inputs;
		return $clone;
	}
}

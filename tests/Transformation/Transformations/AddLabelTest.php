<?php

/* Copyright (c) 2017 Stefan Hecken <stefan.hecken@concepts-and-training.de> Extended GPL, see docs/LICENSE */

use ILIAS\Transformation\Factory;

/**
 * TestCase for the factory of constraints
 *
 * @author Stefan Hecken <stefan.hecken@concepts-and-training.de>
 */
class AddLabelTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->f = new Transformation\Factory();
	}

	protected function tearDown() {
		$this->f = null;
	}

	public function testTransform() {
		$add_label = $this->f->addLabeld(array("A", "B", "C"));
		$without = array(1,2,3);
		$with = $add_label->transform($without);
		$this->assertEquals(array("A"=>1, "B"=>2, "C"=>3), $with);

		$raised = false;
		try {
			$next_with =  = $add_label->transform($with);
		} catch (InvalidArgumentException $e) {
			$raised = true;
		}
		$this->assertTrue($raised);

		$raised = false;
		try {
			$without = array(1, 2, 3, 4);
			$with = $add_label->transform($without);
		} catch (InvalidArgumentException $e) {
			$raised = true;
		}
		$this->assertTrue($raised);

		$raised = false;
		try {
			$without = "1, 2, 3";
			$with = $add_label->transform($without);
		} catch (InvalidArgumentException $e) {
			$raised = true;
		}
		$this->assertTrue($raised);
	}

	public function testInvoke() {
		$add_label = $this->f->addLabeld(array("A", "B", "C"));
		$without = array(1,2,3);
		$with = $add_label($without);
		$this->assertEquals(array("A"=>1, "B"=>2, "C"=>3), $with);

		$raised = false;
		try {
			$next_with = $add_label($with);
		} catch (InvalidArgumentException $e) {
			$raised = true;
		}
		$this->assertTrue($raised);

		$raised = false;
		try {
			$without = array(1, 2, 3, 4);
			$with = $add_label($without);
		} catch (InvalidArgumentException $e) {
			$raised = true;
		}
		$this->assertTrue($raised);

		$raised = false;
		try {
			$without = "1, 2, 3";
			$with = $add_label($without);
		} catch (InvalidArgumentException $e) {
			$raised = true;
		}
		$this->assertTrue($raised);
	}
}
<?php

/* Copyright (c) 2018 Nils Haagen <nils.haagen@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once(__DIR__."/../../../../libs/composer/vendor/autoload.php");
require_once(__DIR__."/../../Base.php");

use \ILIAS\UI\Component as C;
use \ILIAS\UI\Implementation\Component\Signal;

/**
 * Test on button implementation.
 */
class BulkyButtonTest extends ILIAS_UI_TestBase {

	public function setUp() {
		$this->factory = new \ILIAS\UI\Implementation\Factory();
		$this->glyph = $this->factory->glyph()->briefcase();
		$this->icon = $this->factory->icon()->standard('someExample', 'Example');
	}

	public function test_implements_factory_interface() {
		$this->assertInstanceOf
			( "ILIAS\\UI\\Component\\Button\\Bulky"
			, $this->factory->button()->bulky($this->glyph, "label", "http://www.ilias.de")
		);
	}

	public function test_construction_wrong_params_label() {
		$f = $this->factory->button();

		try {
			$f->bulky($this->glyph, 1, "http://www.ilias.de");
			$this->assertFalse("This should not happen");
		}
		catch (\InvalidArgumentException $e) {
			$this->assertTrue(true);
		}
	}

	public function test_construction_wrong_params_url() {
		$f = $this->factory->button();
		try {
			$f->bulky("", "", 1);
			$this->assertFalse("This should not happen");
		}
		catch (\InvalidArgumentException $e) {
			$this->assertTrue(true);
		}
	}

	public function test_construction_wrong_params_icon() {
		$f = $this->factory->button();
		try {
			$f->bulky("", "label", "http://www.ilias.de");
			$this->assertFalse("This should not happen");
		}
		catch (\InvalidArgumentException $e) {
			$this->assertTrue(true);
		}
	}

	public function test_with_glyph() {
		$b = $this->factory->button()->bulky($this->glyph, "label", "http://www.ilias.de");
		$this->assertEquals(
			$this->glyph,
			$b->getIconOrGlyph()
		);
	}

	public function test_with_icon() {
		$b = $this->factory->button()->bulky($this->icon, "label", "http://www.ilias.de");
		$this->assertEquals(
			$this->icon,
			$b->getIconOrGlyph()
		);
	}

	public function test_engaged() {
		$b = $this->factory->button()->bulky($this->glyph, "label", "http://www.ilias.de");
		$this->assertFalse($b->isEngaged());

		$b = $b->withEngagedState(true);
		$this->assertInstanceOf(
			"ILIAS\\UI\\Component\\Button\\Bulky",
			$b
		);
		$this->assertTrue($b->isEngaged());
	}

	public function test_render_glyph_context_and_state() {
		$r = $this->getDefaultRenderer();
		$b = $this->factory->button()->bulky($this->glyph, "label", "http://www.ilias.de");

		$expected = ''
			.'<button class="btn btn-bulky" data-action="http://www.ilias.de" id="id_1" aria-pressed="undefined">'
			.'	<span class="glyph" aria-label="briefcase">'
			.'		<span class="glyphicon glyphicon-briefcase" aria-hidden="true"></span>'
			.'	</span>'
			.'	<span class="bulky-label">label</span>'
			.'</button>';

		$this->assertHTMLEquals(
			$expected,
			$r->render($b)
		);

		$b = $b->withEngagedState(true);
		$expected = ''
			.'<button class="btn btn-bulky engaged" data-action="http://www.ilias.de" id="id_2" aria-pressed="true">'
			.'	<span class="glyph" aria-label="briefcase">'
			.'		<span class="glyphicon glyphicon-briefcase" aria-hidden="true"></span>'
			.'	</span>'
			.'	<span class="bulky-label">label</span>'
			.'</button>';

		$this->assertHTMLEquals(
			$expected,
			$r->render($b)
		);
	}

	public function test_render_icon() {
		$r = $this->getDefaultRenderer();
		$b = $this->factory->button()->bulky($this->icon, "label", "http://www.ilias.de");

		$expected = ''
			.'<button class="btn btn-bulky" data-action="http://www.ilias.de" id="id_1" aria-pressed="undefined">'
			.'	<div class="icon someExample small" aria-label="Example"></div>'
			.'	<span class="bulky-label">label</span>'
			.'</button>';

		$this->assertHTMLEquals(
			$expected,
			$r->render($b)
		);
	}

}
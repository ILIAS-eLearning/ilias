<?php

require_once(__DIR__."/../../../../libs/composer/vendor/autoload.php");
require_once(__DIR__."/../../Base.php");

use \ILIAS\UI\Component as C;

class ViewControlTest extends ILIAS_UI_TestBase {

	protected $actions = array (
		"ILIAS" => "http://www.ilias.de",
		"Github" => "http://www.github.com"
	);

	protected $aria_label = "Mode View Controler";
	protected $role = "group";
	protected $active = "Github";

	public function getViewControlFactory()
	{
		return new \ILIAS\UI\Implementation\Component\ViewControl\Factory();
	}

	public function test_implements_factory_interface()
	{
		$f = $this->getViewControlFactory();
		$this->assertInstanceOf("ILIAS\\UI\\Component\\ViewControl\\Factory", $f);

		$mode = $f->mode($this->actions);
		$this->assertInstanceOf( "ILIAS\\UI\\Component\\ViewControl\\Mode", $mode);
	}

	public function test_viewcontrol_with_active()
	{
		$f = $this->getViewControlFactory();

		$this->assertEquals($this->active, $f->mode($this->actions)->withActive($this->active)->getActive());
		$this->assertNotEquals($this->active, $f->mode($this->actions)->withActive("Dummy text")->getActive());
	}

	public function test_viewcontrol_get_actions()
	{
		$f = $this->getViewControlFactory();
		$r = $this->getDefaultRenderer();

		$this->assertInternalType("array",$f->mode($this->actions)->getLabelledActions());
	}

	public function test_render_viewcontrol_mode()
	{
		$f = $this->getViewControlFactory();
		$r = $this->getDefaultRenderer();
		$mode = $f->mode($this->actions);

		$html = $this->normalizeHTML($r->render($mode));

		$active = $mode->getActive();
		if($active == "") {
			$activate_first_item = true;
		}

		$expected = "<div class=\"btn-group il-viewcontrol-mode\" aria-label=\"".$this->aria_label."\" role=\"".$this->role."\">";
		foreach ($this->actions as $label => $action)
		{

			if($activate_first_item) {
				$expected .= "<a class=\"btn btn-default ilSubmitInactive\" data-action=\"$action\">$label</a>";
				$activate_first_item = false;
			} else if($active == $label) {
				$expected .= "<a class=\"btn btn-default ilSubmitInactive\" data-action=\"$action\">$label</a>";
			}
			else {
				$expected .= "<a class=\"btn btn-default\" href=\"$action\" data-action=\"$action\">$label</a>";
			}
		}
		$expected .= "</div>";

		$this->assertHTMLEquals($expected, $html);
	}

	public function getUIFactory()
	{
		return new \ILIAS\UI\Implementation\Factory();
	}

}
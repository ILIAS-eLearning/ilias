<?php

/* Copyright (c) 2017 Nils Haagen <nils.haagen@concepts.and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Layout\Page;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;
use ILIAS\UI\Implementation\Render\ilTemplateWrapper as UITemplateWrapper;
use ILIAS\UI\Component\Signal;

class Renderer extends AbstractComponentRenderer {
	/**
	 * @inheritdoc
	 */
	public function render(Component\Component $component, RendererInterface $default_renderer) {
		$this->checkComponent($component);

		if ($component instanceof Component\Layout\Page\Standard) {
			return $this->renderPage($component, $default_renderer);
		}
	}

	protected function renderPage(Component\Layout\Page\Standard $component, RendererInterface $default_renderer) {
		$tpl = $this->getTemplate("tpl.standardpage.html", true, true);

		if($metabar = $component->getMetabar()) {
			$tpl->setVariable('METABAR', $default_renderer->render($metabar));
		}
		if($sidebar = $component->getMainbar()) {
			$tpl->setVariable('SIDEBAR', $default_renderer->render($sidebar));
		}
		if($breadcrumbs = $component->getBreadcrumbs()) {
			$tpl->setVariable('BREADCRUMBS', $default_renderer->render($breadcrumbs));
		}

		$tpl->setVariable('CONTENT', $default_renderer->render($component->getContent()));

		if($component->getWithHeaders()) {
			$tpl = $this->setHeaderVars($tpl);
		}
		return $tpl->get();
	}

	/**
	 * When rendering the whole page, all resources must be included.
	 * This is for now and the page-demo to work, lateron this must be replaced
	 * with resources set as properties at the page or similar mechanisms.
	 */
	protected function setHeaderVars($tpl) {

		global $DIC;
		$il_tpl = $DIC["tpl"];

		$base_url = '../../../../../../';

		// always load jQuery
		include_once("./Services/jQuery/classes/class.iljQueryUtil.php");
		\iljQueryUtil::initjQuery($il_tpl);
		include_once("./Services/UICore/classes/class.ilUIFramework.php");
		\ilUIFramework::init($il_tpl);

		$il_js_files = $il_tpl->js_files_batch;
		asort($il_js_files);

		$js_files = array();
		foreach($il_js_files as $il_js_file=>$batch) {
			$js_files[] = $il_js_file;
		}

		$css_files = array();
		foreach($il_tpl->css_files as $il_css_file) {
			$css_files[] = $il_css_file['file'];
		}
		$css_files[] = \ilUtil::getStyleSheetLocation("filesystem", "delos.css");
		$css_files[] = \ilUtil::getNewContentStyleSheetLocation();

		$css_inline = $il_tpl->inline_css;

		$olc = '';
		if($il_tpl->on_load_code) {
			foreach ($il_tpl->on_load_code as $key => $value) {
				$olc .= implode(PHP_EOL, $value);
			 }
		}

		 //fill
		foreach ($js_files as $js_file) {
			$tpl->setCurrentBlock("js_file");
			$tpl->setVariable("JS_FILE", $js_file);
			$tpl->parseCurrentBlock();
		}
		foreach ($css_files as $css_file) {
			$tpl->setCurrentBlock("css_file");
			$tpl->setVariable("CSS_FILE", $css_file);
			$tpl->parseCurrentBlock();
		}
		$tpl->setVariable("CSS_INLINE", implode(PHP_EOL, $css_inline));
		$tpl->setVariable("OLCODE", $olc);

		$tpl->setVariable("BASE", $base_url);
		return $tpl;
	}

	/**
	 * @inheritdoc
	 */
	public function registerResources(\ILIAS\UI\Implementation\Render\ResourceRegistry $registry) {
		parent::registerResources($registry);
	}

	/**
	 * @inheritdoc
	 */
	protected function getComponentInterfaceName() {
		return array(
			Component\Layout\Page\Standard::class
		);

	}

}

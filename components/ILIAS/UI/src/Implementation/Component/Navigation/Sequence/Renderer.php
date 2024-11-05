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

namespace ILIAS\UI\Implementation\Component\Navigation\Sequence;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;

class Renderer extends AbstractComponentRenderer
{
    /**
     * @inheritdoc
     */
    public function render(Component\Component $component, RendererInterface $default_renderer): string
    {
        if ($component instanceof Component\Navigation\Sequence\Sequence) {
            return $this->renderLinear($component, $default_renderer);
        }

        $this->cannotHandleComponent($component);
    }

    protected function renderLinear(
        Component\Navigation\Sequence\Sequence $component,
        RendererInterface $default_renderer
    ): string {
        $tpl = $this->getTemplate("tpl.sequence.html", true, true);

        $binding = $component->getBinding();
        $vc_data = $component->getViewControls()?->getData() ?? [];
        $filter_data = $component->getFilter()?->getData() ?? [];
        $positions = $binding->getSequencePositions(
            $vc_data,
            $filter_data
        );

        $position = $component->getCurrentPosition();
        if ($position >= count($positions) || $position < 0) {
            $position = 0;
            $component = $component->withCurrentPosition($position);
        }

        $segment = $component->getBinding()->getSegment(
            $component->getSegmentBuilder(),
            $positions[$position],
            $vc_data,
            $filter_data
        );

        $ui_factory = $this->getUIFactory();
        $back = $ui_factory->button()->standard('back', $component->getNext(-1)->__toString())
            ->withSymbol($ui_factory->symbol()->glyph()->back())
            ->withUnavailableAction($position - 1 < 0);

        $next = $ui_factory->button()->standard('next', $component->getNext(1)->__toString())
            ->withSymbol($ui_factory->symbol()->glyph()->next())
            ->withUnavailableAction($position + 1 === count($positions));

        $tpl->setVariable('BACK', $default_renderer->render($back));
        $tpl->setVariable('NEXT', $default_renderer->render($next));

        $tpl->setVariable('VIEWCONTROLS', $default_renderer->render($component->getViewControls()));

        $tpl->setVariable('SEGMENT_TITLLE', $segment->getTitle());
        $tpl->setVariable('SEGMENT_CONTENTS', $default_renderer->render($segment->getContents()));

        if ($actions = $segment->getActions()) {
            $tpl->setVariable('ACTIONS_SEGMENT', $default_renderer->render($actions));
        }
        if ($actions = $component->getActions()) {
            $tpl->setVariable('ACTIONS_GLOBAL', $default_renderer->render($actions));
        }
        return $tpl->get();
    }
}

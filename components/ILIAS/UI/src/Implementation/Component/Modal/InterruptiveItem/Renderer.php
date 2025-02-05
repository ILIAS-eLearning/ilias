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

namespace ILIAS\UI\Implementation\Component\Modal\InterruptiveItem;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Modal\InterruptiveItem as ItemInterface;

class Renderer extends AbstractComponentRenderer
{
    public function render(
        Component $component,
        RendererInterface $default_renderer
    ): string {
        if ($component instanceof ItemInterface\Standard) {
            return $this->renderStandard($component, $default_renderer);
        } elseif ($component instanceof ItemInterface\KeyValue) {
            return $this->renderKeyValue($component, $default_renderer);
        }
        $this->cannotHandleComponent($component);
    }

    protected function renderStandard(
        ItemInterface\Standard $component,
        RendererInterface $default_renderer
    ): string {
        $tpl = $this->getTemplate(
            'tpl.standardInterruptiveItem.html',
            true,
            true
        );
        $icon = ($component->getIcon()) ?
            $default_renderer->render($component->getIcon()) : '';
        $tpl->setVariable('ITEM_ICON', $icon);
        $tpl->setVariable('ITEM_ID', $component->getId());
        $tpl->setVariable('ITEM_TITLE', $component->getTitle());
        if ($desc = $component->getDescription()) {
            $tpl->setVariable('ITEM_DESCRIPTION', $desc);
        }
        return $tpl->get();
    }

    protected function renderKeyValue(
        ItemInterface\KeyValue $component,
        RendererInterface $default_renderer
    ): string {
        $tpl = $this->getTemplate(
            'tpl.keyValueInterruptiveItem.html',
            true,
            true
        );
        $tpl->setVariable('ITEM_KEY', $component->getKey());
        $tpl->setVariable('ITEM_VALUE', $component->getValue());
        $tpl->setVariable('ITEM_ID', $component->getId());
        return $tpl->get();
    }
}

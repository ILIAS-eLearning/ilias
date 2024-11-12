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

namespace ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer;

use ILIAS\GlobalScreen\Scope\MainMenu\Factory\isItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\Item\Complex;
use ILIAS\UI\Component\Component;

/**
 * Class ComplexItemRenderer
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ComplexItemRenderer extends BaseTypeRenderer
{
    use MakeSlateAsync, SlateSessionStateCode {
        MakeSlateAsync::hash insteadof SlateSessionStateCode;
        MakeSlateAsync::unhash insteadof SlateSessionStateCode;
    }

    /**
     * @inheritDoc
     */
    public function getComponentWithContent(isItem $item): Component
    {
        /**
         * @var $item Complex
         */
        global $DIC;
        $content = $this->ui_factory->legacy()->legacyContent($DIC->ui()->renderer()->render($item->getContent()));

        return $this->ui_factory->mainControls()->slate()->legacy($item->getTitle(), $this->getStandardSymbol($item), $content);
    }
}

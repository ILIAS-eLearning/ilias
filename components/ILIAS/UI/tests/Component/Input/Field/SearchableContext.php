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
 */

declare(strict_types=1);

use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Implementation\Component\Symbol as S;
use ILIAS\UI\Implementation\Component as I;

trait SearchableContext
{
    public function getUIFactory(): NoUIFactory
    {
        return new class () extends NoUIFactory {
            public function symbol(): I\Symbol\Factory
            {
                return new S\Factory(
                    new S\Icon\Factory(),
                    new S\Glyph\Factory(),
                    new S\Avatar\Factory()
                );
            }
        };
    }
    protected function testWithSearchable(FormInput $component): void
    {
        $this->assertTrue($component->isSearchable(), 'The component should be searchable.');

        $html = $this->render($component);
        $expected1 = 'role="search"';
        $expected2 = 'c-field--searchable__item';
        $this->assertStringContainsString($expected1, $html);
        $this->assertStringContainsString($expected2, $html);
    }
}

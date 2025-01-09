<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Container\Form\Standard;

/**
 * ---
 * description: >
 *   Example showing catamorphism with Form to factor out classes and structure as JSON.
 *
 * expected output: >
 *   ILIAS shows a JSON like that:
 *   {
 *    "Standard Form Container Input": [
 *        {
 *            "Section Field Input": [
 *                "Text Field Input"
 *            ]
 *        },
 *        {
 *            "Section Field Input": [
 *                "Text Field Input"
 *            ]
 *        },
 *        "Text Field Input"
 *    ]
 *   }
 * ---
 */
function catamorph()
{
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $text_input = $ui->input()->field()
        ->text("Required Input", "User needs to fill this field")
        ->withRequired(true);

    $section = $ui->input()->field()->section(
        [$text_input],
        "Section with required field",
        "The Form should show an explaining hint at the bottom"
    );

    $form = $ui->input()->container()->form()->standard("", [$section, $section, $text_input]);

    $array = $form->foldWith(
        function ($c) {
            $subs = $c->getSubStructure();
            if ($subs !== null) {
                return [$c->getCanonicalName() => $subs];
            } else {
                return $c->getCanonicalName();
            }
        }
    );

    return $renderer->render(
        $ui->legacy()->content('<pre>' . print_r(json_encode($array, JSON_PRETTY_PRINT), true) . '</pre>')
    );
}

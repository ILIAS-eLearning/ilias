<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Modal\RoundTrip;

/**
 * ---
 * description: >
 *   Example for rendering a round trip modal with different buttons.
 *
 * expected output: >
 *   ILIAS shows three buttons with different titles. After clicking one of the first two buttonns a modal with primary
 *   and secondary buttons is opened. Both buttons do not have any functions. The modal can be closed by hitting the ESC
 *   key or clicking the "Close" button or the "X" glyph. A click onto the third button does not activate any actions.
 * ---
 */
function show_the_same_modal_with_different_buttons()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $modal = $factory->modal()->roundtrip('My Modal 1', $factory->legacy()->legacyContent('My Content'))
        ->withActionButtons([
            $factory->button()->primary('Primary Action', ''),
            $factory->button()->standard('Secondary Action', ''),
        ]);

    $out = '';
    $button1 = $factory->button()->standard('Open Modal 1', '#')
        ->withOnClick($modal->getShowSignal());
    $out .= ' ' . $renderer->render($button1);

    $button2 = $button1->withLabel('Also opens modal 1');
    $out .= ' ' . $renderer->render($button2);

    $button3 = $button2->withLabel('Does not open modal 1')
        ->withResetTriggeredSignals();
    $out .= ' ' . $renderer->render($button3);

    return $out . $renderer->render($modal);
}

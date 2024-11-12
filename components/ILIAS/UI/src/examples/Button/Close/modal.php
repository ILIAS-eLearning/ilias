<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Close;

/**
 * ---
 * description: >
 *   This example shows a scenario in which the Close Button is used in an overlay
 *   as indicated in the purpose description. Note that in the Modal the Close Button
 *   is properly placed in the top right corner.
 *
 * expected output: >
 *   ILIAS shows a button titled "Show Close Button Demo". Clicking the button will open a modal with text and a Close-Button.
 *   A click onto the button will close the modal.
 * ---
 */
function modal()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $modal = $factory->modal()->roundtrip(
        'Close Button Demo',
        $factory->legacy()->legacyContent('See the Close Button in the top right corner.')
    );
    $button1 = $factory->button()->standard('Show Close Button Demo', '#')
        ->withOnClick($modal->getShowSignal());

    return $renderer->render([$button1, $modal]);
}

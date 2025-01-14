<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Primary;

/**
 * ---
 * description: >
 *   This example provides the given button with an unavailable action. Note
 *   that the disabled attribute is set in the DOM. No action must be fired,
 *   even if done by keyboard.
 *
 * expected output: >
 *   ILIAS shows a grayed out inactive button with a title. Clicking the button won't activate any actions.
 *   Hovering over the button will change the look of the cursor.
 * ---
 */
function unavailable_action()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()->primary('Unavailable', '#')->withUnavailableAction();

    return $renderer->render([$button]);
}

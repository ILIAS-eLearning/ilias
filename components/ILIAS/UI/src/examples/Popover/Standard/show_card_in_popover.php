<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Popover\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard popover.
 *
 * expected output: >
 *   ILIAS shows a button titled "Show Card".
 *   A click onto the button opens a card popover with...
 *   - a popover title: Card
 *   - a card image: ILIAS-Logo
 *   - a card title: Title
 *   - a card description: Hello World, I'm a card
 *   The popover can be closed by clicking onto the ILIAS background outside of the popover.
 * ---
 */
function show_card_in_popover()
{
    global $DIC;

    // This example shows how to render a card containing an image and a descriptive list inside a popover.
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $image = $factory->image()->responsive("./assets/images/logo/HeaderIcon.svg", "Thumbnail Example");
    $card = $factory->card()->standard("Title", $image)->withSections(array($factory->legacy("Hello World, I'm a card")));
    $popover = $factory->popover()->standard($card)->withTitle('Card');
    $button = $factory->button()->standard('Show Card', '#')
        ->withOnClick($popover->getShowSignal());

    return $renderer->render([$popover, $button]);
}

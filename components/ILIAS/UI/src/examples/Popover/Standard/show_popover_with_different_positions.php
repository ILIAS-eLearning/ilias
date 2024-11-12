<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Popover\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard popover with different positions.
 *
 * expected output: >
 *   In this example ILIAS shows depending on the screen size and scroll position the popover's text accordingly.
 *   ILIAS shows three different buttons:
 *   - Auto Popover
 *   - Vertical Popover
 *   - Horizontal Popover
 *   Click onto the button and check if the popover's behaviour aligns with the description in the first sentence of this example's
 *   expected output.
 *   Please do some tests with different size and scroll positions on your browser window to see if the functions still work.
 * ---
 */
function show_popover_with_different_positions()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $content = $factory->legacy()->legacyContent('The position of this popover is calculated automatically based on the available space. Note that the max width CSS setting is used here, as this text is quite long.');
    $popover = $factory->popover()->standard($content);
    $button = $factory->button()->standard('Auto Popover', '#')
        ->withOnClick($popover->getShowSignal());

    $content = $factory->legacy()->legacyContent('The position of this popover is either on top or bottom of the triggerer, based on the available space');
    $popover2 = $factory->popover()->standard($content)
        ->withVerticalPosition();
    $button2 = $factory->button()->standard('Vertical Popover', '#')
        ->withOnClick($popover2->getShowSignal());

    $content = $factory->legacy()->legacyContent('The position of this popover is either on the left or right of the triggerer, based on the available space');
    $popover3 = $factory->popover()->standard($content)
        ->withHorizontalPosition();
    $button3 = $factory->button()->standard('Horizontal Popover', '#')
        ->withOnClick($popover3->getShowSignal());

    $buttons = implode(' ', [$renderer->render($button), $renderer->render($button2), $renderer->render($button3)]);

    return $buttons . $renderer->render([$popover, $popover2, $popover3]);
}

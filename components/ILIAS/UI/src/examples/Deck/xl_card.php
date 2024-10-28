<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Deck;

/**
 * ---
 * description: >
 *   Example for rendering a XL card
 *
 * expected output: >
 *   ILIAS shows three "Cards" with a title and text each. The number of cards displayed in each line will change according
 *   the size of the browser window/desktop.
 * ---
 */
function xl_card()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generate some content
    $content = $f->listing()->descriptive(
        array(
            "Entry 1" => "Some text",
            "Entry 2" => "Some more text",
        )
    );

    //Define the some responsive image
    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    //Define the card by using the content and the image
    $card = $f->card()->standard(
        "Title",
        $image
    )->withSections(array(
        $content
    ));

    //Define the extra large deck
    $deck = $f->deck(array($card,$card,$card))->withExtraLargeCardsSize();

    //Render
    return $renderer->render($deck);
}

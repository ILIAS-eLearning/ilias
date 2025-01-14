<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Card\RepositoryObject;

/**
 * ---
 * description: >
 *   Example for rendering a repository card with an object icon title action.
 *
 * expected output: >
 *   ILIAS shows a base logo. Additionally a dropdown menu is displayed in the right top corner. The menu can be
 *   opened by a click and displays links. Only the last link functions.
 * ---
 */
function with_object_icon_and_actions()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $icon = $f->symbol()->icon()->standard("crs", 'Course');

    $items = array(
        $f->button()->shy("Go to Course", "#"),
        $f->button()->shy("Go to Portfolio", "#"),
        $f->divider()->horizontal(),
        $f->button()->shy("ilias.de", "http://www.ilias.de")
    );

    $dropdown = $f->dropdown()->standard($items);

    $content = $f->listing()->descriptive(
        array(
            "Entry 1" => "Some text",
            "Entry 2" => "Some more text",
        )
    );

    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    $card = $f->card()->repositoryObject(
        "Title",
        $image
    )->withActions(
        $dropdown
    )->withObjectIcon(
        $icon
    )->withSections(
        array(
            $content,
            $content,
            $content
        )
    );

    //Render
    return $renderer->render($card);
}

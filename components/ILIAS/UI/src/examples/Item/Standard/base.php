<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard item.
 *
 * expected output: >
 *   ILIAS shows a box including the following informations: A heading "Item Title" with a dummy text in small writings
 *   ("Lorem ipsum...") below. Beneath those you can see a fine line and more informations about "Origin", "Last Update"
 *   and "Location". Additionally a action menu is displayed in the box on the right top.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $actions = $f->dropdown()->standard(array(
        $f->button()->shy("ILIAS", "https://www.ilias.de"),
        $f->button()->shy("GitHub", "https://www.github.com")
    ));
    $app_item = $f->item()->standard("Item Title")
        ->withActions($actions)
        ->withProperties(array(
            "Origin" => "Course Title 1",
            "Last Update" => "24.11.2011",
            "Location" => "Room 123, Main Street 44, 3012 Bern"))
        ->withDescription("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.");
    return $renderer->render($app_item);
}

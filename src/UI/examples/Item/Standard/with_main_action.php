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
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard item with main actions.
 *
 * expected output: >
 *   ILIAS shows a box including the following informations: A heading with a dummy text in small writings
 *   ("Lorem ipsum...") below. Beneath those you can see a fine line and more informations about "Origin", "Last Update"
 *   and "Location". Additionally a action menu is displayed in the box on the right top. On the left next to the action
 *   menu a "Edit" button ist rendered.
 * ---
 */
function with_main_action()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $actions = $f->dropdown()->standard(array(
        $f->button()->shy("ILIAS", "https://www.ilias.de"),
        $f->button()->shy("GitHub", "https://www.github.com")
    ));
    $main_action = $f->button()->standard("Edit", "#");
    $app_item = $f->item()->standard("Item Title")
        ->withActions($actions)
        ->withMainAction($main_action)
        ->withProperties(array(
            "Origin" => "Course Title 1",
            "Last Update" => "24.11.2011",
            "Location" => "Room 123, Main Street 44, 3012 Bern"))
        ->withDescription("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.");
    return $renderer->render($app_item);
}

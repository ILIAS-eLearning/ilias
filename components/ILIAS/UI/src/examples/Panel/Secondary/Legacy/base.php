<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Panel\Secondary\Legacy;

/**
 * ---
 * description: >
 *  Example for rendering a secondary legacy panel listing.
 *
 * expected output: >
 *   ILIAS shows a panel titled "Legacy Panel Title" and including an action menu. The panel's content is set to "Legacy Content".
 * ---
 */
function base()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $actions = $factory->dropdown()->standard(array(
        $factory->button()->shy("ILIAS", "https://www.ilias.de"),
        $factory->button()->shy("GitHub", "https://www.github.com")
    ));

    $legacy = $factory->legacy()->content("Legacy content");

    $panel = $factory->panel()->secondary()->legacy(
        "Legacy panel title",
        $legacy
    )->withActions($actions);

    return $renderer->render($panel);
}

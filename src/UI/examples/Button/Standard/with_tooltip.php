<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Standard;

function with_tooltip()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()
        ->standard("Goto ILIAS", "http://www.ilias.de")
        ->withHelpTopics(
            ...$f->helpTopics("ilias", "learning management system")
        );

    return $renderer->render($button);
}

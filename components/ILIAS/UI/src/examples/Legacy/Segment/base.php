<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Legacy\Segment;

/**
 * ---
 * description: >
 *   Example for rendering a legacy segment.
 *
 * expected output: >
 *   ILIAS shows the word "content".
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $legacy = $f->legacy()->segment('title', 'content');
    return $renderer->render($legacy);
}

<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Legacy\LegacySegment;

/**
 * ---
 * description: >
 *   Example for rendering a legacy segment.
 *
 * expected output: >
 *   ILIAS shows a box titled "Panel Title" and a grey background. In the lower part of the box the text "Legacy Content"
 *   on a white background is written.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $legacy = $f->legacy()->legacySegment('title', 'content');
    return $renderer->render($legacy);
}

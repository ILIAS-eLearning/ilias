<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\MainControls\SystemInfo;

use ILIAS\UI\Component\MainControls\SystemInfo;

/**
 * ---
 * description: >
 *   This example show how the UI-Elements itself looks like. For a full
 *   example use the example of the UI-Component Layout\Page\Standard.
 *
 * expected output: >
 *   Instead of but one message, ILIAS will display three messages in differently
 *   colored boxes. The intensity of the colors decreases from top to bottom.
 * ---
 */
function multiple()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $first = $f->mainControls()
        ->systemInfo('This is the first Message!', 'content of the first message...')
        ->withDenotation(SystemInfo::DENOTATION_BREAKING);
    $second = $f->mainControls()
        ->systemInfo('This is the second Message!', 'content of the second message...')
        ->withDenotation(SystemInfo::DENOTATION_IMPORTANT);
    $third = $f->mainControls()
        ->systemInfo('This is the third Message!', 'content of the third message...');

    return $renderer->render([$first, $second, $third]);
}

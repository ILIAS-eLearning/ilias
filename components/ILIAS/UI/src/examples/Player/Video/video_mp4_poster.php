<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Player\Video;

/**
 * ---
 * description: >
 *   Example for rendering a mp4 video player with a poster as a start screen.
 *
 * expected output: >
 *   ILIAS shows a base video player with the ILIAS Logo as a start screen including all functions like stop/start symbols.
 *   The subtitle symbol (CC) is missing.
 *
 *   In addition following functions have to be tested:
 *   - The video starts playing if clicking the start/stop symbol in the middle of the image. The video stops after another click.
 *   - The sound fades or raises if the volumes gets changed by using the volume control.
 *   - Clicking the full screen icon maximizes the video player to the size of the desktop size. Clicking ESC will diminish the video player.
 * ---
 */
function video_mp4_poster(): string
{
    global $DIC;
    $renderer = $DIC->ui()->renderer();
    $f = $DIC->ui()->factory();

    $video = $f->player()->video("https://files.ilias.de/ILIAS-Video.mp4");
    $video = $video->withPoster("assets/ui-examples/images/Image/HeaderIconLarge.svg");

    return $renderer->render($video);
}

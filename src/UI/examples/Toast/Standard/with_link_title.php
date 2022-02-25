<?php declare(strict_types=1);

namespace ILIAS\UI\examples\Toast\Standard;

function with_link_title() : string
{
    global $DIC;
    $tc = $DIC->ui()->factory()->toast()->container()->withAdditionalToast(
        $DIC->ui()->factory()->toast()->standard(
            $DIC->ui()->factory()->link()->standard('Example', 'https://www.ilias.de'),
            $DIC->ui()->factory()->symbol()->icon()->standard('info', 'Test')
        )
    );
    return $DIC->ui()->renderer()->render($tc);
}

<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Toast\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard toast with a link title.
 *
 * expected output: >
 *   ILIAS shows a blue button "Show". Clicking onto the button opens a message "Example" on the right top edge which
 *   disappears after a few seconds. The message box's title is rendered as link.
 * ---
 */
function with_link_title(): string
{
    global $DIC;
    $tc = $DIC->ui()->factory()->toast()->container();

    $toasts = [
        $DIC->ui()->factory()->toast()->standard(
            $DIC->ui()->factory()->link()->standard('Example', 'https://www.ilias.de'),
            $DIC->ui()->factory()->symbol()->icon()->standard('info', 'Test')
        )
    ];

    $toasts = base64_encode($DIC->ui()->renderer()->renderAsync($toasts));
    $button = $DIC->ui()->factory()->button()->standard($DIC->language()->txt('show'), '');
    $button = $button->withAdditionalOnLoadCode(function ($id) use ($toasts) {
        return "$id.addEventListener('click', () => {
            $id.parentNode.querySelector('.il-toast-container').innerHTML = atob('$toasts');
            $id.parentNode.querySelector('.il-toast-container').querySelectorAll('script').forEach(element => {
                let newScript = document.createElement('script');
                newScript.innerHTML = element.innerHTML;
                element.parentNode.appendChild(newScript);
            })
        });";
    });

    return $DIC->ui()->renderer()->render([$button,$tc]);
}

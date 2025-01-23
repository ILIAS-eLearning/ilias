<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\Radio;

/**
 * ---
 * description: >
 *   An example showing a radio with search
 *
 * expected output: >
 *   A form with a Radio with Search that can be expanded, collapsed and filtered.
 * ---
 */
function searchable_email_templates()
{
    //Step 1: Declare dependencies
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Step 2: define the radio with options
    $options = array(
        "1" => "Welcome (External Guest)",
        "2" => "Welcome (Office)",
        "3" => "Welcome (International)",
        "4" => "Reminder Expiration",
        "5" => "Reminder Deadline",
        "6" => "Passed",
        "7" => "Failed - Try Again",
        "8" => "Failed Permanently",
        "9" => "Notification for Trainer",
        "10" => "Notification for Leader",
        "11" => "Notification for Staff",
        "12" => "Hotel Booking Request",
        "13" => "Hotel Booking Information Participant",
        "14" => "Hotel Booking Information Trainer",
    );

    $radio = $ui->input()->field()->radio("Email Template", "Choose the wording for your email. You can add custom templates in the administration settings.");
    $radio = $radio->withSearchable(true);

    foreach ($options as $value => $label) {
        $radio = $radio->withOption((string) $value, $label);
    }

    //Step 3: define form and form actions
    $form = $ui->input()->container()->form()->standard('#', ['radio' => $radio]);

    //Step 4: Render the radio with the enclosing form.
    return $renderer->render($form);
}

<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\Radio;

/**
 * ---
 * description: >
 *   An example using the Radio with Search for selecting a section design from a content style.
 *   We use a heavily styled preview as the label.
 *
 * expected output: >
 *   A Radio with Search allowing to filter through mockups of content style sections.
 * ---
 */
function searchable_section_style_templates()
{
    //Step 1: Declare dependencies
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $DIC->ui()->mainTemplate()->addCss('./assets/ui-examples/css/radio_searchable_section_style.css');

    //Step 2: define the radio with options
    $template1 = <<<HTML
<div class="ilc_section_Card" style="min-height: auto;">Card</div>
HTML;

    $template2 = <<<HTML
<div class="ilc_section_Citation" style="min-height: auto;">Citation</div>
HTML;

    $template3 = <<<HTML
<div class="ilc_section_Example" style="min-height: auto;">Example</div>
HTML;

    $template4 = <<<HTML
<div class="ilc_section_Excursus" style="min-height: auto;">Excursus</div>
HTML;

    $options = array(
        "1" => $template1,
        "2" => $template2,
        "3" => $template3,
        "4" => $template4,
    );

    $single_select = $ui->input()->field()->radio("Content Style", "Edit and add more styles by using a custom content style.")
                    ->withSearchable(true);

    foreach ($options as $value => $label) {
        $single_select = $single_select->withOption((string) $value, $label);
    }

    $single_select = $single_select->withValue("2");

    //Step 3: define form and form actions
    $form = $ui->input()->container()->form()->standard('#', ['radio' => $single_select]);

    //Step 4: Render the radio with the enclosing form.
    return $renderer->render($form);
}

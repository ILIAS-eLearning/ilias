<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\MultiSelect;

/**
 * ---
 * description: >
 *   An example showing a multiselect with search
 *
 * expected output: >
 *   A form with a Searchable Multi-Select that can be expanded, collapsed and filtered.
 * ---
 */

function searchable()
{
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $json_data = file_get_contents(ILIAS_ABSOLUTE_PATH . '/public/assets/ui-examples/misc/multiselect_searchable_data.json');
    $data = json_decode($json_data, true);

    $multi_select = $ui->input()->field()->multiselect("Group Members", $data)
                    ->withSearchable();

    $form = $ui->input()->container()->form()->standard('#', [$multi_select]);

    return $renderer->render($form);
}

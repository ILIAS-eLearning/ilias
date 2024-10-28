<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\MultiSelect;

/**
 * ---
 * description: >
 *   Multi-Select without options
 *
 * expected output: >
 *   ILIAS shows an empty field with the following text: "No options". Clicking "Save" will result into following output:
 *
 *   Array
 *   (
 *       [empty] =>
 *   )
 * ---
 */
function empty_options()
{
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();

    $multi = $ui->input()->field()
        ->multiselect("No options", []);

    $form = $ui->input()->container()->form()->standard('#', ['empty' => $multi]);

    if ($request->getMethod() == "POST") {
        try {
            $form = $form->withRequest($request);
            $result = $form->getData();
        } catch (\InvalidArgumentException $e) {
            $result = "No result. Probably, the other form was used.";
        }
    } else {
        $result = "No result yet.";
    }

    return
        "<pre>" . print_r($result, true) . "</pre><br/>" .
        $renderer->render($form);
}

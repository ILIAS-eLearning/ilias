<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\File;

/**
 * ---
 * description: >
 *   Example of how to create and render a file input field and attach it to a form.
 *
 * expected output: >
 *   ILIAS shows a field titled "Upload File" next to a box surrounded by dashed lines. You can choose a file by dragging
 *   the file to the box or by clicking "Select files". If you save your selection ILIAS will reset the field.
 * ---
 */
function base()
{
    // Step 0: Declare dependencies.
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    // Step 1: Define the input field.
    // See the implementation of a UploadHandler in components/ILIAS/UI_/classes/class.ilUIDemoFileUploadHandlerGUI.php
    $file_input = $ui->input()->field()->file(new \ilUIDemoFileUploadHandlerGUI(), "Upload File", "you can drop your files here");

    // Step 2: Define the form and attach the field.
    $form = $ui->input()->container()->form()->standard("#", [$file_input]);

    // Step 4: Render the form.
    return $renderer->render($form);
}

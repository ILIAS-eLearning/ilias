<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\Rating;

function base()
{
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();

    $txt = "This allows for a preceding text and longer questions to ask.";

    $rating = $ui->input()->field()->rating("Rate with the Stars:", "change the rating")
        ->withQuestionText($txt)
        ->withValue(3);
    $rating_required = $ui->input()->field()->rating("Rate with the Stars:", 'this is required')
        ->withRequired(true)
        ->withOptionLabels('nah', 'ok', 'undecided', 'good', 'awsome');
    $rating_disabled = $ui->input()->field()->rating("Rate with the Stars:", "this is disabled")
        ->withValue(2)
        ->withDisabled(true);

    $form = $ui->input()->container()->form()
        ->standard('#', [
            'rating_1' => $rating,
            'rating_2' => $rating_required,
            'rating_3' => $rating_disabled
        ]);

    if ($request->getMethod() == "POST") {
        $form = $form->withRequest($request);
        $result = $form->getData();
    } else {
        $result = "No result yet.";
    }

    //Step 5: Render the radio with the enclosing form.
    return
        "<pre>" . print_r($result, true) . "</pre><br/>" .
        $renderer->render($form);
}

<?php

declare(strict_types=1);

namespace ILIAS\UI\Examples\Entity\Standard;

/**
 * ---
 * expected output: >
 *   ILIAS shows the rendered Component.
 * ---
 */
function semantic_groups()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $entity = $f->entity()->standard('Primary Identifier', 'Secondary Identifier')
        ->withBlockingAvailabilityConditions($f->legacy()->legacyContent('Blocking Conditions'))
        ->withFeaturedProperties($f->legacy()->legacyContent('Featured_properties'))
        ->withPersonalStatus($f->legacy()->legacyContent('Personal Status'))
        ->withMainDetails($f->legacy()->legacyContent('Main Details'))
        ->withAvailability($f->legacy()->legacyContent('Availability'))
        ->withDetails($f->legacy()->legacyContent('Details'))
        ->withReactions($f->button()->tag('reaction', '#'))
        ->withPrioritizedReactions($f->symbol()->glyph()->like())
        ->withActions($f->button()->shy('action', '#'))
    ;

    return $renderer->render($entity);
}

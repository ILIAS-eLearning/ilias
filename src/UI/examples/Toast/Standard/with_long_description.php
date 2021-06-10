<?php declare(strict_types=1);

namespace ILIAS\UI\examples\Toast\Standard;

/**
 * With a very long description
 */
function with_long_description()
{
    global $DIC;
    $toast = $DIC->ui()->factory()->toast()->standard(
        'Example',
        $DIC->ui()->factory()->symbol()->icon()->standard('info', 'Test')
    )->withDescription(
        'This is an example description which is very long to provide a representative view of the object when it has ' .
        'to occupy enough space to show a very long toast. This may not be the favorable way  of displaying  a toast, ' .
        'since toast are assumed to be readable in a short time due to the temporary visibility, therefore they only ' .
        'should contain short description which can be read withing seconds. But even if this long description softly ' .
        'violates the concepts  of toast itself due to its long character it still provides a good view on the ' .
        'scalability of the object and could therefore be called to  proof its responsivity which confirms its benefit ' .
        'as an example in spite of its unnatural form and missing usecase for productive systems'
    );
    return $DIC->ui()->renderer()->render($toast);
}

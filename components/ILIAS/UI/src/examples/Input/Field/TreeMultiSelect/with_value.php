<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\TreeMultiSelect;

use ILIAS\UI\Component\Input\Field\Node\NodeRetrieval;
use ILIAS\UI\Component\Input\Field\Node\Factory as NodeFactory;
use ILIAS\UI\Component\Input\Field\Node\Node;
use ILIAS\UI\Component\Symbol\Icon\Factory as IconFactory;

/**
 * ---
 * description: >
 *   The example shows how to create and render a Tree Multi Select Field and attach it to a
 *   Form. This example does not contain any data processing.
 *
 * expected output: >
 *   @todo: describe final product here.
 * ---
 */
function with_value(): string
{
    global $DIC;

    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $node_retrieval = new class () implements NodeRetrieval {
        public function getNode(NodeFactory $node_factory, int|string $node_id): ?Node
        {
            return $node_factory->leaf($node_id, "dummy leaf node $node_id");
        }
        public function getNodes(
            NodeFactory $node_factory,
            IconFactory $icon_factory,
            ?string $parent_id = null,
        ): \Generator {
            yield from [];
        }
    };

    $input = $factory->input()->field()->treeMultiSelect(
        $node_retrieval,
        "select multiple nodes",
        "there should already be a minor selection.",
    );

    $input = $input->withValue(['1.2', '2.3', '3.1']);

    $form = $factory->input()->container()->form()->standard('#', [$input]);

    return $renderer->render($form);
}

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
function with_select_child_nodes(): string
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
            yield $node_factory->node('1', 'branch node 1', null,
                $node_factory->node('1.1', 'branch node 1.1', null,
                    $node_factory->leaf('1.1.1', 'leaf node 1.1.1'),
                    $node_factory->leaf('1.1.2', 'leaf node 1.1.2'),
                ),
                $node_factory->leaf('1.2', 'leaf node 1.2'),
            );
        }
    };

    $input = $factory->input()->field()->treeMultiSelect(
        $node_retrieval,
        "select multiple nodes",
        "you can also select child-nodes of selected nodes!",
    );

    $input = $input->withSelectChildNodes(true);

    $form = $factory->input()->container()->form()->standard('#', [$input]);

    return $renderer->render($form);
}

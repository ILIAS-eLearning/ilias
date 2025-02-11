<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\TreeSelect;

use ILIAS\UI\Component\Input\Field\Node\NodeRetrieval;
use ILIAS\UI\Component\Input\Field\Node\Factory as NodeFactory;
use ILIAS\UI\Component\Input\Field\Node\Node;
use ILIAS\UI\Component\Symbol\Icon\Factory as IconFactory;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\URI;
use ILIAS\Filesystem\Stream\Streams;

/**
 * ---
 * description: >
 *   The example shows how to create and render a Tree Select Field and attach it to a
 *   Form. This example does also show data processing.
 *
 * expected output: >
 *   @todo: describe final product here.
 * ---
 */
function base(): string
{
    global $DIC;

    $http = $DIC->http();
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $get_request = $http->wrapper()->query();
    $data_factory = new \ILIAS\Data\Factory();
    $refinery_factory = new \ILIAS\Refinery\Factory($data_factory, $DIC->language());

    $example_uri = $data_factory->uri((string) $http->request()->getUri());
    $url_builder = new URLBuilder($example_uri);
    [$url_builder, $node_id_parameter] = $url_builder->acquireParameter(explode('\\', __NAMESPACE__), "node_id");
    [$url_builder, $process_form_parameter] = $url_builder->acquireParameter(explode('\\', __NAMESPACE__), "process");

    $node_retrieval = new TreeSelectExampleNodeRetrieval($url_builder, $node_id_parameter);

    // simulates an async node rendering endpoint:
    if ($get_request->has($node_id_parameter->getName())) {
        $parent_node_id = $get_request->retrieve(
            $node_id_parameter->getName(),
            $refinery_factory->kindlyTo()->string(),
        );

        $node_generator = $node_retrieval->getNodes(
            $factory->input()->field()->node(),
            $factory->symbol()->icon(),
            $parent_node_id
        );

        $html = '';
        foreach ($node_generator as $node) {
            $html .= $renderer->renderAsync($node);
        }

        $http->saveResponse(
            $http->response()
                 ->withHeader('Content-Type', 'text/html; charset=utf-8')
                 ->withBody(Streams::ofString($html))
        );
        $http->sendResponse();
        $http->close();
    }

    $input = $factory->input()->field()->treeSelect(
        $node_retrieval,
        "select a single node",
        "you can open the select input by clicking the button above.",
    );

    $form = $factory->input()->container()->form()->standard(
        (string) $url_builder->withParameter($process_form_parameter, '1')->buildURI(),
        [$input]
    );

    // simulates a form processing endpoint:
    if ($get_request->has($process_form_parameter->getName())) {
        $form = $form->withRequest($http->request());
        $data = $form->getData();
    } else {
        $data = 'No submitted data yet.';
    }

    return '<pre>' . print_r($data, true) . '</pre>' . $renderer->render($form);
}

/** @noinspection AutoloadingIssuesInspection */
class TreeSelectExampleNodeRetrieval implements NodeRetrieval
{
    public function __construct(
        protected URLBuilder $builder,
        protected URLBuilderToken $node_id_parameter,
    ) {
    }

    public function getNode(NodeFactory $node_factory, int|string $node_id): ?Node
    {
        return $node_factory->leaf($node_id, "dummy leaf node $node_id");
    }

    public function getNodes(NodeFactory $node_factory, IconFactory $icon_factory, ?string $parent_id = null): \Generator
    {
        if (null !== $parent_id) {
            yield $this->getExampleNodeChildren($node_factory, $parent_id);
            return;
        }

        yield $node_factory->node('1', 'branch 1', null, ...$this->getExampleNodeChildren($node_factory, '1'));
        yield $node_factory->node('2', 'branch 2', null, ...$this->getExampleNodeChildren($node_factory, '2'));
        yield $node_factory->leaf('3', 'leaf 3');
    }

    protected function getExampleNodeChildren(NodeFactory $node_factory, string|int $parent_id): array
    {
        return [
            $node_factory->node("$parent_id.1", "branch $parent_id.1", null,
                $node_factory->leaf("$parent_id.1.1", "leaf $parent_id.1.1"),
                $node_factory->leaf("$parent_id.1.2", "leaf $parent_id.1.2"),
                $node_factory->leaf("$parent_id.1.3", "leaf $parent_id.1.3"),
            ),
            $node_factory->node("$parent_id.2", "branch $parent_id.2", null,
                $node_factory->leaf("$parent_id.2.1", "leaf $parent_id.2.1"),
                $node_factory->leaf("$parent_id.2.2", "leaf $parent_id.2.2"),
                $node_factory->leaf("$parent_id.2.3", "leaf $parent_id.2.3"),
            ),
            $node_factory->async($this->getAsyncNodeRenderUrl("$parent_id.3"),"$parent_id.3", "async branch $parent_id.3"),
            $node_factory->leaf("$parent_id.4", "leaf $parent_id.4"),
        ];
    }

    protected function getAsyncNodeRenderUrl(int|string $node_id): URI
    {
        return $this->builder->withParameter($this->node_id_parameter, (string) $node_id)->buildURI();
    }
}

<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Navigation\Sequence;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\UI\Component\Navigation\Sequence\Binding;
use ILIAS\UI\Component\Navigation\Sequence\SegmentBuilder;
use ILIAS\UI\Component\Navigation\Sequence\Segment;
use ILIAS\UI\URLBuilder;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ---
 * description: >
 *   Base example for rendering a sequence navigation.
 *
 * expected output: >
 *   ILIAS shows a group of buttons and a characteristic value listing.
 *   Buttons are "back" and "next", of which the back button is inactive until
 *   the next button was clicked.
 *   A vieww control allows the user to select chunks of data, and an additional
 *   button (without real function) is labeled "a global action".
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC['ui.factory'];
    $r = $DIC['ui.renderer'];
    $df = new \ILIAS\Data\Factory();
    $refinery = $DIC['refinery'];
    $request = $DIC->http()->request();

    $binding = new class ($f, $r) implements Binding {
        private array $seq_data;

        public function __construct(
            protected UIFactory $f,
            protected UIRenderer $r
        ) {
            $this->seq_data = [
                ['c0', 'pos 1', '<div style="width: 100%;
                                            height: 500px;
                                            background-color: #b8d7ea;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;">
                                            placeholder for the segment at position 1</div>'],
                ['c0', 'pos 2', '<div style="width: 100%;
                                            height: 700px;
                                            background-color: #f6d9a1;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;">
                                            placeholder for the segment at position 2</div>'],
                ['c1', 'pos 3', 'the segment at position 3'],
                ['c2', 'pos 4', 'the segment at position 4'],
                ['c1', 'pos 5', 'the segment at position 5'],
            ];
        }

        public function getSequencePositions(
            mixed $viewcontrol_values,
            mixed $filter_values
        ): array {
            $chunks = $viewcontrol_values['chunks'] ?? [];
            $chunks[] = 'c0';
            return array_values(
                array_filter(
                    $this->seq_data,
                    fn($posdata) => in_array($posdata[0], $chunks)
                )
            );
        }

        public function getSegment(
            mixed $position_data,
            mixed $viewcontrol_values,
            mixed $filter_values
        ): Segment {
            list($chunk, $title, $data) = $position_data;

            $segment = $this->f->legacy()->segment($title, $data);

            if ($chunk === 'c0') {
                $segment = $segment->withSegmentActions(
                    $this->f->button()->standard('a segment action for ' . $title, '#')
                );
            }
            return $segment;
        }
    };

    $viewcontrols = $f->input()->container()->viewControl()->standard([
        $f->input()->viewControl()->fieldSelection(
            [
                'c1' => 'chunk 1',
                'c2' => 'chunk 2',
            ],
            'shown chunks',
            'apply'
        )
        ->withAdditionalTransformation($refinery->custom()->transformation(
            fn($v) => ['chunks' => $v]
        ))
    ]);

    $global_actions = [
        $f->button()->standard('a global action', '#')
    ];

    $sequence = $f->navigation()->sequence($binding)
        ->withViewControls($viewcontrols)
        ->withId('example')
        ->withActions($global_actions)
        ->withRequest($request);

    $out = [];
    $out[] = $sequence;

    return $r->render($out);
}

<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Navigation\Sequence;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Navigation\Sequence\Binding;
use ILIAS\UI\Component\Navigation\Sequence\SegmentBuilder;
use ILIAS\UI\Component\Navigation\Sequence\Segment;
use ILIAS\UI\URLBuilder;
use Psr\Http\Message\ServerRequestInterface;

function base()
{
    global $DIC;
    $f = $DIC['ui.factory'];
    $r = $DIC['ui.renderer'];
    $df = new \ILIAS\Data\Factory();
    $refinery = $DIC['refinery'];
    $request = $DIC->http()->request();

    $binding = new class ($f) implements Binding {
        public function __construct(
            protected UIFactory $f
        ) {
        }

        private const SEQDATA = [
            ['c0', 'pos 1', 'data 1'],
            ['c1', 'pos 2', 'data 2'],
            ['c1', 'pos 3', 'data 3'],
            ['c2', 'pos 4', 'data 4'],
            ['c2', 'pos 5', 'data 5'],
        ];

        public function getSequencePositions(
            array $viewcontrol_values,
            array $filter_values
        ): array {
            //var_dump($viewcontrol_values); die();
            //this is a filter, not a vc!
            $chunks = $viewcontrol_values['chunks'] ?? [];
            $chunks[] = 'c0';
            return array_values(
                array_filter(
                    self::SEQDATA,
                    fn($posdata) => in_array($posdata[0], $chunks)
                )
            );
        }

        public function getSegment(
            SegmentBuilder $builder,
            mixed $position_data,
            array $viewcontrol_values,
            array $filter_values
        ): Segment {
            list($chunk, $title, $data) = $position_data;

            $segment = $builder->build($title, $this->f->legacy($data));

            if ($chunk === 'c2') {
                $segment = $segment->withActions([
                   $action = $this->f->button()->standard('a segment action for ' . $title, '#')
                ]);
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

    //$filter = $f->input()->container()->filter()->standard();

    $global_actions = [
        $f->button()->standard('a global action', '#')
    ];


    $sequence = $f->navigation()->sequence($binding)
        ->withViewControls($viewcontrols)
        //->withFilter($filter),
        ->withActions($global_actions)
        ->withRequest($request);

    $out = [];
    $out[] = $sequence;

    return $r->render($out);
}

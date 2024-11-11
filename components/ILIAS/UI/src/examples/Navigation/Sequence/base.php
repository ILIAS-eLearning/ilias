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
        private array $seq_data;

        public function __construct(
            protected UIFactory $f
        ) {
            $this->seq_data = [
                ['c0', 'pos 1', [getListing($f)]],
                ['c0', 'pos 2', [$f->legacy('some legacy content')]],
                ['c1', 'pos 3', [getImage($f)]],
                ['c2', 'pos 4', [getTable($f)]],
                ['c1', 'pos 5', [$f->legacy('some more legacy content')]],
            ];
        }

        public function getSequencePositions(
            array $viewcontrol_values,
            array $filter_values
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
            SegmentBuilder $builder,
            mixed $position_data,
            array $viewcontrol_values,
            array $filter_values
        ): Segment {
            list($chunk, $title, $data) = $position_data;

            $segment = $builder->build($title, ...$data);

            if ($chunk === 'c1') {
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

function getListing(UIFactory $ui_factory): \ILIAS\UI\Component\Listing\CharacteristicValue\Text
{
    $items = [
       'Any Label for the First Item' => 'Item 1',
       'Another Label for the Second Item' => 'Item 2',
       'Third Item Label' => 'Item 3',
       'Fourth Item Label' => 'Item 4'
    ];
    return $ui_factory->listing()->characteristicValue()->text($items);
}

function getImage(UIFactory $ui_factory): \ILIAS\UI\Component\Image\Image
{
    return $ui_factory->image()->responsive("assets/ui-examples/images/Image/HeaderIconLarge.svg", '');
}

function getTable(UIFactory $ui_factory): \ILIAS\UI\Component\Table\Presentation
{
    return $ui_factory->table()->presentation(
        'Presentation Table',
        [],
        function ($row, $record, $ui_factory, $environment) { //mapping-closure
            return $row
                ->withHeadline($record['title'])
                ->withSubheadline($record['type'])
                ->withImportantFields(
                    array(
                        $record['begin_date'],
                        $record['location'],
                        'Available Slots: ' => $record['bookings_available']
                    )
                )

                ->withContent(
                    $ui_factory->listing()->descriptive(
                        array(
                            'Targetgroup' => $record['target_group'],
                            'Goals' => $record['goals'],
                            'Topics' => $record['topics']
                        )
                    )
                )

                ->withFurtherFieldsHeadline('Detailed Information')
                ->withFurtherFields(
                    array(
                        'Location: ' => $record['location'],
                        $record['address'],
                        'Date: ' => $record['date'],
                        'Available Slots: ' => $record['bookings_available'],
                        'Fee: ' => $record['fee']
                    )
                );
        }
    )->withData(
        [
            [
                'title' => 'Online Presentation of some Insurance Topic',
                'type' => 'Webinar',
                'begin_date' => (new \DateTime())->modify('+1 day')->format('d.m.Y'),
                'bookings_available' => '3',
                'target_group' => 'Employees, Field Service',
                'goals' => 'Lorem Ipsum....',
                'topics' => '<ul><li>Tranportations</li><li>Europapolice</li></ul>',
                'date' => (new \DateTime())->modify('+1 day')->format('d.m.Y')
                    . ' - '
                    . (new \DateTime())->modify('+2 day')->format('d.m.Y'),
                'location' => 'Hamburg',
                'address' => 'Hauptstraße 123',
                'fee' => '380 €'
            ],
            [
                'title' => 'Workshop: Life Insurance 2017',
                'type' => 'Face 2 Face',
                'begin_date' => '12.12.2017',
                'bookings_available' => '12',
                'target_group' => 'Agencies, Field Service',
                'goals' => 'Life insurance (or life assurance, especially in the Commonwealth   of Nations), is a contract between an insurance policy holder and an insurer or assurer, where the insurer promises to pay a designated beneficiary a sum of money (the benefit) in exchange for a premium, upon the death of an insured person (often the policy holder). Depending on the contract, other events such as terminal illness or critical illness can also trigger payment. The policy holder typically pays a premium, either regularly or as one lump sum. Other expenses (such as funeral expenses) can also be included in the benefits.',
                'topics' => 'Life-based contracts tend to fall into two major categories:
                            <ul><li>Protection policies – designed to provide a benefit, typically a lump sum payment, in the event of a specified occurrence. A common form - more common in years past - of a protection policy design is term insurance.</li>
                            <li>Investment policies – the main objective of these policies is to facilitate the growth of capital by regular or single premiums. Common forms (in the U.S.) are whole life, universal life, and variable life policies.</li></ul>',
                'date' => '12.12.2017 - 14.12.2017',
                'location' => 'Cologne',
                'address' => 'Holiday Inn, Am Dom 12, 50667 Köln',
                'fee' => '500 €'
            ],
            [
                'title' => 'Basics: Preparation for Seminars',
                'type' => 'Online Training',
                'begin_date' => '-',
                'bookings_available' => 'unlimited',
                'target_group' => 'All',
                'goals' => '',
                'topics' => '',
                'date' => '-',
                'location' => 'online',
                'address' => '',
                'fee' => '-'
            ]
        ]
    );
}

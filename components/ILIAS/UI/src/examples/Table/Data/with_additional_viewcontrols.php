<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Table\Data;

use ILIAS\UI\Implementation\Component\Table as T;
use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\URLBuilder;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ---
 * expected output: >
 *   ILIAS shows the rendered Table Component with two additional Mode View Controls
 *   and one additional Field Selection Control.
 *   The first Field Selection Control is from the optional columns.
 * ---
 */
function with_additional_viewcontrols()
{
    global $DIC;
    $f = $DIC['ui.factory'];
    $r = $DIC['ui.renderer'];
    $df = new \ILIAS\Data\Factory();
    $refinery = $DIC['refinery'];
    $request = $DIC->http()->request();

    $columns = [
        'usr_id' => $f->table()->column()->number("User ID")
            ->withIsSortable(false),
        'login' => $f->table()->column()->text("Login")
            ->withIsOptional(true)
            ->withIsSortable(false),
        'email' => $f->table()->column()->eMail("eMail")
            ->withIsOptional(true)
            ->withIsSortable(false),
    ];


    /**
     * Please note the parameter $additional_viewcontrol_data
     */
    $data_retrieval = new class ($f, $r) implements I\DataRetrieval {
        public function __construct(
            protected \ILIAS\UI\Factory $ui_factory,
            protected \ILIAS\UI\Renderer $ui_renderer
        ) {
        }

        public function getRows(
            I\DataRowBuilder $row_builder,
            array $visible_column_ids,
            Range $range,
            Order $order,
            mixed $additional_viewcontrol_data,
            mixed $filter_data,
            mixed $additional_parameters
        ): \Generator {
            $records = $this->getRecords($range, $order);
            foreach ($records as $idx => $record) {
                $row_id = (string) $record['usr_id'];

                if (in_array('hide_login', $additional_viewcontrol_data)) {
                    $record['login'] = '-';
                }
                if (in_array('hide_mail', $additional_viewcontrol_data)) {
                    $record['email'] = '-';
                }

                yield $row_builder->buildDataRow($row_id, $record);
            }
        }

        public function getTotalRowCount(
            mixed $additional_viewcontrol_data,
            mixed $filter_data,
            mixed $additional_parameters
        ): ?int {
            return count($this->getRecords());
        }

        protected function getRecords(Range $range = null, Order $order = null): array
        {
            return [
                ['usr_id' => 123,'login' => 'superuser','email' => 'user@example.com',
                 'last' => (new \DateTimeImmutable())->modify('-1 day') ,'achieve' => 20,'fee' => 0
                ],
                ['usr_id' => 867,'login' => 'student1','email' => 'student1@example.com',
                 'last' => (new \DateTimeImmutable())->modify('-10 day'),'achieve' => 90,'fee' => 40
                ],
                ['usr_id' => 8923,'login' => 'student2','email' => 'student2@example.com',
                 'last' => (new \DateTimeImmutable())->modify('-8 day'),'achieve' => 66,'fee' => 36.789
                ]
            ];
        }
    };

    $table = $f->table()
            ->data('a data table with additional view controls', $columns, $data_retrieval)
            /**
             * add view controls to the table
             */
            ->withAdditionalViewControl(
                $f->input()->viewControl()->group(
                    [
                        $f->input()->viewControl()->mode([
                            'show_login' => 'show login',
                            'hide_login' => 'anon login'
                        ])
                        ->withValue('show_login'),

                        $f->input()->viewControl()->mode([
                            'show_mail' => 'show mails',
                            'hide_mail' => 'anon mails'
                        ])
                        ->withValue('show_mail'),

                        $f->input()->viewControl()->fieldSelection([
                            'opt1' => 'option 1',
                            'opt2' => 'option 2',
                            'opt3' => 'option 3',
                        ]),
                    ]
                )
                ->withAdditionalTransformation(
                    $refinery->custom()->transformation(
                        fn($v) => array_shift($v)
                    )
                )
            )
            ->withRequest($request);

    return $r->render($table->withId('_addvc_example'));

}

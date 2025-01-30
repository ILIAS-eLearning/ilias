<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Table\Data;

use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use Generator;

/**
 * ---
 * description: >
 *   Example showing a data table without any data and hence no entries, which
 *   will automatically display an according message.
 *
 * expected output: >
 *   ILIAS shows the rendered Component.
 * ---
 */
function without_data(): string
{
    global $DIC;

    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();

    $empty_retrieval = new class () implements DataRetrieval {
        public function getRows(
            DataRowBuilder $row_builder,
            array $visible_column_ids,
            Range $range,
            Order $order,
            mixed $additional_viewcontrol_data,
            mixed $filter_data,
            mixed $additional_parameters
        ): Generator {
            yield from [];
        }

        public function getTotalRowCount(
            mixed $additional_viewcontrol_data,
            mixed $filter_data,
            mixed $additional_parameters
        ): ?int {
            return 0;
        }
    };

    $table = $factory->table()->data(
        'Empty Data Table',
        [
            'col1' => $factory->table()->column()->text('Column 1')
                ->withIsSortable(false),
            'col2' => $factory->table()->column()->number('Column 2')
                ->withIsSortable(false),
        ],
        $empty_retrieval
    );

    return $renderer->render($table->withRequest($request));
}

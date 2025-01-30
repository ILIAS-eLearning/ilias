<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Table\Column\Date;

use ILIAS\UI\Implementation\Component\Table as T;
use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;

/**
 * ---
 * expected output: >
 *   ILIAS shows the rendered Component.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC['ui.factory'];
    $r = $DIC['ui.renderer'];
    $df = new \ILIAS\Data\Factory();

    $columns = [
        'd1' => $f->table()->column()->date("German Long", $df->dateFormat()->germanLong()),
        'd2' => $f->table()->column()->date("German Short", $df->dateFormat()->germanShort())
    ];

    $data_retrieval = new class () implements I\DataRetrieval {
        public function getRows(
            I\DataRowBuilder $row_builder,
            array $visible_column_ids,
            Range $range,
            Order $order,
            mixed $additional_viewcontrol_data,
            mixed $filter_data,
            mixed $additional_parameters
        ): \Generator {
            $row_id = '';
            $dat = new \DateTimeImmutable();
            $record = [
                'd1' => $dat,
                'd2' => $dat
            ];
            yield $row_builder->buildDataRow($row_id, $record);
        }

        public function getTotalRowCount(
            mixed $additional_viewcontrol_data,
            mixed $filter_data,
            mixed $additional_parameters
        ): ?int {
            return 1;
        }
    };

    $table = $f->table()->data('Date Columns', $columns, $data_retrieval)
        ->withRequest($DIC->http()->request());
    return $r->render($table);
}

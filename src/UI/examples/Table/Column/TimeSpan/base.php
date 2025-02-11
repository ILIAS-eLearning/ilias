<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Table\Column\TimeSpan;

use ILIAS\UI\Implementation\Component\Table as T;
use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;

function base()
{
    global $DIC;
    $f = $DIC['ui.factory'];
    $r = $DIC['ui.renderer'];
    $df = new \ILIAS\Data\Factory();
    $current_user = $DIC['ilUser'];

    $columns = [
        'd1' => $f->table()->column()->timeSpan("German Long", $df->dateFormat()->germanLong()),
        'd2' => $f->table()->column()->timeSpan("Time Only", $df->dateFormat()->custom()->hours24()->colon()->minutes()->get()),
        'd3' => $f->table()->column()->timeSpan("User Preference", $current_user->getDateFormat()),
    ];

    $data_retrieval = new class () implements I\DataRetrieval {
        public function getRows(
            I\DataRowBuilder $row_builder,
            array $visible_column_ids,
            Range $range,
            Order $order,
            ?array $filter_data,
            ?array $additional_parameters
        ): \Generator {
            $row_id = '';
            $dat = new \DateTimeImmutable();
            $dat2 = $dat->add(new \DateInterval('PT10H30S'));
            $record = [
                'd1' => [$dat, $dat2],
                'd2' => [$dat, $dat2],
                'd3' => [$dat, $dat2],
            ];
            yield $row_builder->buildDataRow($row_id, $record);
        }

        public function getTotalRowCount(
            ?array $filter_data,
            ?array $additional_parameters
        ): ?int {
            return 1;
        }
    };

    $table = $f->table()->data('TimeSpan Columns', $columns, $data_retrieval)
        ->withRequest($DIC->http()->request());
    return $r->render($table);
}

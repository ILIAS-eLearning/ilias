<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\Bar\Vertical;

use ILIAS\UI\Component\Chart\Bar\Bar;
use ILIAS\UI\Component\Chart\Bar\BarConfig;
use ILIAS\UI\Component\Chart\Bar\YAxis;

function stacked_with_groups()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $df = new \ILIAS\Data\Factory();
    $renderer = $DIC->ui()->renderer();

    //Generating Dimensions
    $c_dimension = $df->dimension()->cardinal();

    //Generating Dataset with points and tooltips
    $dataset = $df->dataset([
        "Dataset 1.1" => $c_dimension,
        "Dataset 1.2" => $c_dimension,
        "Dataset 2" => $c_dimension,
    ]);

    $dataset = $dataset->withPoint(
        "Item 1",
        [
            "Dataset 1.1" => 8,
            "Dataset 1.2" => 7,
            "Dataset 2" => 10
        ]
    );
    $dataset = $dataset->withPoint(
        "Item 2",
        [
            "Dataset 1.1" => 2,
            "Dataset 1.2" => 5,
            "Dataset 2" => 3
        ]
    );
    $dataset = $dataset->withPoint(
        "Item 3",
        [
            "Dataset 1.1" => -4,
            "Dataset 1.2" => 10,
            "Dataset 2" => 6
        ]
    );

    //Generating Bar Configurations
    $b1 = new BarConfig();
    $b1 = $b1->withColor($df->color("#d38000"));
    $b1 = $b1->withStackGroup("Stack 1");
    $b2 = new BarConfig();
    $b2 = $b2->withColor($df->color("#307C88"));
    $b2 = $b2->withStackGroup("Stack 1");
    $b3 = new BarConfig();
    $b3 = $b3->withColor($df->color("#557b2e"));
    $b3 = $b3->withStackGroup("Stack 2");

    $bars = [
        "Dataset 1.1" => $b1,
        "Dataset 1.2" => $b2,
        "Dataset 2" => $b3
    ];

    //Generating and rendering the vertical chart
    $bar = $f->chart()->bar()->vertical(
        "A vertical stacked bar chart with stack groups",
        $dataset,
        $bars
    );
    $bar = $bar->withStacked(true);

    // render
    return $renderer->render($bar);
}

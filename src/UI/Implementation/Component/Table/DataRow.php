<?php
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Table;

use ILIAS\UI\Component\Table as T;
use ILIAS\UI\Implementation\Component\ComponentHelper;

class DataRow implements T\DataRow
{
    use ComponentHelper;

    /**
     * @var mixed[]
     */
    public $record;

    /**
     * @var array<string, bool>
     */
    protected array $disabled_actions = [];

    public function __construct(
        protected bool $table_has_singleactions,
        protected bool $table_has_multiactions,
        protected array $columns,
        protected array $actions,
        protected string $id,
        array $record
    ) {
        $this->record = $record;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function withDisabledAction(string $action_id, bool $disable = true): T\DataRow
    {
        if (!$disable) {
            return $this;
        }
        $clone = clone $this;
        $clone->disabled_actions[$action_id] = true;
        return $clone;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function tableHasSingleActions(): bool
    {
        return $this->table_has_singleactions;
    }
    public function tableHasMultiActions(): bool
    {
        return $this->table_has_multiactions;
    }

    public function getActions(): array
    {
        return array_filter(
            $this->actions,
            function (string $id): bool {
                return !array_key_exists($id, $this->disabled_actions);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    public function getCellContent(string $col_id): string
    {
        if (!array_key_exists($col_id, $this->record)) {
            return '';
        }
        return $this->columns[$col_id]->format($this->record[$col_id]);
    }
}

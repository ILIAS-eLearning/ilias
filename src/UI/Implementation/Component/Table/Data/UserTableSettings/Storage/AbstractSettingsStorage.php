<?php

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Table\Data\UserTableSettings\Storage;

use ILIAS\DI\Container;
use ILIAS\UI\Component\Table\Data\Column\Column;
use ILIAS\UI\Component\Table\Data\Table;
use ILIAS\UI\Component\Table\Data\UserTableSettings\Settings as SettingsInterface;
use ILIAS\UI\Component\Table\Data\UserTableSettings\Sort\SortField as SortFieldInterface;
use ILIAS\UI\Component\Table\Data\UserTableSettings\Storage\SettingsStorage;
use ILIAS\UI\Implementation\Component\Table\Data\UserTableSettings\Sort\SortField;

/**
 * Class AbstractSettingsStorage
 *
 * @package ILIAS\UI\Implementation\Component\Table\Data\UserTableSettings\Storage
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
abstract class AbstractSettingsStorage implements SettingsStorage
{

    /**
     * @var Container
     */
    protected $dic;


    /**
     * @inheritDoc
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }


    /**
     * @inheritDoc
     */
    public function handleDefaultSettings(SettingsInterface $user_table_settings, Table $component) : SettingsInterface
    {
        if (!$user_table_settings->isFilterSet() && empty($user_table_settings->getSortFields())) {
            $user_table_settings = $user_table_settings->withSortFields(array_map(function (Column $column) use ($component): SortFieldInterface {
                return new SortField($column->getKey(), $column->getDefaultSortDirection());
            }, array_filter($component->getColumns(), function (Column $column) : bool {
                return ($column->isSortable() && $column->isDefaultSort());
            })));
        }

        if (!$user_table_settings->isFilterSet() && empty($user_table_settings->getSelectedColumns())) {
            $user_table_settings = $user_table_settings->withSelectedColumns(array_map(function (Column $column) : string {
                return $column->getKey();
            }, array_filter($component->getColumns(), function (Column $column) : bool {
                return ($column->isSelectable() && $column->isDefaultSelected());
            })));
        }

        return $user_table_settings;
    }


    /**
     * @param string $string
     *
     * @return string
     */
    protected function strToCamelCase(string $string) : string
    {
        return str_replace("_", "", ucwords($string, "_"));
    }
}

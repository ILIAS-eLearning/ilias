<?php

declare(strict_types=1);

namespace ILIAS\UI\Component\Table\Data;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Input\Field\FilterInput;
use ILIAS\UI\Component\Table\Data\Column\Column;
use ILIAS\UI\Component\Table\Data\Data\Fetcher\DataFetcher;
use ILIAS\UI\Component\Table\Data\Format\BrowserFormat;
use ILIAS\UI\Component\Table\Data\Format\Format;
use ILIAS\UI\Component\Table\Data\Settings\Storage\SettingsStorage;

/**
 * Interface Table
 *
 * @package ILIAS\UI\Component\Table\Data
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
interface Table extends Component
{

    const ACTION_GET_VAR = "row_id";
    const MULTIPLE_SELECT_POST_VAR = "selected_row_ids";
    const LANG_MODULE = "datatable";


    /**
     * Table constructor
     *
     * @param string      $table_id
     * @param string      $action_url
     * @param string      $title
     * @param Column[]    $columns
     * @param DataFetcher $data_fetcher
     */
    public function __construct(string $table_id, string $action_url, string $title, array $columns, DataFetcher $data_fetcher);


    /**
     * @return string
     */
    public function getTableId() : string;


    /**
     * @param string $table_id
     *
     * @return self
     */
    public function withTableId(string $table_id) : self;


    /**
     * @return string
     */
    public function getActionUrl() : string;


    /**
     * @param string $action_url
     *
     * @return self
     */
    public function withActionUrl(string $action_url) : self;


    /**
     * @return string
     */
    public function getTitle() : string;


    /**
     * @param string $title
     *
     * @return self
     */
    public function withTitle(string $title) : self;


    /**
     * @return Column[]
     */
    public function getColumns() : array;


    /**
     * @param Column[] $columns
     *
     * @return self
     */
    public function withColumns(array $columns) : self;


    /**
     * @return DataFetcher
     */
    public function getDataFetcher() : DataFetcher;


    /**
     * @param DataFetcher $data_fetcher
     *
     * @return self
     */
    public function withFetchData(DataFetcher $data_fetcher) : self;


    /**
     * @return FilterInput[]
     */
    public function getFilterFields() : array;


    /**
     * @param FilterInput[] $filter_fields
     *
     * @return self
     */
    public function withFilterFields(array $filter_fields) : self;


    /**
     * @return BrowserFormat|null
     */
    public function getCustomBrowserFormat() : ?BrowserFormat;


    /**
     * @param BrowserFormat|null $custom_browser_format
     *
     * @return self
     */
    public function withCustomBrowserFormat(?BrowserFormat $custom_browser_format = null) : self;


    /**
     * @return Format[]
     */
    public function getFormats() : array;


    /**
     * @param Format[] $formats
     *
     * @return self
     */
    public function withFormats(array $formats) : self;


    /**
     * @return string[]
     */
    public function getMultipleActions() : array;


    /**
     * @param string[] $multiple_actions
     *
     * @return self
     */
    public function withMultipleActions(array $multiple_actions) : self;


    /**
     * @return SettingsStorage|null
     */
    public function getCustomSettingsStorage() : ?SettingsStorage;


    /**
     * @param SettingsStorage|null $custom_settings_storage
     *
     * @return self
     */
    public function withCustomSettingsStorage(?SettingsStorage $custom_settings_storage = null) : self;


    /**
     * @return string
     */
    public function getActionRowId() : string;


    /**
     * @return string[]
     */
    public function getMultipleActionRowIds() : array;
}

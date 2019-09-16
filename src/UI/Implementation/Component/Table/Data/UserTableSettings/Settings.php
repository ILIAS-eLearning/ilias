<?php

namespace ILIAS\UI\Implementation\Component\Table\Data\UserTableSettings;

use ILIAS\UI\Component\Table\Data\Data\Data;
use ILIAS\UI\Component\Table\Data\UserTableSettings\Settings as SettingsInterface;
use ILIAS\UI\Component\Table\Data\UserTableSettings\Sort\SortField;
use ILIAS\UI\Component\ViewControl\Pagination;

/**
 * Class Settings
 *
 * @package ILIAS\UI\Implementation\Component\Table\Data\UserTableSettings
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class Settings implements SettingsInterface {

	/**
	 * @var Pagination
	 */
	protected $pagination;
	/**
	 * @var mixed[]
	 */
	protected $field_values = [];
	/**
	 * @var SortField[]
	 */
	protected $sort_fields = [];
	/**
	 * @var string[]
	 */
	protected $selected_columns = [];
	/**
	 * @var bool
	 */
	protected $filter_set = false;


	/**
	 * @inheritDoc
	 */
	public function __construct(Pagination $pagination) {
		$this->pagination = $pagination->withPageSize(self::DEFAULT_ROWS_COUNT);
	}


	/**
	 * @inheritDoc
	 */
	public function getFieldValues(): array {
		return $this->field_values;
	}


	/**
	 * @inheritDoc
	 */
	public function getFieldValue(string $key) {
		return $this->field_values[$key] ?? null;
	}


	/**
	 * @inheritDoc
	 */
	public function withFieldValues(array $field_values): SettingsInterface {
		$clone = clone $this;

		$clone->field_values = $field_values;

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function getSortFields(): array {
		return $this->sort_fields;
	}


	/**
	 * @inheritDoc
	 */
	public function getSortField(string $sort_field): ?SortField {
		$sort_field = current(array_filter($this->sort_fields, function (SortField $sort_field_) use ($sort_field): bool {
			return ($sort_field_->getSortField() === $sort_field);
		}));

		if ($sort_field !== false) {
			return $sort_field;
		} else {
			return null;
		}
	}


	/**
	 * @inheritDoc
	 */
	public function withSortFields(array $sort_fields): SettingsInterface {
		$clone = clone $this;

		$clone->sort_fields = $sort_fields;

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function addSortField(SortField $sort_field): SettingsInterface {
		$clone = clone $this;

		if ($this->getSortField($sort_field->getSortField()) !== null) {
			$clone->sort_fields = array_reduce($clone->sort_fields, function (array $sort_fields, SortField $sort_field_) use ($sort_field): array {
				if ($sort_field_->getSortField() === $sort_field->getSortField()) {
					$sort_field_ = $sort_field;
				}

				$sort_fields[] = $sort_field_;

				return $sort_fields;
			}, []);
		} else {
			$clone->sort_fields[] = $sort_field;
		}

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function removeSortField(string $sort_field): SettingsInterface {
		$clone = clone $this;

		$clone->sort_fields = array_values(array_filter($clone->sort_fields, function (SortField $sort_field_) use ($sort_field): bool {
			return ($sort_field_->getSortField() !== $sort_field);
		}));

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function getSelectedColumns(): array {
		return $this->selected_columns;
	}


	/**
	 * @inheritDoc
	 */
	public function withSelectedColumns(array $selected_columns): SettingsInterface {
		$clone = clone $this;

		$clone->selected_columns = $selected_columns;

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function selectColumn(string $selected_column): SettingsInterface {
		$clone = clone $this;

		if (!in_array($selected_column, $clone->selected_columns)) {
			$clone->selected_columns[] = $selected_column;
		}

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function deselectColumn(string $selected_column): SettingsInterface {
		$clone = clone $this;

		$clone->selected_columns = array_values(array_filter($clone->selected_columns, function (string $selected_column_) use ($selected_column): bool {
			return ($selected_column_ !== $selected_column);
		}));

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function isFilterSet(): bool {
		return $this->filter_set;
	}


	/**
	 * @inheritDoc
	 */
	public function withFilterSet(bool $filter_set = false): SettingsInterface {
		$clone = clone $this;

		$clone->filter_set = $filter_set;

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function getRowsCount(): int {
		return $this->pagination->getPageSize();
	}


	/**
	 * @inheritDoc
	 */
	public function withRowsCount(int $rows_count = self::DEFAULT_ROWS_COUNT): SettingsInterface {
		$clone = clone $this;

		$clone->pagination = $clone->pagination->withPageSize($rows_count);

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function getCurrentPage(): int {
		return $this->pagination->getCurrentPage();
	}


	/**
	 * @inheritDoc
	 */
	public function withCurrentPage(int $current_page = 0): SettingsInterface {
		$clone = clone $this;

		$clone->pagination = $clone->pagination->withCurrentPage($current_page);

		return $clone;
	}


	/**
	 * @inheritDoc
	 */
	public function getLimitStart(): int {
		return $this->pagination->getOffset();
	}


	/**
	 * @inheritDoc
	 */
	public function getLimitEnd(): int {
		return (($this->getCurrentPage() + 1) * $this->getRowsCount());
	}


	/**
	 * @inheritDoc
	 *
	 * @internal
	 */
	public function getPagination(Data $data): Pagination {
		return $this->pagination->withTotalEntries($data->getMaxCount());
	}
}

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
 ********************************************************************
 */

declare(strict_types=1);

class ilDclDetailedViewDefinition extends ilPageObject
{
    public const PARENT_TYPE = 'dclf';
    protected int $table_id;

    /**
     * Get parent type
     */
    public function getParentType(): string
    {
        return self::PARENT_TYPE;
    }

    /**
     * Get all placeholders for table id
     */
    public function getAvailablePlaceholders(): array
    {
        $all = [];

        $tableview = new ilDclTableView($this->getId());
        $table_id = $tableview->getTableId();
        $objTable = ilDclCache::getTableCache($table_id);
        $fields = $objTable->getRecordFields();
        $standardFields = $objTable->getStandardFields();

        foreach ($fields as $field) {
            $all[] = "[" . $field->getTitle() . "]";

            if ($field->getDatatypeId() == ilDclDatatype::INPUTFORMAT_REFERENCE) {
                $all[] = '[dclrefln field="' . $field->getTitle() . '"][/dclrefln]';
            }
        }

        foreach ($standardFields as $field) {
            $all[] = "[" . $field->getId() . "]";
        }

        return $all;
    }

    public static function exists(int $id): bool
    {
        return parent::_exists(self::PARENT_TYPE, $id);
    }

    public static function isActive(int $id): bool
    {
        if (!parent::_exists(self::PARENT_TYPE, $id)) {
            return false;
        }
        return parent::_lookupActive($id, self::PARENT_TYPE);
    }
}

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

namespace ILIAS\Forum\Setup;

use ilDatabaseUpdateSteps;
use ilDBConstants;
use ilDBInterface;

class IsAuthorModeratorBoolMigration implements ilDatabaseUpdateSteps
{
    public const TABLE_NAME = 'frm_posts';

    private ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }

    public function step_1(): void
    {
        $this->db->manipulateF(
            'UPDATE ' . self::TABLE_NAME . ' SET is_author_moderator = %s WHERE is_author_moderator IS NULL',
            [ilDBConstants::T_INTEGER],
            [0]
        );

        $this->db->modifyTableColumn(
            self::TABLE_NAME,
            'is_author_moderator',
            [
                'type' => ilDBConstants::T_INTEGER,
                'length' => 4,
                'notnull' => true,
                'default' => 0
            ]
        );
    }
}

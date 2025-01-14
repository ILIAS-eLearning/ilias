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

use ILIAS\ResourceStorage\Collection\ResourceCollection;
use ILIAS\Setup\Environment;
use ILIAS\Setup\Migration;

class ilBadgeTemplatesFilesMigration implements Migration
{
    private const TABLE_NAME = 'badge_image_template';

    protected ilResourceStorageMigrationHelper $helper;

    public function getLabel(): string
    {
        return 'Migration of files of badge templates to the resource storage service.';
    }

    public function getDefaultAmountOfStepsPerRun(): int
    {
        return 1000;
    }

    public function getPreconditions(Environment $environment): array
    {
        return ilResourceStorageMigrationHelper::getPreconditions();
    }

    public function prepare(Environment $environment): void
    {
        $this->helper = new ilResourceStorageMigrationHelper(
            new ilBadgeFileStakeholder(),
            $environment
        );
    }

    public function step(Environment $environment): void
    {
        $this->helper->getDatabase()->setLimit(1);
        $res = $this->helper->getDatabase()->query(
            'SELECT id, image, image_rid FROM ' . self::TABLE_NAME . " WHERE image_rid IS NULL OR image_rid = ''"
        );
        $row = $this->helper->getDatabase()->fetchObject($res);
        if (!($row instanceof stdClass)) {
            return;
        }

        $id = (int) $row->id;
        $image = $row->image;

        if ($image !== '' && $image !== null) {
            $image_path = $this->getImagePath($id, $image);
            $identification = $this->helper->movePathToStorage($image_path, ResourceCollection::NO_SPECIFIC_OWNER);
            if ($identification === null) {
                $identification = '-';
            } else {
                $identification = $identification->serialize();
            }

            $this->helper->getDatabase()->update(
                self::TABLE_NAME,
                [
                    'image_rid' => [ilDBConstants::T_TEXT, $identification],
                    'image' => [ilDBConstants::T_TEXT, null]
                ],
                ['id' => [ilDBConstants::T_INTEGER, $id]]
            );
        }
    }

    private function getImagePath(int $id, string $image): string
    {
        $exp = explode('.', $image);
        $suffix = strtolower(array_pop($exp));

        return $this->getFilePath($id) . '/img' . $id . '.' . $suffix;
    }

    private function getFilePath(int $a_id): string
    {
        return ILIAS_ABSOLUTE_PATH . '/' . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/sec/ilBadge/' . $this->createLegacyPathSegmentForBadgeTemplateId($a_id);
    }

    private function createLegacyPathSegmentForBadgeTemplateId(int $id): string
    {
        $path = [];
        $found = false;
        $num = $id;
        $path_string = '';
        for ($i = 3; $i > 0; $i--) {
            $factor = 100 ** $i;
            if (($tmp = (int) ($num / $factor)) || $found) {
                $path[] = $tmp;
                $num %= $factor;
                $found = true;
            }
        }

        if (count($path)) {
            $path_string = (implode('/', $path) . '/');
        }

        return $path_string . 'badgetmpl_' . $id;
    }

    public function getRemainingAmountOfSteps(): int
    {
        $res = $this->helper->getDatabase()->query(
            'SELECT COUNT(id) as amount FROM ' . self::TABLE_NAME . " WHERE image_rid IS NULL OR image_rid = ''"
        );
        $row = $this->helper->getDatabase()->fetchObject($res);

        return (int) ($row->amount ?? 0);
    }
}

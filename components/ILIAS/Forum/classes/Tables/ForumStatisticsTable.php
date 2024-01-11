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

use ILIAS\UI\Component\Table\Data as DataTable;
use ILIAS\UI\Factory as UIFactory;
use Psr\Http\Message\ServerRequestInterface as HttpRequest;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\Data\Order;
use ILIAS\Data\Range;

class ForumStatisticsTable
{
    private UIFactory $ui_factory;
    private ilLanguage $lng;
    private bool $has_active_lp = false;
    /** @var int[] */
    private array $completed = [];
    /** @var int[] */
    private array $failed = [];
    /** @var int[] */
    private array $in_progress = [];
    private HttpRequest $request;
    private UIRenderer $ui_renderer;

    public function __construct(
        private readonly ilObjForum $forum,
        private readonly ilForumProperties $objProperties,
        private readonly bool $has_general_lp_access,
        private readonly bool $has_rbac_or_position_access,
        private readonly ilObjUser $actor,
    ) {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->request = $DIC->http()->request();
        $this->lng = $DIC->language();

        $lp = ilObjectLP::getInstance($forum->getId());
        if ($lp->isActive()) {
            $this->has_active_lp = true;
        }

        if ($this->has_active_lp && $this->has_general_lp_access) {
            $this->lng->loadLanguageModule('trac');
            $this->completed = ilLPStatusWrapper::_lookupCompletedForObject($forum->getId());
            $this->in_progress = ilLPStatusWrapper::_lookupInProgressForObject($forum->getId());
            $this->failed = ilLPStatusWrapper::_lookupFailedForObject($forum->getId());
        }
    }

    public function getComponent(): DataTable
    {
        $columns = $this->getColumns();
        $data_retrieval = $this->getDataRetrieval();

        return $this->ui_factory->table()
                                ->data($this->lng->txt('frm_moderators'), $columns, $data_retrieval)
                                ->withRequest($this->request);
    }

    protected function getColumns(): array
    {
        $columns = [
            'ranking' => $this->ui_factory->table()->column()->text(
                $this->lng->txt('frm_statistics_ranking')
            )->withIsSortable(true),
            'login' => $this->ui_factory->table()->column()->text(
                $this->lng->txt('login')
            )->withIsSortable(true),
            'lastname' => $this->ui_factory->table()->column()->text(
                $this->lng->txt('lastname')
            )->withIsSortable(true),
            'firstname' => $this->ui_factory->table()->column()->text(
                $this->lng->txt('firstname')
            )->withIsSortable(true),
        ];
        if ($this->has_active_lp && $this->has_general_lp_access) {
            $columns['progress'] = $this->ui_factory->table()->column()->text(
                $this->lng->txt('learning_progress')
            )->withIsSortable(false);
        }
        return $columns;
    }

    protected function getDataRetrieval(): DataRetrieval
    {
        $data_retrieval = new class (
            $this->ui_factory,
            $this->ui_renderer,
            $this->forum,
            $this->objProperties,
            $this->has_active_lp,
            $this->has_general_lp_access,
            $this->completed,
            $this->in_progress,
            $this->failed,
            $this->actor,
            $this->lng,
            $this->has_rbac_or_position_access,
        ) implements DataRetrieval {
            private ilLPStatusIcons $icons;
            private ?array $records = null;

            public function __construct(
                protected \ILIAS\UI\Factory $ui_factory,
                protected UIRenderer $ui_renderer,
                protected ilObjForum $object,
                protected ilForumProperties $objProperties,
                protected bool $has_active_lp,
                protected bool $has_general_lp_access,
                protected array $completed,
                protected array $in_progress,
                protected array $failed,
                protected ilObjUser $actor,
                protected ilLanguage $lng,
                protected bool $has_rbac_or_position_access,
            ) {
                $this->icons = ilLPStatusIcons::getInstance(ilLPStatusIcons::ICON_VARIANT_LONG);
            }

            private function initRecords(): void
            {
                if ($this->records === null) {
                    $this->records = [];
                    $data = $this->object->Forum->getUserStatistics($this->objProperties->isPostActivationEnabled());
                    $result = [];
                    $counter = 0;
                    foreach ($data as $row) {
                        $this->records[$counter]['usr_id'] = $row['usr_id'];
                        $this->records[$counter]['ranking'] = $row['num_postings'];
                        $this->records[$counter]['login'] = $row['login'];
                        $this->records[$counter]['lastname'] = $row['lastname'];
                        $this->records[$counter]['firstname'] = $row['firstname'];
                        if ($this->has_active_lp && $this->has_general_lp_access) {
                            $this->records[$counter]['progress'] = $this->getProgressStatus($row['usr_id']);
                        }
                        ++$counter;
                    }
                }
            }

            private function sortedRecords(array $records, Order $order): array
            {
                [$order_field, $order_direction] = $order->join([], fn($ret, $key, $value) => [$key, $value]);
                usort($records, static fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
                if ($order_direction === 'DESC') {
                    $records = array_reverse($records);
                }
                return $records;
            }

            private function getRecords(Range $range, Order $order): array
            {
                $this->initRecords();
                $records = $this->sortedRecords($this->records, $order);
                return $this->limitRecords($records, $range);
            }

            private function limitRecords(array $records, Range $range): array
            {
                return array_slice($records, $range->getStart(), $range->getLength());
            }

            public function getRows(
                \ILIAS\UI\Component\Table\DataRowBuilder $row_builder,
                array $visible_column_ids,
                \ILIAS\Data\Range $range,
                \ILIAS\Data\Order $order,
                ?array $filter_data,
                ?array $additional_parameters,
            ): Generator {
                $records = $this->getRecords($range, $order);
                foreach ($records as $record) {
                    $row_id = (string) $record['usr_id'];
                    yield $row_builder->buildDataRow($row_id, $record);
                }
            }

            public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
            {
                $this->initRecords();
                return count((array) $this->records);
            }

            private function getProgressStatus(int $user_id): string
            {
                if ($this->has_active_lp && $this->has_general_lp_access) {
                    if ($this->has_rbac_or_position_access || $this->actor->getId() === $user_id) {
                        switch (true) {
                            case in_array($user_id, $this->completed, false):
                                $status = $this->lng->txt(ilLPStatus::LP_STATUS_COMPLETED);
                                $icon = $this->icons->renderIconForStatus(ilLPStatus::LP_STATUS_COMPLETED_NUM);
                                break;

                            case in_array($user_id, $this->in_progress, false):
                                $status = $this->lng->txt(ilLPStatus::LP_STATUS_IN_PROGRESS);
                                $icon = $this->icons->renderIconForStatus(ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
                                break;

                            case in_array($user_id, $this->failed, false):
                                $status = $this->lng->txt(ilLPStatus::LP_STATUS_FAILED);
                                $icon = $this->icons->renderIconForStatus(ilLPStatus::LP_STATUS_FAILED_NUM);
                                break;

                            default:
                                $status = $this->lng->txt(ilLPStatus::LP_STATUS_NOT_ATTEMPTED);
                                $icon = $this->icons->renderIconForStatus(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
                                break;
                        }
                    } else {
                        $status = '';
                        $icon = '';
                    }
                }
                return ($status ?? '') . ' ' . ($icon ?? '');
            }
        };

        return $data_retrieval;
    }
}

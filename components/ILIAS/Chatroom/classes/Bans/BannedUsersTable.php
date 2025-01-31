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

namespace ILIAS\Chatroom\Bans;

use ILIAS\Data;
use ILIAS\UI;
use Psr\Http\Message\ServerRequestInterface;
use ilArrayUtil;
use ilDateTime;
use ilDatePresentation;
use ilObjChatroomGUI;
use ilLanguage;
use ilCtrlInterface;
use ILIAS\HTTP\GlobalHttpState;

class BannedUsersTable implements UI\Component\Table\DataRetrieval
{
    private readonly ServerRequestInterface $request;
    private readonly Data\Factory $data_factory;
    /** @var list<array<string, mixed>>|null */
    private ?array $records = null;

    /**
     * @param list<array<string, scalar|null>> $banned_users
     */
    public function __construct(
        private readonly \ilObjUser $actor,
        private readonly int $room_id,
        private readonly array $banned_users,
        private readonly ilCtrlInterface $ctrl,
        private readonly ilLanguage $lng,
        GlobalHttpState $http,
        private readonly \ILIAS\UI\Factory $ui_factory
    ) {
        $this->request = $http->request();
        $this->data_factory = new Data\Factory();
    }

    public function getComponent(): UI\Component\Table\Data
    {
        $columns = $this->getColumns();
        $actions = $this->getActions();

        return $this->ui_factory
            ->table()
            ->data($this->lng->txt('ban_table_title'), $columns, $this)
            ->withId(self::class . '_' . $this->room_id)
            ->withOrder(new \ILIAS\Data\Order('datetime', \ILIAS\Data\Order::DESC))
            ->withActions($actions)
            ->withRequest($this->request);
    }

    /**
     * @return array<string, UI\Component\Table\Column\Column>
     */
    private function getColumns(): array
    {
        if ((int) $this->actor->getTimeFormat() === \ilCalendarSettings::TIME_FORMAT_12) {
            $date_format = $this->data_factory->dateFormat()->withTime12($this->actor->getDateFormat());
        } else {
            $date_format = $this->data_factory->dateFormat()->withTime24($this->actor->getDateFormat());
        }

        return [
            'login' => $this->ui_factory
                ->table()->column()->text($this->lng->txt('login'))
                ->withIsSortable(true),
            'firstname' => $this->ui_factory
                ->table()->column()->text($this->lng->txt('firstname'))
                ->withIsSortable(true),
            'lastname' => $this->ui_factory
                ->table()->column()->text($this->lng->txt('lastname'))
                ->withIsSortable(true),
            'datetime' => $this->ui_factory
                ->table()->column()->date($this->lng->txt('chtr_ban_ts_tbl_head'), $date_format)
                ->withIsSortable(true),
            'actor' => $this->ui_factory
                ->table()->column()->text($this->lng->txt('chtr_ban_actor_tbl_head'))
                ->withIsSortable(true),
        ];
    }

    /**
     * @return array<string, UI\Component\Table\Action\Action>
     */
    private function getActions(): array
    {
        $query_params_namespace = ['chat', 'ban', 'table'];

        $uri = $this->data_factory->uri(
            ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass(ilObjChatroomGUI::class, 'ban-handleTableActions')
        );

        $url_builder = new UI\URLBuilder($uri);
        [
            $url_builder,
            $action_parameter_token_copy,
            $row_id_token
        ] = $url_builder->acquireParameters(
            $query_params_namespace,
            'action',
            'user_ids'
        );

        return [
            'delete' => $this->ui_factory->table()->action()->multi(
                $this->lng->txt('unban'),
                $url_builder->withParameter($action_parameter_token_copy, 'delete'),
                $row_id_token
            ),
        ];
    }

    private function initRecords(): void
    {
        if ($this->records === null) {
            $this->records = [];
            $i = 0;
            $entries = $this->banned_users;

            foreach ($entries as $entry) {
                $this->records[$i]['user_id'] = $entry['user_id'];
                $this->records[$i]['login'] = $entry['login'];
                $this->records[$i]['firstname'] = $entry['firstname'];
                $this->records[$i]['lastname'] = $entry['lastname'];
                $this->records[$i]['timestamp'] = $entry['timestamp'];
                $this->records[$i]['datetime'] = (new \DateTimeImmutable('@' . $entry['timestamp']))->setTimezone(
                    new \DateTimeZone($this->actor->getTimeZone())
                );

                $this->records[$i]['actor'] = $entry['actor'];
                ++$i;
            }
        }
    }

    public function getRows(
        UI\Component\Table\DataRowBuilder $row_builder,
        array $visible_column_ids,
        Data\Range $range,
        Data\Order $order,
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): \Generator {
        $records = $this->getRecords($range, $order);

        foreach ($records as $record) {
            $row_id = (string) $record['user_id'];
            yield $row_builder->buildDataRow($row_id, $record);
        }
    }

    public function getTotalRowCount(
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): ?int {
        $this->initRecords();

        return \count($this->records);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sortedRecords(Data\Order $order): array
    {
        [$order_field, $order_direction] = $order->join([], fn($ret, $key, $value) => [$key, $value]);

        if ($order_field === 'datetime') {
            $order_field = 'timestamp';
        }

        return ilArrayUtil::stableSortArray(
            $this->records,
            $order_field,
            strtolower($order_direction),
            $order_field === 'timestamp'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getRecords(Data\Range $range, Data\Order $order): array
    {
        $this->initRecords();

        $records = $this->sortedRecords($order);

        return $this->limitRecords($records, $range);
    }

    /**
     * @param list<array<string, mixed>> $records
     * @return list<array<string, mixed>>
     */
    private function limitRecords(array $records, Data\Range $range): array
    {
        return \array_slice($records, $range->getStart(), $range->getLength());
    }
}

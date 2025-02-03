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

final class ilSamlIdpTableGUI implements \ILIAS\UI\Component\Table\DataRetrieval
{
    /** @var list<ilSamlIdp> */
    private array $idps;
    private readonly ILIAS\UI\URLBuilder $url_builder;
    private readonly ILIAS\UI\URLBuilderToken $action_parameter_token;
    private readonly ILIAS\UI\URLBuilderToken $row_id_token;

    public function __construct(
        private readonly ilSamlSettingsGUI $parent_gui,
        private readonly \ILIAS\UI\Factory $ui_factory,
        private readonly \ILIAS\UI\Renderer $ui_renderer,
        private readonly ilLanguage $lng,
        private readonly ilCtrlInterface $ctrl,
        private readonly \Psr\Http\Message\ServerRequestInterface $http_request,
        private readonly \ILIAS\Data\Factory $df,
        private readonly string $parent_cmd,
        private readonly bool $has_write_access
    ) {
        $this->idps = ilSamlIdp::getAllIdps();

        $form_action = $this->df->uri(
            ilUtil::_getHttpPath() . '/' .
            $this->ctrl->getLinkTarget($this->parent_gui, $this->parent_cmd)
        );

        [
            $this->url_builder,
            $this->action_parameter_token,
            $this->row_id_token
        ] = (new ILIAS\UI\URLBuilder($form_action))->acquireParameters(
            ['saml', 'idps'],
            'table_action',
            'idp_id'
        );
    }

    public function get(): \ILIAS\UI\Component\Table\Data
    {
        return $this->ui_factory
            ->table()
            ->data(
                $this->lng->txt('auth_saml_idps'),
                $this->getColumnDefinition(),
                $this
            )
            ->withId(self::class)
            ->withOrder(new \ILIAS\Data\Order('title', \ILIAS\Data\Order::ASC))
            ->withActions($this->getActions())
            ->withRequest($this->http_request);
    }

    /**
     * @return array<string, \ILIAS\UI\Component\Table\Column\Column>
     */
    private function getColumnDefinition(): array
    {
        return [
            'title' => $this->ui_factory
                ->table()
                ->column()
                ->text($this->lng->txt('saml_tab_head_idp'))
                ->withIsSortable(true),
            'active' => $this->ui_factory
                ->table()
                ->column()
                ->boolean(
                    $this->lng->txt('status'),
                    $this->ui_factory->symbol()->icon()->custom(
                        'assets/images/standard/icon_ok.svg',
                        $this->lng->txt('active'),
                        'small'
                    ),
                    $this->ui_factory->symbol()->icon()->custom(
                        'assets/images/standard/icon_not_ok.svg',
                        $this->lng->txt('inactive'),
                        'small'
                    )
                )
                ->withIsSortable(true)
                ->withOrderingLabels(
                    "{$this->lng->txt('status')}, {$this->lng->txt('active')} {$this->lng->txt('order_option_first')}",
                    "{$this->lng->txt('status')}, {$this->lng->txt('inactive')} {$this->lng->txt('order_option_first')}"
                )
        ];
    }

    /**
     * @return array<string, \ILIAS\UI\Component\Table\Action\Action>
     */
    private function getActions(): array
    {
        if (!$this->has_write_access) {
            return [];
        }

        return [
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('edit'),
                $this->url_builder->withParameter($this->action_parameter_token, 'showIdpSettings'),
                $this->row_id_token
            ),
            'activate' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('activate'),
                $this->url_builder->withParameter($this->action_parameter_token, 'activateIdp'),
                $this->row_id_token
            ),
            'deactivate' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('deactivate'),
                $this->url_builder->withParameter($this->action_parameter_token, 'deactivateIdp'),
                $this->row_id_token
            ),
            'delete' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('delete'),
                $this->url_builder->withParameter($this->action_parameter_token, 'confirmDeleteIdp'),
                $this->row_id_token
            )
        ];
    }

    /**
     * @return list<ilSamlIdp>
     */
    private function getRecords(\ILIAS\Data\Range $range, \ILIAS\Data\Order $order): array
    {
        $records = $this->idps;

        [$order_field, $order_direction] = $order->join([], static function ($ret, $key, $value) {
            return [$key, $value];
        });

        usort($records, static function (ilSamlIdp $left, ilSamlIdp $right) use ($order_field): int {
            if ($order_field === 'title') {
                return ilStr::strCmp($left->getEntityId(), $right->getEntityId());
            }

            return (int) $right->isActive() <=> (int) $left->isActive();
        });

        if ($order_direction === \ILIAS\Data\Order::DESC) {
            $records = array_reverse($records);
        }

        $records = array_slice($records, $range->getStart(), $range->getLength());

        return $records;
    }

    public function getRows(
        \ILIAS\UI\Component\Table\DataRowBuilder $row_builder,
        array $visible_column_ids,
        \ILIAS\Data\Range $range,
        \ILIAS\Data\Order $order,
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): Generator {
        foreach ($this->getRecords($range, $order) as $item) {
            yield $row_builder
                ->buildDataRow((string) $item->getIdpId(), [
                    'title' => $item->getEntityId(),
                    'active' => $item->isActive()
                ])
                ->withDisabledAction(
                    'activate',
                    $item->isActive(),
                )
                ->withDisabledAction(
                    'deactivate',
                    !$item->isActive(),
                );
        }
    }

    public function getTotalRowCount(
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): ?int {
        return count($this->idps);
    }
}

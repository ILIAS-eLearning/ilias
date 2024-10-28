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

namespace ILIAS\Test\Questions;

use GuzzleHttp\Psr7\ServerRequest;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\URI;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Test\Utilities\TitleColumnsBuilder;
use ILIAS\Test\RequestDataCollector;
use ILIAS\UI\Component\Table\Action\Action;
use ILIAS\UI\Component\Table\OrderingBinding;
use ILIAS\UI\Component\Table\OrderingRowBuilder;
use ILIAS\UI\Component\Table\Ordering as OrderingTable;
use ILIAS\UI\Component\Table\Column\Column;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ilTestRandomQuestionSetSourcePoolDefinitionList as ilPoolDefinitionList;

class RandomQuestionSetSourcePoolDefinitionListTable implements OrderingBinding
{
    public function __construct(
        protected readonly \ilCtrlInterface $ctrl,
        protected readonly \ilLanguage $lng,
        protected readonly UIFactory $ui_factory,
        protected readonly DataFactory $data_factory,
        protected readonly GlobalHttpState $http,
        protected readonly TitleColumnsBuilder $title_builder,
        protected readonly \ilTestQuestionFilterLabelTranslator $taxonomy_translator,
        protected readonly ilPoolDefinitionList $source_pool_definition_list,
        protected readonly bool $editable,
        protected readonly bool $show_amount,
        protected readonly bool $show_mapped_taxonomy_filter
    ) {
    }

    public function getRows(OrderingRowBuilder $row_builder, array $visible_column_ids): \Generator
    {
        foreach ($this->getData() as $qp) {
            $record = [
                'sequence_position' => (int) $qp['sequence_position'],
                'source_pool_label' => $this->title_builder->buildAccessCheckedQuestionpoolTitleAsLink(
                    $qp['ref_id'],
                    $qp['source_pool_label'],
                    true
                ),
                'taxonomy_filter' => $this->taxonomy_translator->getTaxonomyFilterLabel(
                    $qp['taxonomy_filter'],
                    '<br />'
                ),
                'lifecycle_filter' => $this->taxonomy_translator->getLifecycleFilterLabel($qp['lifecycle_filter']),
                'type_filter' => $this->taxonomy_translator->getTypeFilterLabel($qp['type_filter']),
                'question_amount' => $this->getAmountCellContent($qp['def_id'], $qp['question_amount'])
            ];
            yield $row_builder->buildOrderingRow((string) $qp['def_id'], $record);
        }
    }

    protected function getData(): array
    {
        $data = [];

        foreach ($this->source_pool_definition_list as $source_pool_definition) {
            $set = [];

            $set['def_id'] = $source_pool_definition->getId();
            $set['sequence_position'] = $source_pool_definition->getSequencePosition();
            $set['source_pool_label'] = $source_pool_definition->getPoolTitle();
            // fau: taxFilter/typeFilter - get the type and taxonomy filter for display
            if ($this->show_mapped_taxonomy_filter) {
                // mapped filter will be used after synchronisation
                $set['taxonomy_filter'] = $source_pool_definition->getMappedTaxonomyFilter();
            } else {
                // original filter will be used before synchronisation
                $set['taxonomy_filter'] = $source_pool_definition->getOriginalTaxonomyFilter();
            }
            $set['lifecycle_filter'] = $source_pool_definition->getLifecycleFilter();
            $set['type_filter'] = $source_pool_definition->getTypeFilter();
            // fau.
            $set['question_amount'] = $source_pool_definition->getQuestionAmount();
            $set['ref_id'] = $source_pool_definition->getPoolRefId();
            $data[] = $set;
        }

        usort($data, fn($a, $b) => $a['sequence_position'] <=> $b['sequence_position']);
        return $data;
    }

    public function getComponent(): OrderingTable
    {
        $target = $this->buildTargetURI(\ilTestRandomQuestionSetConfigGUI::CMD_SAVE_SRC_POOL_DEF_LIST);
        $title = $this->lng->txt('tst_src_quest_pool_def_list_table');
        return $this->ui_factory->table()
            ->ordering($title, $this->getColumns(), $this, $target)
            ->withRequest($this->http->request())
            ->withActions($this->getActions())
            ->withOrderingDisabled(!$this->editable)
            ->withId('src_pool_def_list');
    }

    public function applySubmit(RequestDataCollector $request): void
    {
        $quest_pos = array_flip($this->getComponent()->getData());
        $quest_amounts = $request->raw('quest_amount');

        foreach ($this->source_pool_definition_list as $source_pool_definition) {
            $source_pool_definition->setSequencePosition($quest_pos[$source_pool_definition->getId()] ?? 0);

            $amount = (int) $quest_amounts[$source_pool_definition->getId()] ?? 0;
            $source_pool_definition->setQuestionAmount($this->show_amount ? $amount : null);
        }
    }

    /**
     * @return array<string, Column>
     */
    protected function getColumns(): array
    {
        $column_factory = $this->ui_factory->table()->column();
        $columns_definition = [
            'sequence_position' => $column_factory->number($this->lng->txt('position'))->withUnit('.'),
            'source_pool_label' => $column_factory->link($this->lng->txt('tst_source_question_pool')),
            'taxonomy_filter' => $column_factory->text(
                $this->lng->txt('tst_filter_taxonomy') . ' / ' . $this->lng->txt('tst_filter_tax_node')
            ),
            'lifecycle_filter' => $column_factory->text($this->lng->txt('qst_lifecycle')),
            'type_filter' => $column_factory->text($this->lng->txt('tst_filter_question_type')),
            'question_amount' => $column_factory->text($this->lng->txt('tst_question_amount')),
        ];

        $columns_conditions = [
            'sequence_position' => !$this->editable,
            'question_amount' => $this->show_amount,
        ];

        return array_filter($columns_definition, function ($key) use ($columns_conditions) {
            return !isset($columns_conditions[$key]) || $columns_conditions[$key];
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return array<string, Action>
     */
    protected function getActions(): array
    {
        return [
            'delete' => $this->ui_factory->table()->action()->standard(
                $this->lng->txt('delete'),
                ... $this->getActionURI(\ilTestRandomQuestionSetConfigGUI::CMD_DELETE_MULTI_SRC_POOL_DEFS, true)
            ),
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('edit'),
                ... $this->getActionURI(\ilTestRandomQuestionSetConfigGUI::CMD_SHOW_EDIT_SRC_POOL_DEF_FORM)
            )
        ];
    }

    protected function getAmountCellContent(int $def_id, ?int $amount): string
    {
        return $this->editable
            ? '<input type="text" size="4" value="' . $amount . '" name="quest_amount[' . $def_id . ']" />'
            : (string) $amount;
    }

    protected function buildTargetURI(string $cmd): URI
    {
        $target = $this->ctrl->getLinkTargetByClass(\ilTestRandomQuestionSetConfigGUI::class, $cmd);
        $path = parse_url($target, PHP_URL_PATH);
        $query = parse_url($target, PHP_URL_QUERY);
        return $this->data_factory->uri((string) ServerRequest::getUriFromGlobals()->withPath($path)->withQuery($query));
    }

    /**
     * @return array{URLBuilder, URLBuilderToken}
     */
    protected function getActionURI(string $cmd, bool $multi = false): array
    {
        $builder = new URLBuilder($this->buildTargetURI($cmd));
        return $builder->acquireParameters(['src_pool_def'], $multi ? 'ids' : 'id');
    }
}

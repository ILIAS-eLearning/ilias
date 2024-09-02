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

namespace ILIAS\Test\Logging;

use ILIAS\Test\Utilities\TitleColumnsBuilder;

use ILIAS\TestQuestionPool\Questions\GeneralQuestionPropertiesRepository;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Listing\Descriptive as DescriptiveListing;
use ILIAS\StaticURL\Services as StaticURLServices;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\UI\Component\Table\DataRow;

class TestScoringInteraction implements TestUserInteraction
{
    public const IDENTIFIER = 'si';

    private int $id;

    /**
    * @param array<string label_lang_var => mixed value> $additional_data
    */
    public function __construct(
        private readonly int $test_ref_id,
        private readonly int $question_id,
        private readonly int $admin_id,
        private readonly int $pax_id,
        private readonly TestScoringInteractionTypes $interaction_type,
        private readonly int $modification_timestamp,
        private readonly array $additional_data
    ) {

    }

    public function getUniqueIdentifier(): ?string
    {
        return self::IDENTIFIER . '_' . $this->id;
    }

    public function withId(int $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function getLogEntryAsDataTableRow(
        \ilLanguage $lng,
        TitleColumnsBuilder $title_builder,
        DataRowBuilder $row_builder,
        array $environment
    ): DataRow {
        $values = [
            'date_and_time' => \DateTimeImmutable::createFromFormat('U', (string) $this->modification_timestamp)
                ->setTimezone($environment['timezone']),
            'corresponding_test' => $title_builder->buildTestTitleAsLink(
                $this->test_ref_id
            ),
            'admin' => \ilUserUtil::getNamePresentation(
                $this->admin_id,
                false,
                false,
                '',
                true
            ),
            'participant' => \ilUserUtil::getNamePresentation(
                $this->pax_id,
                false,
                false,
                '',
                true
            ),
            'log_entry_type' => $lng->txt(self::LANG_VAR_PREFIX . self::IDENTIFIER),
            'interaction_type' => $lng->txt(self::LANG_VAR_PREFIX . $this->interaction_type->value)
        ];

        if ($this->question_id !== null) {
            $values['question'] = $title_builder->buildQuestionTitleAsLink(
                $this->question_id,
                $this->test_ref_id
            );
        }

        return $row_builder->buildDataRow(
            $this->getUniqueIdentifier(),
            $values
        )->withDisabledAction(
            LogTable::ACTION_ID_SHOW_ADDITIONAL_INFO,
            $this->additional_data === []
        );
    }

    public function getLogEntryAsExportRow(
        \ilLanguage $lng,
        TitleColumnsBuilder $title_builder,
        AdditionalInformationGenerator $additional_info,
        array $environment
    ): array {
        return [
            \DateTimeImmutable::createFromFormat('U', (string) $this->modification_timestamp)
                ->setTimezone($environment['timezone'])
                ->format($environment['date_format']),
            $title_builder->buildTestTitleAsText($this->test_ref_id),
            \ilUserUtil::getNamePresentation(
                $this->admin_id,
                false,
                false,
                '',
                true
            ),
            \ilUserUtil::getNamePresentation(
                $this->pax_id,
                false,
                false,
                '',
                true
            ),
            '',
            $title_builder->buildQuestionTitleAsText($this->question_id),
            $lng->txt(self::LANG_VAR_PREFIX . self::IDENTIFIER),
            $lng->txt(self::LANG_VAR_PREFIX . $this->interaction_type->value),
            $additional_info->parseForExport($this->additional_data, $environment)
        ];
    }

    public function getParsedAdditionalInformation(
        AdditionalInformationGenerator $additional_info,
        UIFactory $ui_factory,
        array $environment
    ): DescriptiveListing {
        return $additional_info->parseForTable($this->additional_data, $environment);
    }

    public function toStorage(): array
    {
        return [
            'ref_id' => [\ilDBConstants::T_INTEGER , $this->test_ref_id],
            'qst_id' => [\ilDBConstants::T_INTEGER , $this->question_id],
            'admin_id' => [\ilDBConstants::T_INTEGER , $this->admin_id],
            'pax_id' => [\ilDBConstants::T_INTEGER , $this->pax_id],
            'interaction_type' => [\ilDBConstants::T_TEXT , $this->interaction_type->value],
            'modification_ts' => [\ilDBConstants::T_INTEGER , $this->modification_timestamp],
            'additional_data' => [\ilDBConstants::T_CLOB , json_encode($this->additional_data)]
        ];
    }
}

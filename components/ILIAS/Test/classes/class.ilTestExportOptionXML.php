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

use ILIAS\Data\ObjectId;
use ILIAS\Data\ReferenceId;
use ILIAS\Export\ExportHandler\Consumer\ExportOption\BasicLegacyHandler as ilBasicLegacyExportOption;
use ILIAS\Export\ExportHandler\I\Consumer\Context\HandlerInterface as ilExportHandlerConsumerContextInterface;
use ILIAS\DI\Container;
use ILIAS\Export\ExportHandler\I\Info\File\CollectionInterface as ilExportHandlerFileInfoCollectionInterface;
use ILIAS\Export\ExportHandler\I\Consumer\File\Identifier\CollectionInterface as ilExportHandlerConsumerFileIdentifierCollectionInterface;
use ILIAS\Export\ExportHandler\I\Consumer\File\Identifier\HandlerInterface as ilExportHandlerConsumerFileIdentifierInterface;

class ilTestExportOptionXML extends ilExportExportOptionXML
{
    protected ilLanguage $lng;

    public function init(
        Container $DIC
    ): void {
        $this->lng = $DIC->language();
        parent::init($DIC);
    }

    public function getExportType(): string
    {
        return 'ZIP';
    }

    public function getExportOptionId(): string
    {
        return 'test_exp_option_xml';
    }

    public function getSupportedRepositoryObjectTypes(): array
    {
        return ['tst'];
    }

    public function getLabel(): string
    {
        $this->lng->loadLanguageModule('exp');
        return $this->lng->txt("exp_format_dropdown-xml");
    }

    public function onExportOptionSelected(
        ilExportHandlerConsumerContextInterface $context
    ): void {
        $this->ctrl->redirect($context->exportGUIObject(), ilExportGUI::CMD_EXPORT_XML);
    }
}

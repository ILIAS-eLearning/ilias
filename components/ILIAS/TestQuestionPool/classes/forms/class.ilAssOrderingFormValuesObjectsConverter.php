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

use ILIAS\TestQuestionPool\RequestDataCollector;

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package components\ILIAS/Test(QuestionPool)
 */
class ilAssOrderingFormValuesObjectsConverter implements ilFormValuesManipulator
{
    public const INDENTATIONS_POSTVAR_SUFFIX = '_ordering';
    public const INDENTATIONS_POSTVAR_SUFFIX_JS = '__default';

    public const CONTEXT_MAINTAIN_ELEMENT_TEXT = 'maintainItemText';
    public const CONTEXT_MAINTAIN_ELEMENT_IMAGE = 'maintainItemImage';
    public const CONTEXT_MAINTAIN_HIERARCHY = 'maintainHierarchy';

    /**
     * @var string
     */
    protected $context = null;

    /**
     * @var string
     */
    protected $postVar = null;

    /**
     * @var string
     */
    protected $imageRemovalCommand = null;

    /**
     * @var string
     */
    protected $imageUrlPath;

    /**
     * @var string
     */
    protected $imageFsPath;

    /**
     * @var string
     */
    protected $thumbnailPrefix;

    private readonly RequestDataCollector $request_data_collector;

    public function __construct()
    {
        global $DIC;
        $this->request_data_collector = new RequestDataCollector($DIC->http(), $DIC->refinery(), $DIC->upload());
    }

    /**
     * @return string
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * @param $context
     */
    public function setContext($context): void
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getPostVar(): ?string
    {
        return $this->postVar;
    }

    /**
     * @param $postVar
     */
    public function setPostVar($postVar): void
    {
        $this->postVar = $postVar;
    }

    public function getImageRemovalCommand(): ?string
    {
        return $this->imageRemovalCommand;
    }

    public function setImageRemovalCommand($imageRemovalCommand): void
    {
        $this->imageRemovalCommand = $imageRemovalCommand;
    }

    public function getImageUrlPath(): string
    {
        return $this->imageUrlPath;
    }

    /**
     * @param string $imageUrlPath
     */
    public function setImageUrlPath($imageUrlPath): void
    {
        $this->imageUrlPath = $imageUrlPath;
    }

    /**
     * @return string
     */
    public function getImageFsPath(): string
    {
        return $this->imageFsPath;
    }

    /**
     * @param string $imageFsPath
     */
    public function setImageFsPath($imageFsPath): void
    {
        $this->imageFsPath = $imageFsPath;
    }

    /**
     * @return string
     */
    public function getThumbnailPrefix(): string
    {
        return $this->thumbnailPrefix;
    }

    /**
     * @param string $thumbnailPrefix
     */
    public function setThumbnailPrefix($thumbnailPrefix): void
    {
        $this->thumbnailPrefix = $thumbnailPrefix;
    }

    public function getIndentationsPostVar(): string
    {
        $postVar = $this->getPostVar();
        $postVar .= self::INDENTATIONS_POSTVAR_SUFFIX;
        $postVar .= self::INDENTATIONS_POSTVAR_SUFFIX_JS;

        return $postVar;
    }

    protected function needsConvertToValues($elements_or_values): bool
    {
        if (!count($elements_or_values)) {
            return false;
        }

        return (current($elements_or_values) instanceof ilAssOrderingElement);
    }

    public function manipulateFormInputValues(array $input_values): array
    {
        if ($this->needsConvertToValues($input_values)) {
            $input_values = $this->collectValuesFromElements($input_values);
        }

        return $input_values;
    }

    protected function collectValuesFromElements(array $elements): array
    {
        $values = [];

        foreach ($elements as $identifier => $orderingElement) {
            switch ($this->getContext()) {
                case self::CONTEXT_MAINTAIN_ELEMENT_TEXT:

                    $values[$identifier] = $this->getTextContentValueFromObject($orderingElement);
                    break;

                case self::CONTEXT_MAINTAIN_ELEMENT_IMAGE:

                    $values[$identifier] = $this->getImageContentValueFromObject($orderingElement);
                    break;

                case self::CONTEXT_MAINTAIN_HIERARCHY:

                    $values[$identifier] = $this->getStructValueFromObject($orderingElement);
                    break;

                default:
                    throw new ilFormException('unsupported context: ' . $this->getContext());
            }
        }

        return $values;
    }

    protected function getTextContentValueFromObject(ilAssOrderingElement $element): ?string
    {
        return $element->getContent();
    }

    protected function getImageContentValueFromObject(ilAssOrderingElement $element): array
    {
        $element->setImagePathWeb($this->getImageUrlPath());
        $element->setImagePathFs($this->getImageFsPath());
        $element->setImageThumbnailPrefix($this->getThumbnailPrefix());

        return [
            'title' => $element->getContent(),
            'src' => $element->getPresentationImageUrl()
        ];
    }

    protected function getStructValueFromObject(ilAssOrderingElement $element): array
    {
        return [
            'answer_id' => $element->getId(),
            'random_id' => $element->getRandomIdentifier(),
            'content' => (string) $element->getContent(),
            'ordering_position' => $element->getPosition(),
            'ordering_indentation' => $element->getIndentation()
        ];
    }

    protected function needsConvertToElements($valuesOrElements): bool
    {
        if (!count($valuesOrElements)) {
            return false;
        }

        return !(current($valuesOrElements) instanceof ilAssOrderingElement);
    }

    public function manipulateFormSubmitValues(array $submitValues): array
    {
        if ($this->needsConvertToElements($submitValues)) {
            $submitValues = $this->constructElementsFromValues($submitValues);
        }

        return $submitValues;
    }

    public function constructElementsFromValues(array $values): array
    {
        $elements = [];

        $content = $values;
        if (array_key_exists('content', $values)) {
            $content = $values['content'];
        }

        $position = [];
        if (array_key_exists('position', $values)) {
            $position = $values['position'];
        }

        $indentation = [];
        if (array_key_exists('indentation', $values)) {
            $indentation = $values['indentation'];
        }

        $counter = 0;
        foreach ($content as $identifier => $value) {
            $element = new ilAssOrderingElement();

            $element->setRandomIdentifier((int) $identifier);
            $element->setPosition((int) ($position[$identifier] ?? $counter));
            $element->setContent($value);
            $element->setIndentation((int) ($indentation[$identifier] ?? 0));

            if ($this->getContext() === self::CONTEXT_MAINTAIN_ELEMENT_IMAGE) {
                $element->setUploadImageName($this->fetchSubmittedImageFilename($identifier));
                $element->setUploadImageFile($this->fetchSubmittedUploadFilename($identifier));

                $element->setImageRemovalRequest($this->wasImageRemovalRequested($identifier));
            }

            $elements[$identifier] = $element;
        }

        return $elements;
    }

    protected function fetchSubmittedImageFilename($identifier)
    {
        $fileUpload = $this->fetchElementFileUpload($identifier);
        return $this->fetchSubmittedFileUploadProperty($fileUpload, 'name');
    }

    protected function fetchSubmittedUploadFilename($identifier)
    {
        $fileUpload = $this->fetchElementFileUpload($identifier);
        return $this->fetchSubmittedFileUploadProperty($fileUpload, 'tmp_name');
    }

    protected function fetchSubmittedFileUploadProperty(mixed $fileUpload, string $property)
    {
        return $fileUpload[$property] ?? null;
    }

    protected function fetchElementFileUpload($identifier)
    {
        return $this->fetchSubmittedUploadFiles()[$identifier] ?? [];
    }

    protected function fetchSubmittedUploadFiles(): array
    {
        $submittedUploadFiles = $this->getFileSubmitDataRestructuredByIdentifiers();
        //$submittedUploadFiles = $this->getFileSubmitsHavingActualUpload($submittedUploadFiles);
        return $submittedUploadFiles;
    }

    protected function getFileSubmitsHavingActualUpload(array $submittedUploadFiles): array
    {
        foreach ($submittedUploadFiles as $identifier => $uploadProperties) {
            if (!isset($uploadProperties['tmp_name'])) {
                unset($submittedUploadFiles[$identifier]);
                continue;
            }

            if ($uploadProperties['tmp_name'] === '') {
                unset($submittedUploadFiles[$identifier]);
                continue;
            }

            if (!is_uploaded_file($uploadProperties['tmp_name'])) {
                unset($submittedUploadFiles[$identifier]);
            }
        }

        return $submittedUploadFiles;
    }

    /**
     * @return array
     */
    protected function getFileSubmitDataRestructuredByIdentifiers(): array
    {
        $submittedUploadFiles = [];

        foreach ($this->getFileSubmitData() as $uploadProperty => $valueElement) {
            foreach ($valueElement as $elementIdentifier => $uploadValue) {
                if (!isset($submittedUploadFiles[$elementIdentifier])) {
                    $submittedUploadFiles[$elementIdentifier] = [];
                }

                $submittedUploadFiles[$elementIdentifier][$uploadProperty] = $uploadValue;
            }
        }

        return $submittedUploadFiles;
    }

    protected function getFileSubmitData(): array
    {
        return $_FILES[$this->getPostVar()] ?? [];
    }

    /**
     * TODO: Instead of accessing post, the complete ilFormValuesManipulator should be aware of a server request or the corresponding processed input values.
     * @param $identifier
     * @return bool
     */
    protected function wasImageRemovalRequested($identifier): bool
    {
        if (!$this->getImageRemovalCommand()) {
            return false;
        }

        $cmd = $this->request_data_collector->retrieveNestedArraysOfStrings('cmd', 3);

        if (!isset($cmd[$this->getImageRemovalCommand()])) {
            return false;
        }

        $fieldArr = $cmd[$this->getImageRemovalCommand()];

        if (!isset($fieldArr[$this->getPostVar()])) {
            return false;
        }

        return (string) str_replace(
            ilIdentifiedMultiValuesJsPositionIndexRemover::IDENTIFIER_INDICATOR_PREFIX,
            '',
            (string) key($fieldArr[$this->getPostVar()])
        ) === (string) $identifier;
    }
}

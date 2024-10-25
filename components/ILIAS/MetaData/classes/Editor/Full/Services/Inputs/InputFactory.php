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

namespace ILIAS\MetaData\Editor\Full\Services\Inputs;

use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Component\Input\Field\Factory as UIFactory;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Repository\Dictionary\DictionaryInterface as DatabaseDictionary;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Editor\Full\Services\DataFinder;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactory;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactoryInterface;
use ILIAS\MetaData\Editor\Full\Services\Inputs\Conditions\FactoryWithConditionTypesService;
use ILIAS\MetaData\Elements\Data\Type;
use ILIAS\MetaData\Vocabularies\ElementHelper\ElementHelperInterface;

class InputFactory
{
    protected UIFactory $ui_factory;
    protected Refinery $refinery;
    protected PresenterInterface $presenter;
    protected PathFactory $path_factory;
    protected NavigatorFactoryInterface $navigator_factory;
    protected ElementHelperInterface $element_vocab_helper;
    protected DataFinder $data_finder;
    protected FactoryWithConditionTypesService $types;

    /**
     * This is only here because the
     * editor needs to know which elements can be created (meaning
     * have a non-null create query).
     * This should be changed when we change the DB structure to
     * something that can work better with the new editor.
     */
    protected DatabaseDictionary $db_dictionary;

    public function __construct(
        UIFactory $ui_factory,
        Refinery $refinery,
        PresenterInterface $presenter,
        PathFactory $path_factory,
        DataFinder $data_finder,
        DatabaseDictionary $db_dictionary,
        FactoryWithConditionTypesService $types,
        ElementHelperInterface $element_vocab_helper
    ) {
        $this->ui_factory = $ui_factory;
        $this->refinery = $refinery;
        $this->presenter = $presenter;
        $this->path_factory = $path_factory;
        $this->data_finder = $data_finder;
        $this->db_dictionary = $db_dictionary;
        $this->types = $types;
        $this->element_vocab_helper = $element_vocab_helper;
    }

    public function getInputFields(
        ElementInterface $element,
        ElementInterface $context_element,
        bool $with_title
    ): Section|Group {
        /**
         * Vocab Sources don't have their own inputs, but are set implicitely with
         * the corresponding Vocab Values (see VocabValueFactory).
         */
        $data_carriers = iterator_to_array($this->data_finder->getDataCarryingElements($element, true));
        $conditional_elements = [];
        $input_elements = [];
        foreach ($data_carriers as $data_carrier) {
            $conditional_element = null;
            foreach ($this->element_vocab_helper->slotsForElementWithoutCondition($data_carrier) as $slot) {
                /**
                 * The conditions of multiple slots for the same element should point at the
                 * same element (or at least not more than one per context element).
                 */
                if ($el = $this->element_vocab_helper->findElementOfCondition($slot, $data_carrier, ...$data_carriers)) {
                    $conditional_element = $data_carrier;
                    $data_carrier = $el;
                    break;
                }
            }
            $path_string = $this->path_factory->toElement($data_carrier, true)
                                              ->toString();
            $input_elements[$path_string] = $data_carrier;
            if (isset($conditional_element)) {
                $conditional_elements[$path_string] = $conditional_element;
            }
        }

        $inputs = [];
        $exclude_required = [];
        foreach ($input_elements as $path_string => $input_element) {
            $data_type = $input_element->getDefinition()->dataType();
            if (isset($conditional_elements[$path_string])) {
                $input = $this->types->conditionFactory($data_type)->getConditionInput(
                    $input_element,
                    $context_element,
                    $conditional_elements[$path_string]
                );
            } else {
                $input = $this->types->factory($data_type)->getInput(
                    $input_element,
                    $context_element
                );
            }
            $inputs[$path_string] = $input;

            /**
             * If a data element can't be created, it needs to be excluded
             * from checking whether at least one input field is not empty.
             */
            if (is_null($this->db_dictionary->tagForElement($input_element))) {
                $exclude_required[] = $path_string;
            }
        }

        if ($with_title) {
            $fields = $this->ui_factory->section(
                $inputs,
                $this->presenter->elements()->nameWithParents($context_element)
            );
        } else {
            $fields = $this->ui_factory->group($inputs);
        }

        // needs flattening twice because of vocab sources in conditional inputs
        return $this->addNotEmptyConstraintIfNeeded(
            $context_element,
            $this->flattenOutput($this->flattenOutput($fields)),
            ...$exclude_required
        );
    }

    protected function flattenOutput(
        Section|Group $fields
    ): Section|Group {
        return $fields->withAdditionalTransformation(
            $this->refinery->custom()->transformation(function ($vs) {
                foreach ($vs as $key => $value) {
                    if (!is_array($value)) {
                        continue;
                    }
                    $vs[$key] = $value[0];
                    foreach ($value[1] as $k => $v) {
                        $vs[$k] = $v;
                    }
                }
                return $vs;
            })
        );
    }

    /**
     * If the current element can't be created on its own due to the db
     * structure, the editor has to require that at least one of the
     * inputs is not empty.
     */
    protected function addNotEmptyConstraintIfNeeded(
        ElementInterface $context_element,
        Section|Group $fields,
        string ...$excluded_input_keys
    ): Section|Group {
        $db_tag = $this->db_dictionary->tagForElement($context_element);
        if (!is_null($db_tag) && !$db_tag->hasData()) {
            return $fields;
        }
        return $fields->withAdditionalTransformation(
            $this->refinery->custom()->constraint(
                function ($vs) use ($excluded_input_keys) {
                    foreach ($vs as $p => $v) {
                        if (in_array($p, $excluded_input_keys)) {
                            continue;
                        }
                        if ($v !== '' && $v !== null) {
                            return true;
                        }
                    }
                    return false;
                },
                $this->presenter->utilities()->txt('meta_error_empty_input')
            )
        );
    }
}

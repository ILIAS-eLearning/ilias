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

namespace ILIAS\UI\Component\Input\Field;

use ILIAS\UI\Component\Input\Container\Form\FormInput;

/**
 * Describes an Input Field which dynamically generates inputs according to
 * a template. This happens on both server and client when values are provided.
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
interface HasDynamicInputs extends FormInput
{
    /**
     * Returns an Input Field which will be used to generate similar inputs
     * on both server and client.
     */
    public function getTemplateForDynamicInputs(): FormInput;

    /**
     * Returns serverside generated dynamic Inputs, which happens when
     * providing values with @see HasDynamicInputs::withValue()
     *
     * @return FormInput[]
     */
    public function getGeneratedDynamicInputs(): array;
}

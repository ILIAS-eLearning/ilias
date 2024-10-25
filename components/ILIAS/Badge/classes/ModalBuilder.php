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

namespace ILIAS\Badge;

use ILIAS\UI\Implementation\Component\Image\Image;
use ILIAS\UI\Component\Modal\Modal;
use ilBadgeAssignment;
use ilLanguage;
use ilDateTime;
use ilDatePresentation;
use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;

class ModalBuilder
{
    private Factory $ui_factory;
    private Renderer $ui_renderer;
    private ilLanguage $lng;
    private ?ilBadgeAssignment $assignment = null;

    public function __construct(ilBadgeAssignment $assignment = null)
    {
        global $DIC;

        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('badge');

        if ($assignment) {
            $this->assignment = $assignment;
        }
    }

    /**
     * @param array<string, string> $badge_properties
     */
    public function constructModal(
        Image $badge_image,
        string $badge_title,
        array $badge_properties = []
    ): Modal {
        $modal_content[] = $badge_image;

        if ($this->assignment) {
            $badge_properties['badge_issued_on'] = ilDatePresentation::formatDate(
                new ilDateTime($this->assignment->getTimestamp(), IL_CAL_UNIX)
            );
        }

        $badge_properties = $this->translateKeysWithValidDataAttribute($badge_properties);
        $modal_content[] = $this->ui_factory->listing()->descriptive($badge_properties);

        return $this->ui_factory->modal()->roundtrip($badge_title, $modal_content);
    }

    public function renderModal(Modal $modal): string
    {
        return $this->ui_renderer->render($modal);
    }

    public function renderShyButton(string $label, Modal $modal): string
    {
        return $this->ui_renderer->render($this->ui_factory->button()->shy($label, $modal->getShowSignal()));
    }

    /**
     * @param array<string, string> $properties
     * @return array<string, string>
     */
    private function translateKeysWithValidDataAttribute(array $properties): array
    {
        $translations = [];

        if (\count($properties) > 0) {
            foreach ($properties as $lang_var => $data) {
                if ($data !== '') {
                    $translations[$this->lng->txt($lang_var)] = $data;
                }
            }
        }
        return $translations;
    }
}

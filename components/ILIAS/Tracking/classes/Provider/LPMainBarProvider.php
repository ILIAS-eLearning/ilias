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

declare(strict_types=0);

namespace ILIAS\LearningProgress;

use ilAchievementsGUI;
use ilDashboardGUI;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuProvider;
use ILIAS\MainMenu\Provider\StandardTopItemsProvider;
use ilObjUserTracking;
use ilLPPersonalGUI;

/**
 * Class LPMainBarProvider
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class LPMainBarProvider extends AbstractStaticMainMenuProvider
{
    /**
     * @inheritDoc
     */
    public function getStaticTopItems(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getStaticSubItems(): array
    {
        global $DIC;

        $title = $this->dic->language()->txt("mm_learning_progress");
        $icon = $this->dic->ui()->factory()->symbol()->icon()->standard(
            "trac",
            $title
        );
        $ctrl = $DIC->ctrl();

        return [
            $this->mainmenu->link($this->if->identifier('mm_pd_lp'))
                ->withTitle($title)
                ->withAction(
                    $ctrl->getLinkTargetByClass(
                        [
                            ilDashboardGUI::class,
                            ilAchievementsGUI::class,
                            ilLPPersonalGUI::class
                        ]
                    )
                )
                ->withParent(
                    StandardTopItemsProvider::getInstance()->getAchievementsIdentification()
                )
                ->withPosition(30)
                ->withSymbol($icon)
                ->withNonAvailableReason(
                    $this->dic->ui()->factory()->legacy()->legacyContent(
                        "{$this->dic->language()->txt('component_not_active')}"
                    )
                )
                ->withAvailableCallable(
                    function () {
                        return ilObjUserTracking::_enabledLearningProgress() &&
                            ilObjUserTracking::_hasLearningProgressLearner();
                    }
                ),
            ];
    }
}

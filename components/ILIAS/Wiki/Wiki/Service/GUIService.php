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

namespace ILIAS\Wiki\Wiki;

use ILIAS\Wiki\InternalGUIService;
use ILIAS\Wiki\InternalDomainService;

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class GUIService
{
    protected InternalGUIService $gui_service;
    protected InternalDomainService $domain_service;

    public function __construct(
        InternalDomainService $domain_service,
        InternalGUIService $gui_service
    ) {
        $this->gui_service = $gui_service;
        $this->domain_service = $domain_service;
    }

    protected function getObjWikiGUI(): \ilObjWikiGUI
    {
        $ref_id = $this->gui_service->request()->getRefId();
        $this->domain_service->wiki()->checkRefId($ref_id);
        return new \ilObjWikiGUI(
            "",
            $this->gui_service->request()->getRefId(),
            true,
            false
        );
    }

    public function translation(int $wiki_ref_id = 0): \ilObjectTranslation
    {
        if ($wiki_ref_id === 0) {
            $wiki_ref_id = $this->gui_service->request()->getRefId();
        }
        return $this->domain_service->wiki()->translation(
            $this->domain_service->wiki()->getObjId(
                $wiki_ref_id
            )
        );
    }


}

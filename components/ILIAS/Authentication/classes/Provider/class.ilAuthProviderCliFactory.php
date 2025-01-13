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

use ILIAS\Cron\CronException;

class ilAuthProviderCliFactory extends ilAuthProviderFactory
{
    public function getProviders(ilAuthCredentials $credentials): array
    {
        return [
            $this->getProviderByAuthMode($credentials, ilAuthUtils::AUTH_LOCAL)
        ];
    }

    public function getProviderByAuthMode(ilAuthCredentials $credentials, $a_authmode): ?ilAuthProviderInterface
    {
        switch ((int) $a_authmode) {
            case ilAuthUtils::AUTH_LOCAL:
                /** @var ilAuthProviderDatabase $provider */
                $provider = parent::getProviderByAuthMode($credentials, $a_authmode);
                return $provider->withoutPasswordVerification();

            default:
                throw new CronException("The cron CLI script supports local authentication only.");
        }
    }
}

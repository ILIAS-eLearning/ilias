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

class ilPRGCertificateHelper
{
    public function __construct(): void
    {
        private \ilLogger $log,
        private \ilCertificateTemplateRepository $certificate_template_repository,
        private \ilCertificateTypeClassMap $certificate_type_class_map,
        private \ilCertificateCron $certificate_cron,
        private ilUserCertificateRepository $user_certificate_repository
    }

    public function updateCertificateForPrg(
        int $obj_id,
        int $usr_id
    ): bool {
        try {
            $this->init();
            $class_name = $this->certificate_type_class_map->getPlaceHolderClassNameByType('prg');
            $template = $this->certificate_template_repository->fetchCurrentlyActiveCertificate($obj_id);

            $this->processEntry(
                $class_name,
                $obj_id,
                $usr_id,
                $template
            );

            return true;
        } catch (\ilException $exception) {
            $this->log->warning($exception->getMessage());
            return false;
        }
    }

    public function removeCertificateForUser(
        int $node_id,
        int $usr_id,
    ): void {
        $this->user_certificate_repository->deleteUserCertificatesForObject($usr_id, $node_id);
    }

    private function processEntry(
        string $class_name,
        int $obj_id,
        int $usr_id,
        \ilCertificateTemplate $template
    ): void {
        $entry = new \ilCertificateQueueEntry(
            $obj_id,
            $usr_id,
            $class_name,
            \ilCronConstants::IN_PROGRESS,
            (int) $template->getId(),
            time()
        );
        $this->certificate_cron->init();
        $this->certificate_cron->processEntry(0, $entry, []);
    }
}

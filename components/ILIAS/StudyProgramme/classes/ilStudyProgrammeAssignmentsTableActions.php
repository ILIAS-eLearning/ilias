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

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Modal;
use ILIAS\UI\Component\Prompt\IsPromptContent;
use ILIAS\Data\DateFormat\DateFormat;
use ILIAS\UI\Component\Input\Container\Form\Standard as Form;

class ilStudyProgrammeAssignmentsTableActions
{
    public const ACTION_MARK_ACCREDITED = "mark_accredited";
    public const ACTION_UNMARK_ACCREDITED = "unmark_accredited";
    public const ACTION_SHOW_INDIVIDUAL_PLAN = "show_individual_plan";
    public const ACTION_REMOVE_USER = "remove_user";
    public const ACTION_REMOVE_USER_CONFIRMED = "remove_user_confirmed";
    public const ACTION_CHANGE_DEADLINE = "change_deadline";
    public const ACTION_CHANGE_DEADLINE_SUBMITTED = "change_deadline_submitted";
    public const ACTION_CHANGE_EXPIRE_DATE = "change_expire_date";
    public const ACTION_CHANGE_EXPIRE_DATE_SUBMITTED = "change_expire_date_submitted";
    public const ACTION_MARK_RELEVANT = "mark_relevant";
    public const ACTION_UNMARK_RELEVANT = "unmark_relevant";
    public const ACTION_UPDATE_FROM_CURRENT_PLAN = "update_from_current_plan";
    public const ACTION_UPDATE_FROM_CURRENT_PLAN_CONFIRMED = "update_from_current_plan_confirmed";
    public const ACTION_UPDATE_CERTIFICATE = "update_certificate";
    public const ACTION_UPDATE_CERTIFICATE_CONFIRMED = "update_certificate_confirmed";
    public const ACTION_REMOVE_CERTIFICATE = "remove_certificate";
    public const ACTION_REMOVE_CERTIFICATE_CONFIRMED = "remove_certificate_confirmed";
    public const ACTION_ACKNOWLEDGE_COURSES = "acknowledge_completed_courses";
    public const ACTION_MAIL_USER = "mail_user";


    //actions following an async ation, i.e.: a modal triggered them
    public const ACTIONS_FROM_MODALS = [
      self::ACTION_REMOVE_USER_CONFIRMED,
      self::ACTION_UPDATE_FROM_CURRENT_PLAN_CONFIRMED,
      self::ACTION_UPDATE_CERTIFICATE_CONFIRMED,
      self::ACTION_REMOVE_CERTIFICATE_CONFIRMED,
    ];

    public function __construct(
        protected UIFactory $ui_factory,
        protected UIRenderer $ui_renderer,
        protected Refinery $refinery,
        protected ilCtrl $ctrl,
        protected ilLanguage $lng,
        protected ilObjStudyProgramme $prg,
        protected ilPRGAssignmentDBRepository $assignment_repo,
        protected ilStudyProgrammeAssignmentsTableQuery $table_query,
        protected ilPRGPermissionsHelper $permissions,
        protected ilPRGMessagePrinter $messages,
        protected ilObjUser $user
    ) {

    }


    public function doCommand(string $command, array $prgrs_ids): ?string
    {
        /*        var_dump($command);
                print '<hr>';
                var_dump($prgrs_ids);
                die();
        */
        switch ($command) {

            case self::ACTION_MARK_ACCREDITED:
                $this->markAccredited($prgrs_ids);
                break;

            case self::ACTION_UNMARK_ACCREDITED:
                $this->unmarkAccredited($prgrs_ids);
                break;

            case self::ACTION_SHOW_INDIVIDUAL_PLAN:
                $ass_id = current($prgrs_ids)->getAssignmentId();
                $target = $this->individual_plan_gui->getLinkTargetView($ass_id);
                $this->ctrl->redirectToURL($target);
                break;

            case self::ACTION_REMOVE_USER:
                $modal = $this->getConfirmationModal(
                    self::ACTION_REMOVE_USER_CONFIRMED,
                    $prgrs_ids
                );
                echo $this->ui_renderer->render($modal);
                exit();

            case self::ACTION_REMOVE_USER_CONFIRMED:
                $this->confirmedRemoveAssignment($prgrs_ids);
                break;


            case self::ACTION_MARK_RELEVANT:
                $this->markRelevant($prgrs_ids);
                break;

            case self::ACTION_UNMARK_RELEVANT:
                $this->markNotRelevant($prgrs_ids);
                break;

            case self::ACTION_UPDATE_FROM_CURRENT_PLAN:
                $modal = $this->getConfirmationModal(
                    self::ACTION_UPDATE_FROM_CURRENT_PLAN_CONFIRMED,
                    $prgrs_ids
                );
                echo $this->ui_renderer->render($modal);
                exit();

            case self::ACTION_UPDATE_FROM_CURRENT_PLAN_CONFIRMED:
                $this->updateFromCurrentPlan($prgrs_ids);
                break;


            case self::ACTION_UPDATE_CERTIFICATE:
                $modal = $this->getConfirmationModal(
                    self::ACTION_UPDATE_CERTIFICATE_CONFIRMED,
                    $prgrs_ids
                );
                echo $this->ui_renderer->render($modal);
                exit();
                break;

            case self::ACTION_UPDATE_CERTIFICATE_CONFIRMED:
                $this->updateCertificate($prgrs_ids);
                break;

            case self::ACTION_REMOVE_CERTIFICATE:
                $modal = $this->getConfirmationModal(
                    self::ACTION_REMOVE_CERTIFICATE_CONFIRMED,
                    $prgrs_ids
                );
                echo $this->ui_renderer->render($modal);
                exit();

            case self::ACTION_REMOVE_CERTIFICATE_CONFIRMED:
                $this->removeCertificate($prgrs_ids);
                break;

            case self::ACTION_ACKNOWLEDGE_COURSES:
                $cont = $this->acknowledgeCourses($prgrs_ids);
                $this->tpl->setContent($cont);
                break;

            case self::ACTION_MAIL_USER:
                $this->mailToSelectedUsers($prgrs_ids);
                break;


            case self::ACTION_CHANGE_DEADLINE:
                $this->respond(
                    $this->getChangeDeadlineForm(
                        self::ACTION_CHANGE_DEADLINE_SUBMITTED,
                        $prgrs_ids,
                        $this->user->getDateFormat()
                    )
                );

                // no break
            case self::ACTION_CHANGE_DEADLINE_SUBMITTED:
                $form = $this->getChangeDeadlineForm(
                    self::ACTION_CHANGE_DEADLINE_SUBMITTED,
                    $prgrs_ids,
                    $this->user->getDateFormat()
                )
                ->withRequest($this->table_query->getRequest());

                $data = $form->getData();
                if($data === null) {
                    $this->respond($form);
                }
                list($deadline_mode, $date) = $data;
                $date = array_shift($date);
                $this->changeDeadline($prgrs_ids, $date);
                break;

            case self::ACTION_CHANGE_EXPIRE_DATE:
                $this->respond(
                    $this->getExpiryForm(
                        self::ACTION_CHANGE_EXPIRE_DATE_SUBMITTED,
                        $prgrs_ids,
                        $this->user->getDateFormat()
                    )
                );


            case self::ACTION_CHANGE_EXPIRE_DATE_SUBMITTED:
                $modal = $this->getExpiryModal(
                    self::ACTION_CHANGE_EXPIRE_DATE_SUBMITTED,
                    $prgrs_ids,
                    $this->user->getDateFormat()
                )
                ->withRequest($this->request);

                $data = $modal->getData();
                list($expiry_mode, $date) = $data;

                if($data === null ||
                    ($expiry_mode === ilObjStudyProgrammeSettingsGUI::OPT_VALIDITY_OF_QUALIFICATION_DATE
                    && $date === null)
                ) {
                    $cont = $this->view()
                    . $this->ui_renderer->renderAsync(
                        $modal->withOnLoad($modal->getShowSignal())
                    );
                    $this->tpl->setContent($cont);
                    break;
                }
                $date = array_shift($date);

                $this->changeExpiryDate($prgrs_ids, $date);

                break;
        }
    }

    protected function getMessageCollection(string $topic): ilPRGMessageCollection
    {
        return $this->messages->getMessageCollection($topic);
    }

    protected function showMessages(ilPRGMessageCollection $msg): void
    {
        $this->messages->showMessages($msg);
    }

    protected function showSuccessMessage(string $lng_var): void
    {

        global $DIC; //TODO: messageCollection
        $tpl = $DIC['tpl'];
        $tpl->setOnScreenMessage("success", $this->lng->txt("prg_$lng_var"), true);
    }

    protected function showInfoMessage(string $lng_var): void
    {
        global $DIC; //TODO: messageCollection
        $tpl = $DIC['tpl'];
        $tpl->setOnScreenMessage("info", $this->lng->txt("prg_$lng_var"), true);
    }

    protected function mayCurrentUserEditProgressForUser(int $usr_id): bool
    {
        return in_array(
            $usr_id,
            $this->permissions->getUserIdsSusceptibleTo(ilOrgUnitOperation::OP_EDIT_INDIVIDUAL_PLAN)
        );
    }

    protected function returnToGUI(): void
    {
        $this->ctrl->redirectByClass(ilObjStudyProgrammeMembersGUI::class, "view");
    }

    protected function respond(IsPromptContent $response): void
    {
        echo $this->ui_renderer->renderAsync(
            $this->ui_factory->prompt()->state()->show($response)
        );
        exit();
    }

    protected function respondToGUI(): void
    {
        echo $this->ui_renderer->renderAsync(
            $this->ui_factory->state()->redirect(
                new \ILIAS\Data\URI(
                    ILIAS_HTTP_PATH . '/'
                    . $this->ctrl->getLinkTargetByClass(ilObjStudyProgrammeMembersGUI::class, "view"),
                )
            )
        );
        exit();
    }



    protected const MODAL_TEXTS = [
        ilStudyProgrammeAssignmentsTableActions::ACTION_REMOVE_USER_CONFIRMED => [
            'prg_remove_user',
            'confirm_to_remove_selected_assignments',
            'prg_remove_user',
        ],
        ilStudyProgrammeAssignmentsTableActions::ACTION_UPDATE_FROM_CURRENT_PLAN_CONFIRMED => [
            'confirm',
            'header_update_current_plan',
            'confirm',
        ],
        ilStudyProgrammeAssignmentsTableActions::ACTION_UPDATE_CERTIFICATE_CONFIRMED => [
            'confirm',
            'header_update_certificate',
            'confirm',
        ],
        ilStudyProgrammeAssignmentsTableActions::ACTION_REMOVE_CERTIFICATE_CONFIRMED => [
            'confirm',
            'header_remove_certificate',
            'confirm',
        ]
    ];

    protected function getConfirmationModal(string $action, array $prgs_ids): Modal\Interruptive
    {
        $affected = [];
        foreach ($prgs_ids as $id) {
            $user_name = ilObjUser::_lookupFullname($id->getUsrId());
            $affected[] = $this->ui_factory->modal()->interruptiveItem()->keyvalue(
                (string) $id,
                $user_name,
                (string) $id
            );
        }

        list($caption, $txt, $button_label) = self::MODAL_TEXTS[$action];

        return $this->ui_factory->modal()->interruptive(
            $this->lng->txt($caption),
            $this->lng->txt($txt),
            $this->table_query->getUrlBuilder()->withParameter(
                $this->table_query->getActionToken(),
                $action
            )
            ->buildURI()
            ->__toString()
        )
        ->withAffectedItems($affected)
        ->withActionButtonLabel($this->lng->txt($button_label));
    }

    public function getChangeDeadlineForm(
        string $action,
        array $prgrs_ids,
        DateFormat $format
    ): Form {
        $shift = $this->refinery->custom()->transformation(fn($v) => array_shift($v));
        $ff = $this->ui_factory->input()->field();
        $settings = $ff->switchableGroup(
            [
                ilObjStudyProgrammeSettingsGUI::OPT_NO_DEADLINE => $ff->group(
                    [],
                    $this->lng->txt('prg_no_deadline')
                ),
                ilObjStudyProgrammeSettingsGUI::OPT_DEADLINE_DATE => $ff->group(
                    [
                        $ff->dateTime('', $this->lng->txt('prg_deadline_date_desc'))
                        ->withFormat($format)
                        ->withRequired(true)
                    ],
                    $this->lng->txt('prg_deadline_date')
                )
            ],
            ''
        )->withValue(ilObjStudyProgrammeSettingsGUI::OPT_DEADLINE_DATE);

        $section = $ff->section(
            [$settings],
            $this->lng->txt('prg_deadline_settings'),
        )
        ->withAdditionalTransformation($shift);

        $ids = array_map(fn($id) => $id->__toString(), $prgrs_ids);
        $action = $this->table_query->getUrlBuilder()
            ->withParameter($this->table_query->getActionToken(), $action)
            ->withParameter($this->table_query->getRowIdToken(), $ids)
            ->buildURI()
            ->__toString();

        return $this->ui_factory->input()->container()->form()->standard(
            $action,
            [$section],
        )
        ->withAdditionalTransformation($shift);
    }


   public function getExpiryForm(
        string $action,
        array $prgrs_ids,
        DateFormat $format
    ): Form {
        $shift = $this->refinery->custom()->transformation(fn($v) => array_shift($v));
        $ff = $this->ui_factory->input()->field();
        $settings = $ff->switchableGroup(
            [
                ilObjStudyProgrammeSettingsGUI::OPT_NO_VALIDITY_OF_QUALIFICATION => $ff->group(
                    [],
                    $this->lng->txt('prg_no_validity_qualification')
                ),
                ilObjStudyProgrammeSettingsGUI::OPT_VALIDITY_OF_QUALIFICATION_DATE => $ff->group(
                    [
                        $ff->dateTime('', $this->lng->txt('validity_qualification_date_desc'))
                        ->withFormat($format)
                        ->withRequired(true)
                    ],
                    $this->lng->txt('validity_qualification_date')
                )
            ],
            ''
        )->withValue(ilObjStudyProgrammeSettingsGUI::OPT_VALIDITY_OF_QUALIFICATION_DATE);

        $section = $ff->section(
            [$settings],
            $this->lng->txt('prg_expiry_date'),
        )
        ->withAdditionalTransformation($shift);

        $ids = array_map(fn($id) => $id->__toString(), $prgrs_ids);
        $action = $this->table_query->getUrlBuilder()
            ->withParameter($this->table_query->getActionToken(), $action)
            ->withParameter($this->table_query->getRowIdToken(), $ids)
            ->buildURI()
            ->__toString();

        return $this->ui_factory->input()->container()->form()->standard(
            $action,
            [$section],
        )
        ->withAdditionalTransformation($shift);
    }



    protected function markAccredited(array $prgrs_ids): void
    {
        $msgs = $this->getMessageCollection('msg_mark_accredited');
        foreach ($prgrs_ids as $key => $prgrs_id) {
            $this->markAccreditedByProgressId($prgrs_id, $msgs);
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }

    protected function markAccreditedByProgressId(PRGProgressId $prgrs_id, ilPRGMessageCollection $msgs): void
    {
        $usr_id = $prgrs_id->getUsrId();
        if (!$this->mayCurrentUserEditProgressForUser($usr_id)) {
            $msgs->add(false, 'No permission to edit progress of user', (string) $prgrs_id);
        } else {
            $this->prg->markAccredited($prgrs_id->getAssignmentId(), $this->user->getId(), $msgs);
        }
    }

    protected function unmarkAccredited(array $prgrs_ids): void
    {
        $msgs = $this->getMessageCollection('msg_unmark_accredited');
        foreach ($prgrs_ids as $key => $prgrs_id) {
            $this->unmarkAccreditedByProgressId($prgrs_id, $msgs);
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }

    protected function unmarkAccreditedByProgressId(PRGProgressId $prgrs_id, ilPRGMessageCollection $msgs): void
    {
        $usr_id = $prgrs_id->getUsrId();
        if (!$this->mayCurrentUserEditProgressForUser($usr_id)) {
            $msgs->add(false, 'No permission to edit progress of user', (string) $prgrs_id);
        } else {
            $this->prg->unmarkAccredited($prgrs_id->getAssignmentId(), $this->user->getId(), $msgs);
        }
    }

    protected function markRelevant(array $prgrs_ids): void
    {
        $msgs = $this->getMessageCollection('msg_mark_relevant');
        foreach ($prgrs_ids as $key => $prgrs_id) {
            if (!$this->mayCurrentUserEditProgressForUser($prgrs_id->getUsrId())) {
                $msgs->add(false, 'No permission to edit progress of user', (string) $prgrs_id);
            } else {
                $this->prg->markRelevant($prgrs_id->getAssignmentId(), $this->user->getId(), $msgs);
            }
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }

    protected function markNotRelevant(array $prgrs_ids): void
    {
        $msgs = $this->getMessageCollection('msg_mark_not_relevant');
        foreach ($prgrs_ids as $key => $prgrs_id) {
            if (!$this->mayCurrentUserEditProgressForUser($prgrs_id->getUsrId())) {
                $msgs->add(false, 'No permission to edit progress of user', (string) $prgrs_id);
            } else {
                $this->prg->markNotRelevant($prgrs_id->getAssignmentId(), $this->user->getId(), $msgs);
            }
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }

    protected function updateFromCurrentPlan(array $prgrs_ids): void
    {
        $msgs = $this->getMessageCollection('msg_update_from_settings');
        foreach ($prgrs_ids as $idx => $prgrs_id) {
            if (!$this->mayCurrentUserEditProgressForUser($prgrs_id->getUsrId())) {
                $msgs->add(false, 'no_permission_to_update_plan_of_user', (string) $prgrs_id);
                continue;
            } else {
                $msgs->add(true, '', (string) $prgrs_id);
            }

            $this->prg->updatePlanFromRepository(
                $prgrs_id->getAssignmentId(),
                $this->user->getId(),
                $msgs
            );
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }

    protected function changeDeadline(array $prgrs_ids, ?DateTimeImmutable $deadline): void
    {
        $msgs = $this->getMessageCollection('msg_change_deadline_date');
        foreach ($prgrs_ids as $progress_id) {
            $assignment_id = $progress_id->getAssignmentId();
            $this->prg->changeProgressDeadline($assignment_id, $this->user->getId(), $msgs, $deadline);
        }
        $this->showMessages($msgs);
        $this->respondToGUI();
    }

    protected function changeExpiryDate(array $prgrs_ids, ?DateTimeImmutable $validity): void
    {
        $msgs = $this->getMessageCollection('msg_change_expire_date');
        foreach ($prgrs_ids as $progress_id) {
            $assignment_id = $progress_id->getAssignmentId();
            $this->prg->changeProgressValidityDate($assignment_id, $this->user->getId(), $msgs, $validity);
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }


    protected function confirmedRemoveAssignment(array $prgrs_ids): void
    {
        $not_removed = [];
        foreach ($prgrs_ids as $idx => $prgrs_id) {
            try {
                $this->removeAssignmentByProgressId($prgrs_id);
            } catch (ilException $e) {
                $not_removed[] = $prgrs_id;
            }
        }
        if (count($not_removed) === count($prgrs_ids)) {
            $this->showInfoMessage("remove_users_not_possible");
        } elseif (count($not_removed) > 0) {
            $this->showSuccessMessage("remove_users_partial_success");
        } else {
            $this->showSuccessMessage("remove_users_success");
        }
        $this->returnToGUI();
    }

    protected function removeAssignmentByProgressId(PRGProgressId $prgrs_id): void
    {
        if (!in_array(
            $prgrs_id->getUsrId(),
            $this->permissions->getUserIdsSusceptibleTo(ilOrgUnitOperation::OP_MANAGE_MEMBERS)
        )) {
            throw new ilStudyProgrammePositionBasedAccessViolationException(
                'No permission to manage membership of user'
            );
        }

        $ass = $this->assignment_repo->get($prgrs_id->getAssignmentId());
        $prg_ref_id = ilObjStudyProgramme::getRefIdFor($ass->getRootId());
        if ($prg_ref_id !== $this->prg->getRefId()) {
            throw new ilException("Can only remove users from the node they where assigned to.");
        }
        $this->prg->removeAssignment($ass);
    }


    protected function mailToSelectedUsers(array $prgrs_ids): void 
    {
        $this->ctrl->setParameterByClass(
            ilStudyProgrammeMailMemberSearchGUI::class,
            ilObjStudyProgrammeMembersGUI::F_QUERY_PROGRESS_IDS,
            implode(',', array_map('strval', $prgrs_ids))
        );

        $link = $this->ctrl->getLinkTargetByClass(
            ilStudyProgrammeMailMemberSearchGUI::class,
            'sendMailToSelectedUsers'
        );
        $this->ctrl->redirectToURL($link);
    }

    protected function acknowledgeCourses(array $prgrs_ids): string 
    {
        $assignments = [];
        foreach ($prgrs_ids as $progress_id) {
            $assignments[] = $this->assignment_repo->get($progress_id->getAssignmentId());
        }
        //return $this->viewCompletedCourses($assignments);
        die('viewCompletedCourses on MembersGUI');

        //TRY TO GET RID OF assignment_repo HERE.
    }

    protected function updateCertificate(array $prgrs_ids): void 
    {
        $msgs = $this->getMessageCollection('msg_update_certificate');
        foreach ($prgrs_ids as $progress_id) {

            // do not exeute, but delegate to prg
            $assignment = $this->assignment_repo->get($progress_id->getAssignmentId());
            $progress = $assignment->getProgressForNode($progress_id->getNodeId());
            if(!$progress->isSuccessful()) {
                $msgs->add(false, 'will_not_update_cert_for_unsuccessful_progress', (string)$progress_id);
                continue;
            }
            if ($this->prg->updateCertificate( // from a trait, that should be a class
                $progress_id->getNodeId(),
                $progress_id->getUsrId()
            )) {
                $msgs->add(true, '', (string)$progress_id);
            } else {
                $msgs->add(false, 'error_updating_certificate', (string)$progress_id);
            }

        }
        $this->showMessages($msgs);
        $this->returnToGUI();
    }
    
    protected function removeCertificate(array $prgrs_ids): void 
    {

    }

    /**
     *  
     * $msgs = $this->getMessageCollection('msg_mark_not_relevant');
        foreach ($prgrs_ids as $key => $prgrs_id) {
            if (!$this->mayCurrentUserEditProgressForUser($prgrs_id->getUsrId())) {
                $msgs->add(false, 'No permission to edit progress of user', (string) $prgrs_id);
            } else {
                $this->prg->markNotRelevant($prgrs_id->getAssignmentId(), $this->user->getId(), $msgs);
            }
        }
        $this->showMessages($msgs);
        $this->returnToGUI();
        */




}

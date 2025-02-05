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

use ILIAS\BackgroundTasks\Task\TaskFactory;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Exercise\InternalService;
use ILIAS\Exercise\GUIRequest;
use ILIAS\Exercise\TutorFeedbackFile\TutorFeedbackFileManager;
use ILIAS\Exercise\InternalGUIService;
use ILIAS\Repository\Resources\ZipAdapter;
use ILIAS\Repository\Form\FormAdapterGUI;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;

/**
 * @ilCtrl_Calls ilExerciseManagementGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls ilExerciseManagementGUI: ilExSubmissionTeamGUI, ilExSubmissionFileGUI
 * @ilCtrl_Calls ilExerciseManagementGUI: ilExSubmissionTextGUI, ilExPeerReviewGUI
 * @ilCtrl_Calls ilExerciseManagementGUI: ilParticipantsPerAssignmentTableGUI
 * @ilCtrl_Calls ilExerciseManagementGUI: ilResourceCollectionGUI, ilRepoStandardUploadHandlerGUI
 * @ilCtrl_Calls ilExerciseManagementGUI: ilExerciseSubmissionFeedbackGUI
 */
class ilExerciseManagementGUI
{
    public const VIEW_ASSIGNMENT = 1;
    public const VIEW_PARTICIPANT = 2;
    public const VIEW_GRADES = 3;

    public const FEEDBACK_ONLY_SUBMISSION = "submission_only";
    public const FEEDBACK_FULL_SUBMISSION = "submission_feedback";

    public const GRADE_NOT_GRADED = "notgraded";
    public const GRADE_PASSED = "passed";
    public const GRADE_FAILED = "failed";
    protected ZipAdapter $zip;
    protected ilGlobalTemplateInterface $tpl;
    protected \ILIAS\Exercise\InternalDomainService $domain;
    protected \ILIAS\Exercise\Notification\NotificationManager $notification;
    protected TutorFeedbackFileManager $tutor_feedback_file;
    protected InternalGUIService $gui;
    protected \ILIAS\HTTP\Services $http;

    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs_gui;
    protected ilLanguage $lng;
    protected ilAccessHandler $access;
    protected Factory $ui_factory;
    protected Renderer $ui_renderer;
    protected array $filter = [];
    protected ilToolbarGUI $toolbar;
    protected ?ilObjExercise $exercise;
    protected ?ilExAssignment $assignment = null;
    protected TaskFactory $task_factory;
    protected ilLogger $log;
    protected ilObjUser $user;
    protected InternalService $service;
    protected ?ilDBInterface $db = null;
    protected int $ass_id = 0;
    protected int $requested_member_id = 0;
    protected int $requested_part_id = 0;
    protected int $requested_ass_id = 0;
    protected string $requested_idl_id;
    protected bool $done = false;
    protected array $requested_learning_comments;
    protected string $requested_comment;
    protected string $requested_user_login;
    protected array $selected_participants;
    protected array $listed_participants;
    protected array $selected_ass_ids;
    protected array $listed_ass_ids;
    protected array $requested_marks;                      // key might be ass_ids or user_ids!
    protected array $requested_status;                     // key might be ass_ids or user_ids!
    protected array $requested_tutor_notices;                     // key might be ass_ids or user_ids!
    protected array $requested_group_members;                     // "grpt"
    /** @var array<int, list<string>> */
    protected array $requested_files;                     // "file"
    protected string $requested_filter_status;
    protected string $requested_filter_feedback;
    protected GUIRequest $request;
    protected ilExerciseSubmissionFeedbackGUI $feedback_gui;

    /**
     * Constructor
     *
     * @param InternalService $service
     * @param ilExAssignment|null       $a_ass
     */
    public function __construct(InternalService $service, ?ilExAssignment $a_ass = null)
    {
        global $DIC;

        $this->service = $service;
        $this->gui = $gui = $service->gui();
        $this->domain = $domain = $service->domain();

        $this->user = $domain->user();
        $this->log = $domain->logger()->exc();
        $this->access = $domain->access();
        $this->lng = $domain->lng();

        $this->toolbar = $gui->toolbar();
        $this->ui_factory = $gui->ui()->factory();
        $this->ui_renderer = $gui->ui()->renderer();
        $this->ctrl = $gui->ctrl();
        $this->tabs_gui = $gui->tabs();
        $this->tpl = $gui->ui()->mainTemplate();
        $this->http = $gui->http();

        $this->task_factory = $domain->backgroundTasks()->taskFactory();
        $this->request = $gui->request();
        $request = $this->request;

        $this->exercise = $request->getExercise();
        if ($a_ass !== null) {
            $this->assignment = $a_ass;
            $this->ass_id = $this->assignment->getId();
        }
        $this->requested_member_id = $request->getMemberId();
        $this->requested_part_id = $request->getParticipantId();
        $this->requested_ass_id = $request->getAssId();
        $this->requested_idl_id = $request->getIdlId();
        $this->done = $request->getDone();
        $this->requested_learning_comments = $request->getLearningComments();
        $this->requested_comment = $request->getComment();
        $this->requested_user_login = $request->getUserLogin();
        $this->selected_participants = $request->getSelectedParticipants();
        $this->listed_participants = $request->getListedParticipants();
        $this->selected_ass_ids = $request->getSelectedAssignments();
        $this->listed_ass_ids = $request->getListedAssignments();
        $this->requested_marks = $request->getMarks();
        $this->requested_status = $request->getStatus();
        $this->requested_tutor_notices = $request->getTutorNotices();
        $this->requested_group_members = $request->getGroupMembers();
        $this->requested_files = $request->getFiles();
        $this->requested_filter_status = $request->getFilterStatus();
        $this->requested_filter_feedback = $request->getFilterFeedback();

        $this->notification = $domain->notification($request->getRefId());

        $this->feedback_gui = $DIC->exercise()->internal()->gui()->getSubmissionFeedbackGUI(
            $this->exercise,
            $this->notification
        );

        $this->ctrl->saveParameter($this, array("vw", "member_id"));
        if ($this->ass_id > 0) {
            $this->tutor_feedback_file = $domain->assignment()->tutorFeedbackFile($this->ass_id);
        }
        $this->zip = $domain->resources()->zip();
        $this->ctrl->saveParameter($this, array("part_id"));
    }

    /**
     * @throws ilCtrlException
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function executeCommand(): void
    {
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;
        $ilTabs = $this->tabs_gui;

        $class = $ilCtrl->getNextClass($this);
        //$cmd = $ilCtrl->getCmd("listPublicSubmissions");

        switch ($class) {
            // feedback files IRSS
            case strtolower(ilResourceCollectionGUI::class):
                $ilTabs->clearTargets();
                $ilTabs->setBackTarget(
                    $lng->txt("back"),
                    $ilCtrl->getLinkTarget($this, $this->getViewBack())
                );
                $this->domain->assignment()->tutorFeedbackFile($this->ass_id)->addObserver();
                $this->tpl->setOnScreenMessage('info', $lng->txt("exc_fb_tutor_info"));
                $gui = $this->gui->assignment()->getTutorFeedbackFileResourceCollectionGUI(
                    $this->exercise->getRefId(),
                    $this->assignment->getId(),
                    $this->requested_member_id
                );
                $this->ctrl->forwardCommand($gui);
                break;

            case 'ilrepositorysearchgui':
                $rep_search = new ilRepositorySearchGUI();
                $ref_id = $this->exercise->getRefId();
                $rep_search->addUserAccessFilterCallable(function ($a_user_ids) use ($ref_id) {
                    return $GLOBALS['DIC']->access()->filterUserIdsByRbacOrPositionOfCurrentUser(
                        'edit_submissions_grades',
                        'edit_submissions_grades',
                        $ref_id,
                        $a_user_ids
                    );
                });
                $rep_search->setTitle($this->lng->txt("exc_add_participant"));
                $rep_search->setCallback($this, 'addMembersObject');

                // Set tabs
                $this->addSubTabs("assignment");
                $this->ctrl->setReturn($this, 'members');

                $this->ctrl->forwardCommand($rep_search);
                break;

            case strtolower(ilRepoStandardUploadHandlerGUI::class):
                $form = $this->getMultiFeedbackForm($this->assignment->getId());
                $gui = $form->getRepoStandardUploadHandlerGUI("mfzip");
                $this->ctrl->forwardCommand($gui);
                break;

            case "ilexsubmissionteamgui":
                $gui = new ilExSubmissionTeamGUI($this->exercise, $this->initSubmission());
                $ilCtrl->forwardCommand($gui);
                break;

            case "ilexsubmissionfilegui":
                $gui = new ilExSubmissionFileGUI($this->exercise, $this->initSubmission());
                $ilCtrl->forwardCommand($gui);
                break;

            case "ilexsubmissiontextgui":
                $ilCtrl->saveParameter($this, array("part_id"));
                $gui = new ilExSubmissionTextGUI($this->exercise, $this->initSubmission());
                $ilCtrl->forwardCommand($gui);
                break;

            case "ilexpeerreviewgui":
                $gui = new ilExPeerReviewGUI($this->assignment, $this->initSubmission());
                $ilCtrl->forwardCommand($gui);
                break;

            case "ilparticipantsperassignmenttablegui":
                $table = new ilParticipantsPerAssignmentTableGUI(
                    $this,
                    "members",
                    $this->exercise,
                    $this->assignment->getId(),
                    $this->feedback_gui
                );
                $this->ctrl->forwardCommand($table);
                break;

            case "ilexercisesubmissionfeedbackgui":
                $this->ctrl->forwardCommand($this->feedback_gui);
                break;

            default:
                $cmd = $ilCtrl->getCmd();
                switch ($cmd) {
                    case 'downloadSubmissions':
                        $cmd = $ilCtrl->getCmd("downloadSubmissions");
                        break;
                    default:
                        $cmd = $ilCtrl->getCmd("listPublicSubmissions");
                        break;
                }
                $this->{$cmd . "Object"}();
                break;
        }
    }

    protected function getViewBack(): string
    {
        switch ($this->request->getBackView()) {
            case self::VIEW_PARTICIPANT:
                $back_cmd = "showParticipant";
                break;

            case self::VIEW_GRADES:
                $back_cmd = "showGradesOverview";
                break;

            default:
                // case self::VIEW_ASSIGNMENT:
                $back_cmd = "members";
                break;
        }
        return $back_cmd;
    }

    protected function initSubmission(): ilExSubmission
    {
        $back_cmd = $this->getViewBack();
        $this->ctrl->setReturn($this, $back_cmd);

        $this->tabs_gui->clearTargets();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, $back_cmd)
        );

        return new ilExSubmission($this->assignment, $this->requested_member_id, null, true);
    }

    public function addSubTabs(string $a_activate): void
    {
        $ilTabs = $this->tabs_gui;
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        $ass_id = $this->assignment !== null ? $this->assignment->getId() : 0;
        $part_id = $this->requested_part_id;

        $ilCtrl->setParameter($this, "vw", "");
        $ilCtrl->setParameter($this, "member_id", "");
        $ilCtrl->setParameter($this, "ass_id", "");
        $ilCtrl->setParameter($this, "part_id", "");

        $ilTabs->addSubTab(
            "assignment",
            $lng->txt("exc_assignment_view"),
            $ilCtrl->getLinkTarget($this, "members")
        );
        $ilTabs->addSubTab(
            "participant",
            $lng->txt("exc_participant_view"),
            $ilCtrl->getLinkTarget($this, "showParticipant")
        );
        $ilTabs->addSubTab(
            "grades",
            $lng->txt("exc_grades_overview"),
            $ilCtrl->getLinkTarget($this, "showGradesOverview")
        );
        $ilTabs->activateSubTab($a_activate);

        $ilCtrl->setParameter($this, "ass_id", $ass_id);
        $ilCtrl->setParameter($this, "part_id", $part_id);
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function waitingDownloadObject(): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        $ilCtrl->setParameterByClass("ilExSubmissionFileGUI", "member_id", $this->requested_member_id);
        $url = $ilCtrl->getLinkTargetByClass(array("ilExerciseHandlerGUI", "ilObjExerciseGUI", "ilExerciseManagementGUI", "ilExSubmissionFileGUI"), "downloadNewReturned");
        $js_url = $ilCtrl->getLinkTargetByClass(array("ilExerciseHandlerGUI", "ilObjExerciseGUI", "ilExerciseManagementGUI", "ilExSubmissionFileGUI"), "downloadNewReturned", "", "", false);
        $this->tpl->setOnScreenMessage('info', $lng->txt("exc_wait_for_files") . "<a href='$url'> " . $lng->txt('exc_download_files') . "</a><script>window.location.href ='" . $js_url . "';</script>");
        $this->membersObject();
    }

    /**
     * All participants and submission of one assignment
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function membersObject(): void
    {
        $tpl = $this->tpl;
        $ilToolbar = $this->toolbar;
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;

        $this->addSubTabs("assignment");
        $this->gui->permanentLink()->setGradesPermanentLink();

        // assignment selection
        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());

        if ($this->assignment === null && count($ass) > 0) {
            $this->assignment = current($ass);
        }

        reset($ass);
        if (count($ass) > 1) {
            $options = array();
            foreach ($ass as $a) {
                $options[$a->getId()] = $a->getTitle();
            }
            $si = new ilSelectInputGUI($this->lng->txt("exc_assignment"), "ass_id");
            $si->setOptions($options);
            $si->setValue($this->assignment->getId());
            $ilToolbar->addStickyItem($si, true);
            $this->gui->button(
                $this->lng->txt("select"),
                "selectAssignment"
            )->submit()->toToolbar(true);

            $ilToolbar->addSeparator();
        }
        // #16165 - if only 1 assignment dropdown is not displayed;
        elseif ($this->assignment) {
            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());
        }

        // add member
        // is only shown if 'edit_submissions_grades' is granted by rbac. positions
        // access is not sufficient.
        $has_rbac_access = $GLOBALS['DIC']->access()->checkAccess(
            'edit_submissions_grades',
            '',
            $this->exercise->getRefId()
        );
        if ($has_rbac_access) {
            ilRepositorySearchGUI::fillAutoCompleteToolbar(
                $this,
                $ilToolbar,
                array(
                    'auto_complete_name' => $lng->txt('user'),
                    'submit_name' => $lng->txt('add'),
                    'add_search' => true,
                    'add_from_container' => $this->exercise->getRefId()
                )
            );
        }

        // #16168 - no assignments
        if ($ass !== []) {
            if ($has_rbac_access) {
                $ilToolbar->addSeparator();
            }

            // we do not want the ilRepositorySearchGUI form action
            $ilToolbar->setFormAction($ilCtrl->getFormAction($this));

            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());

            if ($this->assignment->getType() == ilExAssignment::TYPE_UPLOAD_TEAM) {
                if (ilExAssignmentTeam::getAdoptableGroups($this->exercise->getRefId())) {
                    $ilToolbar->addButton(
                        $this->lng->txt("exc_adopt_group_teams"),
                        $this->ctrl->getLinkTarget($this, "adoptTeamsFromGroup")
                    );

                    $ilToolbar->addSeparator();
                }
            } elseif ($this->exercise->hasTutorFeedbackFile()) {
                if (!$this->assignment->getAssignmentType()->usesTeams()) {
                    // multi-feedback
                    $ilToolbar->addButton(
                        $this->lng->txt("exc_multi_feedback"),
                        $this->ctrl->getLinkTarget($this, "showMultiFeedback")
                    );

                    $ilToolbar->addSeparator();
                }
            }

            $submission_repository = $this->service->repo()->submission();

            if ($submission_repository->hasSubmissions($this->assignment->getId()) !== 0) {
                $ass_type = $this->assignment->getType();
                //todo change addFormButton for addButtonInstance
                if ($ass_type == ilExAssignment::TYPE_TEXT) {
                    $ilToolbar->addFormButton($lng->txt("exc_list_text_assignment"), "listTextAssignment");
                }
                $ilToolbar->addFormButton($lng->txt("download_all_returned_files"), "downloadSubmissions");
            }
            $this->ctrl->setParameter($this, "vw", self::VIEW_ASSIGNMENT);

            $exc_tab = new ilParticipantsPerAssignmentTableGUI(
                $this,
                "members",
                $this->exercise,
                $this->assignment->getId(),
                $this->feedback_gui
            );
            $tpl->setContent(
                $exc_tab->getHTML() .
                $this->initIndividualDeadlineModal()
            );
        } else {
            $this->tpl->setOnScreenMessage('info', $lng->txt("exc_no_assignments_available"));
        }

        $ilCtrl->setParameter($this, "ass_id", "");
    }

    public function downloadSelectedObject(): void
    {
        if (count($this->selected_participants) > 0) {
            $this->downloadSubmissionsObject($this->selected_participants);
        } else {
            $this->backToCurrentOverview();
        }
    }
    public function downloadSubmissionsObject(?array $selected_participants = null): void
    {
        $participant_id = $this->requested_part_id;

        $download_task = new ilDownloadSubmissionsBackgroundTask(
            (int) $GLOBALS['DIC']->user()->getId(),
            $this->exercise->getRefId(),
            $this->exercise->getId(),
            $this->ass_id,
            $participant_id,
            $selected_participants
        );

        if ($download_task->run()) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt('exc_down_files_started_bg'), true);
        }

        $this->backToCurrentOverview();
    }

    public function backToCurrentOverview(): void
    {
        if ($this->assignment !== null) {
            $this->ctrl->redirect($this, "members");
        } else {
            $this->ctrl->redirect($this, "showParticipant");
        }
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function membersApplyObject(): void
    {
        $this->saveStatusAllObject(null, false);
        $exc_tab = new ilParticipantsPerAssignmentTableGUI(
            $this,
            "members",
            $this->exercise,
            $this->assignment->getId(),
            $this->feedback_gui
        );
        $exc_tab->resetOffset();
        $exc_tab->writeFilterToSession();

        $this->membersObject();
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function membersResetObject(): void
    {
        $exc_tab = new ilParticipantsPerAssignmentTableGUI(
            $this,
            "members",
            $this->exercise,
            $this->assignment->getId(),
            $this->feedback_gui
        );
        $exc_tab->resetOffset();
        $exc_tab->resetFilter();

        $this->membersObject();
    }

    public function saveGradesObject(): void
    {
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;

        foreach ($this->requested_learning_comments as $k => $v) {
            $marks_obj = new ilLPMarks($this->exercise->getId(), (int) $k);
            $marks_obj->setComment($v);
            $marks_obj->update();
        }
        foreach ($this->requested_marks as $k => $v) {
            $marks_obj = new ilLPMarks($this->exercise->getId(), (int) $k);
            $marks_obj->setMark($v);
            $marks_obj->update();
        }
        $this->tpl->setOnScreenMessage('success', $lng->txt("exc_msg_saved_grades"), true);
        $ilCtrl->redirect($this, "showGradesOverview");
    }


    // TEXT ASSIGNMENT ?!

    /**
     * todo: Pagination.
     * @throws ilDateTimeException
     */
    public function listTextAssignmentObject(): void
    {
        $this->initFilter();
        $this->setBackToMembers();

        /** @var $button_print \ILIAS\UI\Component\Component */
        $button_print = $this->ui_factory->button()->standard($this->lng->txt('print'), "#")
            ->withOnLoadCode(function ($id) {
                return "$('#$id').click(function() { window.print(); return false; });";
            });
        $this->toolbar->addSeparator();
        $this->toolbar->addComponent($button_print);

        $group_panels_tpl = new ilTemplate("tpl.exc_group_report_panels.html", true, true, "components/ILIAS/Exercise");
        $group_panels_tpl->setVariable('TITLE', $this->lng->txt("exc_list_text_assignment") . ": " . $this->assignment->getTitle());

        $report_html = "";
        $total_reports = 0;

        $members = ilExSubmission::getAssignmentParticipants($this->exercise->getId(), $this->ass_id);
        $members_filter = new ilExerciseMembersFilter($this->exercise->getRefId(), $members, $this->user->getId());
        $members = $members_filter->filterParticipantsByAccess();

        $sm = $this->domain->submission($this->assignment->getId());
        foreach ($sm->getSubmissionsOfOwners($members) as $sub) {
            if (trim($sub->getText()) && $this->domain->profile()->exists($sub->getUserId())) {
                $feedback_data = $this->collectFeedbackDataFromPeer(
                    $sub->getUserId(),
                    $sub->getTimestamp(),
                    $sub->getText()
                );
                $submission_data = $this->assignment->getExerciseMemberAssignmentData($sub->getUserId(), $this->filter["status"] ?? "");

                if (is_array($submission_data)) {
                    $data = array_merge($feedback_data, $submission_data);
                    $report_html .= $this->getReportPanel($data);
                    $total_reports++;
                }
            }
        }
        if ($total_reports == 0) {
            $mess = $this->ui_factory->messageBox()->info($this->lng->txt("fiter_no_results"));
            $report_html .= $this->ui_renderer->render($mess);
        }

        $group_panels_tpl->setVariable('CONTENT', $report_html);
        $this->tpl->setContent($group_panels_tpl->get());
    }

    /**
     * TODO -> Deal with the redirection after update the grade via action button.
     * Extract the data collection to another method. List and compare use this. DRY
     * @throws ilDateTimeException|ilExcUnknownAssignmentTypeException
     */
    public function compareTextAssignmentsObject(): void
    {
        $this->setBackToMembers();
        $subm = $this->domain->submission($this->assignment->getId());

        $group_panels_tpl = new ilTemplate("tpl.exc_group_report_panels.html", true, true, "components/ILIAS/Exercise");
        $group_panels_tpl->setVariable('TITLE', $this->lng->txt("exc_compare_selected_submissions"));

        $report_html = "";
        //participant ids selected via checkboxes
        $participants = array_keys($this->getMultiActionUserIds());

        foreach ($participants as $participant_id) {
            $subs = $subm->getSubmissionsOfUser($participant_id);
            $ts = "";
            $text = "";
            if ($s = $subs->current()) {
                $ts = $s->getTimestamp();
                $text = $s->getText();
            }

            $feedback_data = $this->collectFeedbackDataFromPeer(
                $participant_id,
                $ts,
                $text
            );

            $submission_data = $this->assignment->getExerciseMemberAssignmentData((int) $participant_id, $this->filter["status"] ?? "");

            if (is_array($submission_data)) {
                $data = array_merge($feedback_data, $submission_data);
                $report_html .= $this->getReportPanel($data);
            }
        }

        $group_panels_tpl->setVariable('CONTENT', $report_html);
        $this->tpl->setContent($group_panels_tpl->get());
    }

    /**
     * @throws ilDateTimeException
     */
    public function getReportPanel(array $a_data): string
    {
        $modal = $this->getEvaluationModal($a_data);

        $this->ctrl->setParameter($this, "member_id", $a_data['uid']);
        $actions = array(
            $this->ui_factory->button()->shy($this->lng->txt("grade_evaluate"), "#")->withOnClick($modal->getShowSignal())
        );

        if ($this->exercise->hasTutorFeedbackMail()) {
            $actions[] = $this->ui_factory->button()->shy(
                $this->lng->txt("exc_tbl_action_feedback_mail"),
                $this->ctrl->getLinkTarget($this, "redirectFeedbackMail")
            );
        }
        if ($this->exercise->hasTutorFeedbackFile()) {
            $actions[] = $this->ui_factory->button()->shy(
                $this->lng->txt("exc_tbl_action_feedback_file"),
                $this->ctrl->getLinkTargetByClass(ilResourceCollectionGUI::class)
            );
        }

        $this->ctrl->setParameter($this, "member_id", "");

        $actions_dropdown = $this->ui_factory->dropdown()->standard($actions);
        if ($a_data['status'] == self::GRADE_NOT_GRADED) {
            $str_status_key = $this->lng->txt('exc_tbl_status') . ": ";
            $str_status_value = "-";
        } else {
            $str_status_key = $this->lng->txt('exc_tbl_status_time') . ": ";
            $str_status_value = ilDatePresentation::formatDate(new ilDateTime($a_data["status_time"], IL_CAL_DATETIME));
        }

        $str_mark_key = $this->lng->txt("exc_tbl_mark") . ": ";
        $str_mark_value = "-";

        if (($a_data['mark'] != "")) {
            $str_mark_value = $a_data['mark'];
        }

        if ($a_data['feedback_time']) {
            $str_evaluation_key = $this->lng->txt('exc_tbl_feedback_time') . ": ";
            $str_evaluation_value = ilDatePresentation::formatDate(new ilDateTime($a_data["feedback_time"], IL_CAL_DATETIME));
        } else {
            $str_evaluation_key = $this->lng->txt('exc_settings_feedback') . ": ";
            $str_evaluation_value = "-";
        }

        $card_content = array(
            $this->lng->txt("exc_tbl_submission_date") . ": " => ilDatePresentation::formatDate(new ilDateTime($a_data["udate"], IL_CAL_DATETIME)),
            $str_status_key => $str_status_value,
            $str_mark_key => $str_mark_value,
            $str_evaluation_key => $str_evaluation_value,
            $this->lng->txt('feedback_given') . ": " => $a_data['fb_given'],
            $this->lng->txt('feedback_received') . ": " => $a_data['fb_received']
        );
        $card_tpl = new ilTemplate("tpl.exc_report_details_card.html", true, true, "components/ILIAS/Exercise");
        foreach ($card_content as $key => $value) {
            $card_tpl->setCurrentBlock("assingment_card");
            $card_tpl->setVariable("ROW_KEY", $key);
            $card_tpl->setVariable("ROW_VALUE", $value);
            $card_tpl->parseCurrentBlock();
        }

        $main_panel = $this->ui_factory->panel()->sub($a_data['uname'], $this->ui_factory->legacy()->content($a_data['utext']))
            ->withFurtherInformation($this->ui_factory->card()->standard($this->lng->txt('text_assignment'))->withSections(array($this->ui_factory->legacy()->content($card_tpl->get()))))->withActions($actions_dropdown);

        $feedback_tpl = new ilTemplate("tpl.exc_report_feedback.html", true, true, "components/ILIAS/Exercise");
        //if no feedback filter the feedback is displayed. Can be list submissions or compare submissions.
        $filter_feedback = $this->filter["feedback"] ?? "";
        if (array_key_exists("peer", $a_data) && (($filter_feedback == self::FEEDBACK_FULL_SUBMISSION) || $filter_feedback == "")) {
            $feedback_tpl->setCurrentBlock("feedback");
            foreach ($a_data["peer"] as $peer_id) {
                if (ilObject::_lookupType($peer_id) == "usr") {
                    $user = new ilObjUser($peer_id);
                    $peer_name = $user->getFirstname() . " " . $user->getLastname();
                } else {
                    $peer_name = $this->lng->txt("exc_deleted_user");
                }

                $feedback_tpl->setCurrentBlock("peer_feedback");
                $feedback_tpl->setVariable("PEER_NAME", $peer_name);

                $submission = new ilExSubmission($this->assignment, $a_data["uid"]);
                $values = $submission->getPeerReview()->getPeerReviewValues($peer_id, $a_data["uid"]);

                $review_html = "";
                foreach ($this->assignment->getPeerReviewCriteriaCatalogueItems() as $crit) {
                    $crit_id = $crit->getId()
                        ? $crit->getId()
                        : $crit->getType();
                    $crit->setPeerReviewContext($this->assignment, $peer_id, $a_data["uid"]);

                    $review_html .=
                        '<div class="ilBlockPropertyCaption">' . $crit->getTitle() . '</div>' .
                        '<div style="margin:2px 0;">' . $crit->getHTML($values[$crit_id] ?? null) . '</div>';
                }
                $feedback_tpl->setVariable("PEER_FEEDBACK", $review_html);
                $feedback_tpl->parseCurrentBlock();
            }
            $feedback_tpl->parseCurrentBlock();
        }
        $feedback_tpl->setVariable("GRADE", $this->lng->txt('exc_grading') . ": " . $this->lng->txt('exc_' . $a_data['status']));
        $comment = ($a_data['comment'] === "")
            ? "-"
            : $a_data['comment'];
        $feedback_tpl->setVariable("COMMENT", $this->lng->txt('exc_comment') . ": <br>" . $comment);

        $feedback_panel = $this->ui_factory->panel()->sub("", $this->ui_factory->legacy()->content($feedback_tpl->get()));

        $report = $this->ui_factory->panel()->report("", array($main_panel, $feedback_panel));

        return $this->ui_renderer->render([$modal,$report]);
    }

    public function getEvaluationModal(
        array $a_data
    ): RoundTrip {
        $modal_tpl = new ilTemplate("tpl.exc_report_evaluation_modal.html", true, true, "components/ILIAS/Exercise");
        $modal_tpl->setVariable("USER_NAME", $a_data['uname']);

        $form = $this->getEvaluationModalForm($a_data);
        //TODO: CHECK ilias string utils. ilUtil shortenText with net blank.
        if ($this->exercise->hasTutorFeedbackText()) {
            $max_chars = 500;

            $u_text = strip_tags($a_data["utext"]); //otherwise will get open P
            $text = $u_text;
            //show more
            if (strlen($u_text) > $max_chars) {
                $text = "<input type='checkbox' class='read-more-state' id='post-1' />";
                $text .= "<div class='read-more-wrap'>";
                $text .= mb_substr($u_text, 0, $max_chars);
                $text .= "<span class='read-more-target'>";
                $text .= mb_substr($u_text, $max_chars);
                $text .= "</span></div>";
                $text .= "<label for='post-1' class='read-more-trigger'></label>";
            }
            $modal_tpl->setVariable("USER_TEXT", $text);
        }

        $modal_tpl->setVariable("FORM", $form->getHTML());

        $form_id = 'form_' . $form->getId();
        $submit_btn = $this->ui_factory->button()->primary($this->lng->txt("save"), '#')
            ->withOnLoadCode(function ($id) use ($form_id) {
                return "$('#$id').click(function() { $('#$form_id').submit(); return false; });";
            });

        return  $this->ui_factory->modal()->roundtrip(strtoupper($this->lng->txt("grade_evaluate")), $this->ui_factory->legacy()->content($modal_tpl->get()))->withActionButtons([$submit_btn]);
    }

    public function getEvaluationModalForm(
        array $a_data
    ): ilPropertyFormGUI {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, "saveEvaluationFromModal"));
        $form->setId(uniqid('form'));

        //Grade
        $options = array(
            self::GRADE_NOT_GRADED => $this->lng->txt("exc_notgraded"),
            self::GRADE_PASSED => $this->lng->txt("exc_passed"),
            self::GRADE_FAILED => $this->lng->txt("exc_failed")
        );
        $si = new ilSelectInputGUI($this->lng->txt("exc_tbl_status"), "grade");
        $si->setOptions($options);
        $si->setValue($a_data['status'] ?? "");
        $form->addItem($si);

        //Mark
        $mark_input = new ilTextInputGUI($this->lng->txt("exc_tbl_mark"), "mark");
        $mark_input->setValue($a_data['mark'] ?? "");
        $mark_input->setMaxLength(32);
        $mark_input->setSize(4);
        $form->addItem($mark_input);

        $item = new ilHiddenInputGUI('mem_id');
        $item->setValue($a_data['uid'] ?? "");
        $form->addItem($item);

        //TODO: CHECK ilias string utils. ilUtil shortenText with net blank.
        if ($this->exercise->hasTutorFeedbackText()) {
            $ta = new ilTextAreaInputGUI($this->lng->txt("exc_comment"), 'comment');
            $ta->setInfo($this->lng->txt("exc_comment_for_learner_info"));
            $ta->setValue($a_data['comment'] ?? "");
            $ta->setRows(10);
            $form->addItem($ta);
        }
        return $form;
    }

    // Save assignment submission grade(status) and comment from the roundtrip modal.

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function saveEvaluationFromModalObject(): void
    {
        $form = $this->getEvaluationModalForm([]);
        $user_id = 0;
        $comment = "";
        $mark = "";
        $grade = "";
        if ($form->checkInput()) {
            $comment = trim($form->getInput('comment'));
            $user_id = (int) $form->getInput('mem_id');
            $grade = trim($form->getInput('grade'));
            $mark = trim($form->getInput('mark'));
        }

        if ($this->assignment->getId() && $user_id > 0) {
            $member_status = $this->assignment->getMemberStatus($user_id);
            $member_status->setComment(ilUtil::stripSlashes($comment));
            if ($grade != "") {
                $member_status->setStatus($grade);
            }
            $member_status->setMark($mark);
            if ($comment != "") {
                $member_status->setFeedback(true);
            }
            $member_status->update();
        }
        $this->tpl->setOnScreenMessage('success', $this->lng->txt("exc_status_saved"), true);
        $this->ctrl->redirect($this, "listTextAssignment");
    }

    // Add user as member

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function addUserFromAutoCompleteObject(): void
    {
        if ($this->requested_user_login == "") {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('msg_no_search_string'));
            $this->membersObject();
            return;
        }
        $users = explode(',', $this->requested_user_login);

        $user_ids = array();
        foreach ($users as $user) {
            $user_id = ilObjUser::_lookupId($user);

            if (!$user_id) {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('user_not_known'));
                $this->membersObject();
                return;
            }

            $user_ids[] = $user_id;
        }

        $this->addMembersObject($user_ids);
    }

    // Add new partipant
    public function addMembersObject($a_user_ids = array()): void
    {
        if (!count($a_user_ids)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("no_checkbox"), true);
        } else {
            if (!$this->exercise->members_obj->assignMembers($a_user_ids)) {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt("exc_members_already_assigned"), true);
            } else {
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("exc_members_assigned"), true);
            }
        }
        $this->ctrl->redirect($this, "members");
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function selectAssignmentObject(): void
    {
        $ctrl = $this->ctrl;
        $ctrl->setParameter($this, "ass_id", $this->requested_ass_id);
        $ctrl->redirect($this, "members");
    }

    /**
     * Show Participant
     */
    public function showParticipantObject(): void
    {
        $tpl = $this->tpl;
        $ilToolbar = $this->toolbar;
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;
        $access = $this->access;

        $this->addSubTabs("participant");
        $this->ctrl->setParameter($this, "ass_id", "");

        // participant selection
        $members = $this->exercise->members_obj->getMembers();

        $members = $access->filterUserIdsByRbacOrPositionOfCurrentUser(
            'edit_submissions_grades',
            'edit_submissions_grades',
            $this->exercise->getRefId(),
            $members
        );


        if (count($members) == 0) {
            $this->tpl->setOnScreenMessage('info', $lng->txt("exc_no_participants"));
            return;
        }

        $mems = array();
        foreach ($members as $mem_id) {
            if (ilObject::_lookupType($mem_id) == "usr") {
                $name = ilObjUser::_lookupName($mem_id);
                if (trim($name["login"]) != "") {		// #20073
                    $mems[$mem_id] = $name;
                }
            }
        }

        $mems = ilArrayUtil::sortArray($mems, "lastname", "asc", false, true);

        if ($this->requested_part_id == 0 && $mems !== [] && key($mems) > 0) {
            $ilCtrl->setParameter($this, "part_id", key($mems));
            $ilCtrl->redirect($this, "showParticipant");
        }

        $current_participant = $this->requested_part_id;

        reset($mems);
        if (count($mems) > 1) {
            $options = array();
            foreach ($mems as $k => $m) {
                $options[$k] =
                    $m["lastname"] . ", " . $m["firstname"] . " [" . $m["login"] . "]";
            }
            $si = new ilSelectInputGUI($this->lng->txt("exc_participant"), "part_id");
            $si->setOptions($options);
            $si->setValue($current_participant);
            $ilToolbar->addStickyItem($si, true);

            $this->gui->button(
                $this->lng->txt("select"),
                "selectParticipant"
            )->submit()->toToolbar(true);
        }

        if ($mems !== []) {
            $this->ctrl->setParameter($this, "vw", self::VIEW_PARTICIPANT);
            $this->ctrl->setParameter($this, "part_id", $current_participant);

            $ilToolbar->addSeparator();
            $ilToolbar->setFormAction($ilCtrl->getFormAction($this));
            $ilToolbar->addFormButton($lng->txt("download_all_returned_files"), "downloadSubmissions");

            $part_tab = new ilAssignmentsPerParticipantTableGUI(
                $this,
                "showParticipant",
                $this->exercise,
                $current_participant,
                $this->feedback_gui
            );
            $tpl->setContent($part_tab->getHTML() .
                $this->initIndividualDeadlineModal());
        } else {
            $this->tpl->setOnScreenMessage('info', $this->lng->txt("exc_no_assignments_available"));
        }
    }

    /**
     */
    public function showParticipantApplyObject(): void
    {
        $exc_tab = new ilAssignmentsPerParticipantTableGUI(
            $this,
            "showParticipant",
            $this->exercise,
            $this->requested_part_id,
            $this->feedback_gui
        );
        $exc_tab->resetOffset();
        $exc_tab->writeFilterToSession();

        $this->showParticipantObject();
    }

    /**
     */
    public function showParticipantResetObject(): void
    {
        $exc_tab = new ilAssignmentsPerParticipantTableGUI(
            $this,
            "showParticipant",
            $this->exercise,
            $this->requested_part_id,
            $this->feedback_gui
        );
        $exc_tab->resetOffset();
        $exc_tab->resetFilter();

        $this->showParticipantObject();
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function selectParticipantObject(): void
    {
        $ctrl = $this->ctrl;
        $ctrl->setParameter($this, "part_id", $this->requested_part_id);
        $ctrl->redirect($this, "showParticipant");
    }

    public function showGradesOverviewObject(): void
    {
        $tpl = $this->tpl;
        $ilToolbar = $this->toolbar;
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;

        $this->addSubTabs("grades");

        $mem_obj = new ilExerciseMembers($this->exercise);
        $mems = $mem_obj->getMembers();

        $mems = $GLOBALS['DIC']->access()->filterUserIdsByRbacOrPositionOfCurrentUser(
            'edit_submissions_grades',
            'edit_submissions_grades',
            $this->exercise->getRefId(),
            $mems
        );
        if (count($mems) > 0) {
            $ilToolbar->addButton(
                $lng->txt("exc_export_excel"),
                $ilCtrl->getLinkTarget($this, "exportExcel")
            );
        }

        $this->ctrl->setParameter($this, "vw", self::VIEW_GRADES);

        $grades_tab = new ilExGradesTableGUI(
            $this,
            "showGradesOverview",
            $this->service,
            $mem_obj
        );
        $tpl->setContent($grades_tab->getHTML());
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function redirectFeedbackMailObject(): void
    {
        if ($this->requested_member_id > 0) {
            $submission = new ilExSubmission($this->assignment, $this->requested_member_id);
            $members = $submission->getUserIds();
        } elseif ($members = $this->getMultiActionUserIds()) {
            $members = array_keys($members);
        }

        if ($members !== []) {
            $logins = array();
            foreach ($members as $user_id) {
                $member_status = $this->assignment->getMemberStatus($user_id);
                $member_status->setFeedback(true);
                $member_status->update();

                $logins[] = ilObjUser::_lookupLogin($user_id);
            }
            $logins = implode(",", $logins);

            // #16530 - see ilObjCourseGUI::createMailSignature
            $sig = chr(13) . chr(10) . chr(13) . chr(10);
            $sig .= $this->lng->txt('exc_mail_permanent_link');
            $sig .= chr(13) . chr(10) . chr(13) . chr(10);
            $sig .= ilLink::_getLink($this->exercise->getRefId());
            $sig = rawurlencode(base64_encode($sig));

            ilUtil::redirect(ilMailFormCall::getRedirectTarget(
                $this,
                $this->getViewBack(),
                array(),
                array(
                    'type' => 'new',
                    'rcp_to' => $logins,
                    ilMailFormCall::SIGNATURE_KEY => $sig
                )
            ));
        }
    }

    // Download all submitted files (of all members).

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    protected function getMultiActionUserIds(bool $a_keep_teams = false): array
    {
        $members = [];
        // multi-user
        if ($this->assignment !== null) {
            if (count($this->selected_participants) == 0) {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt("no_checkbox"), true);
                $this->ctrl->redirect($this, "members");
            }

            foreach ($this->selected_participants as $user_id) {
                $submission = new ilExSubmission($this->assignment, $user_id);
                $tmembers = $submission->getUserIds();
                if (!$a_keep_teams) {
                    foreach ($tmembers as $tuser_id) {
                        $members[$tuser_id] = 1;
                    }
                } else {
                    if ($tmembers) {
                        $members[] = $tmembers;
                    } else {
                        // no team yet
                        $members[] = $user_id;
                    }
                }
            }
        }
        // multi-ass
        else {
            if (count($this->selected_ass_ids) == 0) {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt("no_checkbox"), true);
                $this->ctrl->redirect($this, "showParticipant");
            }

            $user_id = $this->requested_part_id;

            foreach ($this->selected_ass_ids as $ass_id) {
                $submission = new ilExSubmission(new ilExAssignment($ass_id), $user_id);
                $tmembers = $submission->getUserIds();
                if (!$a_keep_teams) {
                    foreach ($tmembers as $tuser_id) {
                        $members[$ass_id][] = $tuser_id;
                    }
                } else {
                    if ($tmembers) {
                        $members[$ass_id][] = $tmembers;
                    } else {
                        // no team yet
                        $members[$ass_id][] = $user_id;
                    }
                }
            }
        }

        return $members;
    }

    // Send assignment per mail to participants

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function sendMembersObject(): void
    {
        $members = $this->getMultiActionUserIds();

        $this->tpl->setOnScreenMessage('success', $this->lng->txt("exc_sent"), true);
        if ($this->assignment !== null) {
            $this->exercise->sendAssignment($this->assignment, array_keys($members));
            $this->ctrl->redirect($this, "members");
        } else {
            foreach ($members as $ass_id => $users) {
                $this->exercise->sendAssignment(new ilExAssignment($ass_id), $users);
            }
            $this->ctrl->setParameter($this, "part_id", $this->requested_part_id); // #17629
            $this->ctrl->redirect($this, "showParticipant");
        }
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function confirmDeassignMembersObject(): void
    {
        $ilCtrl = $this->ctrl;
        $tpl = $this->tpl;
        $lng = $this->lng;

        $members = $this->getMultiActionUserIds();

        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($ilCtrl->getFormAction($this));
        $cgui->setHeaderText($lng->txt("exc_msg_sure_to_deassign_participant"));
        $cgui->setCancel($lng->txt("cancel"), "members");
        $cgui->setConfirm($lng->txt("remove"), "deassignMembers");
        foreach ($members as $k => $m) {
            $cgui->addItem(
                "member_ids[]",
                $k,
                ilUserUtil::getNamePresentation((int) $k, false, false, "", true)
            );
        }

        $tpl->setContent($cgui->getHTML());
    }

    // Deassign members from exercise

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function deassignMembersObject(): void
    {
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;

        $member_ids = $this->request->getMemberIds();

        foreach ($member_ids as $usr_id) {
            $this->exercise->members_obj->deassignMember((int) $usr_id);
        }
        $this->tpl->setOnScreenMessage('success', $lng->txt("exc_msg_participants_removed"), true);
        $ilCtrl->redirect($this, "members");
    }

    /**
     * Save assignment status (participant view)
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function saveStatusParticipantObject(?array $selected_ass_ids = null): void
    {
        $ilCtrl = $this->ctrl;

        $member_id = $this->requested_part_id;
        $data = array();
        $marks = $this->requested_marks;
        $status = $this->requested_status;
        $notices = $this->requested_tutor_notices;
        foreach ($this->listed_ass_ids as $ass_id) {
            if (is_array($selected_ass_ids) &&
                !in_array($ass_id, $selected_ass_ids)) {
                continue;
            }

            $data[$ass_id][$member_id] = array(
                "status" => $status[$ass_id]
            );
            if (isset($marks[$ass_id])) {
                $data[$ass_id][$member_id]["mark"] = $marks[$ass_id];
            }
            if (isset($notices[$ass_id])) {
                $data[$ass_id][$member_id]["notice"] = $notices[$ass_id];
            }
        }

        $ilCtrl->setParameter($this, "part_id", $member_id); // #17629
        $this->saveStatus($data);
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function saveStatusAllObject(
        ?array $a_selected = null,
        bool $a_redirect = true
    ): void {
        $user_ids = $this->listed_participants;
        $marks = $this->requested_marks;
        $notices = $this->requested_tutor_notices;
        $status = $this->requested_status;
        $filtered_user_ids = $GLOBALS['DIC']->access()->filterUserIdsByRbacOrPositionOfCurrentUser(
            'edit_submissions_grades',
            'edit_submissions_grades',
            $this->exercise->getRefId(),
            $user_ids
        );

        $data = array();
        foreach ($filtered_user_ids as $user_id) {
            if (is_array($a_selected) &&
                !in_array($user_id, $a_selected)) {
                continue;
            }

            $data[-1][$user_id] = array(
                "status" => $status[$user_id] ?? null
            );

            if (isset($marks[$user_id])) {
                $data[-1][$user_id]["mark"] = $marks[$user_id];
            }
            if (isset($notices[$user_id])) {
                $data[-1][$user_id]["notice"] = $notices[$user_id];
            }
        }
        $this->saveStatus($data, $a_redirect);
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function saveStatusSelectedObject(): void
    {
        //$members = $this->getMultiActionUserIds();

        if ($this->assignment !== null) {
            $this->saveStatusAllObject($this->selected_participants);
        } else {
            $this->saveStatusParticipantObject($this->selected_ass_ids);
        }
    }

    // Save status of selected members

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    protected function saveStatus(
        array $a_data,
        bool $a_redirect = true
    ): void {
        $ilCtrl = $this->ctrl;
        $saved_for = array();
        foreach ($a_data as $ass_id => $users) {
            $ass = ($ass_id < 0)
                ? $this->assignment
                : new ilExAssignment($ass_id);
            foreach ($users as $user_id => $values) {
                // this will add team members if available
                // $user_id is only the ID of one team member here,
                // $sub_user_id will be all team members
                $submission = new ilExSubmission($ass, $user_id);
                foreach ($submission->getUserIds() as $sub_user_id) {
                    $uname = ilObjUser::_lookupName($sub_user_id);
                    $saved_for[$sub_user_id] = $uname["lastname"] . ", " . $uname["firstname"];

                    $member_status = $ass->getMemberStatus($sub_user_id);

                    // see bug #22566
                    $status = $values["status"];
                    if ($status == "") {
                        $status = self::GRADE_NOT_GRADED;
                    }
                    $member_status->setStatus($status);
                    if (array_key_exists("mark", $values)) {
                        $member_status->setMark($values["mark"]);
                    }
                    if (array_key_exists("notice", $values)) {
                        $member_status->setNotice($values["notice"]);
                    }
                    $member_status->update();
                }
            }
        }

        $save_for_str = "";
        if ($saved_for !== []) {
            $save_for_str = "(" . implode(" - ", $saved_for) . ")";
        }

        if ($a_redirect) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("exc_status_saved") . " " . $save_for_str, true);
            $ilCtrl->redirect($this, $this->getViewBack());
        }
    }

    public function exportExcelObject(): void
    {
        $this->exercise->exportGradesExcel();
        exit;
    }


    //
    // TEAM
    //

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function createTeamsObject(): void
    {
        $ilCtrl = $this->ctrl;

        $members = $this->getMultiActionUserIds(true);

        $new_members = array();

        foreach ($members as $group) {
            if (is_array($group)) {
                $new_members = array_merge($new_members, $group);

                $first_user = $group;
                $first_user = array_shift($first_user);
                $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first_user);
                foreach ($group as $user_id) {
                    $team->removeTeamMember($user_id);
                }
            } else {
                $new_members[] = $group;
            }
        }

        if ($new_members !== []) {
            // see ilExSubmissionTeamGUI::addTeamMemberActionObject()

            $first_user = array_shift($new_members);
            $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first_user, true);
            foreach ($new_members as $user_id) {
                $team->addTeamMember($user_id);
            }

            // re-evaluate complete team, as some members might have had submitted
            $submission = new ilExSubmission($this->assignment, $first_user);
            $this->exercise->processExerciseStatus(
                $this->assignment,
                $team->getMembers(),
                $submission->hasSubmitted(),
                $submission->validatePeerReviews()
            );
        }

        $this->tpl->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
        $ilCtrl->redirect($this, "members");
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function dissolveTeamsObject(): void
    {
        $ilCtrl = $this->ctrl;

        $members = $this->getMultiActionUserIds(true);

        foreach ($members as $group) {
            // if single member - nothing to do
            if (is_array($group)) {
                // see ilExSubmissionTeamGUI::removeTeamMemberObject()

                $first_user = $group;
                $first_user = array_shift($first_user);
                $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first_user);
                foreach ($group as $user_id) {
                    $team->removeTeamMember($user_id);
                }

                // reset ex team members, as any submission is not valid without team
                $this->exercise->processExerciseStatus(
                    $this->assignment,
                    $group,
                    false
                );
            }
        }

        $this->tpl->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
        $ilCtrl->redirect($this, "members");
    }

    public function adoptTeamsFromGroupObject(
        ?ilPropertyFormGUI $a_form = null
    ): void {
        $ilCtrl = $this->ctrl;
        $ilTabs = $this->tabs_gui;
        $lng = $this->lng;
        $tpl = $this->tpl;

        $ilTabs->clearTargets();
        $ilTabs->setBackTarget(
            $lng->txt("back"),
            $ilCtrl->getLinkTarget($this, $this->getViewBack())
        );

        if ($a_form === null) {
            $a_form = $this->initGroupForm();
        }
        $tpl->setContent($a_form->getHTML());
    }

    protected function initGroupForm(): ilPropertyFormGUI
    {
        $lng = $this->lng;

        $form = new ilPropertyFormGUI();
        $form->setTitle($lng->txt("exc_adopt_group_teams") . " - " . $this->assignment->getTitle());
        $form->setFormAction($this->ctrl->getFormAction($this, "createTeamsFromGroups"));

        $all_members = array();
        foreach (ilExAssignmentTeam::getGroupMembersMap($this->exercise->getRefId()) as $grp_id => $group) {
            if (count($group["members"]) !== 0) {
                $grp_team = new ilCheckboxGroupInputGUI($lng->txt("obj_grp") . " \"" . $group["title"] . "\"", "grpt[" . $grp_id . "]");
                $grp_value = $options = array();
                foreach ($group["members"] as $user_id) {
                    $user_name = ilUserUtil::getNamePresentation($user_id, false, false, "", true);
                    $options[$user_id] = $user_name;
                    if (!in_array($user_id, $all_members)) {
                        $grp_value[] = $user_id;
                        $all_members[] = $user_id;
                    }
                }
                asort($options);
                foreach ($options as $user_id => $user_name) {
                    $grp_team->addOption(new ilCheckboxOption($user_name, $user_id));
                }
                $grp_team->setValue($grp_value);
            } else {
                $grp_team = new ilNonEditableValueGUI($group["title"]);
                $grp_team->setValue($lng->txt("exc_adopt_group_teams_no_members"));
            }
            $form->addItem($grp_team);
        }

        if ($all_members !== []) {
            $form->addCommandButton("createTeamsFromGroups", $lng->txt("save"));
        }
        $form->addCommandButton("members", $lng->txt("cancel"));

        return $form;
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function createTeamsFromGroupsObject(): void
    {
        $lng = $this->lng;

        $req_members = $this->requested_group_members;

        $form = $this->initGroupForm();
        if ($form->checkInput()) {
            $map = ilExAssignmentTeam::getGroupMembersMap($this->exercise->getRefId());
            $all_members = $teams = array();
            $valid = true;
            foreach (array_keys($map) as $grp_id) {
                if (isset($req_members[$grp_id]) && is_array($req_members[$grp_id])) {
                    $members = $req_members[$grp_id];
                    $teams[] = $members;
                    $invalid_team_members = array();

                    foreach ($members as $user_id) {
                        if (!array_key_exists($user_id, $all_members)) {
                            $all_members[$user_id] = $grp_id;
                        } else {
                            // user is selected in multiple groups
                            $invalid_team_members[] = $user_id;
                        }
                    }

                    if ($invalid_team_members !== []) {
                        $valid = false;

                        $alert = array();
                        foreach ($invalid_team_members as $user_id) {
                            $user_name = ilUserUtil::getNamePresentation($user_id, false, false, "", true);
                            $grp_title = $map[$all_members[$user_id]]["title"];
                            $alert[] = sprintf($lng->txt("exc_adopt_group_teams_conflict"), $user_name, $grp_title);
                        }
                        $input = $form->getItemByPostVar("grpt[" . $grp_id . "]");
                        $input->setAlert(implode("<br/>", $alert));
                    }
                }
            }
            if ($valid) {
                if ($teams !== []) {
                    $existing_users = array_keys(ilExAssignmentTeam::getAssignmentTeamMap($this->assignment->getId()));

                    // create teams from group selections
                    $sum = array("added" => 0, "blocked" => 0);
                    foreach ($teams as $members) {
                        foreach ($members as $user_id) {
                            if (!$this->exercise->members_obj->isAssigned($user_id)) {
                                $this->exercise->members_obj->assignMember($user_id);
                            }

                            if (!in_array($user_id, $existing_users)) {
                                $sum["added"]++;
                            } else {
                                $sum["blocked"]++;
                            }
                        }

                        $first = array_shift($members);
                        $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first, true);

                        // getTeamId() does NOT send notification
                        // $team->sendNotification($this->exercise->getRefId(), $first, "add");

                        foreach ($members as $user_id) {
                            $team->addTeamMember($user_id);
                        }
                    }

                    $mess = array();
                    if ($sum["added"] !== 0) {
                        $mess[] = sprintf($lng->txt("exc_adopt_group_teams_added"), $sum["added"]);
                    }
                    if ($sum["blocked"] !== 0) {
                        $mess[] = sprintf($lng->txt("exc_adopt_group_teams_blocked"), $sum["blocked"]);
                    }
                    if ($sum["added"] !== 0) {
                        $this->tpl->setOnScreenMessage('success', implode(" ", $mess), true);
                    } else {
                        $this->tpl->setOnScreenMessage('failure', implode(" ", $mess), true);
                    }
                }
                $this->ctrl->redirect($this, "members");
            } else {
                $this->tpl->setOnScreenMessage('failure', $lng->txt("form_input_not_valid"));
            }
        }

        $form->setValuesByPost();
        $this->adoptTeamsFromGroupObject($form);
    }


    ////
    //// Multi Feedback
    ////

    public function getMultiFeedbackForm(int $a_ass_id): FormAdapterGUI
    {
        $lng = $this->lng;

        $form = $this->gui->form([self::class], "uploadMultiFeedback")
            ->section("main", ilExAssignment::lookupTitle($a_ass_id))
            ->file(
                "mfzip",
                $lng->txt("exc_multi_feedback_file"),
                $this->handleMultiFeedbackUploadResult(...),
                "rc_id",
                "",
                1,
                ["application/zip"]
            );
        return $form;
    }

    public function handleMultiFeedbackUploadResult(
        FileUpload $upload,
        UploadResult $result
    ): BasicHandlerResult {
        $feedback_zip = $this->domain->assignment()->tutorFeedbackZip();
        $rid = $feedback_zip->importFromUploadResult(
            $this->ass_id,
            $this->user->getId(),
            $result
        );
        return new \ILIAS\FileUpload\Handler\BasicHandlerResult(
            '',
            \ILIAS\FileUpload\Handler\HandlerResult::STATUS_OK,
            $rid,
            ''
        );
    }


    public function showMultiFeedbackObject(
        ?FormAdapterGUI $form = null
    ): void {
        $lng = $this->lng;
        $tpl = $this->tpl;

        $this->tpl->setOnScreenMessage('info', $lng->txt("exc_multi_feedb_info"));

        $this->addSubTabs("assignment");

        // #13719
        $this->gui->button(
            $this->lng->txt("exc_download_zip_structure"),
            $this->ctrl->getLinkTarget($this, "downloadMultiFeedbackZip")
        )->toToolbar();

        if ($form === null) {
            $form = $this->getMultiFeedbackForm($this->assignment->getId());
        }

        $tpl->setContent($form->render());
    }

    /**
     * Download multi-feedback structrue file
     */
    public function downloadMultiFeedbackZipObject(): void
    {
        $st_file = $this->domain->assignment()->tutorFeedbackZip()->getMultiFeedbackStructureFile(
            $this->assignment,
            $this->exercise
        );
        $this->gui->httpUtil()->deliverString(
            $st_file->content,
            $st_file->filename,
            "application/zip"
        );
    }

    /**
     * Upload multi feedback file
     */
    public function uploadMultiFeedbackObject(): void
    {
        // #11983
        $form = $this->getMultiFeedbackForm($this->assignment->getId());
        if ($form->isValid()) {
            $this->ctrl->redirect($this, "showMultiFeedbackConfirmationTable");
        }

        $this->showMultiFeedbackObject($form);
    }

    /**
     * Show multi feedback confirmation table
     */
    public function showMultiFeedbackConfirmationTableObject(): void
    {
        $tpl = $this->tpl;

        $this->addSubTabs("assignment");

        $tab = new ilFeedbackConfirmationTable2GUI($this, "showMultiFeedbackConfirmationTable", $this->assignment);
        $tpl->setContent($tab->getHTML());
    }

    /**
     * Cancel Multi Feedback
     */
    public function cancelMultiFeedbackObject(): void
    {
        $this->ctrl->redirect($this, "members");
    }

    /**
     * Save multi feedback
     */
    public function saveMultiFeedbackObject(): void
    {
        $feedback_zip = $this->domain->assignment()->tutorFeedbackZip();
        $feedback_zip->saveMultiFeedbackFiles(
            $this->exercise,
            $this->assignment->getId(),
            $this->user->getId(),
            $this->requested_files
        );

        $this->tpl->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
        $this->ctrl->redirect($this, "members");
    }


    //
    // individual deadlines
    //

    protected function initIndividualDeadlineModal(): string
    {
        $lng = $this->lng;
        $tpl = $this->tpl;

        // prepare modal
        $modal = $this->ui_factory->modal()->roundtrip(
            $lng->txt("exc_individual_deadline"),
            $this->ui_factory->legacy()->content('<div id="ilExcIDlBody"></div>')
        );

        $ajax_url = $this->ctrl->getLinkTarget($this, "handleIndividualDeadlineCalls", "", true, false);

        $tpl->addJavaScript("assets/js/ilExcIDl.js", true, 3);
        $tpl->addOnLoadCode('il.ExcIDl.init("' . $ajax_url . '");');

        ilCalendarUtil::initDateTimePicker();

        return $this->ui_renderer->render($modal);
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    protected function parseIndividualDeadlineData(
        array $a_data
    ): array {
        if ($a_data) {
            $map = array();
            $ass_tmp = array();
            foreach ($a_data as $item) {
                $item = explode("_", $item);
                $ass_id = $item[0];
                $user_id = $item[1];

                if (!array_key_exists($ass_id, $ass_tmp)) {
                    if ($this->assignment &&
                        $ass_id == $this->assignment->getId()) {
                        $ass_tmp[$ass_id] = $this->assignment;
                    } else {
                        $ass_tmp[$ass_id] = new ilExAssignment($ass_id);
                    }
                }

                $map[$ass_id][] = $user_id;
            }

            return array($map, $ass_tmp);
        }
        return [];
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     * @throws ilDateTimeException
     */
    protected function handleIndividualDeadlineCallsObject(): void
    {
        $tpl = $this->tpl;

        $this->ctrl->saveParameter($this, "part_id");

        // from request "dn", see ilExcIdl.js
        if ($this->done) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, $this->assignment !== null
                ? "members"
                : "showParticipant");
        }

        // initial form call
        if ($this->requested_idl_id != "") {
            $tmp = $this->parseIndividualDeadlineData(explode(",", $this->requested_idl_id));
            if (is_array($tmp)) {
                $form = $this->initIndividualDeadlineForm($tmp[1], $tmp[0]);
                echo $form->getHTML() .
                    $tpl->getOnLoadCodeForAsynch();
            }
        }
        // form "submit"
        else {
            $tmp = array();
            $post = $this->http->request()->getParsedBody();
            foreach (array_keys($post) as $id) {
                if (substr($id, 0, 3) == "dl_") {
                    $tmp[] = substr($id, 3);
                }
            }
            $tmp = $this->parseIndividualDeadlineData($tmp);
            $ass_map = $tmp[1];
            $users = $tmp[0];
            unset($tmp);

            $form = $this->initIndividualDeadlineForm($ass_map, $users);
            $res = array();
            if ($valid = $form->checkInput()) {
                foreach ($users as $ass_id => $users2) {
                    $ass = $ass_map[$ass_id];

                    // :TODO: should individual deadlines BEFORE extended be possible?
                    $dl = new ilDateTime($ass->getDeadline(), IL_CAL_UNIX);

                    foreach ($users2 as $user_id) {
                        $date_field = $form->getItemByPostVar("dl_" . $ass_id . "_" . $user_id);
                        if (ilDate::_before($date_field->getDate(), $dl)) {
                            $date_field->setAlert(sprintf($this->lng->txt("exc_individual_deadline_before_global"), ilDatePresentation::formatDate($dl)));
                            $valid = false;
                        } else {
                            $res[$ass_id][$user_id] = $date_field->getDate();
                        }
                    }
                }
            }

            if (!$valid) {
                $form->setValuesByPost();
                echo $form->getHTML() .
                    $tpl->getOnLoadCodeForAsynch();
            } else {
                foreach ($res as $ass_id => $users) {
                    $ass = $ass_map[$ass_id];

                    foreach ($users as $id => $date) {
                        $ass->setIndividualDeadline($id, $date);
                        if (is_numeric($id)) {
                            $this->notification->sendDeadlineSetNotification($ass_id, $id);
                        }
                    }

                    $ass->recalculateLateSubmissions();
                }

                echo "ok";
            }
        }

        exit();
    }

    /**
     * @throws ilDateTimeException
     */
    protected function initIndividualDeadlineForm(
        array $a_ass_map,
        array $ids
    ): ilPropertyFormGUI {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setName("ilExcIDlForm");

        foreach ($ids as $ass_id => $users) {
            $ass = $a_ass_map[$ass_id];

            $section = new ilFormSectionHeaderGUI();
            $section->setTitle($ass->getTitle());
            $form->addItem($section);

            $teams = ilExAssignmentTeam::getInstancesFromMap($ass->getId());

            $values = $ass->getIndividualDeadlines();

            foreach ($users as $id) {
                // single user
                if (is_numeric($id)) {
                    $name = ilObjUser::_lookupName($id);
                    $name = $name["lastname"] . ", " . $name["firstname"];
                }
                // team
                else {
                    $name = "";
                    $team_id = (int) substr($id, 1);
                    if (array_key_exists($team_id, $teams)) {
                        $name = array();
                        foreach ($teams[$team_id]->getMembers() as $member_id) {
                            $uname = ilObjUser::_lookupName($member_id);
                            $name[] = $uname["lastname"] . ", " . $uname["firstname"];
                        }
                        asort($name);
                        $name = implode("<br />", $name);
                    }
                }

                $dl = new ilDateTimeInputGUI($name, "dl_" . $ass_id . "_" . $id);
                $dl->setShowTime(true);
                $dl->setRequired(true);
                $form->addItem($dl);

                if (array_key_exists($id, $values)) {
                    $dl->setDate(new ilDateTime($values[$id], IL_CAL_UNIX));
                }
            }
        }

        $form->addCommandButton("", $this->lng->txt("save"));

        return $form;
    }

    /**
     * @throws ilExcUnknownAssignmentTypeException
     */
    protected function setIndividualDeadlineObject(): void
    {
        // this will only get called if no selection
        $this->tpl->setOnScreenMessage('failure', $this->lng->txt("select_one"));

        if ($this->assignment !== null) {
            $this->membersObject();
        } else {
            $this->showParticipantObject();
        }
    }

    public function initFilter(): void
    {
        if ($this->requested_filter_status != "") {
            $this->filter["status"] = $this->requested_filter_status;
        }

        $this->lng->loadLanguageModule("search");

        $this->toolbar->setFormAction($this->ctrl->getFormAction($this, "listTextAssignment"));

        // Status

        $si_status = new ilSelectInputGUI($this->lng->txt("exc_tbl_status"), "filter_status");
        $options = array(
            "" => $this->lng->txt("search_any"),
            self::GRADE_NOT_GRADED => $this->lng->txt("exc_notgraded"),
            self::GRADE_PASSED => $this->lng->txt("exc_passed"),
            self::GRADE_FAILED => $this->lng->txt("exc_failed")
        );
        $si_status->setOptions($options);
        $si_status->setValue($this->filter["status"] ?? "");

        $si_feedback = new ilSelectInputGUI($this->lng->txt("feedback"), "filter_feedback");
        $options = array(
            self::FEEDBACK_FULL_SUBMISSION => $this->lng->txt("submissions_feedback"),
            self::FEEDBACK_ONLY_SUBMISSION => $this->lng->txt("submissions_only")
        );
        $si_feedback->setOptions($options);
        $si_feedback->setValue($this->filter["feedback"] ?? "");

        $this->toolbar->addInputItem($si_status, true);

        // Submissions and Feedback
        #24713
        if ($this->assignment->getPeerReview()) {
            if ($this->requested_filter_feedback != "") {
                $this->filter["feedback"] = $this->requested_filter_feedback;
            } else {
                $this->filter["feedback"] = "submission_feedback";
            }

            $si_feedback = new ilSelectInputGUI($this->lng->txt("feedback"), "filter_feedback");
            $options = array(
                "submission_feedback" => $this->lng->txt("submissions_feedback"),
                "submission_only" => $this->lng->txt("submissions_only")
            );
            $si_feedback->setOptions($options);
            $si_feedback->setValue($this->filter["feedback"] ?? "");

            $this->toolbar->addInputItem($si_feedback, true);
        }

        $this->gui->button(
            $this->lng->txt("filter"),
            "listTextAssignment"
        )->submit()->toToolbar();
    }

    /**
     * Open HTML view for portfolio submissions
     */
    public function openSubmissionPrintViewObject(): void
    {
        $this->openSubmissionViewObject(true);
    }

    /**
     * Open HTML view for portfolio submissions
     */
    public function openSubmissionViewObject(bool $print_version = false): void
    {
        global $DIC;

        $member_id = $this->requested_member_id;

        $submission = new ilExSubmission($this->assignment, $member_id);
        $entry = $submission->getSubmittedEntry($print_version);
        if (is_null($entry)) {
            throw new ilExerciseException("No submission entry for " . $this->assignment->getId() . " / " .
                $member_id . ".");
        }
        $rid = $entry->getRid();

        $last_opening = $submission->getLastOpeningHTMLView();
        $submission_time = $submission->getLastSubmission();

        if ($rid !== "") {
            $zip_internal_path = "ilExercise/" . \ilFileSystemAbstractionStorage::createPathFromId(
                $this->assignment->getExerciseId(),
                "exc"
            ) . "/subm_" . $this->assignment->getId() . "/" . $member_id . "/resource.zip";
            $obj_id = str_replace([".zip", "print"], "", $entry->getTitle());
            $obj_id = $this->assignment->getAssignmentType()->getExportObjIdForResourceId($obj_id);
        }
        if ($print_version) {
            $obj_id .= "print";
        }

        $obj_dir = $this->assignment->getAssignmentType()->getStringIdentifier() . "_" . $obj_id;

        $index_html_file = ILIAS_WEB_DIR .
            DIRECTORY_SEPARATOR .
            CLIENT_ID .
            DIRECTORY_SEPARATOR .
            dirname($zip_internal_path) .
            DIRECTORY_SEPARATOR .
            $obj_dir .
            DIRECTORY_SEPARATOR .
            "index.html";
        $this->log->debug("index html file: " . $index_html_file);

        $web_filesystem = $DIC->filesystem()->web();
        if ($last_opening > $submission_time && $web_filesystem->has($index_html_file)) {
            ilWACSignedPath::signFolderOfStartFile($index_html_file);
            ilUtil::redirect($index_html_file);
        }
        $error_msg = "";
        if ($rid !== "") {
            $file_copied = $this->domain->submission($this->ass_id)->copyRidToWebDir($rid, $zip_internal_path);
        }

        // e.g. data/ilias/ilExercise/3/exc_327/subm_9/2/20231212085734_167.zip ?
        if ($file_copied) {
            $this->zip->unzipFile($file_copied);
            $web_filesystem->delete($zip_internal_path);
            $this->log->debug("deleting: " . $zip_internal_path);

            $submission_repository = $this->service->repo()->submission();
            $submission_repository->updateWebDirAccessTime($this->assignment->getId(), $member_id);
            ilWACSignedPath::signFolderOfStartFile($index_html_file);
            ilUtil::redirect($index_html_file . "?" . time());
        }

        $error_msg = $this->lng->txt("exc_copy_zip_error");

        if ($error_msg === '' || $error_msg === '0') {
            $error_msg = $this->lng->txt("exc_find_zip_error");
        }

        $this->tpl->setOnScreenMessage('failure', $error_msg);
    }

    /**
     * Get the object specific file path from an external full file path.
     */
    protected function getWebFilePathFromExternalFilePath(
        string $external_file_path
    ): string {
        list($external_path, $internal_file_path) = explode(CLIENT_ID . "/ilExercise", $external_file_path);
        $internal_file_path = "ilExercise" . $internal_file_path;
        return $internal_file_path;
    }

    /*
     * Add the Back link to the tabs. (used in submission list and submission compare)
     */
    protected function setBackToMembers(): void
    {
        //tabs
        $this->tabs_gui->clearTargets();
        $this->tabs_gui->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, "members")
        );
    }

    public function collectFeedbackDataFromPeer(
        int $user_id,
        string $ts,
        string $text
    ): array {
        $user = new ilObjUser($user_id);
        $uname = $user->getFirstname() . " " . $user->getLastname();

        $data = array(
            "uid" => $user_id,
            "uname" => $uname,
            "udate" => $ts,
            "utext" => ilRTE::_replaceMediaObjectImageSrc($text, 1) // mob id to mob src
        );

        //get data peer and assign it
        $peer_review = new ilExPeerReview($this->assignment);
        $data["peer"] = array();
        foreach ($peer_review->getPeerReviewsByPeerId($user_id) as $value) {
            $data["peer"][] = $value['giver_id'];
        }

        $data["fb_received"] = count($data["peer"]);
        $data["fb_given"] = $peer_review->countGivenFeedback(true, $user_id);

        return $data;
    }

    public function sendGradingNotificationObject(): void
    {

        $ass_id = $this->request->getAssId();
        $selected_users = $this->request->getSelectedParticipants();

        $graded_users = array_filter($selected_users, function ($user_id) {
            return $this->assignment->getMemberStatus($user_id)->getStatus() !== "notgraded";
        });

        if (count($graded_users) === 0) {
            $this->tpl->setOnScreenMessage("failure", $this->lng->txt("exc_no_graded_mem_selected"), true);
            $this->ctrl->redirect($this, $this->getViewBack());
        }

        $not = new ilExerciseMailNotification();
        $not->setType(ilExerciseMailNotification::TYPE_GRADING_DONE);
        $not->setAssignmentId($ass_id);
        $not->setObjId($this->exercise->getId());
        $not->setRefId($this->exercise->getRefId());
        $not->setRecipients($graded_users);
        $not->send();
        $this->tpl->setOnScreenMessage("success", $this->lng->txt("exc_graded_mem_notified"), true);
        $this->ctrl->redirect($this, $this->getViewBack());
    }

    protected function setFailedObject(): void
    {
        $members = $this->getMultiActionUserIds();
        $done = false;
        if ($this->assignment !== null) {
            foreach (array_keys($members) as $mem) {
                $done = true;
                $this->setSingleStatus($this->assignment->getId(), $mem, "failed");
            }
            if ($done) {
                $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            }
        }
        $this->ctrl->redirect($this, "members");
    }

    protected function setPassedObject(): void
    {
        $members = $this->getMultiActionUserIds();
        $done = false;
        if ($this->assignment !== null) {
            foreach (array_keys($members) as $mem) {
                $done = true;
                $this->setSingleStatus($this->assignment->getId(), $mem, "passed");
            }
            if ($done) {
                $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            }
        }
        $this->ctrl->redirect($this, "members");
    }

    protected function setSingleStatus($ass_id, $part_id, $status): void
    {
        $ass = new ilExAssignment($ass_id);
        $submission = new ilExSubmission($ass, $part_id);
        $member_status = $ass->getMemberStatus($part_id);
        $member_status->setStatus($status);
        $member_status->update();
    }
}

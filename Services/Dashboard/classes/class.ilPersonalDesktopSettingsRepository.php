<?php

/* Copyright (c) 1998-2021 ILIAS open source, GPLv3, see LICENSE */

/**
 * Personal desktop settings repo
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilPersonalDesktopSettingsRepository
{
    /**
     * @var ilSetting
     */
    protected $settings;

    /**
     * Constructor
     */
    public function __construct(ilSetting $settings)
    {
        $this->settings = $settings;
    }


    /**
     * Notes enabled?
     *
     * @return bool
     */
    protected function ifNotesEnabled() : bool
    {
        return (bool) !$this->settings->get("disable_notes");
    }

    /**
     * Enable notes
     *
     * @param bool $active
     */
    protected function enableNotes(bool $active = true) : void
    {
        $this->settings->set("disable_notes", (int) !$active);
    }

    /**
     * Comments enabled?
     *
     * @return bool
     */
    protected function ifCommentsEnabled() : bool
    {
        return (bool) !$this->settings->get("disable_comments");
    }

    /**
     * Enable comments
     *
     * @param bool $active
     */
    protected function enableComments(bool $active = true) : void
    {
        $this->settings->set("disable_comments", (int) !$active);
    }

    /**
     * Can authors delete their comments
     *
     * @return bool
     */
    protected function ifAuthorsCanDelete() : bool
    {
        return (bool) $this->settings->get("comments_del_user", 0);
    }

    /**
     * Enable authors delete their comments
     *
     * @param bool $active
     */
    protected function enableAuthorsCanDelete(bool $active = true) : void
    {
        $this->settings->set("comments_del_user", (int) $active);
    }

    /**
     * Can tutors delete comments of others
     *
     * @return bool
     */
    protected function ifTutorsCanDelete() : bool
    {
        return (bool) $this->settings->get("comments_del_tutor", 1);
    }

    /**
     * Enable tutors delete comments of others
     *
     * @param bool $active
     */
    protected function enableTutorsCanDelete(bool $active = true) : void
    {
        $this->settings->set("comments_del_tutor", (int) $active);
    }

    /**
     * Get recipients of comments notification
     *
     * @return bool
     */
    protected function getCommentsNotificationRecipients() : string
    {
        return (string) $this->settings->get("comments_noti_recip");
    }

    /**
     * Update recipients of comments notification
     *
     * @param string $recipients
     */
    protected function updateCommentsNotificationRecipients(string $recipients)
    {
        $this->settings->set("comments_noti_recip", $recipients);
    }

    /**
     * learning history enabled?
     *
     * @return bool
     */
    protected function ifLearningHistoryEnabled() : bool
    {
        return (bool) $this->settings->get("enable_learning_history");
    }

    /**
     * Enable learning history
     *
     * @param bool $active
     */
    protected function enableLearningHistory(bool $active = true) : void
    {
        $this->settings->set("enable_learning_history", (int) $active);
    }

    /**
     * chat viewer enabled?
     *
     * @return bool
     */
    protected function ifChatViewerEnabled() : bool
    {
        return (bool) $this->settings->get("block_activated_chatviewer");
    }

    /**
     * Enable chat viewer
     *
     * @param bool $active
     */
    protected function enableChatViewer(bool $active = true) : void
    {
        $this->settings->set("block_activated_chatviewer", (int) $active);
    }

    /**
     * Get system message presentation
     *
     * @return int
     */
    protected function getSystemMessagePresentation() : int
    {
        return (int) $this->settings->get("pd_sys_msg_mode");
    }

    /**
     * Update system message presentation
     *
     * @param int $mode
     */
    protected function updateSystemMessagePresentation(int $mode) : void
    {
        $this->settings->set("pd_sys_msg_mode", $mode);
    }

    /**
     * forum draft block enabled?
     *
     * @return bool
     */
    protected function ifForumDrafts() : bool
    {
        return (bool) $this->settings->get('block_activated_pdfrmpostdraft', 0);
    }

    /**
     * Enable forum draft block
     *
     * @param bool $active
     */
    protected function enableForumDrafts(bool $active = true) : void
    {
        $this->settings->set("block_activated_pdfrmpostdraft", (int) $active);
    }

    /**
     * block moving enabled?
     *
     * @return bool
     */
    protected function ifMoveBlocks() : bool
    {
        return (bool) $this->settings->get('enable_block_moving', 0);
    }

    /**
     * Enable block moving
     *
     * @param bool $active
     */
    protected function enableMoveBlocks(bool $active = true) : void
    {
        $this->settings->set("enable_block_moving", (int) $active);
    }
}

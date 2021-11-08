<?php


/**
 * Class ilLTIAppEventListener
 */
class ilLTIAppEventListener implements \ilAppEventListener
{
    /**
     * @var \ilLTIAppEventListener
     */
    private static $instance = null;

    /**
     * @var \ilLogger
     */
    private $logger = null;

    /**
     * @var ilLTIDataConnector|null
     */
    private $connector = null;


    /**
     * ilLTIAppEventListener constructor.
     */
    protected function __construct()
    {
        global $DIC;

        $this->logger = $DIC->logger()->lti();
        $this->connector = new ilLTIDataConnector();
    }

    /**
     * @return \ilLTIAppEventListener
     */
    protected static function getInstance()
    {
        if (!self::$instance instanceof \ilLTIAppEventListener) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * Handle update status
     */
    protected function handleUpdateStatus($a_obj_id, $a_usr_id, $a_status, $a_percentage)
    {
        $this->logger->debug('Handle update status');
        $auth_mode = ilObjUser::_lookupAuthMode($a_usr_id);
        if (!$this->isLTIAuthMode($auth_mode)) {
            $this->logger->debug('Ignoring update for non-LTI-user.');
            return false;
        }
        $ext_account = ilObjUser::_lookupExternalAccount($a_usr_id);
        list($lti, $consumer) = explode('_', $auth_mode);

        // iterate through all references
        $refs = ilObject::_getAllReferences($a_obj_id);
        foreach ((array) $refs as $ref_id) {
            $resources = $this->connector->lookupResourcesForUserObjectRelation(
                $ref_id,
                $ext_account,
                $consumer
            );

            $this->logger->debug('Resources for update:');
            $this->logger->dump($resources, ilLogLevel::DEBUG);

            foreach ($resources as $resource) {
                $this->tryOutcomeService($resource, $ext_account, $a_status, $a_percentage);
            }
        }
    }


    /**
     * @param ilDateTime $since
     * @throws ilDateTimeException
     */
    protected function doCronUpdate(ilDateTime $since)
    {
        $this->logger->debug('Starting cron update for lti outcome service');

        $resources = $this->connector->lookupResourcesForAllUsersSinceDate($since);
        foreach ($resources as $consumer_ext_account => $user_resources) {
            list($consumer, $ext_account) = explode('__', $consumer_ext_account, 2);

            $login = ilObjUser::_checkExternalAuthAccount('lti_' . $consumer, $ext_account);
            if (!$login) {
                $this->logger->info('No user found for lti_' . $consumer . ' -> ' . $ext_account);
                continue;
            }
            $usr_id = ilObjUser::_lookupId($login);
            foreach ($user_resources as $resource_info) {
                $this->logger->debug('Found resource: ' . $resource_info);
                list($resource_id, $resource_ref_id) = explode('__', $resource_info);

                // lookup lp status
                $status = ilLPStatus::_lookupStatus(
                    ilObject::_lookupObjId($resource_ref_id),
                    $usr_id
                );
                $percentage = ilLPStatus::_lookupPercentage(
                    ilObject::_lookupObjId($resource_ref_id),
                    $usr_id
                );
                $this->tryOutcomeService($resource_id, $ext_account, $status, $percentage);
            }
        }
    }

    /**
     * @param $a_usr_id
     * @return bool
     */
    protected function isLTIAuthMode($auth_mode)
    {
        return strpos($auth_mode, 'lti_') === 0;
    }


    /**
     * try outcome service
     */
    protected function tryOutcomeService($resource, $ext_account, $a_status, $a_percentage)
    {
        $resource_link = \IMSGlobal\LTI\ToolProvider\ResourceLink::fromRecordId($resource, $this->connector);
        if (!$resource_link->hasOutcomesService()) {
            $this->logger->debug('No outcome service available for resource id: ' . $resource);
            return false;
        }
        $this->logger->debug('Trying outcome service with status ' . $a_status . ' and percentage ' . $a_percentage);
        $user = \IMSGlobal\LTI\ToolProvider\User::fromResourceLink($resource_link, $ext_account);

        if ($a_status == ilLPStatus::LP_STATUS_COMPLETED_NUM) {
            $score = 1;
        } elseif (
            $a_status == ilLPStatus::LP_STATUS_FAILED_NUM ||
            $a_status == ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM
        ) {
            $score = 0;
        } elseif (!$a_percentage) {
            $score = 0;
        } else {
            $score = (int) $a_percentage / 100;
        }

        $this->logger->debug('Sending score: ' . (string) $score);

        $outcome = new \IMSGlobal\LTI\ToolProvider\Outcome($score);

        $resource_link->doOutcomesService(
            \IMSGlobal\LTI\ToolProvider\ResourceLink::EXT_WRITE,
            $outcome,
            $user
        );
    }


    /**
     * @inheritdoc
     */
    public static function handleEvent(string $a_component, string $a_event, array $a_parameter) : void
    {
        global $DIC;

        $logger = $DIC->logger()->lti()->debug('Handling event: ' . $a_event . ' from ' . $a_component);

        switch ($a_component) {
            case 'Services/Tracking':
                if ($a_event == 'updateStatus') {
                    $listener = self::getInstance();
                    $listener->handleUpdateStatus(
                        $a_parameter['obj_id'],
                        $a_parameter['usr_id'],
                        $a_parameter['status'],
                        $a_parameter['percentage']
                    );
                }
                break;
        }
    }

    /**
     * @param ilDateTime $since
     * @return bool
     * @throws ilDateTimeException
     */
    public static function handleCronUpdate(ilDateTime $since)
    {
        $listener = self::getInstance();
        $listener->doCronUpdate($since);
        return true;
    }


    public static function handleOutcomeWithoutLP($a_obj_id, $a_usr_id, $a_percentage)
    {
        global $DIC;
        $score = 0;

        $auth_mode = ilObjUser::_lookupAuthMode($a_usr_id);
        if (strpos($auth_mode, 'lti_') === false) {
            $DIC->logger()->lti()->debug('Ignoring outcome for non-LTI-user.');
            return false;
        }
        //check if LearningPress enabled
        $olp = ilObjectLP::getInstance($a_obj_id);
        if (ilLPObjSettings::LP_MODE_DEACTIVATED != $olp->getCurrentMode()) {
            $DIC->logger()->lti()->debug('Ignoring outcome if LP is activated.');
            return false;
        }

        if ($a_percentage && $a_percentage > 0) {
            $score = round($a_percentage / 100, 4);
        }

        $connector = new ilLTIDataConnector();
        $ext_account = ilObjUser::_lookupExternalAccount($a_usr_id);
        list($lti, $consumer) = explode('_', $auth_mode);

        // iterate through all references
        $refs = ilObject::_getAllReferences($a_obj_id);
        foreach ((array) $refs as $ref_id) {
            $resources = $connector->lookupResourcesForUserObjectRelation(
                $ref_id,
                $ext_account,
                $consumer
            );

            $DIC->logger()->lti()->debug('Resources for update: ' . $resources);

            foreach ($resources as $resource) {
                // $this->tryOutcomeService($resource, $ext_account, $a_status, $a_percentage);
                $resource_link = \IMSGlobal\LTI\ToolProvider\ResourceLink::fromRecordId($resource, $connector);
                if ($resource_link->hasOutcomesService()) {
                    $user = \IMSGlobal\LTI\ToolProvider\User::fromResourceLink($resource_link, $ext_account);
                    $DIC->logger()->lti()->debug('Sending score: ' . (string) $score);
                    $outcome = new \IMSGlobal\LTI\ToolProvider\Outcome($score);

                    $resource_link->doOutcomesService(
                        \IMSGlobal\LTI\ToolProvider\ResourceLink::EXT_WRITE,
                        $outcome,
                        $user
                    );
                }
            }
        }
    }
}

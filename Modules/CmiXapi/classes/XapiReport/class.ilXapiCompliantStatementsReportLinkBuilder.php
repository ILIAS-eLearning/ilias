<?php

/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
 * Class ilCmiXapiReportLinkBuilder
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 * @package     Module/CmiXapi
 */
class ilXapiCompliantStatementsReportLinkBuilder
{
    /**
     * @var ilObjCmiXapi
     */
    protected $object;
    
    /**
     * @var ilCmiXapiLrsType
     */
    protected $lrsType;
    
    /**
     * @var ilCmiXapiStatementsReportFilter
     */
    protected $filter;
    
    public function __construct(ilObjCmiXapi $object, ilCmiXapiStatementsReportFilter $filter)
    {
        $this->object = $object;
        $this->lrsType = $object->getLrsType();
        $this->filter = $filter;
    }
    
    public function getUrl(): string
    {
        $link = $this->lrsType->getLrsEndpointStatementsLink();
        $link = $this->appendRequestParameters($link);
        return $link;
    }
    
    protected function appendRequestParameters(string $link): string
    {
        if ($this->filter->getLimit()) {
            $link = ilUtil::appendUrlParameterString($link, $this->buildParamLimit());
        }
        
        if ($this->filter->getActor()) {
            $link = ilUtil::appendUrlParameterString($link, $this->buildParamAgent());
        }
        
        if ($this->filter->getVerb()) {
            $link = ilUtil::appendUrlParameterString($link, $this->buildParamVerb());
        }
        
        if ($this->filter->getStartDate()) {
            $link = ilUtil::appendUrlParameterString($link, $this->buildParamSince());
        }
        
        if ($this->filter->getEndDate()) {
            $link = ilUtil::appendUrlParameterString($link, $this->buildParamUntil());
        }
        
        $link = ilUtil::appendUrlParameterString($link, $this->buildParamRelatedAgents());
        $link = ilUtil::appendUrlParameterString($link, $this->buildParamRelatedActivities());
        $link = ilUtil::appendUrlParameterString($link, $this->buildParamActivity());
        
        return $link;
    }
    
    protected function buildParamAgent()
    {
        $agent = json_encode([
            'objectType' => 'Agent',
            'mbox' => 'mailto:' . $this->filter->getActor()->getUsrIdent()
        ]);
        
        return "agent={$agent}";
    }
    
    protected function buildParamVerb()
    {
        $verb = urlencode($this->filter->getVerb());
        return "verb={$verb}";
    }
    
    protected function buildParamSince()
    {
        $since = urlencode($this->filter->getStartDate()->toXapiTimestamp());
        return "since={$since}";
    }
    
    protected function buildParamUntil()
    {
        $until = urlencode($this->filter->getEndDate()->toXapiTimestamp());
        return "until={$until}";
    }
    
    protected function buildParamActivity()
    {
        return "activity={$this->object->getActivityId()}";
    }
    
    protected function buildParamRelatedAgents(): string
    {
        return "related_agents=false";
    }
    
    protected function buildParamRelatedActivities(): string
    {
        return "related_activities=false";
    }
    
    protected function buildParamLimit()
    {
        return "limit={$this->filter->getLimit()}";
    }
}

<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilAsqQuestionPage
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  Adrian Lüthi <al@studer-raimann.ch>
 * @author  Björn Heyser <bh@bjoernheyser.de>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilAsqQuestionPage extends ilPageObject
{
    const ASQ_OBJECT_TYPE = 'asq'; // was 'qpl' for all questions in the past

    /**
     * @return string parent type
     */
    function getParentType()
    {
        return self::ASQ_OBJECT_TYPE;
    }
}

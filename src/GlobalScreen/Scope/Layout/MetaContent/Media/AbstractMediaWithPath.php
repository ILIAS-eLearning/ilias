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

namespace ILIAS\GlobalScreen\Scope\Layout\MetaContent\Media;

/**
 * Class AbstractMediaWithPath
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class AbstractMediaWithPath extends AbstractMedia
{
    public function getContent() : string
    {
        $content = parent::getContent();

        // the version string is only appended if the content string is not
        // a data uri, otherwise the data uri will behave incorrectly.
        if (!$this->isContentDataUri($content)) {
            if ($this->hasContentParameters($content)) {
                return rtrim($content, "&") . "&version=" . $this->version;
            } else {
                return rtrim($content, "?") . "?version=" . $this->version;
            }
        }

        return $content;
    }

    protected function isContentDataUri(string $content) : bool
    {
        // regex pattern matches if a string follows the data uri syntax.
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs#syntax

        return (bool) preg_match('/^(data:)([a-z\/]*)((;base64)?)(,?)([A-z0-9=\/\+]*)$/', $content);
    }

    protected function hasContentParameters(string $content) : bool
    {
        return (strpos($content, "?") !== false);
    }
}

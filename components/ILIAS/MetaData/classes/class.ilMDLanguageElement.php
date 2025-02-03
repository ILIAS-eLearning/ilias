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
/**
 * Meta Data class Language codes and translations
 * @package ilias-core
 * @version $Id$
 * @deprecated will be removed with ILIAS 11, please use the new API (see {@see ../docs/api.md})
 */
class ilMDLanguageElement
{
    protected string $language_code;
    protected array $possible_language_codes;

    public function __construct(string $a_code)
    {
        $this->language_code = $a_code;

        $this->possible_language_codes = array(
            "aa",
            "ab",
            "af",
            "am",
            "ar",
            "as",
            "ay",
            "az",
            "ba",
            "be",
            "bg",
            "bh",
            "bi",
            "bn",
            "bo",
            "br",
            "ca",
            "co",
            "cs",
            "cy",
            "da",
            "de",
            "dz",
            "el",
            "en",
            "eo",
            "es",
            "et",
            "eu",
            "fa",
            "fi",
            "fj",
            "fo",
            "fr",
            "fy",
            "ga",
            "gd",
            "gl",
            "gn",
            "gu",
            "ha",
            "he",
            "hi",
            "hr",
            "hu",
            "hy",
            "ia",
            "ie",
            "ik",
            "id",
            "is",
            "it",
            "iu",
            "ja",
            "jv",
            "ka",
            "kk",
            "kl",
            "km",
            "kn",
            "ko",
            "ks",
            "ku",
            "ky",
            "la",
            "ln",
            "lo",
            "lt",
            "lv",
            "mg",
            "mi",
            "mk",
            "ml",
            "mn",
            "mo",
            "mr",
            "ms",
            "mt",
            "my",
            "na",
            "ne",
            "nl",
            "no",
            "oc",
            "om",
            "or",
            "pa",
            "pl",
            "ps",
            "pt",
            "qu",
            "rm",
            "rn",
            "ro",
            "ru",
            "rw",
            "sa",
            "sd",
            "sg",
            "sh",
            "si",
            "sk",
            "sl",
            "sm",
            "sn",
            "so",
            "sq",
            "sr",
            "ss",
            "st",
            "su",
            "sv",
            "sw",
            "ta",
            "te",
            "tg",
            "th",
            "ti",
            "tk",
            "tl",
            "tn",
            "to",
            "tr",
            "ts",
            "tt",
            "tw",
            "ug",
            "uk",
            "ur",
            "uz",
            "vi",
            "vo",
            "wo",
            "xh",
            "yi",
            "yo",
            "za",
            "zh",
            "zu"
        );
    }

    public function getLanguageCode(): string
    {
        if (in_array($this->language_code, $this->possible_language_codes, true)) {
            return $this->language_code;
        }
        return '';
    }
}

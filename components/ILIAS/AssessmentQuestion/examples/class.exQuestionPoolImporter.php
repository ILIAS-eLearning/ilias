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

/**
 * Class exQuestionPoolImporter
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/Test(QuestionPool)
 */
class exQuestionPoolImporter extends ilXmlImporter
{
    /**
     * @param string          $a_entity
     * @param string          $a_id
     * @param string          $a_xml
     * @param ilImportMapping $a_mapping
     */
    public function importXmlRepresentation(string $a_entity, string $a_id, string $a_xml, ilImportMapping $a_mapping): void
    {
        /**
         * here consumers can regularly process their own import stuff.
         *
         * although the assessment questions are imported by declared tail depencies,
         * any consumer component can import any overall qti xml file, that was added
         * to the export by the consumer itself.
         */
    }

    /**
     * Final processing
     * @param ilImportMapping $a_mapping
     * @return void
     */
    public function finalProcessing(ilImportMapping $a_mapping): void
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $maps = $a_mapping->getMappingsOfEntity("components/ILIAS/TestQuestionPool", "qpl");

        foreach ($maps as $old => $new) {
            if ($old != "new_id" && (int) $old > 0) {
                $newQstIds = $a_mapping->getMapping("components/ILIAS/AssessmentQuestion", "qst", $old);

                if ($newQstIds !== false) {
                    $qstIds = explode(":", $newQstIds);
                    foreach ($qstIds as $qId) {
                        $qstInstance = $DIC->question()->getQuestionInstance($qId);
                        $qstInstance->setParentId($new);
                        $qstInstance->save();
                    }
                }

                $qstMappings = $a_mapping->getMappingsOfEntity('components/ILIAS/AssessmentQuestion', 'qst');

                foreach ($qstMappings as $oldQstId => $newQstId) {
                    // process all question ids within the consumer component database,
                    // look for the old qst id and map to the new qst id
                }
            }
        }
    }
}

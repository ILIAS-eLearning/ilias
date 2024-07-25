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

namespace Administration;

use assFormulaQuestion;
use assNumeric;
use ILIAS\Test\Administration\TestGlobalSettingsRepository;
use ILIAS\Test\Logging\TestLogViewer;
use ilObjTestFolder;
use ilSetting;
use ilTestBaseTestCase;

/**
 * Class ilObjTestFolderTest
 * @author Marvin Beym <mbeym@databay.de>
 */
class ilObjTestFolderTest extends ilTestBaseTestCase
{
    private ilObjTestFolder $testObj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testObj = new ilObjTestFolder();
    }

    public function test_instantiateObject_shouldReturnInstance(): void
    {
        $this->assertInstanceOf(ilObjTestFolder::class, $this->testObj);
    }

    public function test_GetterWithoutSetter(): void
    {
        $this->assertInstanceOf(TestGlobalSettingsRepository::class, $this->testObj->getGlobalSettingsRepository());
        $this->assertInstanceOf(TestLogViewer::class, $this->testObj->getTestLogViewer());
    }

    public function test_getSkillTriggerAnswerNumberBarrier(): void
    {
        $this->assertIsInt(ilObjTestFolder::getSkillTriggerAnswerNumberBarrier());
    }

    public function test_enableAssessmentLogging(): void
    {
        $this->testObj->_enableAssessmentLogging(true);
        $setting = new ilSetting('assessment');
        $this->assertEquals('1', $setting->get('assessment_logging'));
    }

    public function test_setLogLanguage(): void
    {
        $this->testObj->_setLogLanguage('blub');
        $setting = new ilSetting('assessment');
        $this->assertEquals('blub', $setting->get('assessment_log_language'));
    }

    public function test_getForbiddenQuestionTypes(): void
    {
        $setting = new ilSetting('assessment');
        $setting->set('forbidden_questiontypes', '');
        $this->assertEmpty(ilObjTestFolder::_getForbiddenQuestionTypes());

        $setting->set('forbidden_questiontypes', serialize(["1", "7", "9", ""]));
        $forbiddenTypes = ilObjTestFolder::_getForbiddenQuestionTypes();
        $this->assertSame([1, 7, 9], $forbiddenTypes);
    }

    public function test_setForbiddenQuestionTypes(): void
    {
        $this->testObj->_setForbiddenQuestionTypes([7, 28, '19']);
        $forbiddenTypes = ilObjTestFolder::_getForbiddenQuestionTypes();
        $this->assertSame([7, 28, 19], $forbiddenTypes);
    }

    public function test_set_and_getManualScoring(): void
    {
        $this->testObj->_setManualScoring([1, 5, '7', '']);
        $this->assertSame([1, 5, 7], ilObjTestFolder::_getManualScoring());

        $this->testObj->_setManualScoring([]);
        $this->assertSame([], ilObjTestFolder::_getManualScoring());
    }

    public function test_mananuallyScorableQuestionTypesExists(): void
    {
        $this->testObj->_setManualScoring([1, 5, '7']);
        $this->assertTrue(ilObjTestFolder::_mananuallyScoreableQuestionTypesExists());

        $this->testObj->_setManualScoring([]);
        $this->assertFalse(ilObjTestFolder::_mananuallyScoreableQuestionTypesExists());
    }

    public function test_getManualScoringTypes(): void
    {
        $this->testObj->_setManualScoring([]);
        $this->assertEmpty(ilObjTestFolder::_getManualScoringTypes());
    }

    public function test_set_and_getScoringAdjustableQuestions(): void
    {
        ilObjTestFolder::setScoringAdjustableQuestions([1, '2', 3, '']);
        $this->assertSame([1, 2, 3], ilObjTestFolder::getScoringAdjustableQuestions());

        ilObjTestFolder::setScoringAdjustableQuestions([]);
        $this->assertEmpty(ilObjTestFolder::getScoringAdjustableQuestions());
    }

    public function test_set_and_getScoringAdjustmentEnabled(): void
    {
        ilObjTestFolder::setScoringAdjustmentEnabled(true);
        $this->assertTrue(ilObjTestFolder::getScoringAdjustmentEnabled());

        ilObjTestFolder::setScoringAdjustmentEnabled(false);
        $this->assertFalse(ilObjTestFolder::getScoringAdjustmentEnabled());
    }


    public function test_isAdditionalQuestionContentEditingModePageObjectEnabled(): void
    {
        $this->assertFalse(ilObjTestFolder::isAdditionalQuestionContentEditingModePageObjectEnabled());
    }

    public function test_get_and_setAssessmentProcessLockMode(): void
    {
        $this->assertEquals(ilObjTestFolder::ASS_PROC_LOCK_MODE_NONE, $this->testObj->getAssessmentProcessLockMode());

        $this->testObj->setAssessmentProcessLockMode("blub");

        $this->assertEquals("blub", $this->testObj->getAssessmentProcessLockMode());
    }

    public function test_getValidAssessmentProcessLockModes(): void
    {
        $this->assertSame([
            ilObjTestFolder::ASS_PROC_LOCK_MODE_NONE,
            ilObjTestFolder::ASS_PROC_LOCK_MODE_FILE,
            ilObjTestFolder::ASS_PROC_LOCK_MODE_DB
        ], ilObjTestFolder::getValidAssessmentProcessLockModes());
    }

    public function test_get_and_setSkillTriggeringNumAnswersBarrier(): void
    {
        $this->assertEquals(ilObjTestFolder::DEFAULT_SKL_TRIG_NUM_ANSWERS_BARRIER, $this->testObj->getSkillTriggeringNumAnswersBarrier());

        $this->testObj->setSkillTriggeringNumAnswersBarrier(15);

        $this->assertSame("15", $this->testObj->getSkillTriggeringNumAnswersBarrier());
    }

    public function test_get_and_setExportEssayQuestionsWithHtml(): void
    {
        $this->assertEquals(false, $this->testObj->getExportEssayQuestionsWithHtml());

        $this->testObj->setExportEssayQuestionsWithHtml(true);

        $this->assertTrue($this->testObj->getExportEssayQuestionsWithHtml());

        $this->testObj->setExportEssayQuestionsWithHtml(false);

        $this->assertFalse($this->testObj->getExportEssayQuestionsWithHtml());
    }

    /**
     * @dataProvider provideQuestionTypeArrays
     */
    public function test_fetchScoringAdjustableTypes($questionTypes, $adjustableQuestionTypes): void
    {
        $this->assertSame($adjustableQuestionTypes, $this->testObj->fetchScoringAdjustableTypes($questionTypes));
    }

    public static function provideQuestionTypeArrays(): array
    {
        return [
            "dataset 1: only adjustable types" => [
                "questionTypes" => [
                    ['type_tag' => assNumeric::class]
                ],
                "adjustableQuestionTypes" => [
                    ['type_tag' => assNumeric::class]

                ]
            ],
            "dataset 2: both types" => [
                "questionTypes" => [
                    ['type_tag' => assNumeric::class],
                    ['type_tag' => assFormulaQuestion::class]
                ],
                "adjustableQuestionTypes" => [
                    ['type_tag' => assNumeric::class]

                ]
            ],
            "dataset 3: only not adjustable types" => [
                "questionTypes" => [
                    ['type_tag' => assFormulaQuestion::class]
                ],
                "adjustableQuestionTypes" => [
                ]
            ]
        ];
    }


}

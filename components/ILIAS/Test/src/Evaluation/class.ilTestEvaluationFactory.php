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

use ILIAS\Test\Scoring\Marks\MarkSchema;

class ilTestEvaluationFactory
{
    public function __construct(
        protected ilDBInterface $db,
        protected ilObjTest $test_obj
    ) {
    }

    protected function getPassScoringSettings(): int
    {
        return $this->test_obj->getPassScoring();
    }

    protected function isRandomTest(): bool
    {
        return $this->test_obj->isRandomTest();
    }

    protected function getTestQuestionCount(): bool
    {
        return $this->test_obj->getQuestionCountWithoutReloading();
    }

    protected function getTestMarkSchema(): MarkSchema
    {
        return $this->test_obj->getMarkSchema();
    }

    protected function getVisitTimeOfParticipant(int $active_id): array
    {
        return $this->test_obj->getVisitingTimeOfParticipant($active_id);
    }

    protected function getQuestionCountAndPointsForPassOfParticipant(
        int $active_id,
        int $pass
    ): array {
        return ilObjTest::_getQuestionCountAndPointsForPassOfParticipant($active_id, $pass);
    }

    protected function buildName(
        int $usr_id,
        string $firstname,
        string $lastname,
        string $title
    ): string {
        return $this->test_obj->buildName($usr_id, $firstname, $lastname, $title);
    }

    /**
     * @return int[]
     */
    protected function getAccessFilteredActiveIds(): array
    {
        if ($participants_list = $this->test_obj->getAccessFilteredParticipantList()) {
            return $participants_list->getAllActiveIds();
        }
        $participants = $this->test_obj->getTestParticipants();
        return array_keys($participants);
    }

    /**
     * @param int[] $active_ids
     */
    protected function queryEvaluationData(array $active_ids): array
    {
        $query = '
        SELECT      tst_test_result.question_fi,
                    tst_test_result.points result_points,
                    tst_test_result.answered,
                    tst_test_result.manual,

                    qpl_questions.original_id,
                    qpl_questions.title questiontitle,
                    qpl_questions.points qpl_maxpoints,

                    tst_active.submitted,
                    tst_active.last_finished_pass,
                    tst_pass_result.*,

                    usr_data.usr_id,
                    usr_data.firstname,
                    usr_data.lastname,
                    usr_data.title,
                    usr_data.login

        FROM        tst_test_result, qpl_questions, tst_active

        LEFT JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
        LEFT JOIN usr_data ON tst_active.user_fi = usr_data.usr_id

        WHERE       tst_active.active_id = tst_test_result.active_fi
        AND         qpl_questions.question_id = tst_test_result.question_fi
        AND         tst_active.test_fi = %s
        AND         %s

        ORDER BY    tst_active.active_id ASC, tst_test_result.pass ASC, tst_test_result.tstamp DESC
        ';


        $query = sprintf(
            $query,
            $this->db->quote($this->test_obj->getTestId(), 'integer'),
            $this->db->in('tst_active.active_id', $active_ids, false, 'integer'),
        );

        $ret = [];
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $ret[] = $row;
        }
        return $ret;
    }

    public function getEvaluationData(): ilTestEvaluationData
    {
        $eval_data_rows = $this->queryEvaluationData($this->getAccessFilteredActiveIds());
        $scoring_settings = $this->getPassScoringSettings();
        $participants = [];
        $current_user = null;

        foreach ($eval_data_rows as $row) {

            if ($current_user != $row['active_fi']) {
                $current_user = $row['active_fi'];

                $user_eval_data = new ilTestEvaluationUserData($scoring_settings);

                $user_eval_data->setName(
                    $this->buildName($row['usr_id'], $row['firstname'], $row['lastname'], $row['title'])
                );

                if ($row['login'] !== null) {
                    $user_eval_data->setLogin($row['login']);
                }
                if ($row['usr_id'] !== null) {
                    $user_eval_data->setUserID($row['usr_id']);
                }
                $user_eval_data->setSubmitted((bool) $row['submitted']);
                $user_eval_data->setLastFinishedPass($row['last_finished_pass']);


                $visiting_time = $this->getVisitTimeOfParticipant($row['active_fi']);
                $user_eval_data->setFirstVisit($visiting_time["first_access"]);
                $user_eval_data->setLastVisit($visiting_time["last_access"]);

                $pass = new \ilTestEvaluationPassData();
                $pass->setPass($row['pass']);
                $pass->setReachedPoints($row['points']);

                if ($row['questioncount'] == 0) {
                    list($count, $points) = array_values(
                        $this->getQuestionCountAndPointsForPassOfParticipant($row['active_fi'], $row['pass'])
                    );
                    $pass->setMaxPoints($points);
                    $pass->setQuestionCount($count);
                } else {
                    $pass->setMaxPoints($row['maxpoints']);
                    $pass->setQuestionCount($row['questioncount']);
                }

                $pass->setNrOfAnsweredQuestions($row['answeredquestions']);
                $pass->setWorkingTime($row['workingtime']);
                $pass->setExamId((string) $row['exam_id']);
                $pass->setRequestedHintsCount($row['hint_count']);
                $pass->setDeductedHintPoints($row['hint_points']);
            }

            $pass->addAnsweredQuestion(
                $row["question_fi"],
                $row["qpl_maxpoints"],
                $row["result_points"],
                (bool) $row['answered'],
                null,
                $row['manual']
            );

            $user_eval_data->addPass($row['pass'], $pass);

            $participants[$row['active_fi']] = $user_eval_data;
        }

        $evaluation_data = new ilTestEvaluationData($participants);
        $evaluation_data = $this->addQuestionsToParticipantPasses($evaluation_data);
        $evaluation_data = $this->addMarksToParticipants($evaluation_data);

        return $evaluation_data;
    }


    protected function addQuestionsToParticipantPasses(ilTestEvaluationData $evaluation_data): ilTestEvaluationData
    {
        $is_random_test = $this->isRandomTest();

        foreach ($evaluation_data->getParticipantIds() as $active_id) {
            $user_eval_data = $evaluation_data->getParticipant($active_id);

            $add_user_questions = $this->isRandomTest() ?
                $this->getQuestionsForParticipantPassesForRandomTests($active_id, $user_eval_data, $this->getTestQuestionCount()) :
                $this->getQuestionsForParticipantPassesForSequencedTests($active_id);

            foreach ($add_user_questions as $q) {

                $original_id = $q['original_id'];
                $question_id = $q['question_id'];
                $max_points = $q['max_points'];
                $sequence = $q['sequence'];
                $pass = $q['pass'];
                $title = $q['title'];

                $user_eval_data->addQuestion(
                    $original_id,
                    $question_id,
                    $max_points,
                    $sequence,
                    $pass
                );

                $evaluation_data->addQuestionTitle($question_id, $title);
            }
        }

        return $evaluation_data;
    }

    protected function getQuestionsForParticipantPassesForRandomTests(
        int $active_id,
        ilTestEvaluationUserData $user_eval_data,
        int $question_count
    ): array {
        $ret = [];
        for ($testpass = 0; $testpass <= $user_eval_data->getLastPass(); $testpass++) {
            $this->db->setLimit($question_count, 0);
            $query = "
                SELECT tst_test_rnd_qst.sequence, tst_test_rnd_qst.question_fi, qpl_questions.original_id,
                tst_test_rnd_qst.pass, qpl_questions.points, qpl_questions.title
                FROM tst_test_rnd_qst, qpl_questions
                WHERE tst_test_rnd_qst.question_fi = qpl_questions.question_id
                AND tst_test_rnd_qst.pass = %s
                AND tst_test_rnd_qst.active_fi = %s ORDER BY tst_test_rnd_qst.sequence
            ";

            $result = $this->db->queryF(
                $query,
                ['integer','integer'],
                [$testpass, $active_id]
            );

            if ($result->numRows()) {
                while ($row = $this->db->fetchAssoc($result)) {
                    $tpass = array_key_exists("pass", $row) ? $row["pass"] : 0;

                    if (
                        !isset($row["question_fi"], $row["points"], $row["sequence"]) ||
                        !is_numeric($row["question_fi"]) || !is_numeric($row["points"]) || !is_numeric($row["sequence"])
                    ) {
                        continue;
                    }

                    $ret[] = [
                        'original_id' => (int) $row["original_id"],
                        'question_id' => (int) $row["question_fi"],
                        'max_points' => (float) $row["points"],
                        'sequence' => (int) $row["sequence"],
                        'pass' => $tpass,
                        'title' => $row["title"]
                    ];
                }
            }
        }
        return $ret;
    }

    protected function getQuestionsForParticipantPassesForSequencedTests(
        int $active_id
    ): array {
        $ret = [];

        $query = "
            SELECT tst_test_question.sequence, tst_test_question.question_fi,
            qpl_questions.points, qpl_questions.title, qpl_questions.original_id
            FROM tst_test_question, tst_active, qpl_questions
            WHERE tst_test_question.question_fi = qpl_questions.question_id
            AND tst_active.active_id = %s
            AND tst_active.test_fi = tst_test_question.test_fi
            ORDER BY tst_test_question.sequence
        ";

        $result = $this->db->queryF(
            $query,
            ['integer'],
            [$active_id]
        );

        if ($result->numRows()) {
            $questionsbysequence = [];
            while ($row = $this->db->fetchAssoc($result)) {
                $questionsbysequence[$row["sequence"]] = $row;
            }

            $seqresult = $this->db->queryF(
                "SELECT * FROM tst_sequence WHERE active_fi = %s",
                ['integer'],
                [$active_id]
            );

            while ($seqrow = $this->db->fetchAssoc($seqresult)) {
                $questionsequence = unserialize($seqrow["sequence"]);

                foreach ($questionsequence as $sidx => $seq) {
                    $ret[] = [
                        'original_id' => $questionsbysequence[$seq]['original_id'] ?? 0,
                        'question_id' => $questionsbysequence[$seq]['question_fi'],
                        'max_points' => $questionsbysequence[$seq]['points'],
                        'sequence' => $sidx + 1,
                        'pass' => $seqrow['pass'],
                        'title' => $questionsbysequence[$seq]["title"]
                    ];
                }
            }
        }
        return $ret;
    }

    protected function addMarksToParticipants(ilTestEvaluationData $evaluation_data): ilTestEvaluationData
    {
        $mark_schema = $this->getTestMarkSchema();

        foreach ($evaluation_data->getParticipantIds() as $active_id) {
            $user_eval_data = $evaluation_data->getParticipant($active_id);

            $percentage = $user_eval_data->getReachedPointsInPercent();
            $mark = $mark_schema->getMatchingMark($percentage);

            if ($mark !== null) {
                $user_eval_data->setMark($mark);
                //$user_eval_data->setMarkOfficial($mark->getOfficialName());
                //$user_eval_data->setPassed($mark->0getPassed() );

                for ($i = 0;$i < $user_eval_data->getPassCount();$i++) {
                    $pass_data = $user_eval_data->getPass($i);
                    $mark = $mark_schema->getMatchingMark(
                        $pass_data->getReachedPointsInPercent()
                    );
                    if ($mark !== null) {
                        $pass_data->setMark($mark);
                    }
                }
            }

        }

        return $evaluation_data;
    }

}

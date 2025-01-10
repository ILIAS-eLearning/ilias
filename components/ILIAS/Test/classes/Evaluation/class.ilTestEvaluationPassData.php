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

use ILIAS\Test\Results\Data\StatusOfAttempt;
use ILIAS\Test\Scoring\Marks\Mark;

/**
 * @deprecated 11; Result/EvaluationData will be refined.
 */
class ilTestEvaluationPassData
{
    /**
    * @var array<int>
    */
    public array $answeredQuestions;
    private ?\DateTimeImmutable $start_time = null;
    private ?\DateTimeImmutable $last_access_time = null;
    private int $workingtime;
    private int $questioncount;
    private float $maxpoints;
    private float $reachedpoints;
    private ?Mark $mark = null;
    private int $nrOfAnsweredQuestions;
    private int $pass;
    private ?int $requestedHintsCount = null;
    private ?float $deductedHintPoints = null;
    private string $exam_id = '';
    private ?StatusOfAttempt $status_of_attempt = null;

    public function __sleep()
    {
        return ['answeredQuestions', 'pass', 'nrOfAnsweredQuestions', 'reachedpoints',
            'maxpoints', 'questioncount', 'workingtime', 'examId'];
    }

    /**
    * Constructor
    *
    * @access	public
    */
    public function __construct()
    {
        $this->answeredQuestions = [];
    }

    public function getNrOfAnsweredQuestions(): int
    {
        return $this->nrOfAnsweredQuestions;
    }

    public function setNrOfAnsweredQuestions(int $nrOfAnsweredQuestions): void
    {
        $this->nrOfAnsweredQuestions = $nrOfAnsweredQuestions;
    }

    public function getReachedPoints(): float
    {
        return $this->reachedpoints;
    }

    public function setReachedPoints(float $reachedpoints): void
    {
        $this->reachedpoints = $reachedpoints;
    }

    public function getMaxPoints(): float
    {
        return $this->maxpoints;
    }

    public function setMaxPoints(float $maxpoints): void
    {
        $this->maxpoints = $maxpoints;
    }

    public function getReachedPointsInPercent(): float
    {
        return $this->getMaxPoints() ? $this->getReachedPoints() / $this->getMaxPoints() * 100.0 : 0.0;
    }

    public function getMark(): ?Mark
    {
        return $this->mark;
    }

    public function setMark(Mark $mark): void
    {
        $this->mark = $mark;
    }

    public function getQuestionCount(): int
    {
        return $this->questioncount;
    }

    public function setQuestionCount(int $questioncount): void
    {
        $this->questioncount = $questioncount;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->start_time;
    }

    public function setStartTime(?string $start_time): void
    {
        if ($start_time !== null) {
            $this->start_time = new \DateTimeImmutable($start_time);
        }
    }

    public function getLastAccessTime(): ?\DateTimeImmutable
    {
        return $this->last_access_time;
    }

    public function setLastAccessTime(?string $last_access_time): void
    {
        if ($last_access_time !== null) {
            $this->last_access_time = new \DateTimeImmutable($last_access_time);
        }
    }

    public function getWorkingTime(): int
    {
        return $this->workingtime;
    }

    public function setWorkingTime(int $workingtime): void
    {
        $this->workingtime = $workingtime;
    }

    public function getPass(): int
    {
        return $this->pass;
    }

    public function setPass(int $pass): void
    {
        $this->pass = $pass;
    }

    public function getAnsweredQuestions(): array
    {
        return $this->answeredQuestions;
    }

    public function addAnsweredQuestion(
        int $question_id,
        float $max_points,
        float $reached_points,
        bool $is_answered,
        ?int $sequence = null,
        int $manual = 0
    ): void {
        $this->answeredQuestions[] = [
            'id' => $question_id,
            'points' => round($max_points, 2),
            'reached' => round($reached_points, 2),
            'isAnswered' => $is_answered,
            'sequence' => $sequence,
            'manual' => $manual
        ];
    }

    public function getAnsweredQuestion(int $index): ?array
    {
        if (array_key_exists($index, $this->answeredQuestions)) {
            return $this->answeredQuestions[$index];
        }

        return null;
    }

    public function getAnsweredQuestionByQuestionId(int $question_id): ?array
    {
        foreach ($this->answeredQuestions as $question) {
            if ($question['id'] == $question_id) {
                return $question;
            }
        }
        return null;
    }

    public function getAnsweredQuestionCount(): int
    {
        return count($this->answeredQuestions);
    }

    public function getRequestedHintsCount(): ?int
    {
        return $this->requestedHintsCount;
    }

    public function setRequestedHintsCount(int $requestedHintsCount): void
    {
        $this->requestedHintsCount = $requestedHintsCount;
    }

    public function getDeductedHintPoints(): ?float
    {
        return $this->deductedHintPoints;
    }

    public function setDeductedHintPoints(float $deductedHintPoints): void
    {
        $this->deductedHintPoints = $deductedHintPoints;
    }

    public function getExamId(): string
    {
        return $this->exam_id;
    }

    public function setExamId(string $exam_id): void
    {
        $this->exam_id = $exam_id;
    }

    public function getStatusOfAttempt(): StatusOfAttempt
    {
        if ($this->status_of_attempt) {
            return $this->status_of_attempt;
        }

        return StatusOfAttempt::FINISHED_BY_UNKNOWN;
    }

    public function setStatusOfAttempt(?StatusOfAttempt $status_of_attempt): void
    {
        $this->status_of_attempt = $status_of_attempt;
    }
}

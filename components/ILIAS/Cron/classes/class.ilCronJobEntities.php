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

class ilCronJobEntities implements ilCronJobCollection
{
    private array $jobs;

    public function __construct(ilCronJobEntity ...$jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * @return ArrayIterator|ilCronJobEntity[]
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->jobs);
    }

    public function count(): int
    {
        return count($this->jobs);
    }

    public function add(ilCronJobEntity $job): void
    {
        $this->jobs[] = $job;
    }

    public function filter(callable $callable): ilCronJobCollection
    {
        return new static(...array_filter($this->jobs, $callable));
    }

    public function slice(int $offset, ?int $length = null): ilCronJobCollection
    {
        return new static(...array_slice($this->jobs, $offset, $length, true));
    }

    /**
     * @return ilCronJobEntity[]
     */
    public function toArray(): array
    {
        return $this->jobs;
    }
}

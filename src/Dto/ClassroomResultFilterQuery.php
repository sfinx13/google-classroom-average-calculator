<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ClassroomResultFilterQuery
{
    public const array TRIMESTERS = [
        'T1' => ['start' => '2025-09-01', 'end' => '2025-12-31'],
        'T2' => ['start' => '2026-01-01', 'end' => '2026-04-30'],
        'T3' => ['start' => '2026-05-01', 'end' => '2026-07-31'],
    ];

    public function __construct(
        #[Assert\Choice(choices: ['none', 'asc', 'desc'])]
        public readonly ?string $sort = 'none',

        #[Assert\Choice(choices: ['T1', 'T2', 'T3', null])]
        public ?string $trimester = null,

        #[Assert\Date]
        public readonly ?string $startDate = null,

        #[Assert\Date]
        public readonly ?string $endDate = null,
    ) {
        if (null === $this->trimester && null === $this->startDate && null === $this->endDate) {
            $today = date('Y-m-d');
            foreach (self::TRIMESTERS as $trimester => $dates) {
                if ($today >= $dates['start'] && $today <= $dates['end']) {
                    $this->trimester = $trimester;
                    break;
                }
            }
        }
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getStartDate(): ?\DateTimeImmutable
    {
        $startStr = $this->startDate;

        if ($this->trimester && isset(self::TRIMESTERS[$this->trimester])) {
            $startStr = self::TRIMESTERS[$this->trimester]['start'];
        }

        if (!$startStr) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $startStr);

        if (!$date) {
            return new \DateTimeImmutable('1970-01-01');
        }

        return $date->modify('-1 day');
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getEndDate(): ?\DateTimeImmutable
    {
        $endStr = $this->endDate;

        if ($this->trimester && isset(self::TRIMESTERS[$this->trimester])) {
            $endStr = self::TRIMESTERS[$this->trimester]['end'];
        }

        if (!$endStr) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $endStr);

        if (!$date) {
            return new \DateTimeImmutable('1970-01-01');
        }

        return $date->modify('+1 day');
    }

    /**
     * @return string[]|null
     */
    public function getOrderBy(): ?array
    {
        if ('desc' === $this->sort) {
            return ['average' => 'DESC'];
        }

        if ('asc' === $this->sort) {
            return ['average' => 'ASC'];
        }

        return null;
    }
}

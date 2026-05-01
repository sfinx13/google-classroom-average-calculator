<?php

declare(strict_types=1);

namespace App\Dto;

class StudentAverage
{
    public function __construct(
        public string $studentName,
        /** @var SubjectAverage[] */
        public array $subjects,
        public float $globalAverage,
        public float $globalTotalAverage,
        public float $globalTotalPoints,
    ) {
    }
}

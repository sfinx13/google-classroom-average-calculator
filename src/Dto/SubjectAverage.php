<?php

declare(strict_types=1);

namespace App\Dto;

readonly class SubjectAverage
{
    public function __construct(
        public string $subjectName,
        public float $average,
        public float $maxPoints = 20.0,
    ) {
    }
}

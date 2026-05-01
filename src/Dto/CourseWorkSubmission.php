<?php

declare(strict_types=1);

namespace App\Dto;

readonly class CourseWorkSubmission
{
    public function __construct(
        public string $id,
        public string $courseWorkId,
        public ?float $assignedGrade = null,
    ) {
    }
}

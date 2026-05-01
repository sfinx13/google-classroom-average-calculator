<?php

declare(strict_types=1);

namespace App\Dto;

readonly class CourseWork
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $topicId = null,
        public ?float $maxPoints = null,
        public ?\DateTimeImmutable $creationTime = null,
    ) {
    }
}

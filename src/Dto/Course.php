<?php

declare(strict_types=1);

namespace App\Dto;

readonly class Course
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}

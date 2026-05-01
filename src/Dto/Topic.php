<?php

declare(strict_types=1);

namespace App\Dto;

readonly class Topic
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}

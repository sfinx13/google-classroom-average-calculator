<?php

namespace App\Dto;

class Student
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {
    }
}

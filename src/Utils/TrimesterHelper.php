<?php

declare(strict_types=1);

namespace App\Utils;

use App\Dto\ClassroomResultFilterQuery;

class TrimesterHelper
{
    public static function getTrimester(string $startDate): ?string
    {
        return substr(array_find_key(ClassroomResultFilterQuery::TRIMESTERS, fn ($dates) => $startDate > $dates['start'] && $startDate < $dates['end']), 1);
    }
}

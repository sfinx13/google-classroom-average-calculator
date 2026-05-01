<?php

namespace App\Entity\Enum;

enum Topic: string
{
    case LECTURE = 'Lecture';
    case DICTEE = 'Dictée';
    case QISSAS = 'Qissas';
    case TRADUCTION = 'Traduction';
    case CONJUGAISON = 'Conjugaison';
    case VOCABULAIRE = 'Vocabulaire';
    case GRAMMAIRE = 'Grammaire';
    case DEVOIR = 'Devoir';

    public function scale(): int
    {
        return match ($this) {
            self::LECTURE, self::CONJUGAISON, self::VOCABULAIRE => 20,
            self::DICTEE, self::TRADUCTION, self::DEVOIR => 10,
            self::QISSAS => 35,
            self::GRAMMAIRE => 40,
        };
    }

    /**
     * @return array<string, int>
     */
    public static function scales(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->value] = $case->scale();
        }

        return $result;
    }
}

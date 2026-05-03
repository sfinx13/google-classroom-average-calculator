<?php

declare(strict_types=1);

namespace App\Service\Bulletin;

use App\Entity\ClassroomResult;
use App\Repository\ClassroomResultRepository;
use App\Utils\TrimesterHelper;

class BulletinPlaceholderMapper
{
    public function __construct(
        private readonly ClassroomResultRepository $classroomResultRepository,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function map(ClassroomResult $classroomResult): array
    {
        $studentAverageResult = $classroomResult->getResult();
        if (null === $studentAverageResult) {
            return [];
        }

        $rank = $this->classroomResultRepository->findRank($classroomResult);

        $placeholders = [
            '{{numero_periode}}' => TrimesterHelper::getTrimester($classroomResult->getStartDate()->format('Y-m-d')),
            '{{start_date}}' => $classroomResult->getStartDate()->format('d/m/Y'),
            '{{end_date}}' => $classroomResult->getEndDate()->format('d/m/Y'),
            '{{student_fullname}}' => mb_convert_case($studentAverageResult['studentName'], MB_CASE_TITLE, 'UTF-8'),
            '{{general_average}}' => $this->formatScore($studentAverageResult['globalAverage']),
            '{{total}}' => $this->formatScore($studentAverageResult['globalTotalAverage']),
            '{{rank}}' => $rank > 0 ? (string) $rank : '-',
            '{{appreciation}}' => $classroomResult->getAppreciation() ?? '',
        ];

        $subjectsMapping = [
            'Lecture' => 'lecture',
            'Dictée' => 'dictee',
            'Qissas' => 'qissas',
            'Traduction' => 'traduction',
            'Grammaire' => 'grammaire',
            'Vocabulaire' => 'vocabulaire',
            'Conjugaison' => 'conjugaison',
            'Devoir' => 'devoir',
        ];

        foreach ($subjectsMapping as $subjectLabel => $placeholderPrefix) {
            $subjectData = $this->findSubject($studentAverageResult['subjects'], $subjectLabel);

            $placeholders["{{{$placeholderPrefix}_average}}"] = $this->formatScore($subjectData['average'] ?? null);
            $placeholders["{{{$placeholderPrefix}_scale}}"] = isset($subjectData['maxPoints']) ? (string) $subjectData['maxPoints'] : '20';
        }

        return $placeholders;
    }

    /**
     * @param array<int, array{subjectName: string, average: float, maxPoints: float}> $subjects
     *
     * @return array{subjectName: string, average: float, maxPoints: float}|null
     */
    private function findSubject(array $subjects, string $label): ?array
    {
        foreach ($subjects as $subject) {
            if ($subject['subjectName'] === $label) {
                return $subject;
            }
        }

        return null;
    }

    private function formatScore(?float $score): string
    {
        if (null === $score) {
            return '-';
        }

        return number_format($score, 2, ',', ' ');
    }
}

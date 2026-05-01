<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\StudentAverage;
use App\Entity\ClassroomResult;
use App\Entity\Student;
use App\Repository\ClassroomResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ClassroomResultManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClassroomResultRepository $classroomResultRepository,
        private readonly NormalizerInterface $normalizer,
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function retrieveStudentAverageBy(Student $student, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): ?StudentAverage
    {
        $result = $this->classroomResultRepository->findOneBy([
            'student' => $student,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        if (!$result || !$result->getResult()) {
            return null;
        }

        return $this->denormalizer->denormalize($result->getResult(), StudentAverage::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function createFrom(
        Student $student,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        StudentAverage $studentAverage,
    ): void {
        $classroomResult = $this->classroomResultRepository->findOneBy([
            'student' => $student,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        if (!$classroomResult) {
            $classroomResult = new ClassroomResult();
            $classroomResult
                ->setStudent($student)
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setAverage($studentAverage->globalAverage);
        }

        $studentAverageNormalized = $this->normalizer->normalize($studentAverage);
        $classroomResult->setResult($studentAverageNormalized);

        $this->entityManager->persist($classroomResult);
        $this->entityManager->flush();
    }
}

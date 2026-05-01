<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Student as StudentDto;
use App\Entity\Student;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;

class StudentManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StudentRepository $studentRepository,
    ) {
    }

    public function createFrom(StudentDto $dto, string $courseId): void
    {
        $student = $this->studentRepository->findOneBy([
            'googleStudentId' => $dto->id,
            'googleClassroomId' => $courseId,
        ]);

        if (!$student) {
            $student = new Student();
            $student->setGoogleStudentId($dto->id);
            $student->setGoogleClassroomId($courseId);
        }

        $student->setEmail($dto->email);
        $student->setFullname($dto->name);

        $this->entityManager->persist($student);
        $this->entityManager->flush();
    }
}

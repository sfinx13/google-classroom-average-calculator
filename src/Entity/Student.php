<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $googleStudentId = null;

    #[ORM\Column(length: 128)]
    private ?string $googleClassroomId = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 128)]
    private ?string $fullname = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGoogleStudentId(): ?string
    {
        return $this->googleStudentId;
    }

    public function setGoogleStudentId(string $googleStudentId): static
    {
        $this->googleStudentId = $googleStudentId;

        return $this;
    }

    public function getGoogleClassroomId(): ?string
    {
        return $this->googleClassroomId;
    }

    public function setGoogleClassroomId(?string $googleClassroomId): static
    {
        $this->googleClassroomId = $googleClassroomId;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }
}

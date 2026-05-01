<?php

namespace App\Entity;

use App\Repository\ClassroomResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassroomResultRepository::class)]
class ClassroomResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $student = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    private ?float $average = null;

    /**
     * @var array{
     *     studentName: string,
     *     subjects: array<int, array{
     *         subjectName: string,
     *         average: float,
     *         maxPoints: float
     *     }>,
     *     globalAverage: float,
     *     globalTotalAverage: float,
     *     globalTotalPoints: float
     * }|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $result = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return array{
     *     studentName: string,
     *     subjects: array<int, array{
     *         subjectName: string,
     *         average: float,
     *         maxPoints: float
     *     }>,
     *     globalAverage: float,
     *     globalTotalAverage: float,
     *     globalTotalPoints: float
     * }|null
     */
    public function getResult(): ?array
    {
        return $this->result;
    }

    /**
     * @param array{
     *     studentName: string,
     *     subjects: array<int, array{
     *         subjectName: string,
     *         average: float,
     *         maxPoints: float
     *     }>,
     *     globalAverage: float,
     *     globalTotalAverage: float,
     *     globalTotalPoints: float
     * }|null $result
     */
    public function setResult(?array $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getAverage(): ?float
    {
        return $this->average;
    }

    public function setAverage(?float $average): static
    {
        $this->average = $average;

        return $this;
    }
}

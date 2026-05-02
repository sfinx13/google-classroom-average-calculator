<?php

namespace App\Repository;

use App\Entity\ClassroomResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClassroomResult>
 */
class ClassroomResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassroomResult::class);
    }

    /**
     * @param array<string, string>|null $orderBy
     *
     * @return ClassroomResult[]
     */
    public function findByFilters(?\DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate, ?array $orderBy): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.student', 's')
            ->addSelect('s');

        if ($startDate) {
            $qb->andWhere('r.startDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('r.endDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('r.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function findRank(ClassroomResult $classroomResult): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.id', 'r.average')
            ->innerJoin('r.student', 's')
            ->where('r.startDate = :startDate')
            ->andWhere('r.endDate = :endDate')
            ->andWhere('s.googleClassroomId = :classroomId')
            ->setParameter('startDate', $classroomResult->getStartDate())
            ->setParameter('endDate', $classroomResult->getEndDate())
            ->setParameter('classroomId', $classroomResult->getStudent()->getGoogleClassroomId())
            ->orderBy('r.average', 'DESC');

        $results = $qb->getQuery()->getResult();
        $rank = 1;
        $previousAverage = null;

        foreach ($results as $index => $row) {
            $average = (float) $row['average'];

            if (null !== $previousAverage && $average < $previousAverage) {
                $rank = $index + 1;
            }

            // Dès que l'on retrouve le résultat demandé dans la liste triée,
            // on retourne le rang calculé pour cette position.
            if ($row['id'] === $classroomResult->getId()) {
                return $rank;
            }

            $previousAverage = $average;
        }

        return 0;
    }
}

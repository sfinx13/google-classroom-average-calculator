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
        $qb = $this->createQueryBuilder('r');

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
}

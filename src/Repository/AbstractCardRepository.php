<?php

namespace App\Repository;

use App\Entity\AbstractCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractCard>
 */
class AbstractCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractCard::class);
    }

    /**
     * Find all character cards by type
     * @return AbstractCard[] Returns an array of AbstractCard objects
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->setParameter('type', $type)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}

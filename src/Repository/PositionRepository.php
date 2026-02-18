<?php

namespace App\Repository;

use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Position>
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function findOneByGameAndNumber(int $gameId, int $number): ?Position
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.game = :gameId')
            ->andWhere('p.number = :number')
            ->setParameter('gameId', $gameId)
            ->setParameter('number', $number)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByGameAndRoll(int $gameId, int $roll): ?Position
    {
        $positions = $this->createQueryBuilder('p')
            ->addSelect('pc')
            ->leftJoin('p.placeCard', 'pc')
            ->andWhere('p.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();

        foreach ($positions as $position) {
            $placeCard = $position->getPlaceCard();
            if (!$placeCard) {
                continue;
            }

            $placeRoll = $placeCard->getRoll();
            if (is_array($placeRoll) && in_array($roll, $placeRoll, true)) {
                return $position;
            }
        }

        return null;
    }

    //    /**
    //     * @return Position[] Returns an array of Position objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Position
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

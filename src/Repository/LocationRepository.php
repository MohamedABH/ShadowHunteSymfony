<?php

namespace App\Repository;

use App\Entity\ActionCard;
use App\Entity\Game;
use App\Entity\Location;
use App\Entity\Player;
use App\Enum\LocationEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findLatestInPlayActionCardLocation(Game $game, Player $player, ActionCard $actionCard): ?Location
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.game = :game')
            ->andWhere('l.player = :player')
            ->andWhere('l.actionCard = :actionCard')
            ->andWhere('l.location = :inPlay')
            ->setParameter('game', $game)
            ->setParameter('player', $player)
            ->setParameter('actionCard', $actionCard)
            ->setParameter('inPlay', LocationEnum::IN_PLAY)
            ->orderBy('l.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Location[] Returns an array of Location objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Location
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\Colors;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function createGame(string $name, User $owner): Game
    {
        $game = new Game();
        $game->setName($name);
        $game->setStatus(GameStatus::PENDING);
        $game->setTurn(0);

        $player = new Player();
        $player->setUser($owner);
        $player->setCurrentDamage(0);
        $player->setRevealed(false);
        $player->setColor(Colors::WHITE);

        $game->addPlayer($player);

        return $game;
    }

    //    /**
    //     * @return Game[] Returns an array of Game objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Game
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

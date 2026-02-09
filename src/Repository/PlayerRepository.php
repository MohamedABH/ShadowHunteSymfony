<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\User;
use App\Entity\Game;
use App\Enum\Colors;
use App\Enum\GameStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function findActivePlayerByUser($user): ?Player
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.game', 'g')
            ->where('p.user = :user')
            ->andWhere('g.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', GameStatus::COMPLETED)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Create a new Player instance attached to a User and Game.
     * Does not persist the entity; caller should persist/flush.
     */
    public function createPlayer(User $user, Game $game): Player
    {

        $player = new Player();
        $player->setUser($user);
        $player->setGame($game);
        $player->setCurrentDamage(0);
        $player->setRevealed(false);

        // assign a color not already taken in the game
        $used = [];
        foreach ($game->getPlayers() as $existingPlayer) {
            if ($existingPlayer->getColor()) {
                $used[] = $existingPlayer->getColor()->value;
            }
        }

        $assigned = null;
        foreach (Colors::cases() as $colorCase) {
            if (!in_array($colorCase->value, $used)) {
                $assigned = $colorCase;
                break;
            }
        }

        if ($assigned === null) {
            $assigned = Colors::WHITE;
        }

        $player->setColor($assigned);

        return $player;
    }

    //    /**
    //     * @return Player[] Returns an array of Player objects
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

    //    public function findOneBySomeField($value): ?Player
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

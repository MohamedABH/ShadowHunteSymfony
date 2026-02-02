<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findValidByToken(string $token): ?RefreshToken
    {
        $refreshToken = $this->findOneBy(['token' => $token]);

        if (!$refreshToken || !$refreshToken->isValid()) {
            return null;
        }

        return $refreshToken;
    }

    public function revokeByUser($user): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.revoked', true)
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function save(RefreshToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RefreshToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

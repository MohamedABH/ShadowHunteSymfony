<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        foreach ($roles as $role) {
            $roleEntity = new \App\Entity\Role();
            $roleEntity->setLibelle($role);
            $manager->persist($roleEntity);
        }

        $manager->flush();
    }
}

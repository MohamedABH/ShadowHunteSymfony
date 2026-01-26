<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\PlaceCard;

class PlaceCardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cardList = [
            array(
                'name' => 'Antre de l\'ermite',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Vous pouvez piocher une carte Vision.',
                'roll' => '2-3',
            ),
            array(
                'name' => 'Porte de l\'Outremonde',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Vous pouvez piocher une carte de la pile de votre choix.',
                'roll' => '4-5',
            ),
            array(
                'name' => 'Monastère',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Vous pouvez piocher une carte Lumière.',
                'roll' => '6',
            ),
            array(
                'name' => 'Cimetière',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Vous pouvez piocher une carte Ténèbres.',
                'roll' => '8',
            ),
            array(
                'name' => 'Forêt hantée',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Le joueur de votre choix peut subir 2 Blessures OU soigner 1 Blessure.',
                'roll' => '9',
            ),
            array(
                'name' => 'Sanctuaire ancien',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Vous pouvez voler une carte équipement à un autre joueur.',
                'roll' => '10',
            ),
        ];

        foreach ($cardList as $cardData) {
            $card = new PlaceCard();
            $card->setName($cardData['name']);
            $card->setLink($cardData['link']);
            $card->setDescription($cardData['description']);
            $card->setAbilityMessage($cardData['abilityMessage']);
            $card->setRoll($cardData['roll']);

            $manager->persist($card);
        }

        $manager->flush();
    }
}

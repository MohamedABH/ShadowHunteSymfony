<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\CharacterCard;
use App\Enum\CharacterCardType;

class CharacterCardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cardList = [
            array(
                'name' => 'Allie',
                'initial' => 'A',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Amour Maternel. Soignez toutes vos Blessures. Utilisation unique.',
                'type' => 'neutral',
                'maxDamage' => 8,
            ),
            array(
                'name' => 'Agnès',
                'initial' => 'A',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Caprice. Au début de votre tour, changez votre condition de victoire par «Le joueur à votre gauche gagne.»',
                'type' => 'neutral',
                'maxDamage' => 8,
            ),
            array(
                'name' => 'Bob',
                'initial' => 'B',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Braquage. Si vous tuez un personnage, vous pouvez prendre toutes ses cartes équipement.',
                'type' => 'neutral',
                'maxDamage' => 10,
            ),
            array(
                'name' => 'Bryan',
                'initial' => 'B',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Oh my God ! Si vous tuez un personnage de 12 Points de Vie ou moins, vous devez révéler votre identité !',
                'type' => 'neutral',
                'maxDamage' => 10,
            ),
            array(
                'name' => 'Charles',
                'initial' => 'C',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Festin sanglant. Après votre attaque, vous pouvez vous infliger 2 Blessures afin d\'attaquer de nouveau le même joueur.',
                'type' => 'neutral',
                'maxDamage' => 11,
            ),
            array(
                'name' => 'Catherine',
                'initial' => 'C',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Stigmates. Guérissez de 1 Bléssure au début de votre tour.',
                'type' => 'neutral',
                'maxDamage' => 11,
            ),
            array(
                'name' => 'David',
                'initial' => 'D',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Pilleur de tombes.Récupérez dans la défausse la carte équipement de votre choix. Utilisation unique.',
                'type' => 'neutral',
                'maxDamage' => 13,
            ),
            array(
                'name' => 'Daniel',
                'initial' => 'D',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Désespoir. Dès qu\'un personnage meurt, vous devez révéler votre identité.',
                'type' => 'neutral',
                'maxDamage' => 13,
            ),
            array(
                'name' => 'Emi',
                'initial' => 'E',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Téléportation. Pour vous déplace, vous poiyvez lancer normalement les dés, ou vous déplacer sur la carte Lieu adjacente.',
                'type' => 'hunter',
                'maxDamage' => 10,
            ),
            array(
                'name' => 'Ellen',
                'initial' => 'E',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Exorcisme. Au début de votre tour, vous pouvez désigner un joueur. Il perd sa capacité spéciale jusqu\'à la fin de la partie. Utilisation unique.',
                'type' => 'hunter',
                'maxDamage' => 10,
            ),
            array(
                'name' => 'Franklin',
                'initial' => 'F',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Foudre. Au début de votre tour, choisissez un joueur et infligez-lui autant de Blessures que le résultat d\'un dé à 6 faces.',
                'type' => 'hunter',
                'maxDamage' => 12,
            ),
            array(
                'name' => 'Fu-ka',
                'initial' => 'F',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Soins particuliers. Au début de votre tour, placez le marqueur de Blessures d\'un joueur sur 7. Utilisation unique.',
                'type' => 'hunter',
                'maxDamage' => 12,
            ),
            array(
                'name' => 'Georges',
                'initial' => 'G',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Démolition. Au début de votre tour, choisissez un joueur et infligez-lui autant de Blessures que le résultat d\'un dé à 4 faces. Utilisation unique.',
                'type' => 'hunter',
                'maxDamage' => 14,
            ),
            array(
                'name' => 'Gregor',
                'initial' => 'G',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Bouclier fantôme. Ce pouvoir peut s\'activer à la fin de votre tour. Vous ne subissez aucune Blessure jusqu\'au début de votre prochain tour. Utilisation unique.',
                'type' => 'hunter',
                'maxDamage' => 14,
            ),
            array(
                'name' => 'Liche',
                'initial' => 'L',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Nécromancie. Vous pouvez rejouer autant de fois qu\'il y a de personnages morts. Utilisation unique.',
                'type' => 'shadow',
                'maxDamage' => 14,
            ),
            array(
                'name' => 'Loup-garou',
                'initial' => 'L',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Contre-attaque. Après avoir subi l\'attaque d\'un joueur, vous pouvez contre-attaquer immédiatement.',
                'type' => 'shadow',
                'maxDamage' => 14,
            ),
            array(
                'name' => 'Momie',
                'initial' => 'M',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Rayon d\'Outremonde. Au début de votre tour, vous pouvez infliger 3 Blessures à un joueur présent dans le Lieu Porte de l\'Outremonde.',
                'type' => 'shadow',
                'maxDamage' => 11,
            ),
            array(
                'name' => 'Métamorphe',
                'initial' => 'M',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Imitation. Vous pouvez mentir (sans avoir à révéler votre identité) lorsqu\'on vous donne une carte Vision.',
                'type' => 'shadow',
                'maxDamage' => 11,
            ),
            array(
                'name' => 'Vampire',
                'initial' => 'V',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Morsure. Si vous attaquez un joueur et lui infligez des Blessures, soignez immédiatement 2 de vos Blessures.',
                'type' => 'shadow',
                'maxDamage' => 13,
            ),
            array(
                'name' => 'Valkyrie',
                'initial' => 'V',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Capacité spécial: Chant de guerre. Quand vous attaquez, lancez seulement le dé à 4 faces pour déterminer les dégâts.',
                'type' => 'shadow',
                'maxDamage' => 13,
            ),
        ];

        foreach ($cardList as $cardData) {
            $card = new CharacterCard();
            $card->setName($cardData['name']);
            $card->setLink($cardData['link']);
            $card->setDescription($cardData['description']);
            $card->setAbilityMessage($cardData['abilityMessage']);
            $card->setType(CharacterCardType::from($cardData['type']));
            $card->setMaxDamage($cardData['maxDamage']);
            $card->setInitial($cardData['initial']);

            $manager->persist($card);
        }

        $manager->flush();
    }
}
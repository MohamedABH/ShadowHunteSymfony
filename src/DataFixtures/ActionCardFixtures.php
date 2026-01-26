<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\ActionCard;
use App\Enum\ActionCardType;

class ActionCardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cardList = [
            array(
                'name' => 'Vision furtive',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Hunter ou Shadow. Si c\'est le cas, tu dois: soit me donner une carte équipement, soit subir 1 Blessure.',
                'type' => 'sight',
                'count' => 2,
            ),
            array(
                'name' => 'Vision enivrante',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Neutre ou Hunter. Si c\'est le cas, tu dois: soit me donner une carte équipement, soit subir 1 Blessure.',
                'type' => 'sight',
                'count' => 2,
            ),
            array(
                'name' => 'Vision cupide',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Neutre ou Shadow. Si c\'est le cas, tu dois: soit me donner une carte équipement, soit subir 1 Blessure.',
                'type' => 'sight',
                'count' => 2,
            ),
            array(
                'name' => 'Vision mortifère',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Hunter. Si c\'est le cas, subis 1 Blessure !',
                'type' => 'sight',
                'count' => 2,
            ),
            array(
                'name' => 'Vision divine',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es hunter. Si c\'est le cas, soigne 1 Blessure. (Toutefois, si tu n\'avais aucune blessure, subis 1 Blessure !)',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision foudroyante',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Shadow. Si c\'est le cas, subis 1 Blessure !',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision purificatrice',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Shadow. Si c\'est le cas, subis 2 Blessures !',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision lugubre',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Shadow. Si c\'est le cas, soigne 1 Blessure. (Toutefois, si tu n\'avais aucune blessure, subis 1 Blessure !)',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision clairvoyante',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es un personnage de 11 Points de Vie ou moins: ABCEM. Si c\'est le cas, subis 1 Blessure !',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision destructrice',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es un personnage de 12 Points de Vie ou plus: DFGLV. Si c\'est le cas, subis 2 Blessures !',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision réconfortante',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Je pense que tu es Neutre. Si c\'est le cas, soigne 1 Blessure. (Toutefois, si tu n\'avais aucune blessure, subis 1 Blessure !)',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Vision suprême',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Montre-moi secrètement ta carte personnage !',
                'type' => 'sight',
                'count' => 1,
            ),
            array(
                'name' => 'Succube tentatrice',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Volez une carte équipement au joueur de votre choix.',
                'type' => 'dark',
                'count' => 2,
            ),
            array(
                'name' => 'Peau de banane',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immétiatement. Donnez une de vos carte équipement à un autre joueur. Si vous n\'en possédez aucune, vous encaissez 1 Blessure.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Chauve-souris vampire',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Infligez 2 Blessures au joueur de votre choix puis soignez une de vos Blessures.',
                'type' => 'dark',
                'count' => 3,
            ),
            array(
                'name' => 'Araignée sanguinaire',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Vous infligez 2 Blessures au joueur de votre choix, puis vous subissez vous-même 2 Blessures.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Tronçonneuse du mal',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Si votre attaque inflige des Blessures, la victime subit 1 Blessure en plus.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Hachoir maudit',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Si votre attaque inflige des Blessures, la victime subit 1 Blessure en plus.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Hache tueuse',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Si votre attaque inflige des Blessures, la victime subit 1 Blessure en plus.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Mitrailleuse funeste',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Votre attaque affect tous les personnages qui sont à votre portée. Effectuez un seul jet de Blessures pour tous les joueurs concernés.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Revolver des ténèbres',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Vous pouvez attaque un joueur sur l\'un des 4 lieux hors de votre secteur, mais vous ne pouvez plus attaquer un joueur situé dans le même secteur que vous.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Sabre hanté Masamune',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Vous êtes obligé d\'attaquer durant votre tour. Lancez uniquement le dé à 4 faces. Le résultat indique les Blessures que vous infligez.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Poupée démoniaque',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Désignez un joueur et lancez le dé à 6 faces. 1 à 4: infligez-lui 3 Blessures. 5 ou 6: subissez 3 Blessures.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Rituel diabolique',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Si vous êtes Shadow et si vous décidez de révéler (ou avez déjà révélé) votre identité, soignez toutes vos Blessures.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Dynamite',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Lancez les 2 dés et infligez 3 Blessures à tous les joueurs (vous compris) se trouvant dans le secteur désigné par le total des 2 dés. Il ne se passe rien si le total est 7.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Cauchemar',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Choisissez un joueur. Il doit lancer un dé à 6 faces: sur un résultat de 1 à 3, il subit 2 Blessures; sur un résultat de 4 à 6, il soigne 2 Blessures.',
                'type' => 'dark',
                'count' => 1,
            ),
            array(
                'name' => 'Eau bénite',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Vous êtes soigné de 2 Blessures.',
                'type' => 'light',
                'count' => 2,
            ),
            array(
                'name' => 'Barre de chocolat',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Si vous êtes Allie, Agnès, Emi, Ellen, Momie ou Métamorphe et que vous choisissez de révéler (ou avez déjà révélé) votre identité, vous soignez toutes vos Blessures.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Avènement suprême',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Si vous êtes un Hunter, vous pouvez révéler votre identité. Si vous le faites ou si vous êtes déjà révélé, vous soignez toutes vos Blessures.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Eclair purificateur',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Chaque personnage, à l\'exception de vous-même, subit 2 Blessures.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Savoir ancestral',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Lorsque votre tour est terminé, jouez immédiatement un nouveau tour.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Ange gardien',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. les attaques ne vous infligent aucune Blessure jusqu\'à votre prochain tour (défaussez alors cette carte).',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Bénédiction',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Choisissez un joueur autre que vous et lancez le dé à 6 faces. Ce joueur guérit d\'autant de Blessures que le résultat du dé.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Miroir divin',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Si vous êtes un Shadow autre que Métamorphe, vous devez révéler votre identité.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Premiers secours',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'À jouer immédiatement. Placez le marque de Blessure du joueur de votre choix (y compris vous) sur le 7.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Boussole mystique',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Quand vous vous déplacez, vous pouvez lancer 2 fois les dés et choisir quel résultat utiliser.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Broche de chance',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Un joueur dans la forêt hantée ne peut pas utiliser le pouvoir du Lieu pour vous infliger des Blessures (mais il peut toujours vous guérir).',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Lance de Longinus',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Si vous êtes un Hunter et que votre identité est révélée, chaque fois qu\'une de vos attaque inflige des Blessures, vous infligez 2 Blessures supplémentaires.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Toge sainte',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Vos attaques infligent 1 Blessure de moins et les Blessures que vous subissez sont réduites de 1.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Crucifix en argent',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Si vous attaquez et tuez un autre personnage, vous récupérez toutes ses cartes équipement.',
                'type' => 'light',
                'count' => 1,
            ),
            array(
                'name' => 'Amulette',
                'link' => '',
                'description' => 'Lorem Ipsum',
                'abilityMessage' => 'Équipement. Vous ne subissez aucune Blessure causées par les cartes Ténèbres: Araignée sanguinaire, Dynamite ou Chauve-souris vampire.',
                'type' => 'light',
                'count' => 1,
            ),
        ];

        foreach ($cardList as $cardData) {
            $card = new ActionCard();
            $card->setName($cardData['name']);
            $card->setLink($cardData['link']);
            $card->setDescription($cardData['description']);
            $card->setAbilityMessage($cardData['abilityMessage']);
            $card->setType(ActionCardType::from($cardData['type']));
            $card->setCount($cardData['count']);

            $manager->persist($card);
        }

        $manager->flush();
    }
}

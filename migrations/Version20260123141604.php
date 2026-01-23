<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123141604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clue (id INT AUTO_INCREMENT NOT NULL, resolution TINYINT DEFAULT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_268AADD1F624B39D (sender_id), INDEX IDX_268AADD1CD53EDB6 (receiver_id), INDEX IDX_268AADD14ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE place (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, current_damage SMALLINT NOT NULL, revealed TINYINT NOT NULL, location_id INT DEFAULT NULL, game_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_98197A6564D218E (location_id), INDEX IDX_98197A65E48FD905 (game_id), INDEX IDX_98197A65A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE clue ADD CONSTRAINT FK_268AADD1F624B39D FOREIGN KEY (sender_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE clue ADD CONSTRAINT FK_268AADD1CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE clue ADD CONSTRAINT FK_268AADD14ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A6564D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY `FK_5E9E89CBDEEE62D0`');
        $this->addSql('DROP INDEX IDX_5E9E89CBDEEE62D0 ON location');
        $this->addSql('ALTER TABLE location CHANGE holder_id place_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBDA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E9E89CBDA6A219 ON location (place_id)');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY `FK_B6BD307FF624B39D`');
        $this->addSql('DROP INDEX IDX_B6BD307FF624B39D ON message');
        $this->addSql('ALTER TABLE message DROP sender_id');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `FK_8D93D649E48FD905`');
        $this->addSql('DROP INDEX IDX_8D93D649E48FD905 ON user');
        $this->addSql('ALTER TABLE user DROP game_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clue DROP FOREIGN KEY FK_268AADD1F624B39D');
        $this->addSql('ALTER TABLE clue DROP FOREIGN KEY FK_268AADD1CD53EDB6');
        $this->addSql('ALTER TABLE clue DROP FOREIGN KEY FK_268AADD14ACC9A20');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A6564D218E');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65E48FD905');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65A76ED395');
        $this->addSql('DROP TABLE clue');
        $this->addSql('DROP TABLE place');
        $this->addSql('DROP TABLE player');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CBDA6A219');
        $this->addSql('DROP INDEX UNIQ_5E9E89CBDA6A219 ON location');
        $this->addSql('ALTER TABLE location CHANGE place_id holder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT `FK_5E9E89CBDEEE62D0` FOREIGN KEY (holder_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CBDEEE62D0 ON location (holder_id)');
        $this->addSql('ALTER TABLE message ADD sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT `FK_B6BD307FF624B39D` FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('ALTER TABLE user ADD game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT `FK_8D93D649E48FD905` FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649E48FD905 ON user (game_id)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205132034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abstract_card (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, link VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, ability_message VARCHAR(255) NOT NULL, object_type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE action_card (type VARCHAR(255) NOT NULL, count SMALLINT NOT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE character_card (type VARCHAR(255) NOT NULL, max_damage SMALLINT NOT NULL, initial VARCHAR(1) NOT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clue (id INT AUTO_INCREMENT NOT NULL, resolution TINYINT DEFAULT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_268AADD1F624B39D (sender_id), INDEX IDX_268AADD1CD53EDB6 (receiver_id), INDEX IDX_268AADD14ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, turn INT NOT NULL, owner_id INT NOT NULL, INDEX IDX_232B318C7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, location VARCHAR(255) NOT NULL, position SMALLINT DEFAULT NULL, game_id INT NOT NULL, action_card_id INT NOT NULL, player_id INT DEFAULT NULL, INDEX IDX_5E9E89CBE48FD905 (game_id), INDEX IDX_5E9E89CB906E12C1 (action_card_id), INDEX IDX_5E9E89CB99E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, card_id INT DEFAULT NULL, game_id INT NOT NULL, sender_id INT DEFAULT NULL, INDEX IDX_B6BD307F4ACC9A20 (card_id), INDEX IDX_B6BD307FE48FD905 (game_id), INDEX IDX_B6BD307FF624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE place_card (roll VARCHAR(255) NOT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, current_damage SMALLINT NOT NULL, revealed TINYINT NOT NULL, color VARCHAR(255) NOT NULL, playing_order SMALLINT DEFAULT NULL, game_id INT NOT NULL, user_id INT NOT NULL, position_id INT DEFAULT NULL, character_card_id INT DEFAULT NULL, INDEX IDX_98197A65E48FD905 (game_id), INDEX IDX_98197A65A76ED395 (user_id), INDEX IDX_98197A65DD842E46 (position_id), INDEX IDX_98197A6523691A1D (character_card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE position (id INT AUTO_INCREMENT NOT NULL, number SMALLINT NOT NULL, game_id INT NOT NULL, place_card_id INT DEFAULT NULL, INDEX IDX_462CE4F5E48FD905 (game_id), INDEX IDX_462CE4F57E008DDB (place_card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE refresh_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, revoked TINYINT DEFAULT 0 NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_C74F21955F37A13B (token), INDEX IDX_C74F2195A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_user (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_F7129A803AD8644E (user_source), INDEX IDX_F7129A80233D34C1 (user_target), PRIMARY KEY (user_source, user_target)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE action_card ADD CONSTRAINT FK_60F523DDBF396750 FOREIGN KEY (id) REFERENCES abstract_card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE character_card ADD CONSTRAINT FK_1E88C4BBF396750 FOREIGN KEY (id) REFERENCES abstract_card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clue ADD CONSTRAINT FK_268AADD1F624B39D FOREIGN KEY (sender_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE clue ADD CONSTRAINT FK_268AADD1CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE clue ADD CONSTRAINT FK_268AADD14ACC9A20 FOREIGN KEY (card_id) REFERENCES action_card (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB906E12C1 FOREIGN KEY (action_card_id) REFERENCES action_card (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F4ACC9A20 FOREIGN KEY (card_id) REFERENCES abstract_card (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE place_card ADD CONSTRAINT FK_DCA1E83FBF396750 FOREIGN KEY (id) REFERENCES abstract_card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65DD842E46 FOREIGN KEY (position_id) REFERENCES position (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A6523691A1D FOREIGN KEY (character_card_id) REFERENCES character_card (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F57E008DDB FOREIGN KEY (place_card_id) REFERENCES place_card (id)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A803AD8644E FOREIGN KEY (user_source) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A80233D34C1 FOREIGN KEY (user_target) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_card DROP FOREIGN KEY FK_60F523DDBF396750');
        $this->addSql('ALTER TABLE character_card DROP FOREIGN KEY FK_1E88C4BBF396750');
        $this->addSql('ALTER TABLE clue DROP FOREIGN KEY FK_268AADD1F624B39D');
        $this->addSql('ALTER TABLE clue DROP FOREIGN KEY FK_268AADD1CD53EDB6');
        $this->addSql('ALTER TABLE clue DROP FOREIGN KEY FK_268AADD14ACC9A20');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C7E3C61F9');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CBE48FD905');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB906E12C1');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB99E6F5DF');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F4ACC9A20');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE48FD905');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE place_card DROP FOREIGN KEY FK_DCA1E83FBF396750');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65E48FD905');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65A76ED395');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65DD842E46');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A6523691A1D');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F5E48FD905');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F57E008DDB');
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F2195A76ED395');
        $this->addSql('ALTER TABLE user_user DROP FOREIGN KEY FK_F7129A803AD8644E');
        $this->addSql('ALTER TABLE user_user DROP FOREIGN KEY FK_F7129A80233D34C1');
        $this->addSql('DROP TABLE abstract_card');
        $this->addSql('DROP TABLE action_card');
        $this->addSql('DROP TABLE character_card');
        $this->addSql('DROP TABLE clue');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE place_card');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_user');
    }
}

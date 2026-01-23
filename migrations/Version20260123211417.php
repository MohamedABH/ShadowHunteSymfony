<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123211417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD player_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB99E6F5DF ON location (player_id)');
        $this->addSql('ALTER TABLE player ADD character_card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A6523691A1D FOREIGN KEY (character_card_id) REFERENCES character_card (id)');
        $this->addSql('CREATE INDEX IDX_98197A6523691A1D ON player (character_card_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB99E6F5DF');
        $this->addSql('DROP INDEX IDX_5E9E89CB99E6F5DF ON location');
        $this->addSql('ALTER TABLE location DROP player_id');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A6523691A1D');
        $this->addSql('DROP INDEX IDX_98197A6523691A1D ON player');
        $this->addSql('ALTER TABLE player DROP character_card_id');
    }
}

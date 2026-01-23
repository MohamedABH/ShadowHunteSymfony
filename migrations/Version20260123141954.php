<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123141954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message ADD sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('ALTER TABLE player ADD place_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65DA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
        $this->addSql('CREATE INDEX IDX_98197A65DA6A219 ON player (place_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('DROP INDEX IDX_B6BD307FF624B39D ON message');
        $this->addSql('ALTER TABLE message DROP sender_id');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65DA6A219');
        $this->addSql('DROP INDEX IDX_98197A65DA6A219 ON player');
        $this->addSql('ALTER TABLE player DROP place_id');
    }
}

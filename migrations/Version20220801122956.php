<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220801122956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ADD commented_by_id FIELD TO comment TABLE';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment ADD commented_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C94F6F716 FOREIGN KEY (commented_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9474526C94F6F716 ON comment (commented_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C94F6F716');
        $this->addSql('DROP INDEX IDX_9474526C94F6F716 ON comment');
        $this->addSql('ALTER TABLE comment DROP commented_by_id');
    }
}

<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190213154543 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge ADD uom_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EBA103EEB1 FOREIGN KEY (uom_id) REFERENCES uom (id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EBA103EEB1 ON hedge (uom_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EBA103EEB1');
        $this->addSql('DROP INDEX IDX_3B22C7EBA103EEB1 ON hedge');
        $this->addSql('ALTER TABLE hedge DROP uom_id');
    }
}

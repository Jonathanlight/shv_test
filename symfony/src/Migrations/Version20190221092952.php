<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190221092952 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency ADD uom_id INT NOT NULL');
        $this->addSql('UPDATE currency SET uom_id = (SELECT id FROM uom LIMIT 1)');
        $this->addSql('ALTER TABLE currency ADD CONSTRAINT FK_6956883FA103EEB1 FOREIGN KEY (uom_id) REFERENCES uom (id)');
        $this->addSql('CREATE INDEX IDX_6956883FA103EEB1 ON currency (uom_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency DROP FOREIGN KEY FK_6956883FA103EEB1');
        $this->addSql('DROP INDEX IDX_6956883FA103EEB1 ON currency');
        $this->addSql('ALTER TABLE currency DROP uom_id');
    }
}

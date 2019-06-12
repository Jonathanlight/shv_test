<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190524151635 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp_sub_segment ADD currency_id INT NOT NULL');
        $this->addSql('UPDATE rmp_sub_segment SET currency_id = 1');
        $this->addSql('SET FOREIGN_KEY_CHECKS=0; ALTER TABLE rmp_sub_segment ADD CONSTRAINT FK_BAEB362C38248176 FOREIGN KEY (currency_id) REFERENCES currency (id); SET FOREIGN_KEY_CHECKS=1;');
        $this->addSql('CREATE INDEX IDX_BAEB362C38248176 ON rmp_sub_segment (currency_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp_sub_segment DROP FOREIGN KEY FK_BAEB362C38248176');
        $this->addSql('DROP INDEX IDX_BAEB362C38248176 ON rmp_sub_segment');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP currency_id');
    }
}

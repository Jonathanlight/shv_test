<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180928152105 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge_line ADD rmp_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE hedge_line ADD CONSTRAINT FK_3FD4992A64B5022B FOREIGN KEY (rmp_id) REFERENCES rmp (id)');
        $this->addSql('CREATE INDEX IDX_3FD4992A64B5022B ON hedge_line (rmp_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge_line DROP FOREIGN KEY FK_3FD4992A64B5022B');
        $this->addSql('DROP INDEX IDX_3FD4992A64B5022B ON hedge_line');
        $this->addSql('ALTER TABLE hedge_line DROP rmp_id');
    }
}

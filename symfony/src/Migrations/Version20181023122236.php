<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181023122236 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmplog ADD recipient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rmplog ADD CONSTRAINT FK_8DCBAFDEE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8DCBAFDEE92F8F78 ON rmplog (recipient_id)');
        $this->addSql('ALTER TABLE hedge_log ADD recipient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE hedge_log ADD CONSTRAINT FK_417398C7E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_417398C7E92F8F78 ON hedge_log (recipient_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge_log DROP FOREIGN KEY FK_417398C7E92F8F78');
        $this->addSql('DROP INDEX IDX_417398C7E92F8F78 ON hedge_log');
        $this->addSql('ALTER TABLE hedge_log DROP recipient_id');
        $this->addSql('ALTER TABLE rmplog DROP FOREIGN KEY FK_8DCBAFDEE92F8F78');
        $this->addSql('DROP INDEX IDX_8DCBAFDEE92F8F78 ON rmplog');
        $this->addSql('ALTER TABLE rmplog DROP recipient_id');
    }
}

<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180921130844 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge ADD first_maturity_id INT NOT NULL, ADD last_maturity_id INT NOT NULL');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EBCE812FD9 FOREIGN KEY (first_maturity_id) REFERENCES maturity (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB10425D17 FOREIGN KEY (last_maturity_id) REFERENCES maturity (id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EBCE812FD9 ON hedge (first_maturity_id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EB10425D17 ON hedge (last_maturity_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EBCE812FD9');
        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EB10425D17');
        $this->addSql('DROP INDEX IDX_3B22C7EBCE812FD9 ON hedge');
        $this->addSql('DROP INDEX IDX_3B22C7EB10425D17 ON hedge');
        $this->addSql('ALTER TABLE hedge DROP first_maturity_id, DROP last_maturity_id');
    }
}

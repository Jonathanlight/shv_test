<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180920150536 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge ADD price_risk_classification_id INT DEFAULT NULL, ADD operation_type INT NOT NULL');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EBE4D24DE9 FOREIGN KEY (price_risk_classification_id) REFERENCES price_risk_classification (id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EBE4D24DE9 ON hedge (price_risk_classification_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EBE4D24DE9');
        $this->addSql('DROP INDEX IDX_3B22C7EBE4D24DE9 ON hedge');
        $this->addSql('ALTER TABLE hedge DROP price_risk_classification_id, DROP operation_type');
    }
}

<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180924112034 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp DROP FOREIGN KEY FK_286201FAE4D24DE9');
        $this->addSql('DROP INDEX IDX_286201FAE4D24DE9 ON rmp');
        $this->addSql('ALTER TABLE rmp DROP price_risk_classification_id');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD price_risk_classification_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD CONSTRAINT FK_BAEB362CE4D24DE9 FOREIGN KEY (price_risk_classification_id) REFERENCES price_risk_classification (id)');
        $this->addSql('CREATE INDEX IDX_BAEB362CE4D24DE9 ON rmp_sub_segment (price_risk_classification_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp ADD price_risk_classification_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rmp ADD CONSTRAINT FK_286201FAE4D24DE9 FOREIGN KEY (price_risk_classification_id) REFERENCES price_risk_classification (id)');
        $this->addSql('CREATE INDEX IDX_286201FAE4D24DE9 ON rmp (price_risk_classification_id)');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP FOREIGN KEY FK_BAEB362CE4D24DE9');
        $this->addSql('DROP INDEX IDX_BAEB362CE4D24DE9 ON rmp_sub_segment');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP price_risk_classification_id');
    }
}

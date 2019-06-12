<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181129113205 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp ADD copied_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rmp ADD CONSTRAINT FK_286201FA58B20D94 FOREIGN KEY (copied_from_id) REFERENCES rmp (id)');
        $this->addSql('CREATE INDEX IDX_286201FA58B20D94 ON rmp (copied_from_id)');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD copied_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD CONSTRAINT FK_BAEB362C58B20D94 FOREIGN KEY (copied_from_id) REFERENCES rmp_sub_segment (id)');
        $this->addSql('CREATE INDEX IDX_BAEB362C58B20D94 ON rmp_sub_segment (copied_from_id)');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level ADD copied_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level ADD CONSTRAINT FK_9EFDCF2258B20D94 FOREIGN KEY (copied_from_id) REFERENCES rmp_sub_segment_risk_level (id)');
        $this->addSql('CREATE INDEX IDX_9EFDCF2258B20D94 ON rmp_sub_segment_risk_level (copied_from_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp DROP FOREIGN KEY FK_286201FA58B20D94');
        $this->addSql('DROP INDEX IDX_286201FA58B20D94 ON rmp');
        $this->addSql('ALTER TABLE rmp DROP copied_from_id');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP FOREIGN KEY FK_BAEB362C58B20D94');
        $this->addSql('DROP INDEX IDX_BAEB362C58B20D94 ON rmp_sub_segment');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP copied_from_id');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level DROP FOREIGN KEY FK_9EFDCF2258B20D94');
        $this->addSql('DROP INDEX IDX_9EFDCF2258B20D94 ON rmp_sub_segment_risk_level');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level DROP copied_from_id');
    }
}

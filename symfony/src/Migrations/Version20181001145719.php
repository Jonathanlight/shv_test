<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181001145719 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rmp_sub_segment_risk_level (id INT AUTO_INCREMENT NOT NULL, rmp_sub_segment_id INT NOT NULL, consumption DOUBLE PRECISION NOT NULL, `limit` DOUBLE PRECISION NOT NULL, risk_level INT NOT NULL, INDEX IDX_9EFDCF22E9D2D08B (rmp_sub_segment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level ADD CONSTRAINT FK_9EFDCF22E9D2D08B FOREIGN KEY (rmp_sub_segment_id) REFERENCES rmp_sub_segment (id)');
        $this->addSql('DROP TABLE rmp_sub_segment_hedging_tool');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD sales_volume DOUBLE PRECISION NOT NULL, ADD maximum_volume DOUBLE PRECISION NOT NULL, ADD ratio_maximum_volume_sales DOUBLE PRECISION NOT NULL, ADD maximum_loss DOUBLE PRECISION NOT NULL, ADD product_category VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rmp_sub_segment_hedging_tool (id INT AUTO_INCREMENT NOT NULL, hedging_tool_id INT NOT NULL, rmp_sub_segment_id INT NOT NULL, consumption DOUBLE PRECISION NOT NULL, `limit` DOUBLE PRECISION NOT NULL, INDEX IDX_3B6598C9823E3243 (hedging_tool_id), INDEX IDX_3B6598C9E9D2D08B (rmp_sub_segment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rmp_sub_segment_hedging_tool ADD CONSTRAINT FK_3B6598C9823E3243 FOREIGN KEY (hedging_tool_id) REFERENCES hedging_tool (id)');
        $this->addSql('ALTER TABLE rmp_sub_segment_hedging_tool ADD CONSTRAINT FK_3B6598C9E9D2D08B FOREIGN KEY (rmp_sub_segment_id) REFERENCES rmp_sub_segment (id)');
        $this->addSql('DROP TABLE rmp_sub_segment_risk_level');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP sales_volume, DROP maximum_volume, DROP ratio_maximum_volume_sales, DROP maximum_loss, DROP product_category');
    }
}

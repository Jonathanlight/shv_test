<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180914155137 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rmp_sub_segment_hedging_tool (id INT AUTO_INCREMENT NOT NULL, hedging_tool_id INT NOT NULL, rmp_sub_segment_id INT NOT NULL, consumption DOUBLE PRECISION NOT NULL, `limit` DOUBLE PRECISION NOT NULL, INDEX IDX_3B6598C9823E3243 (hedging_tool_id), INDEX IDX_3B6598C9E9D2D08B (rmp_sub_segment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hedge (id INT AUTO_INCREMENT NOT NULL, creator_id INT NOT NULL, validator_level1_id INT DEFAULT NULL, validator_level2_id INT DEFAULT NULL, trader_id INT DEFAULT NULL, product1_id INT DEFAULT NULL, product2_id INT DEFAULT NULL, rmp_id INT DEFAULT NULL, currency_id INT NOT NULL, INDEX IDX_3B22C7EB61220EA6 (creator_id), INDEX IDX_3B22C7EB5A868283 (validator_level1_id), INDEX IDX_3B22C7EB48332D6D (validator_level2_id), INDEX IDX_3B22C7EB1273968F (trader_id), INDEX IDX_3B22C7EB5D97111F (product1_id), INDEX IDX_3B22C7EB4F22BEF1 (product2_id), INDEX IDX_3B22C7EB64B5022B (rmp_id), INDEX IDX_3B22C7EB38248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rmp_sub_segment_hedging_tool ADD CONSTRAINT FK_3B6598C9823E3243 FOREIGN KEY (hedging_tool_id) REFERENCES hedging_tool (id)');
        $this->addSql('ALTER TABLE rmp_sub_segment_hedging_tool ADD CONSTRAINT FK_3B6598C9E9D2D08B FOREIGN KEY (rmp_sub_segment_id) REFERENCES rmp_sub_segment (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB5A868283 FOREIGN KEY (validator_level1_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB48332D6D FOREIGN KEY (validator_level2_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB1273968F FOREIGN KEY (trader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB5D97111F FOREIGN KEY (product1_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB4F22BEF1 FOREIGN KEY (product2_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB64B5022B FOREIGN KEY (rmp_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB38248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE rmp_sub_segment_hedging_tool');
        $this->addSql('DROP TABLE hedge');
    }
}

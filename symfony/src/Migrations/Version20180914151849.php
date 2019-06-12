<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180914151849 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, role VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, function VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_business_unit (user_id INT NOT NULL, business_unit_id INT NOT NULL, INDEX IDX_62AEA30EA76ED395 (user_id), INDEX IDX_62AEA30EA58ECB40 (business_unit_id), PRIMARY KEY(user_id, business_unit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp (id INT AUTO_INCREMENT NOT NULL, business_unit_id INT DEFAULT NULL, price_risk_classification_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, general_comment LONGTEXT DEFAULT NULL, validity_period INT NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_286201FAA58ECB40 (business_unit_id), INDEX IDX_286201FAE4D24DE9 (price_risk_classification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp_history (id INT AUTO_INCREMENT NOT NULL, rmp_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, INDEX IDX_2FA3589464B5022B (rmp_id), INDEX IDX_2FA35894A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp_sub_segment (id INT AUTO_INCREMENT NOT NULL, rmp_id INT NOT NULL, sub_segment_id INT NOT NULL, INDEX IDX_BAEB362C64B5022B (rmp_id), INDEX IDX_BAEB362CF7E2F960 (sub_segment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp_sub_segment_product (rmp_sub_segment_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_E49CF955E9D2D08B (rmp_sub_segment_id), INDEX IDX_E49CF9554584665A (product_id), PRIMARY KEY(rmp_sub_segment_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_business_unit ADD CONSTRAINT FK_62AEA30EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_business_unit ADD CONSTRAINT FK_62AEA30EA58ECB40 FOREIGN KEY (business_unit_id) REFERENCES business_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rmp ADD CONSTRAINT FK_286201FAA58ECB40 FOREIGN KEY (business_unit_id) REFERENCES business_unit (id)');
        $this->addSql('ALTER TABLE rmp ADD CONSTRAINT FK_286201FAE4D24DE9 FOREIGN KEY (price_risk_classification_id) REFERENCES price_risk_classification (id)');
        $this->addSql('ALTER TABLE rmp_history ADD CONSTRAINT FK_2FA3589464B5022B FOREIGN KEY (rmp_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmp_history ADD CONSTRAINT FK_2FA35894A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD CONSTRAINT FK_BAEB362C64B5022B FOREIGN KEY (rmp_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD CONSTRAINT FK_BAEB362CF7E2F960 FOREIGN KEY (sub_segment_id) REFERENCES sub_segment (id)');
        $this->addSql('ALTER TABLE rmp_sub_segment_product ADD CONSTRAINT FK_E49CF955E9D2D08B FOREIGN KEY (rmp_sub_segment_id) REFERENCES rmp_sub_segment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rmp_sub_segment_product ADD CONSTRAINT FK_E49CF9554584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_business_unit DROP FOREIGN KEY FK_62AEA30EA76ED395');
        $this->addSql('ALTER TABLE rmp_history DROP FOREIGN KEY FK_2FA35894A76ED395');
        $this->addSql('ALTER TABLE rmp_history DROP FOREIGN KEY FK_2FA3589464B5022B');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP FOREIGN KEY FK_BAEB362C64B5022B');
        $this->addSql('ALTER TABLE rmp_sub_segment_product DROP FOREIGN KEY FK_E49CF955E9D2D08B');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_business_unit');
        $this->addSql('DROP TABLE rmp');
        $this->addSql('DROP TABLE rmp_history');
        $this->addSql('DROP TABLE rmp_sub_segment');
        $this->addSql('DROP TABLE rmp_sub_segment_product');
        $this->addSql('ALTER TABLE product DROP active');
    }
}

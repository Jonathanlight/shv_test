<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190207092243 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE conversion_table (id INT AUTO_INCREMENT NOT NULL, commodity_id INT NOT NULL, uom_id INT NOT NULL, value NUMERIC(10, 0) NOT NULL, INDEX IDX_53433336B4ACC212 (commodity_id), INDEX IDX_53433336A103EEB1 (uom_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commodity (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conversion_table ADD CONSTRAINT FK_53433336B4ACC212 FOREIGN KEY (commodity_id) REFERENCES commodity (id)');
        $this->addSql('ALTER TABLE conversion_table ADD CONSTRAINT FK_53433336A103EEB1 FOREIGN KEY (uom_id) REFERENCES uom (id)');
        $this->addSql('ALTER TABLE product ADD commodity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB4ACC212 FOREIGN KEY (commodity_id) REFERENCES commodity (id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADB4ACC212 ON product (commodity_id)');
        $this->addSql('ALTER TABLE conversion_table CHANGE value value NUMERIC(20, 18) NOT NULL');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB4ACC212');
        $this->addSql('ALTER TABLE conversion_table DROP FOREIGN KEY FK_53433336B4ACC212');
        $this->addSql('DROP TABLE conversion_table');
        $this->addSql('DROP TABLE commodity');
        $this->addSql('DROP INDEX IDX_D34A04ADB4ACC212 ON product');
        $this->addSql('ALTER TABLE product DROP commodity_id');
        $this->addSql('ALTER TABLE conversion_table CHANGE value value NUMERIC(10, 0) NOT NULL');

    }
}

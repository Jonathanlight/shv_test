<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190228140704 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hedge_alert (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT DEFAULT NULL, timestamp DATETIME NOT NULL, type INT NOT NULL, `read` TINYINT(1) NOT NULL, INDEX IDX_98761813727ACA70 (parent_id), INDEX IDX_98761813A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp_alert (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT DEFAULT NULL, timestamp DATETIME NOT NULL, type INT NOT NULL, `read` TINYINT(1) NOT NULL, INDEX IDX_B3DD9142727ACA70 (parent_id), INDEX IDX_B3DD9142A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hedge_alert ADD CONSTRAINT FK_98761813727ACA70 FOREIGN KEY (parent_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge_alert ADD CONSTRAINT FK_98761813A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rmp_alert ADD CONSTRAINT FK_B3DD9142727ACA70 FOREIGN KEY (parent_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmp_alert ADD CONSTRAINT FK_B3DD9142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE hedge_alert');
        $this->addSql('DROP TABLE rmp_alert');
    }
}

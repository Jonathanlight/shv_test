<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190315091143 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hedge_alert_user (id INT AUTO_INCREMENT NOT NULL, alert_id INT NOT NULL, user_id INT DEFAULT NULL, is_read TINYINT(1) NOT NULL, viewed TINYINT(1) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_8BBF208093035F72 (alert_id), INDEX IDX_8BBF2080A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp_alert_user (id INT AUTO_INCREMENT NOT NULL, alert_id INT NOT NULL, user_id INT DEFAULT NULL, is_read TINYINT(1) NOT NULL, viewed TINYINT(1) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_BDC070A293035F72 (alert_id), INDEX IDX_BDC070A2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hedge_alert_user ADD CONSTRAINT FK_8BBF208093035F72 FOREIGN KEY (alert_id) REFERENCES hedge_alert (id)');
        $this->addSql('ALTER TABLE hedge_alert_user ADD CONSTRAINT FK_8BBF2080A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rmp_alert_user ADD CONSTRAINT FK_BDC070A293035F72 FOREIGN KEY (alert_id) REFERENCES rmp_alert (id)');
        $this->addSql('ALTER TABLE rmp_alert_user ADD CONSTRAINT FK_BDC070A2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge_alert DROP FOREIGN KEY FK_98761813A76ED395');
        $this->addSql('DROP INDEX IDX_98761813A76ED395 ON hedge_alert');
        $this->addSql('ALTER TABLE hedge_alert DROP user_id, DROP is_read, DROP viewed');
        $this->addSql('ALTER TABLE rmp_alert DROP FOREIGN KEY FK_B3DD9142A76ED395');
        $this->addSql('DROP INDEX IDX_B3DD9142A76ED395 ON rmp_alert');
        $this->addSql('ALTER TABLE rmp_alert DROP user_id, DROP is_read, DROP viewed');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE hedge_alert_user');
        $this->addSql('DROP TABLE rmp_alert_user');
        $this->addSql('ALTER TABLE hedge_alert ADD user_id INT DEFAULT NULL, ADD is_read TINYINT(1) NOT NULL, ADD viewed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE hedge_alert ADD CONSTRAINT FK_98761813A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_98761813A76ED395 ON hedge_alert (user_id)');
        $this->addSql('ALTER TABLE rmp_alert ADD user_id INT DEFAULT NULL, ADD is_read TINYINT(1) NOT NULL, ADD viewed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE rmp_alert ADD CONSTRAINT FK_B3DD9142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B3DD9142A76ED395 ON rmp_alert (user_id)');
    }
}

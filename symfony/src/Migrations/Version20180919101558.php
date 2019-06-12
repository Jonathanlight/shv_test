<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180919101558 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rmplog (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_8DCBAFDE727ACA70 (parent_id), INDEX IDX_8DCBAFDEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmpcomment (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_195247A2727ACA70 (parent_id), INDEX IDX_195247A2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hedge_comment (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_ABA90689727ACA70 (parent_id), INDEX IDX_ABA90689A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hedge_log (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_417398C7727ACA70 (parent_id), INDEX IDX_417398C7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rmplog ADD CONSTRAINT FK_8DCBAFDE727ACA70 FOREIGN KEY (parent_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmplog ADD CONSTRAINT FK_8DCBAFDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rmpcomment ADD CONSTRAINT FK_195247A2727ACA70 FOREIGN KEY (parent_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmpcomment ADD CONSTRAINT FK_195247A2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge_comment ADD CONSTRAINT FK_ABA90689727ACA70 FOREIGN KEY (parent_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge_comment ADD CONSTRAINT FK_ABA90689A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge_log ADD CONSTRAINT FK_417398C7727ACA70 FOREIGN KEY (parent_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge_log ADD CONSTRAINT FK_417398C7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE hedge_history');
        $this->addSql('DROP TABLE rmp_history');
        $this->addSql('ALTER TABLE user DROP role');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hedge_id INT NOT NULL, message LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526C922DFA27 (hedge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hedge_history (id INT AUTO_INCREMENT NOT NULL, hedge_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, timestamp DATETIME NOT NULL, INDEX IDX_186724AE922DFA27 (hedge_id), INDEX IDX_186724AEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rmp_history (id INT AUTO_INCREMENT NOT NULL, rmp_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, timestamp DATETIME NOT NULL, INDEX IDX_2FA3589464B5022B (rmp_id), INDEX IDX_2FA35894A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C922DFA27 FOREIGN KEY (hedge_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge_history ADD CONSTRAINT FK_186724AE922DFA27 FOREIGN KEY (hedge_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge_history ADD CONSTRAINT FK_186724AEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rmp_history ADD CONSTRAINT FK_2FA3589464B5022B FOREIGN KEY (rmp_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmp_history ADD CONSTRAINT FK_2FA35894A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE rmplog');
        $this->addSql('DROP TABLE rmpcomment');
        $this->addSql('DROP TABLE hedge_comment');
        $this->addSql('DROP TABLE hedge_log');
        $this->addSql('ALTER TABLE user ADD role VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}

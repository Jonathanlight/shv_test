<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180917085025 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hedge_history (id INT AUTO_INCREMENT NOT NULL, hedge_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_186724AE922DFA27 (hedge_id), INDEX IDX_186724AEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hedge_line (id INT AUTO_INCREMENT NOT NULL, hedge_id INT NOT NULL, maturity_id INT NOT NULL, strategy_id INT NOT NULL, quantity DOUBLE PRECISION NOT NULL, INDEX IDX_3FD4992A922DFA27 (hedge_id), INDEX IDX_3FD4992A5074221B (maturity_id), INDEX IDX_3FD4992AD5CAD932 (strategy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hedge_id INT NOT NULL, message LONGTEXT NOT NULL, INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526C922DFA27 (hedge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hedge_history ADD CONSTRAINT FK_186724AE922DFA27 FOREIGN KEY (hedge_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge_history ADD CONSTRAINT FK_186724AEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge_line ADD CONSTRAINT FK_3FD4992A922DFA27 FOREIGN KEY (hedge_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge_line ADD CONSTRAINT FK_3FD4992A5074221B FOREIGN KEY (maturity_id) REFERENCES maturity (id)');
        $this->addSql('ALTER TABLE hedge_line ADD CONSTRAINT FK_3FD4992AD5CAD932 FOREIGN KEY (strategy_id) REFERENCES strategy (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C922DFA27 FOREIGN KEY (hedge_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE user ADD username VARCHAR(180) NOT NULL, ADD username_canonical VARCHAR(180) NOT NULL, ADD email_canonical VARCHAR(180) NOT NULL, ADD enabled TINYINT(1) NOT NULL, ADD salt VARCHAR(255) DEFAULT NULL, ADD password VARCHAR(255) NOT NULL, ADD last_login DATETIME DEFAULT NULL, ADD confirmation_token VARCHAR(180) DEFAULT NULL, ADD password_requested_at DATETIME DEFAULT NULL, ADD roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64992FC23A8 ON user (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A0D96FBF ON user (email_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
        $this->addSql('ALTER TABLE rmp_history ADD timestamp DATETIME NOT NULL');
        $this->addSql('ALTER TABLE hedge ADD hedging_tool_id INT NOT NULL');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB823E3243 FOREIGN KEY (hedging_tool_id) REFERENCES hedging_tool (id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EB823E3243 ON hedge (hedging_tool_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE hedge_history');
        $this->addSql('DROP TABLE hedge_line');
        $this->addSql('DROP TABLE comment');
        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EB823E3243');
        $this->addSql('DROP INDEX IDX_3B22C7EB823E3243 ON hedge');
        $this->addSql('ALTER TABLE hedge DROP hedging_tool_id');
        $this->addSql('ALTER TABLE rmp_history DROP timestamp');
        $this->addSql('DROP INDEX UNIQ_8D93D64992FC23A8 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649A0D96FBF ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649C05FB297 ON user');
        $this->addSql('ALTER TABLE user DROP username, DROP username_canonical, DROP email_canonical, DROP enabled, DROP salt, DROP password, DROP last_login, DROP confirmation_token, DROP password_requested_at, DROP roles, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}

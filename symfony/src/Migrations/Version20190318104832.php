<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190318104832 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hedge_validation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hedge_id INT NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_9F307F23A76ED395 (user_id), INDEX IDX_9F307F23922DFA27 (hedge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hedge_validation ADD CONSTRAINT FK_9F307F23A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hedge_validation ADD CONSTRAINT FK_9F307F23922DFA27 FOREIGN KEY (hedge_id) REFERENCES hedge (id)');
        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EB48332D6D');
        $this->addSql('DROP INDEX IDX_3B22C7EB48332D6D ON hedge');
        $this->addSql('ALTER TABLE hedge DROP validator_level2_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE hedge_validation');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EB48332D6D FOREIGN KEY (validator_level2_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EB48332D6D ON hedge (validator_level2_id)');
    }
}

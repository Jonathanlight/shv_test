<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180919102145 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rmp_sub_segment_comment (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT NOT NULL, timestamp DATETIME NOT NULL, INDEX IDX_A3A2AF94727ACA70 (parent_id), INDEX IDX_A3A2AF94A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rmp_sub_segment_comment ADD CONSTRAINT FK_A3A2AF94727ACA70 FOREIGN KEY (parent_id) REFERENCES rmp_sub_segment (id)');
        $this->addSql('ALTER TABLE rmp_sub_segment_comment ADD CONSTRAINT FK_A3A2AF94A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE rmpcomment');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rmpcomment (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, timestamp DATETIME NOT NULL, INDEX IDX_195247A2727ACA70 (parent_id), INDEX IDX_195247A2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rmpcomment ADD CONSTRAINT FK_195247A2727ACA70 FOREIGN KEY (parent_id) REFERENCES rmp (id)');
        $this->addSql('ALTER TABLE rmpcomment ADD CONSTRAINT FK_195247A2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE rmp_sub_segment_comment');
    }
}

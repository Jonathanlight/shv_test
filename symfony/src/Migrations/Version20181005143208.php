<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181005143208 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge ADD canceler_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE hedge ADD CONSTRAINT FK_3B22C7EBF90DF39E FOREIGN KEY (canceler_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3B22C7EBF90DF39E ON hedge (canceler_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge DROP FOREIGN KEY FK_3B22C7EBF90DF39E');
        $this->addSql('DROP INDEX IDX_3B22C7EBF90DF39E ON hedge');
        $this->addSql('ALTER TABLE hedge DROP canceler_id');
    }
}

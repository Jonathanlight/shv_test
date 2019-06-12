<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181129103407 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp ADD approved_automatically TINYINT(1) NOT NULL, ADD version INT NOT NULL');
        $this->addSql('ALTER TABLE rmp_sub_segment ADD version INT NOT NULL');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level ADD version INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rmp DROP approved_automatically, DROP version');
        $this->addSql('ALTER TABLE rmp_sub_segment DROP version');
        $this->addSql('ALTER TABLE rmp_sub_segment_risk_level DROP version');
    }
}

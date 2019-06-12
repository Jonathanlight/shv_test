<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181017125549 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge ADD waiver_product TINYINT(1) NOT NULL, ADD waiver_class_risk_level TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE hedge_line ADD waiver_volume TINYINT(1) NOT NULL, ADD waiver_maturity TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge DROP waiver_product, DROP waiver_class_risk_level');
        $this->addSql('ALTER TABLE hedge_line DROP waiver_volume, DROP waiver_maturity');
    }
}

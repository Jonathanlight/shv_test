<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181016144925 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge_line ADD swap_price DOUBLE PRECISION DEFAULT NULL, ADD swap1price DOUBLE PRECISION DEFAULT NULL, ADD swap2price DOUBLE PRECISION DEFAULT NULL, ADD call_strike DOUBLE PRECISION DEFAULT NULL, ADD call1strike DOUBLE PRECISION DEFAULT NULL, ADD call2strike DOUBLE PRECISION DEFAULT NULL, ADD call_premium DOUBLE PRECISION DEFAULT NULL, ADD call1premium DOUBLE PRECISION DEFAULT NULL, ADD call2premium DOUBLE PRECISION DEFAULT NULL, ADD put_premium DOUBLE PRECISION DEFAULT NULL, ADD put1premium DOUBLE PRECISION DEFAULT NULL, ADD put2premium DOUBLE PRECISION DEFAULT NULL, ADD put_strike DOUBLE PRECISION DEFAULT NULL, ADD put1strike DOUBLE PRECISION DEFAULT NULL, ADD put2strike DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hedge_line DROP swap_price, DROP swap1price, DROP swap2price, DROP call_strike, DROP call1strike, DROP call2strike, DROP call_premium, DROP call1premium, DROP call2premium, DROP put_premium, DROP put1premium, DROP put2premium, DROP put_strike, DROP put1strike, DROP put2strike');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20251223193544
 * 
 * The default database schema migration
 * 
 * @package DoctrineMigrations
 */
final class Version20251223193544 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Base database migration';
    }

    /**
     * Execute the migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inbox_messages (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, time DATETIME NOT NULL, ip_address VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, visitor_id INT DEFAULT NULL, INDEX inbox_messages_name_idx (name), INDEX inbox_messages_email_idx (email), INDEX inbox_messages_status_idx (status), INDEX inbox_messages_ip_address_idx (ip_address), INDEX inbox_messages_visitor_id_idx (visitor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, time DATETIME NOT NULL, ip_address VARCHAR(255) NOT NULL, browser VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, visitor_id INT DEFAULT NULL, INDEX IDX_F08FC65C70BEE6D (visitor_id), INDEX logs_name_idx (name), INDEX logs_status_idx (status), INDEX logs_ip_address_idx (ip_address), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, token VARCHAR(255) NOT NULL, registered_time DATETIME NOT NULL, last_login_time DATETIME DEFAULT NULL, profile_pic LONGTEXT NOT NULL, visitor_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E95F37A13B (token), INDEX users_role_idx (role), INDEX users_token_idx (token), INDEX users_name_idx (username), INDEX users_ip_address_idx (ip_address), INDEX users_visitor_id_idx (visitor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE visitors (id INT AUTO_INCREMENT NOT NULL, first_visit DATETIME NOT NULL, last_visit DATETIME NOT NULL, first_visit_site VARCHAR(255) NOT NULL, browser VARCHAR(255) NOT NULL, os VARCHAR(255) NOT NULL, referer VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, banned_status TINYINT NOT NULL, ban_reason VARCHAR(255) NOT NULL, banned_time DATETIME DEFAULT NULL, email VARCHAR(255) NOT NULL, INDEX visitors_email_idx (email), INDEX visitors_ip_address_idx (ip_address), INDEX visitors_banned_status_idx (banned_status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE inbox_messages ADD CONSTRAINT FK_F635C51D70BEE6D FOREIGN KEY (visitor_id) REFERENCES visitors (id)');
        $this->addSql('ALTER TABLE logs ADD CONSTRAINT FK_F08FC65C70BEE6D FOREIGN KEY (visitor_id) REFERENCES visitors (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E970BEE6D FOREIGN KEY (visitor_id) REFERENCES visitors (id)');
    }

    /**
     * Undo the migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inbox_messages DROP FOREIGN KEY FK_F635C51D70BEE6D');
        $this->addSql('ALTER TABLE logs DROP FOREIGN KEY FK_F08FC65C70BEE6D');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E970BEE6D');
        $this->addSql('DROP TABLE inbox_messages');
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE visitors');
    }
}

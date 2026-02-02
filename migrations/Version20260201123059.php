<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201123059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chats (id INT AUTO_INCREMENT NOT NULL, fecha_creacion DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invitaciones (id INT AUTO_INCREMENT NOT NULL, fecha_invitacion DATETIME NOT NULL, id_usuario_remitente_id INT NOT NULL, id_usuario_receptor_id INT NOT NULL, chat_id_id INT NOT NULL, INDEX IDX_2808E1001AF6E02E (id_usuario_remitente_id), INDEX IDX_2808E10046ACE344 (id_usuario_receptor_id), INDEX IDX_2808E1007E3973CC (chat_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mensajes (id INT AUTO_INCREMENT NOT NULL, contenido LONGTEXT NOT NULL, imagen VARCHAR(255) DEFAULT NULL, fecha_hora DATETIME NOT NULL, nombre_usuario_id INT NOT NULL, chat_perteneciente_id INT NOT NULL, INDEX IDX_6C929C8026AB182E (nombre_usuario_id), INDEX IDX_6C929C809B498984 (chat_perteneciente_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nombre VARCHAR(100) NOT NULL, token VARCHAR(255) NOT NULL, latitud DOUBLE PRECISION NOT NULL, longitud DOUBLE PRECISION NOT NULL, baneado TINYINT NOT NULL, avatar VARCHAR(255) DEFAULT NULL, biografia LONGTEXT DEFAULT NULL, fecha_creacion DATETIME NOT NULL, activo TINYINT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_chats (user_id INT NOT NULL, chats_id INT NOT NULL, INDEX IDX_CFAAE357A76ED395 (user_id), INDEX IDX_CFAAE357AC6FF313 (chats_id), PRIMARY KEY (user_id, chats_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_user (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_F7129A803AD8644E (user_source), INDEX IDX_F7129A80233D34C1 (user_target), PRIMARY KEY (user_source, user_target)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invitaciones ADD CONSTRAINT FK_2808E1001AF6E02E FOREIGN KEY (id_usuario_remitente_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invitaciones ADD CONSTRAINT FK_2808E10046ACE344 FOREIGN KEY (id_usuario_receptor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invitaciones ADD CONSTRAINT FK_2808E1007E3973CC FOREIGN KEY (chat_id_id) REFERENCES chats (id)');
        $this->addSql('ALTER TABLE mensajes ADD CONSTRAINT FK_6C929C8026AB182E FOREIGN KEY (nombre_usuario_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE mensajes ADD CONSTRAINT FK_6C929C809B498984 FOREIGN KEY (chat_perteneciente_id) REFERENCES chats (id)');
        $this->addSql('ALTER TABLE user_chats ADD CONSTRAINT FK_CFAAE357A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_chats ADD CONSTRAINT FK_CFAAE357AC6FF313 FOREIGN KEY (chats_id) REFERENCES chats (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A803AD8644E FOREIGN KEY (user_source) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A80233D34C1 FOREIGN KEY (user_target) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invitaciones DROP FOREIGN KEY FK_2808E1001AF6E02E');
        $this->addSql('ALTER TABLE invitaciones DROP FOREIGN KEY FK_2808E10046ACE344');
        $this->addSql('ALTER TABLE invitaciones DROP FOREIGN KEY FK_2808E1007E3973CC');
        $this->addSql('ALTER TABLE mensajes DROP FOREIGN KEY FK_6C929C8026AB182E');
        $this->addSql('ALTER TABLE mensajes DROP FOREIGN KEY FK_6C929C809B498984');
        $this->addSql('ALTER TABLE user_chats DROP FOREIGN KEY FK_CFAAE357A76ED395');
        $this->addSql('ALTER TABLE user_chats DROP FOREIGN KEY FK_CFAAE357AC6FF313');
        $this->addSql('ALTER TABLE user_user DROP FOREIGN KEY FK_F7129A803AD8644E');
        $this->addSql('ALTER TABLE user_user DROP FOREIGN KEY FK_F7129A80233D34C1');
        $this->addSql('DROP TABLE chats');
        $this->addSql('DROP TABLE invitaciones');
        $this->addSql('DROP TABLE mensajes');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_chats');
        $this->addSql('DROP TABLE user_user');
    }
}

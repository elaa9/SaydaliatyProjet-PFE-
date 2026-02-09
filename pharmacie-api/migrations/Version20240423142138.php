<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240423142138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE customer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE delivery_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "order_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pharmacist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pharmacy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE prescription_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE customer (id INT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, phone_number VARCHAR(50) NOT NULL, address VARCHAR(50) NOT NULL, blocked BOOLEAN NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE delivery (id INT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, phone_number VARCHAR(50) NOT NULL, blocked BOOLEAN NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "order" (id INT NOT NULL, customer_id INT DEFAULT NULL, pharmacist_id INT DEFAULT NULL, delivery_id INT DEFAULT NULL, product_id INT DEFAULT NULL, prescription_id INT DEFAULT NULL, creation_date DATE NOT NULL, registration_number VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, statue BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F52993989395C3F3 ON "order" (customer_id)');
        $this->addSql('CREATE INDEX IDX_F52993981FDF4367 ON "order" (pharmacist_id)');
        $this->addSql('CREATE INDEX IDX_F529939812136921 ON "order" (delivery_id)');
        $this->addSql('CREATE INDEX IDX_F52993984584665A ON "order" (product_id)');
        $this->addSql('CREATE INDEX IDX_F529939893DB413D ON "order" (prescription_id)');
        $this->addSql('COMMENT ON COLUMN "order".creation_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE pharmacist (id INT NOT NULL, pharmacy_id INT DEFAULT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B819DA5E8A94ABE2 ON pharmacist (pharmacy_id)');
        $this->addSql('CREATE TABLE pharmacy (id INT NOT NULL, name VARCHAR(50) NOT NULL, address VARCHAR(100) NOT NULL, city VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE prescription (id INT NOT NULL, date_issue DATE NOT NULL, picture VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN prescription.date_issue IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE product (id INT NOT NULL, category_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, description VARCHAR(200) NOT NULL, registration_number VARCHAR(100) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
        $this->addSql('CREATE TABLE product_category (id INT NOT NULL, name VARCHAR(255) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN product_category.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993981FDF4367 FOREIGN KEY (pharmacist_id) REFERENCES pharmacist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939812136921 FOREIGN KEY (delivery_id) REFERENCES delivery (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939893DB413D FOREIGN KEY (prescription_id) REFERENCES prescription (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pharmacist ADD CONSTRAINT FK_B819DA5E8A94ABE2 FOREIGN KEY (pharmacy_id) REFERENCES pharmacy (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES product_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD full_name VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER INDEX uniq_8d93d649e7927c74 RENAME TO UNIQ_IDENTIFIER_EMAIL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE customer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE delivery_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "order_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE pharmacist_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pharmacy_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE prescription_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_category_id_seq CASCADE');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993981FDF4367');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939812136921');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993984584665A');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939893DB413D');
        $this->addSql('ALTER TABLE pharmacist DROP CONSTRAINT FK_B819DA5E8A94ABE2');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04AD12469DE2');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE delivery');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE pharmacist');
        $this->addSql('DROP TABLE pharmacy');
        $this->addSql('DROP TABLE prescription');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_category');
        $this->addSql('ALTER TABLE "user" DROP full_name');
        $this->addSql('ALTER TABLE "user" DROP created_at');
        $this->addSql('ALTER TABLE "user" DROP updated_at');
        $this->addSql('ALTER INDEX uniq_identifier_email RENAME TO uniq_8d93d649e7927c74');
    }
}

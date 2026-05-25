-- ============================================================
--  FamBudget — script MySQL completo
--  Generato il 2026-05-25
--  Compatibile con MySQL 8.0+
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
--  Struttura tabelle
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `budget_amounts`;
DROP TABLE IF EXISTS `budget_expenses`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `budget_categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `users` (
  `id`                bigint unsigned NOT NULL AUTO_INCREMENT,
  `name`              varchar(255) NOT NULL,
  `email`             varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password`          varchar(255) NOT NULL,
  `remember_token`    varchar(100) DEFAULT NULL,
  `created_at`        timestamp NULL DEFAULT NULL,
  `updated_at`        timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email`      varchar(255) NOT NULL,
  `token`      varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id`            varchar(255) NOT NULL,
  `user_id`       bigint unsigned DEFAULT NULL,
  `ip_address`    varchar(45) DEFAULT NULL,
  `user_agent`    text,
  `payload`       longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key`        varchar(255) NOT NULL,
  `value`      mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key`        varchar(255) NOT NULL,
  `owner`      varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id`           bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue`        varchar(255) NOT NULL,
  `payload`      longtext NOT NULL,
  `attempts`     tinyint unsigned NOT NULL,
  `reserved_at`  int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at`   int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id`             varchar(255) NOT NULL,
  `name`           varchar(255) NOT NULL,
  `total_jobs`     int NOT NULL,
  `pending_jobs`   int NOT NULL,
  `failed_jobs`    int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options`        mediumtext,
  `cancelled_at`   int DEFAULT NULL,
  `created_at`     int NOT NULL,
  `finished_at`    int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid`       varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue`      text NOT NULL,
  `payload`    longtext NOT NULL,
  `exception`  longtext NOT NULL,
  `failed_at`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `budget_categories` (
  `id`               bigint unsigned NOT NULL AUTO_INCREMENT,
  `categoria`        varchar(255) NOT NULL,
  `nome`             varchar(255) NOT NULL,
  `importo_annuale`  decimal(10,2) NOT NULL DEFAULT '0.00',
  `importo_mensile`  decimal(10,2) NOT NULL DEFAULT '0.00',
  `periodo`          varchar(255) DEFAULT NULL,
  `note`             text,
  `sort_order`       int NOT NULL DEFAULT '0',
  `created_at`       timestamp NULL DEFAULT NULL,
  `updated_at`       timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `budget_amounts` (
  `id`                 bigint unsigned NOT NULL AUTO_INCREMENT,
  `budget_category_id` bigint unsigned NOT NULL,
  `anno`               smallint unsigned NOT NULL,
  `importo_annuale`    decimal(10,2) NOT NULL,
  `importo_mensile`    decimal(10,2) NOT NULL,
  `created_at`         timestamp NULL DEFAULT NULL,
  `updated_at`         timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_amounts_category_anno_unique` (`budget_category_id`,`anno`),
  CONSTRAINT `budget_amounts_budget_category_id_foreign`
    FOREIGN KEY (`budget_category_id`) REFERENCES `budget_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `budget_expenses` (
  `id`                 bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id`            bigint unsigned NOT NULL,
  `budget_category_id` bigint unsigned NOT NULL,
  `importo`            decimal(10,2) NOT NULL,
  `data`               date NOT NULL,
  `note`               text,
  `created_at`         timestamp NULL DEFAULT NULL,
  `updated_at`         timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_expenses_user_id_foreign` (`user_id`),
  KEY `budget_expenses_budget_category_id_foreign` (`budget_category_id`),
  CONSTRAINT `budget_expenses_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_expenses_budget_category_id_foreign`
    FOREIGN KEY (`budget_category_id`) REFERENCES `budget_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transactions` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id`    bigint unsigned NOT NULL,
  `tipo`       enum('entrata','uscita','f24') NOT NULL,
  `importo`    decimal(10,2) NOT NULL,
  `data`       date NOT NULL,
  `causale`    varchar(255) NOT NULL,
  `note`       text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `transactions_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Dati
-- ------------------------------------------------------------

INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) VALUES

('1','Guido','sangiovanni.guido@gmail.com','2026-05-25 16:15:54','$2y$12$Vosf50Lb52IGbqwi/L2h1OaSx.7jaWHB5J9flISiahnEUhNwJHAei','FVOiAh9aOE','2026-05-25 16:15:54','2026-05-25 16:15:54');

INSERT INTO `budget_categories` (`id`,`categoria`,`nome`,`importo_annuale`,`importo_mensile`,`periodo`,`note`,`sort_order`,`created_at`,`updated_at`) VALUES

('1','CASA','Bollette Luce Octopus + Plenitude','1674.69','139.56','Mensile','','0','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('2','CASA','Bollette Luce parti comuni','180','15','Mensile','','1','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('3','CASA','Bollette Acqua Padania Acque','651.39','54.28','Quadrimestrale','','2','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('4','CASA','Telefono TIM','802.68','66.89','Mensile','','3','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('5','CASA','Tari (Giu/Dic)','68','5.67','Annuale','','4','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('6','CASA','Giardiniere','1000','83.33','Annuale','','5','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('7','CASA','IMU','1940','161.67','Semestrale','','6','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('8','CASA','Manutenzione Fotovoltaici','330','27.5','Annuale','','7','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('9','SPESA','Supermercato','9600','800','Mensile','','8','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('10','UFFICIO','Affitto','5153.64','429.47','Mensile','','9','2026-05-25 16:15:54','2026-05-25 16:15:54'),
('11','UFFICIO','Luce (ENI Plenitude)','624.27','52.02','Mensile','','10','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('12','UFFICIO','Telefono (TIM)','655.68','54.64','Mensile','','11','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('13','UFFICIO','Acqua','100','8.33','Quadrimestrale','','12','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('14','UFFICIO','GAS (Enel)','431.1','35.93','Mensile','','13','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('15','UFFICIO','Tari','50','4.17','Semestrale','','14','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('16','AUTO','Assicurazione Clio','910','75.83','Annuale (Settembre)','','15','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('17','AUTO','Assicurazione Captur','719.5','59.96','Annuale (Marzo)','','16','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('18','AUTO','Bollo Clio','100','8.33','Annuale','','17','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('19','AUTO','Bollo Captur','75.56','6.3','Annuale','','18','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('20','AUTO','Gomme Clio','100','8.33','Quinquennale','','19','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('21','AUTO','Gomme Captur','100','8.33','Quinquennale','','20','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('22','AUTO','Lavaggi','120','10','Trimestrale','','21','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('23','AUTO','Telepass','324.7','27.06','Bimestrale','','22','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('24','AUTO','Benzina','1440','120','Mensile','','23','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('25','AUTO','Risparmi acquisto auto nuove','3350','279.17','Decennale','','24','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('26','SCUOLA GIORGIO','Asilo','3500','291.67','Trimestrale','','25','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('27','SCUOLA GIORGIO','Grest','500','41.67','Annuale (Giugno)','','26','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('28','SCUOLA GIORGIO','Mensa','1000','83.33','Settimanale','','27','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('29','SALUTE','Visite/Esami/Farmaci','2400','200','Mensile','','28','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('30','VACANZE','Viaggi vacanze e soggiorni','6000','500','Semestrale','','29','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('31','ELETTRONICA','Macbook Guido','500','41.67','Al bisogno','','30','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('32','ELETTRONICA','Iphone Guido','325','27.08','Al bisogno','','31','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('33','ELETTRONICA','Iphone Fede','100','8.33','Al bisogno','','32','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('34','ELETTRONICA','Garmin Guido','80','6.67','Al bisogno','','33','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('35','ELETTRONICA','Tablet/Ebook','60','5','Al bisogno','','34','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('36','ELETTRONICA','Tastiera/Mouse/Altro','150','12.5','Al bisogno','','35','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('37','HOBBY GUIDO','Scarpe da corsa','750','62.5','Bimestrale','','36','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('38','HOBBY GUIDO','Abbigliamento da corsa','100','8.33','Semestrale','','37','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('39','HOBBY GUIDO','Iscrizioni Gare','350','29.17','Al bisogno','','38','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('40','HOBBY GUIDO','Console','125','10.42','Al bisogno','','39','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('41','HOBBY GUIDO','Videogiochi','200','16.67','Al bisogno','','40','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('42','HOBBY FEDE','Corso ricamo','300','25','Annuale','','41','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('43','HOBBY FEDE','Make Up','200','16.67','Al bisogno','','42','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('44','HOBBY FEDE','Materiale ricamo','150','12.5','Al bisogno','','43','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('45','HOBBY FEDE','Corso disegno','150','12.5','Annuale','','44','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('46','HOBBY FEDE','Materiale disegno','200','16.67','Al bisogno','','45','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('47','HOBBY FEDE','Palestra','800','66.67','Annuale','','46','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('48','ALTRO','Parrucchiere Guido','220','18.33','Bimestrale','','47','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('49','ALTRO','Parrucchiere Fede','300','25','Trimestrale','','48','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('50','ALTRO','Estetista Fede','300','25','Trimestrale','','49','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('51','ALTRO','Parrucchiere Giorgio','150','7.5','Bimestrale','','50','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('52','EXTRA','Regali Guido','500','41.67','Annuale','','51','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('53','EXTRA','Regali Fede','500','41.67','Annuale','','52','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('54','EXTRA','Regali altri','800','66.67','Annuale','','53','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('55','LIFESTYLE','Pranzi/Cene','3600','300','Mensile','','54','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('56','ABBIGLIAMENTO','Abbigliamento per tutti','2400','200','Al bisogno','','55','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('57','ASSICURAZIONI','Vita Guido','420.84','35.07','Annuale (Febbraio)','','56','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('58','ASSICURAZIONI','Casa','330','27.5','Annuale (Gennaio)','','57','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('59','IMPREVISTI','Risparmi per imprevisti','1500','125','Mensile','','58','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('60','RISPARMI','Risparmi/Investimenti','5000','416.67','Mensile','','59','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('61','RISPARMI','Fondo pensione','5300','441.67','Trimestrale','','60','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('62','COMMERCIALISTA','Costo commercialista','1800','150','Trimestrale','','61','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('63','CASTIONE','Super condominio','150','12.5','Annuale','','62','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('64','CASTIONE','Condominio','600','50','Annuale','','63','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('65','CASTIONE','Bollette','150','12.5','Annuale','','64','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('66','AGNADELLO','Sp. Condominiali Agnadello','863.13','71.93','Annuale (Ottobre)','','65','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('67','AGNADELLO','Pulizia Scale Agnadello','40','3.33','Semestrale','','66','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('68','OMBRIANO','Sp. Condominiali Ombriano','756','63','Annuale','','67','2026-05-25 16:15:55','2026-05-25 16:15:55');

INSERT INTO `budget_expenses` (`id`,`user_id`,`budget_category_id`,`importo`,`data`,`note`,`created_at`,`updated_at`) VALUES

('2','1','9','484.67','2026-04-30','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('3','1','9','240.55','2026-03-31','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('4','1','9','550.87','2026-02-28','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('5','1','9','456.78','2026-01-31','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('6','1','9','518.62','2025-12-31','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('7','1','9','183.99','2025-11-30','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('8','1','9','182.24','2025-10-31','Bennet','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('9','1','9','270.35','2026-04-30','Famila','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('10','1','9','134.21','2026-03-31','Famila','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('11','1','9','34.89','2026-01-31','Famila','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('12','1','9','72.61','2025-12-31','Famila','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('13','1','9','85.43','2025-11-30','Famila','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('14','1','9','23.91','2025-10-31','Famila','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('15','1','9','107.35','2026-03-31','Banco Fresco','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('16','1','9','44.37','2026-02-28','Banco Fresco','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('17','1','9','112.79','2026-01-31','Banco Fresco','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('18','1','9','35.91','2026-01-31','Ipercoop','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('19','1','9','3.38','2026-03-31','Salumeria Stringa','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('20','1','9','9.6','2026-01-31','Salumeria Stringa','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('21','1','9','10','2025-12-31','Salumeria Stringa','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('22','1','9','150','2026-01-31','Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('23','1','9','180','2026-01-31','Esselunga','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('24','1','9','118.61','2026-04-30','Altro','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('25','1','9','14.9','2026-03-31','Altro','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('26','1','9','52','2026-01-31','Altro','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('27','1','14','312.25','2026-04-30','Uscite ufficio varie','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('28','1','14','373.16','2026-03-31','Uscite ufficio varie','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('29','1','14','78.25','2026-02-28','Uscite ufficio varie','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('30','1','14','234.14','2026-01-31','Uscite ufficio varie','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('31','1','30','210','2026-02-28','Siviglia - costi durante il viaggio','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('32','1','30','180','2026-02-28','Siviglia - airbnb','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('33','1','30','231','2026-02-28','Siviglia - volo','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('34','1','30','82.1','2026-04-30','Siviglia - iscrizione maratona','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('35','1','30','92','2026-02-28','Siviglia - iscrizione maratona','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('36','1','30','43','2026-02-28','Siviglia - parcheggio','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('37','1','30','2332.8','2026-07-31','PSG Casa','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('38','1','30','1117.2','2026-02-28','PSG Casa','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('39','1','30','135','2026-03-31','Acconto bagni Dolce Vita PSG','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('40','1','29','58.15','2026-05-31','Esami sangue Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('41','1','29','112','2026-04-30','Mascherina Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('42','1','29','45','2026-03-31','Pubalgia (Luca Mombelli)','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('43','1','29','17.6','2026-05-31','Parafarmacia','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('44','1','29','302','2026-04-30','Mascherina Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('45','1','29','45','2026-03-31','Pubalgia + schiena (Luca Mombelli)','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('46','1','29','92','2026-05-31','Mammografia Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('47','1','54','699','2026-04-30','Mac Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('48','1','54','289.2','2026-03-31','Mac Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('49','1','54','20','2026-02-28','Mac Fede','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('50','1','54','600','2026-04-30','Bici Guido','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('51','1','54','51','2026-04-30','Decathlon - Bici Guido','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('52','1','54','515','2026-04-30','Decathlon Online - Bici Guido','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('53','1','54','1865','2026-04-30','Regali vari','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('54','1','41','44.99','2026-03-31','Octopath Traveler NS2','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('55','1','1','556.55','2026-03-31','Luce','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('56','1','3','118.49','2026-04-30','Acqua Padania','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('57','1','3','190.77','2026-01-31','Acqua Padania','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('58','1','10','429.47','2026-03-31','Affitto','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('59','1','10','429.47','2026-02-28','Affitto','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('60','1','10','429.47','2026-01-31','Affitto','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('61','1','11','104.91','2026-02-28','Luce ufficio','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('62','1','14','140','2026-04-30','Altro ufficio','2026-05-25 17:04:17','2026-05-25 17:04:17'),
('63','1','9','2.84','2026-05-06','Famila','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('64','1','9','4','2026-05-06','CApp Caffe','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('65','1','9','96.86','2026-05-05','Famila','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('66','1','9','117.21','2026-05-12','Famila','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('67','1','9','27.05','2026-05-12','Alle Origini','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('68','1','9','56.68','2026-05-14','Banco Fresco','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('69','1','9','24.54','2026-05-15','Bennet','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('70','1','9','32.02','2026-05-16','Bennet','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('71','1','9','6','2026-05-17','Libro Giorgio','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('72','1','9','67.09','2026-05-25','Banco Fresco','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('73','1','9','18.65','2026-05-25','Stringa','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('74','1','9','17.36','2026-05-25','Bennet','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('75','1','9','77','2026-05-25','Bennet','2026-05-25 17:10:01','2026-05-25 17:10:01'),
('76','1','55','35','2026-05-03','Arizona66','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('77','1','55','18.5','2026-05-05','Babar pranzo','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('78','1','55','3.5','2026-05-02','Caffe Babar Fede','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('79','1','55','18','2026-05-09','Gelato Unika','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('80','1','55','23.2','2026-05-15','Buffalo + Bandirali','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('81','1','55','3','2026-05-17','Gelato Lodi','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('82','1','55','14','2026-05-20','Cinema','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('83','1','55','15.5','2026-05-20','Buffalo pizza','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('84','1','55','29.4','2026-05-20','Cena + popcorn','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('85','1','55','24.5','2026-05-23','Pizza + Gelato Bandirali a casa','2026-05-25 17:11:09','2026-05-25 17:11:09'),
('86','1','29','58.15','2026-05-06','Esami sangue Fede','2026-05-25 17:14:06','2026-05-25 17:14:06'),
('87','1','29','17.6','2026-05-15','Parafarmacia','2026-05-25 17:14:06','2026-05-25 17:14:06'),
('88','1','29','92','2026-05-22','Mammografia Fede','2026-05-25 17:14:06','2026-05-25 17:14:06'),
('89','1','29','112','2026-04-24','Mascherina Fede','2026-05-25 17:14:24','2026-05-25 17:14:24'),
('90','1','29','302','2026-04-29','Mascherina Fede','2026-05-25 17:14:24','2026-05-25 17:14:24'),
('91','1','29','45','2026-03-02','Pubalgia (Luca Mombelli)','2026-05-25 17:14:39','2026-05-25 17:14:39'),
('92','1','29','45','2026-03-16','Pubalgia + schiena zona sacrale (Luca Mombelli)','2026-05-25 17:14:39','2026-05-25 17:14:39');

INSERT INTO `transactions` (`id`,`user_id`,`tipo`,`importo`,`data`,`causale`,`note`,`created_at`,`updated_at`) VALUES

('1','1','uscita','2240','2026-01-31','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('2','1','uscita','4486','2026-02-02','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('3','1','uscita','2448','2026-02-28','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('4','1','uscita','3100','2026-02-28','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('5','1','uscita','912','2026-03-31','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('6','1','uscita','1848','2026-03-31','Umberto','fatturati e pagati da diego','2026-05-25 16:15:55','2026-05-25 16:15:55'),
('7','1','uscita','4050','2026-04-01','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('8','1','uscita','2288','2026-04-30','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('9','1','uscita','3325','2026-05-03','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('10','1','f24','111.86','2026-04-30','commercialista',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('11','1','f24','108','2026-05-18','commercialista',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('12','1','f24','9647.13','2026-05-18','iva 1 trimestre',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('13','1','entrata','1583.04','2026-01-13','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('14','1','entrata','3215.04','2026-01-13','Italpacking',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('15','1','entrata','5600','2026-01-23','Apir',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('16','1','entrata','4569.6','2026-01-30','ID3',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('17','1','entrata','3182.4','2026-01-30','BRV',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('18','1','entrata','2987.8','2026-02-04','EAW',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('19','1','entrata','2827.44','2026-02-10','Italpacking',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('20','1','entrata','1456.56','2026-02-13','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('21','1','entrata','1377','2026-02-17','Vetraco',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('22','1','entrata','4569.6','2026-02-27','ID3',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('23','1','entrata','3341.52','2026-03-02','Diego',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('24','1','entrata','941.99','2026-03-03','BRV',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('25','1','entrata','1421.47','2026-03-04','Dibotek',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('26','1','entrata','1627.92','2026-03-11','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('27','1','entrata','3170.16','2026-03-11','Italpacking',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('28','1','entrata','8424.5','2026-03-16','Apir',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('29','1','entrata','891.07','2026-03-31','BRV',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('30','1','entrata','9180','2026-03-31','Prada',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('31','1','entrata','5222.4','2026-03-31','ID3',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('32','1','uscita','1568','2025-01-07','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('33','1','uscita','2325','2025-01-14','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('34','1','uscita','3140','2025-02-03','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('35','1','uscita','2352','2025-02-04','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('36','1','uscita','3375','2025-02-28','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('37','1','uscita','2272','2025-02-28','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('38','1','uscita','752','2025-04-01','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('39','1','uscita','3693','2025-04-02','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('40','1','uscita','3250','2025-05-02','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('41','1','uscita','2560','2025-05-02','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('42','1','uscita','2800','2025-06-30','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('43','1','uscita','1984','2025-06-03','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('44','1','uscita','2875','2025-07-01','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('45','1','uscita','2278','2025-07-02','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('46','1','uscita','2675','2025-07-31','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('47','1','uscita','2416','2025-08-05','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('48','1','uscita','1750','2025-08-01','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('49','1','uscita','1192','2025-09-01','Umberto',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('50','1','uscita','3525','2025-09-30','Emanuele',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('51','1','f24','114','2025-01-16','commercialista',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('52','1','f24','3352','2025-03-17','saldo iva 4 trimestre 2024',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('53','1','f24','9942.36','2025-05-16','iva 1 trimestre',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('54','1','f24','114','2025-08-20','commercialista',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('55','1','f24','8590.3','2025-08-26','iva 2 trimestre',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('56','1','f24','137','2025-10-16','commercialista',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('57','1','f24','6240.31','2025-11-07','iva 3 trimestre',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('58','1','f24','3538.67','2025-12-01','2 acconto imposte 2025',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('59','1','f24','10330.14','2025-12-03','acconto iva 4 trimestre',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('60','1','entrata','6083.28','2025-01-13','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('61','1','entrata','4492.48','2025-01-14','BRV',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('62','1','entrata','2937.6','2025-01-21','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('63','1','entrata','1930.66','2025-02-04','BRV',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('64','1','entrata','4883.76','2025-02-14','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('65','1','entrata','2569.6','2025-02-28','ID3',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('66','1','entrata','4987.7','2025-03-03','EAW',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('67','1','entrata','2524.7','2025-03-07','BRV',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('68','1','entrata','5826.24','2025-03-11','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('69','1','entrata','9180','2025-03-17','Prada',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('70','1','entrata','5222.4','2025-03-31','ID3',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('71','1','entrata','5055.12','2025-04-11','ILT',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55'),
('72','1','entrata','5548.8','2025-04-30','ID3',NULL,'2026-05-25 16:15:55','2026-05-25 16:15:55');
SET FOREIGN_KEY_CHECKS = 1;

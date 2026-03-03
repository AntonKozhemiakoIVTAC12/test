CREATE TABLE IF NOT EXISTS `#__crm_companies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `stage_code` varchar(32) NOT NULL DEFAULT 'C0',
  `discovery_filled_at` datetime DEFAULT NULL,
  `demo_planned_at` datetime DEFAULT NULL,
  `demo_done_at` datetime DEFAULT NULL,
  `invoice_at` datetime DEFAULT NULL,
  `payment_at` datetime DEFAULT NULL,
  `first_certificate_at` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stage` (`stage_code`),
  KEY `idx_updated` (`updated`),
  KEY `idx_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__crm_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `payload` json DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_created` (`company_id`, `created`),
  KEY `idx_event_type` (`event_type`),
  CONSTRAINT `fk_crm_events_company` FOREIGN KEY (`company_id`) REFERENCES `#__crm_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__crm_stages` (
  `code` varchar(32) NOT NULL,
  `title` varchar(128) NOT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__crm_stages` (`code`, `title`, `sort_order`) VALUES
('C0', 'Ice', 0),
('C1', 'Touched', 1),
('C2', 'Aware', 2),
('W1', 'Interested', 3),
('W2', 'demo_planned', 4),
('W3', 'Demo_done', 5),
('H1', 'Committed', 6),
('H2', 'Customer', 7),
('A1', 'Activated', 8),
('N0', 'Null', 9);

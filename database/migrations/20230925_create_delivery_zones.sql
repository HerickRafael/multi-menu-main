CREATE TABLE IF NOT EXISTS `delivery_cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_cities_company_name_unique` (`company_id`, `name`),
  KEY `delivery_cities_company_fk` (`company_id`),
  CONSTRAINT `delivery_cities_company_fk`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `delivery_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `neighborhood` varchar(120) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_zones_company_city_neighborhood_unique` (`company_id`, `city_id`, `neighborhood`),
  KEY `delivery_zones_city_fk` (`city_id`),
  KEY `delivery_zones_company_fk` (`company_id`),
  CONSTRAINT `delivery_zones_city_fk`
    FOREIGN KEY (`city_id`) REFERENCES `delivery_cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_zones_company_fk`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

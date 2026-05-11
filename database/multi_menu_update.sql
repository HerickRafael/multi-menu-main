-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 85.31.231.130:3306
-- Tempo de geração: 22/10/2025 às 23:21
-- Versão do servidor: 8.0.37-29
-- Versão do PHP: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `multi_menu`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `company_id`, `name`, `sort_order`, `active`) VALUES
(1, 1, 'Bebidas', 0, 1),
(2, 1, 'Hambúrgueres', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `combo_groups`
--

CREATE TABLE `combo_groups` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('single','remove','add','swap','component','extra','addon') COLLATE utf8mb4_general_ci DEFAULT 'single',
  `min_qty` int DEFAULT '0',
  `max_qty` int DEFAULT '1',
  `sort` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `combo_groups`
--

INSERT INTO `combo_groups` (`id`, `product_id`, `name`, `type`, `min_qty`, `max_qty`, `sort`, `created_at`, `updated_at`) VALUES
(15, 4, 'Burger', 'component', 0, 1, 0, '2025-10-22 04:10:35', NULL),
(16, 4, 'Vai uma bebida ai?', 'component', 0, 1, 1, '2025-10-22 04:10:35', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `combo_group_items`
--

CREATE TABLE `combo_group_items` (
  `id` int NOT NULL,
  `group_id` int NOT NULL,
  `simple_product_id` int NOT NULL,
  `delta_price` decimal(10,2) DEFAULT '0.00',
  `is_default` tinyint(1) DEFAULT '0',
  `allow_customize` tinyint(1) NOT NULL DEFAULT '0',
  `sort` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `combo_group_items`
--

INSERT INTO `combo_group_items` (`id`, `group_id`, `simple_product_id`, `delta_price`, `is_default`, `allow_customize`, `sort`, `created_at`, `updated_at`) VALUES
(25, 15, 2, 0.00, 1, 1, 0, '2025-10-22 04:10:35', NULL),
(26, 16, 7, 0.00, 0, 0, 0, '2025-10-22 04:10:35', NULL),
(27, 16, 8, 0.00, 0, 0, 1, '2025-10-22 04:10:35', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `companies`
--

CREATE TABLE `companies` (
  `id` int NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highlight_text` text COLLATE utf8mb4_general_ci,
  `min_order` decimal(10,2) DEFAULT NULL,
  `delivery_after_hours_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_free_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `avg_delivery_min_from` int DEFAULT NULL,
  `avg_delivery_min_to` int DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banner` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_header_text_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_header_button_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_header_bg_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_logo_border_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_group_title_bg_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_group_title_text_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_welcome_bg_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menu_welcome_text_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `evolution_server_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evolution_api_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `companies`
--

INSERT INTO `companies` (`id`, `slug`, `name`, `whatsapp`, `address`, `highlight_text`, `min_order`, `delivery_after_hours_fee`, `delivery_free_enabled`, `avg_delivery_min_from`, `avg_delivery_min_to`, `logo`, `banner`, `menu_header_text_color`, `menu_header_button_color`, `menu_header_bg_color`, `menu_logo_border_color`, `menu_group_title_bg_color`, `menu_group_title_text_color`, `menu_welcome_bg_color`, `menu_welcome_text_color`, `active`, `created_at`, `evolution_server_url`, `evolution_api_key`) VALUES
(1, 'wollburger', 'Wollburger', '5551920017687', '', '', 15.00, 0.00, 0, NULL, NULL, 'uploads/logo_1761017616_9803.jpg', 'uploads/banner_1761017616_4337.jpg', '#FFFFFF', '#FACC15', '#500075', '#500075', '#FACC15', '#000000', '#6B21A8', '#FFFFFF', 1, '2025-09-11 01:38:16', 'https://api.grifet.com', 'cb6576b0080edb5e74ae37c6873e7519'),
(4, 'teste', 'Teste Evolution', NULL, NULL, NULL, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-15 02:01:06', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `company_favorites_settings`
--

CREATE TABLE `company_favorites_settings` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `favorites_enabled` tinyint(1) DEFAULT '1',
  `show_favorites_tab` tinyint(1) DEFAULT '1',
  `show_favorites_count` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `company_favorites_settings`
--

INSERT INTO `company_favorites_settings` (`id`, `company_id`, `favorites_enabled`, `show_favorites_tab`, `show_favorites_count`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2025-10-15 05:38:47', '2025-10-15 05:38:47'),
(2, 4, 1, 1, 1, '2025-10-15 05:38:47', '2025-10-15 05:38:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `company_hours`
--

CREATE TABLE `company_hours` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `weekday` tinyint NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '0',
  `open1` time DEFAULT NULL,
  `close1` time DEFAULT NULL,
  `open2` time DEFAULT NULL,
  `close2` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `company_hours`
--

INSERT INTO `company_hours` (`id`, `company_id`, `weekday`, `is_open`, `open1`, `close1`, `open2`, `close2`) VALUES
(1, 1, 1, 0, NULL, NULL, NULL, NULL),
(2, 1, 2, 0, NULL, NULL, NULL, NULL),
(3, 1, 3, 0, NULL, NULL, NULL, NULL),
(4, 1, 4, 0, NULL, NULL, NULL, NULL),
(5, 1, 5, 0, NULL, NULL, NULL, NULL),
(6, 1, 6, 0, NULL, NULL, NULL, NULL),
(7, 1, 7, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `company_map_settings`
--

CREATE TABLE `company_map_settings` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_delivery_distance` decimal(4,2) NOT NULL DEFAULT '20.00',
  `delivery_mode` enum('neighborhood','distance','both') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'neighborhood',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `company_meta`
--

CREATE TABLE `company_meta` (
  `id` int UNSIGNED NOT NULL,
  `company_id` int UNSIGNED NOT NULL,
  `meta_key` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `meta_value` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `company_meta`
--

INSERT INTO `company_meta` (`id`, `company_id`, `meta_key`, `meta_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'evolution_server_url', 'https://api.grifet.com', '2025-10-12 05:58:39', '2025-10-12 06:05:04'),
(2, 1, 'evolution_api_key', 'cb6576b0080edb5e74ae37c6873e7519', '2025-10-12 05:58:39', '2025-10-12 06:05:04'),
(3, 1, 'evolution_base_url', 'https://api.grifet.com', '2025-10-12 06:17:58', NULL),
(4, 1, 'evolution_endpoint', '/', '2025-10-12 06:17:58', NULL),
(5, 1, 'evolution_auth_type', 'bearer', '2025-10-12 06:17:58', NULL),
(6, 1, 'evolution_header_name', '', '2025-10-12 06:17:58', NULL),
(7, 1, 'evolution_token', 'cb6576b0080edb5e74ae37c6873e7519', '2025-10-12 06:17:58', NULL),
(8, 1, 'evolution_method', 'GET', '2025-10-12 06:17:58', NULL),
(9, 1, 'evolution_content_type', 'application/json', '2025-10-12 06:17:58', NULL),
(10, 1, 'evolution_request_body', '', '2025-10-12 06:17:58', NULL),
(11, 1, 'evolution_opt_reject_calls', '0', '2025-10-12 06:17:58', NULL),
(12, 1, 'evolution_opt_ignore_groups', '0', '2025-10-12 06:17:58', NULL),
(13, 1, 'evolution_opt_always_online', '0', '2025-10-12 06:17:58', NULL),
(14, 1, 'evolution_opt_read_messages', '0', '2025-10-12 06:17:58', NULL),
(15, 1, 'evolution_opt_sync_full_history', '0', '2025-10-12 06:17:58', NULL),
(16, 1, 'evolution_opt_read_status', '0', '2025-10-12 06:17:58', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `whatsapp_e164` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `last_login_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `name`, `whatsapp`, `whatsapp_e164`, `created_at`, `updated_at`, `last_login_at`) VALUES
(1, 1, 'herick', '51920017687', '5551920017687', '2025-10-05 13:19:54', '2025-10-22 15:58:19', '2025-10-22 15:58:19'),
(2, 1, 'Teste', '5511999999999', '5511999999999', '2025-10-05 17:28:49', '2025-10-05 17:28:49', '2025-10-05 17:28:49'),
(3, 1, 'herick', '519200176878', '519200176878', '2025-10-05 18:16:29', '2025-10-05 18:16:29', '2025-10-05 18:16:29'),
(4, 1, 'herick', '519820017687', '519820017687', '2025-10-18 23:44:36', '2025-10-18 23:44:36', '2025-10-18 23:44:36'),
(5, 1, 'Victor Gabriel Duarte', '51993032200', '5551993032200', '2025-10-21 19:55:11', '2025-10-22 16:31:51', '2025-10-22 16:31:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `customer_order_history`
--

CREATE TABLE `customer_order_history` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `company_id` int NOT NULL,
  `order_count` int DEFAULT '1',
  `last_ordered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `first_ordered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_spent` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `customer_product_views`
--

CREATE TABLE `customer_product_views` (
  `id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `product_id` int NOT NULL,
  `company_id` int NOT NULL,
  `view_count` int DEFAULT '1',
  `last_viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `customer_recommendations`
--

CREATE TABLE `customer_recommendations` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `company_id` int NOT NULL,
  `recommendation_type` enum('frequent','similar','trending','seasonal') COLLATE utf8mb4_unicode_ci DEFAULT 'frequent',
  `score` decimal(3,2) DEFAULT '0.00',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `delivery_cities`
--

CREATE TABLE `delivery_cities` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `delivery_cities`
--

INSERT INTO `delivery_cities` (`id`, `company_id`, `name`, `created_at`) VALUES
(1, 1, 'Tramandai', '2025-10-16 19:13:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `delivery_zones`
--

CREATE TABLE `delivery_zones` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `city_id` int NOT NULL,
  `neighborhood` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `delivery_zones`
--

INSERT INTO `delivery_zones` (`id`, `company_id`, `city_id`, `neighborhood`, `fee`, `created_at`) VALUES
(1, 1, 1, 'Parque Emboaba', 6.00, '2025-10-16 19:14:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `delivery_zones_by_distance`
--

CREATE TABLE `delivery_zones_by_distance` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `min_distance` decimal(4,2) NOT NULL DEFAULT '0.00',
  `max_distance` decimal(4,2) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_time_minutes` int DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `evolution_blocks`
--

CREATE TABLE `evolution_blocks` (
  `id` int NOT NULL,
  `company_id` int NOT NULL COMMENT 'ID da empresa proprietária',
  `instance_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome da instância Evolution',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Título do bloco',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo do bloco (message, media, webhook, etc)',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT 'Conteúdo principal do bloco',
  `config` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Configurações específicas do bloco em JSON',
  `position` int DEFAULT '0' COMMENT 'Posição/ordem do bloco',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Se o bloco está ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `evolution_blocks`
--

INSERT INTO `evolution_blocks` (`id`, `company_id`, `instance_name`, `title`, `type`, `content`, `config`, `position`, `is_active`, `created_at`, `updated_at`) VALUES
(10, 1, 'WollBurger', 'Mensagem de Boas-vindas', 'message', 'Olá! Bem-vindo ao {company_name}! 🎉\\n\\nSou o assistente virtual e estou aqui para ajudá-lo.\\n\\nData: {date} | Hora: {time}', '{\"delay\":2}', 0, 1, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(11, 1, 'WollBurger', 'Delay de Processamento', 'delay', 'Aguardando processamento...', '{\"seconds\":3}', 1, 1, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(12, 1, 'WollBurger', 'Informações de Contato', 'contact', 'Contato do suporte técnico', '{\"contact_name\":\"Suporte WollBurger\",\"contact_phone\":\"+5511999887766\"}', 2, 1, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(13, 1, 'WollBurger', 'Localização da Loja', 'location', 'Nossa localização principal', '{\"latitude\":-23.5505,\"longitude\":-46.6333,\"address\":\"S\\u00e3o Paulo, SP - Centro\"}', 3, 0, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(14, 1, 'WollBurger', 'Webhook de Notificação', 'webhook', 'Notificar sistema externo', '{\"url\":\"https:\\/\\/httpbin.org\\/post\",\"method\":\"POST\",\"payload\":{\"instance\":\"WollBurger\",\"action\":\"user_interaction\",\"timestamp\":\"{datetime}\"}}', 4, 1, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(15, 1, 'WollBurger', 'Definir Variável de Sessão', 'variable', 'Usuário foi atendido', '{\"variable_name\":\"user_attended\",\"variable_value\":\"true\",\"operation\":\"set\"}', 5, 1, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(16, 1, 'WollBurger', 'Resposta Fora de Horário', 'outside_hours', 'Mensagem automática fora do horário de funcionamento', '{\"auto_reply_message\":\"Ol\\u00e1 {user_name}! \\ud83c\\udf19\\\\n\\\\nObrigado pelo contato! Nosso hor\\u00e1rio de funcionamento \\u00e9 das 8h \\u00e0s 18h.\\\\n\\\\nRetornaremos assim que poss\\u00edvel.\\\\n\\\\nAtenciosamente,\\\\n{company_name}\",\"enabled\":true}', 6, 1, '2025-10-16 00:28:01', '2025-10-16 00:28:01'),
(18, 1, 'teste', 'nm,mjh', 'outside_hours', 'mnvn,vm,', '{\"auto_reply_message\":\",nm,mn\",\"enabled\":\"on\"}', 0, 1, '2025-10-16 00:33:44', '2025-10-16 00:33:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `evolution_instances`
--

CREATE TABLE `evolution_instances` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instance_identifier` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_code` longtext COLLATE utf8mb4_general_ci,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `connected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_main` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `evolution_instances`
--

INSERT INTO `evolution_instances` (`id`, `company_id`, `label`, `number`, `instance_identifier`, `qr_code`, `status`, `connected_at`, `created_at`, `updated_at`, `is_main`) VALUES
(44, 1, 'Teste WhatsApp', NULL, 'herick', NULL, 'disconnected', NULL, '2025-10-15 02:01:56', '2025-10-21 02:02:13', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `evolution_instance_settings`
--

CREATE TABLE `evolution_instance_settings` (
  `id` int NOT NULL,
  `instance_id` int NOT NULL,
  `setting_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `unit_value` decimal(10,3) NOT NULL DEFAULT '1.000',
  `min_qty` int NOT NULL DEFAULT '0',
  `max_qty` int NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ingredients`
--

INSERT INTO `ingredients` (`id`, `company_id`, `name`, `cost`, `sale_price`, `unit`, `unit_value`, `min_qty`, `max_qty`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'Pão Brioche', 0.50, 1.50, 'un', 1.000, 0, 1, 'uploads/ingredient_1760070801_7639.png', '2025-10-05 18:04:43', '2025-10-10 01:33:21'),
(2, 1, 'Bled Costela 90 (carne)', 3.50, 7.50, 'un', 1.000, 0, 1, 'uploads/ingredient_1760070954_2905.png', '2025-10-05 18:04:43', '2025-10-10 01:35:54'),
(3, 1, 'Queijo Cheddar', 0.40, 1.00, 'un', 1.000, 0, 1, 'uploads/ingredient_1760070763_4567.png', '2025-10-05 18:04:43', '2025-10-10 01:32:43'),
(4, 1, 'Bacon', 0.80, 2.50, 'un', 1.000, 0, 1, 'uploads/ingredient_1760069496_3945.png', '2025-10-05 18:04:43', '2025-10-10 01:11:36'),
(5, 1, 'Alface', 0.05, 0.30, 'un', 1.000, 0, 1, 'uploads/ingredient_1760069310_5975.png', '2025-10-05 18:04:43', '2025-10-10 01:08:30'),
(6, 1, 'Tomate', 0.10, 0.50, 'un', 1.000, 0, 2, NULL, '2025-10-05 18:04:43', '2025-10-05 18:04:43'),
(7, 1, 'Cebola', 0.05, 0.30, 'un', 1.000, 0, 1, 'uploads/ingredient_1760069769_7601.png', '2025-10-05 18:04:43', '2025-10-10 01:16:09'),
(8, 1, 'Picles', 0.05, 0.30, 'un', 1.000, 0, 2, NULL, '2025-10-05 18:04:43', '2025-10-05 18:04:43'),
(9, 1, 'Queijo Mussarela', 0.00, 0.00, 'un', 1.000, 0, 1, 'uploads/ingredient_1761026366_7846.png', '2025-10-05 18:04:43', '2025-10-21 02:59:26'),
(10, 1, 'Mostarda', 0.00, 0.00, 'un', 1.000, 0, 5, NULL, '2025-10-05 18:04:43', '2025-10-05 18:04:43'),
(11, 1, 'Maionese', 0.00, 0.00, 'un', 1.000, 0, 5, NULL, '2025-10-05 18:04:43', '2025-10-05 18:04:43'),
(12, 1, 'Hambúrguer Veggie', 1.80, 5.50, 'un', 1.000, 0, 2, NULL, '2025-10-05 18:04:43', '2025-10-05 18:04:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `instance_configs`
--

CREATE TABLE `instance_configs` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `instance_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `instance_configs`
--

INSERT INTO `instance_configs` (`id`, `company_id`, `instance_name`, `config_key`, `config_value`, `created_at`, `updated_at`) VALUES
(13, 1, 'Herick', 'order_notification', '{\"enabled\":true,\"primary_number\":\"5551920017687\",\"secondary_number\":\"\",\"updated_at\":\"2025-10-21 02:15:04\",\"message_fields\":{\"company_name\":true,\"order_number\":true,\"order_status\":true,\"order_date\":true,\"customer_name\":true,\"customer_phone\":false,\"customer_address\":false,\"delivery_type\":true,\"payment_method\":true,\"payment_change\":false,\"subtotal\":true,\"delivery_fee\":false,\"total\":true,\"items_list\":true,\"item_quantity\":true,\"item_price\":true,\"item_subtotal\":true,\"item_customization\":false,\"item_observations\":false,\"order_notes\":false,\"estimated_time\":false,\"system_source\":true}}', '2025-10-21 05:15:04', '2025-10-22 00:42:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','completed','canceled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sla_deadline` datetime DEFAULT NULL,
  `customer_address` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `orders`
--

INSERT INTO `orders` (`id`, `company_id`, `customer_name`, `customer_phone`, `subtotal`, `delivery_fee`, `discount`, `total`, `status`, `notes`, `created_at`, `updated_at`, `status_changed_at`, `sla_deadline`, `customer_address`) VALUES
(126, 1, 'herick', '51920017687', 54.30, 0.00, 0.00, 54.30, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:42:48', '2025-10-12 02:37:07', '2025-10-12 02:37:07', NULL, 'dfsd, 65'),
(127, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:50:01', '2025-10-12 02:37:09', '2025-10-12 02:37:09', NULL, 'dfsd, 65'),
(128, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:50:13', '2025-10-12 01:50:13', '2025-10-12 01:50:13', NULL, 'dfsd, 65'),
(129, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:51:55', '2025-10-12 01:51:55', '2025-10-12 01:51:55', NULL, 'dfsd, 65'),
(130, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:53:10', '2025-10-12 01:53:10', '2025-10-12 01:53:10', NULL, 'dfsd, 65'),
(131, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:54:37', '2025-10-12 01:54:37', '2025-10-12 01:54:37', NULL, 'dfsd, 65'),
(132, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:55:43', '2025-10-12 01:55:43', '2025-10-12 01:55:43', NULL, 'dfsd, 65'),
(133, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 01:55:59', '2025-10-12 01:55:59', '2025-10-12 01:55:59', NULL, 'dfsd, 65'),
(134, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 02:11:20', '2025-10-12 02:11:20', '2025-10-12 02:11:20', NULL, 'dfsd, 65'),
(135, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 02:11:36', '2025-10-12 02:11:36', '2025-10-12 02:11:36', NULL, 'dfsd, 65'),
(136, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 02:37:15', '2025-10-12 02:37:15', '2025-10-12 02:37:15', NULL, 'dfsd, 65'),
(137, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 02:37:27', '2025-10-12 02:37:27', '2025-10-12 02:37:27', NULL, 'dfsd, 65'),
(138, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 04:11:57', '2025-10-12 04:11:57', '2025-10-12 04:11:57', NULL, 'dfsd, 65'),
(142, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 04:13:22', '2025-10-12 04:13:22', '2025-10-12 04:13:22', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(143, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 04:22:15', '2025-10-12 04:22:15', '2025-10-12 04:22:15', NULL, 'dfsd, 65'),
(144, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'completed', 'Pedido de teste enviado pelo script', '2025-10-12 04:23:48', '2025-10-14 00:26:35', '2025-10-14 00:26:35', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(145, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 04:25:02', '2025-10-12 04:25:02', '2025-10-12 04:25:02', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(146, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 04:25:58', '2025-10-12 04:25:58', '2025-10-12 04:25:58', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(147, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 04:26:18', '2025-10-12 04:26:18', '2025-10-12 04:26:18', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(148, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 04:27:02', '2025-10-12 04:27:02', '2025-10-12 04:27:02', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(149, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 04:27:18', '2025-10-12 04:27:18', '2025-10-12 04:27:18', NULL, 'dfsd, 65'),
(150, 1, 'herick', '51920017687', 28.40, 0.00, 0.00, 28.40, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 04:50:33', '2025-10-12 04:50:33', '2025-10-12 04:50:33', NULL, '2375, 5463'),
(151, 1, 'herick', '51920017687', 62.30, 0.00, 0.00, 62.30, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 11:59:43', '2025-10-12 11:59:43', '2025-10-12 11:59:43', NULL, 'vmvb, bvnmvhb'),
(152, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 13:38:46', '2025-10-12 13:38:46', '2025-10-12 13:38:46', NULL, 'vmvb, bvnmvhb'),
(153, 1, 'João Silva (Teste)', '+5551999887766', 0.00, 0.00, 0.00, 115.60, 'pending', 'Pedido de teste - Entregar rápido', '2025-10-12 13:49:25', '2025-10-12 13:49:25', '2025-10-12 13:49:25', NULL, 'Rua das Flores, 456\nApto 101\nBairro Jardim'),
(154, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 15:55:32', '2025-10-12 15:55:32', '2025-10-12 15:55:32', NULL, 'vmvb, bvnmvhb'),
(155, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 23:07:30', '2025-10-12 23:07:30', '2025-10-12 23:07:30', NULL, 'vmvb, bvnmvhb'),
(156, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 23:16:26', '2025-10-12 23:16:26', '2025-10-12 23:16:26', NULL, 'vmvb, bvnmvhb'),
(157, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 23:27:34', '2025-10-12 23:27:34', '2025-10-12 23:27:34', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(158, 1, 'Teste Bot', '+559999999999', 20.00, 5.00, 0.00, 25.00, 'pending', 'Pedido de teste enviado pelo script', '2025-10-12 23:36:53', '2025-10-12 23:36:53', '2025-10-12 23:36:53', NULL, 'Rua Falsa, 123\nBairro\nCidade'),
(159, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-12 23:46:18', '2025-10-14 00:27:36', '2025-10-14 00:27:36', NULL, 'vmvb, bvnmvhb'),
(160, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-14 23:58:25', '2025-10-14 23:58:25', '2025-10-14 23:58:25', NULL, 'hfgh, fghfg'),
(161, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 00:57:16', '2025-10-16 01:21:09', '2025-10-16 01:21:09', NULL, 'fdfd, sdfsdf'),
(162, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 00:59:03', '2025-10-16 01:08:51', '2025-10-16 01:08:51', NULL, 'hfgh, fghfg'),
(163, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 01:07:13', '2025-10-16 01:08:54', '2025-10-16 01:08:54', NULL, 'fdfd, sdfsdf'),
(164, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'paid', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 01:13:34', '2025-10-16 01:20:54', '2025-10-16 01:20:54', NULL, 'fdfd, sdfsdf'),
(165, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 02:06:54', '2025-10-16 22:25:49', '2025-10-16 22:25:49', NULL, 'hfgh, fghfg'),
(166, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 02:10:43', '2025-10-16 22:25:51', '2025-10-16 22:25:51', NULL, 'hfgh, fghfg'),
(167, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 02:16:38', '2025-10-16 22:25:53', '2025-10-16 22:25:53', NULL, 'hfgh, fghfg'),
(168, 1, 'herick', '51920017687', 25.90, 0.00, 0.00, 25.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 02:20:52', '2025-10-16 22:25:54', '2025-10-16 22:25:54', NULL, 'ddv, dfd'),
(169, 1, 'herick', '51920017687', 25.90, 6.00, 0.00, 31.90, 'canceled', 'Pagamento: Dinheiro (genérico) — Valor informado: R$ 50,00 (Troco: R$ 18,10)', '2025-10-16 19:14:49', '2025-10-16 22:25:57', '2025-10-16 22:25:57', NULL, 'Rua 7, 538\nParque Emboaba - Tramandai'),
(170, 1, 'herick', '51920017687', 51.80, 6.00, 0.00, 57.80, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 22:09:39', '2025-10-16 22:25:59', '2025-10-16 22:25:59', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(171, 1, 'herick', '51920017687', 25.90, 6.00, 0.00, 31.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 22:16:44', '2025-10-16 22:25:31', '2025-10-16 22:25:31', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(172, 1, 'herick', '51920017687', 63.60, 6.00, 0.00, 69.60, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 22:24:58', '2025-10-16 22:24:58', '2025-10-16 22:24:58', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(173, 1, 'herick', '51920017687', 54.50, 6.00, 0.00, 60.50, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 22:32:29', '2025-10-16 22:32:29', '2025-10-16 22:32:29', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(174, 1, 'herick', '51920017687', 46.00, 6.00, 0.00, 52.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 22:39:19', '2025-10-16 22:39:19', '2025-10-16 22:39:19', NULL, ',, 4\nParque Emboaba - Tramandai'),
(175, 1, 'herick', '51920017687', 46.00, 6.00, 0.00, 52.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 22:43:42', '2025-10-16 22:43:42', '2025-10-16 22:43:42', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(176, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 23:18:50', '2025-10-16 23:18:50', '2025-10-16 23:18:50', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(177, 1, 'herick', '51920017687', 46.00, 6.00, 0.00, 52.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 23:19:14', '2025-10-16 23:19:14', '2025-10-16 23:19:14', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(178, 1, 'herick', '51920017687', 53.50, 6.00, 0.00, 59.50, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 23:33:49', '2025-10-16 23:33:49', '2025-10-16 23:33:49', NULL, 'rua, 756\nParque Emboaba - Tramandai'),
(179, 1, 'herick', '51920017687', 23.80, 6.00, 0.00, 29.80, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 23:51:12', '2025-10-16 23:51:12', '2025-10-16 23:51:12', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(180, 1, 'herick', '51920017687', 62.30, 6.00, 0.00, 68.30, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-16 23:55:24', '2025-10-16 23:55:24', '2025-10-16 23:55:24', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(181, 1, 'herick', '51920017687', 24.80, 6.00, 0.00, 30.80, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 00:02:27', '2025-10-17 03:29:55', '2025-10-17 03:29:55', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(182, 1, 'herick', '51920017687', 46.00, 6.00, 0.00, 52.00, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 01:10:33', '2025-10-17 03:29:57', '2025-10-17 03:29:57', NULL, ',, 4\nParque Emboaba - Tramandai'),
(183, 1, 'herick', '51920017687', 23.80, 6.00, 0.00, 29.80, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 01:24:33', '2025-10-17 03:30:00', '2025-10-17 03:30:00', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(184, 1, 'herick', '51920017687', 33.30, 6.00, 0.00, 39.30, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 01:28:02', '2025-10-17 03:30:17', '2025-10-17 03:30:17', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(185, 1, 'herick', '51920017687', 32.90, 6.00, 0.00, 38.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 01:37:59', '2025-10-17 03:30:19', '2025-10-17 03:30:19', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(186, 1, 'herick', '51920017687', 24.80, 6.00, 0.00, 30.80, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 01:42:31', '2025-10-17 03:30:21', '2025-10-17 03:30:21', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(187, 1, 'herick', '51920017687', 19.90, 6.00, 0.00, 25.90, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-17 01:48:34', '2025-10-17 01:48:34', '2025-10-17 01:48:34', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(188, 1, 'herick', '51920017687', 32.00, 6.00, 0.00, 38.00, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-18 00:31:08', '2025-10-18 01:51:11', '2025-10-18 01:51:11', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(189, 1, 'herick', '51920017687', 29.90, 6.00, 0.00, 35.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-18 01:32:38', '2025-10-18 01:51:13', '2025-10-18 01:51:13', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(190, 1, 'herick', '51920017687', 29.90, 6.00, 0.00, 35.90, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-18 01:39:38', '2025-10-18 01:51:14', '2025-10-18 01:51:14', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(191, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-18 01:41:58', '2025-10-18 01:51:17', '2025-10-18 01:51:17', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(192, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'canceled', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-18 01:42:49', '2025-10-18 01:51:20', '2025-10-18 01:51:20', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(193, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-18 01:43:10', '2025-10-18 01:43:10', '2025-10-18 01:43:10', NULL, 'rua, 7\nParque Emboaba - Tramandai'),
(211, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 01:39:07', '2025-10-21 01:39:07', '2025-10-21 01:39:07', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(212, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 01:42:23', '2025-10-21 01:42:23', '2025-10-21 01:42:23', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(213, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 02:14:29', '2025-10-21 02:14:29', '2025-10-21 02:14:29', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(214, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 02:15:13', '2025-10-21 02:15:13', '2025-10-21 02:15:13', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(215, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 02:20:29', '2025-10-21 02:20:29', '2025-10-21 02:20:29', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(216, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 02:49:41', '2025-10-21 02:49:41', '2025-10-21 02:49:41', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(217, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 02:50:07', '2025-10-21 02:50:07', '2025-10-21 02:50:07', NULL, 'dcdc, 54\nParque Emboaba - Tramandai'),
(218, 1, 'herick', '51920017687', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 02:52:58', '2025-10-21 02:52:58', '2025-10-21 02:52:58', NULL, 'Rua 7, 538\nParque Emboaba - Tramandai'),
(219, 1, 'herick', '51920017687', 31.00, 6.00, 0.00, 37.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 10:34:11', '2025-10-21 10:34:11', '2025-10-21 10:34:11', NULL, 'Rua 7, 538\nParque Emboaba - Tramandai'),
(220, 1, 'Victor Gabriel Duarte', '51993032200', 16.00, 6.00, 0.00, 22.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-21 19:55:20', '2025-10-21 19:55:20', '2025-10-21 19:55:20', NULL, 'DKASDKAS, 123\nParque Emboaba - Tramandai'),
(221, 1, 'Victor Gabriel Duarte', '51993032200', 26.00, 6.00, 0.00, 32.00, 'pending', 'Pagamento: Pix — Mandar comprovante após o pagamento', '2025-10-22 16:32:01', '2025-10-22 16:32:01', '2025-10-22 16:32:01', NULL, 'DKASDKAS, 123\nParque Emboaba - Tramandai');

-- --------------------------------------------------------

--
-- Estrutura para tabela `order_events`
--

CREATE TABLE `order_events` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` int NOT NULL,
  `company_id` int NOT NULL,
  `event_type` enum('order.created','order.updated','order.status_changed','order.canceled','keepalive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'order.updated',
  `status` enum('pending','paid','completed','canceled') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Despejando dados para a tabela `order_events`
--

INSERT INTO `order_events` (`id`, `order_id`, `company_id`, `event_type`, `status`, `payload`, `created_at`) VALUES
(1, 1, 1, 'order.created', 'pending', '{\"order\":{\"id\":1,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 57\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T20:12:32+00:00\",\"updated_at\":\"2025-10-04T20:12:32+00:00\",\"status_changed_at\":\"2025-10-04T20:12:32+00:00\",\"sla_deadline\":\"2025-10-04T20:32:32+00:00\",\"items\":[{\"id\":1,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T20:12:32+00:00\"}', '2025-10-04 17:12:32'),
(2, 2, 1, 'order.created', 'pending', '{\"order\":{\"id\":2,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 57\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T20:12:37+00:00\",\"updated_at\":\"2025-10-04T20:12:37+00:00\",\"status_changed_at\":\"2025-10-04T20:12:37+00:00\",\"sla_deadline\":\"2025-10-04T20:32:37+00:00\",\"items\":[{\"id\":2,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T20:12:37+00:00\"}', '2025-10-04 17:12:37'),
(3, 1, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":1,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 57\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T20:12:32+00:00\",\"updated_at\":\"2025-10-04T21:34:11+00:00\",\"status_changed_at\":\"2025-10-04T21:34:11+00:00\",\"sla_deadline\":\"2025-10-04T20:32:32+00:00\",\"items\":[{\"id\":1,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T21:34:11+00:00\"}', '2025-10-04 18:34:11'),
(4, 1, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":1,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 57\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T20:12:32+00:00\",\"updated_at\":\"2025-10-04T21:34:16+00:00\",\"status_changed_at\":\"2025-10-04T21:34:16+00:00\",\"sla_deadline\":\"2025-10-04T20:32:32+00:00\",\"items\":[{\"id\":1,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T21:34:16+00:00\"}', '2025-10-04 18:34:16'),
(5, 2, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":2,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 57\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T20:12:37+00:00\",\"updated_at\":\"2025-10-04T22:03:40+00:00\",\"status_changed_at\":\"2025-10-04T22:03:40+00:00\",\"sla_deadline\":\"2025-10-04T20:32:37+00:00\",\"items\":[{\"id\":2,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:03:40+00:00\"}', '2025-10-04 19:03:40'),
(6, 3, 1, 'order.created', 'pending', '{\"order\":{\"id\":3,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:06:37+00:00\",\"updated_at\":\"2025-10-04T22:06:37+00:00\",\"status_changed_at\":\"2025-10-04T22:06:37+00:00\",\"sla_deadline\":\"2025-10-04T22:26:37+00:00\",\"items\":[{\"id\":3,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:06:37+00:00\"}', '2025-10-04 19:06:37'),
(7, 4, 1, 'order.created', 'pending', '{\"order\":{\"id\":4,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:07:18+00:00\",\"updated_at\":\"2025-10-04T22:07:18+00:00\",\"status_changed_at\":\"2025-10-04T22:07:18+00:00\",\"sla_deadline\":\"2025-10-04T22:27:18+00:00\",\"items\":[{\"id\":4,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:07:18+00:00\"}', '2025-10-04 19:07:18'),
(8, 5, 1, 'order.created', 'pending', '{\"order\":{\"id\":5,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:10:26+00:00\",\"updated_at\":\"2025-10-04T22:10:26+00:00\",\"status_changed_at\":\"2025-10-04T22:10:26+00:00\",\"sla_deadline\":\"2025-10-04T22:30:26+00:00\",\"items\":[{\"id\":5,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:10:26+00:00\"}', '2025-10-04 19:10:26'),
(9, 5, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":5,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:10:26+00:00\",\"updated_at\":\"2025-10-04T22:10:52+00:00\",\"status_changed_at\":\"2025-10-04T22:10:52+00:00\",\"sla_deadline\":\"2025-10-04T22:30:26+00:00\",\"items\":[{\"id\":5,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:10:52+00:00\"}', '2025-10-04 19:10:52'),
(10, 6, 1, 'order.created', 'pending', '{\"order\":{\"id\":6,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:59:24+00:00\",\"updated_at\":\"2025-10-04T22:59:24+00:00\",\"status_changed_at\":\"2025-10-04T22:59:24+00:00\",\"sla_deadline\":\"2025-10-04T23:19:24+00:00\",\"items\":[{\"id\":6,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:59:24+00:00\"}', '2025-10-04 19:59:24'),
(11, 4, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":4,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:07:18+00:00\",\"updated_at\":\"2025-10-04T22:59:35+00:00\",\"status_changed_at\":\"2025-10-04T22:59:35+00:00\",\"sla_deadline\":\"2025-10-04T22:27:18+00:00\",\"items\":[{\"id\":4,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:59:35+00:00\"}', '2025-10-04 19:59:35'),
(12, 3, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":3,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:06:37+00:00\",\"updated_at\":\"2025-10-04T22:59:41+00:00\",\"status_changed_at\":\"2025-10-04T22:59:41+00:00\",\"sla_deadline\":\"2025-10-04T22:26:37+00:00\",\"items\":[{\"id\":3,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:59:41+00:00\"}', '2025-10-04 19:59:41'),
(13, 6, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":6,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:59:24+00:00\",\"updated_at\":\"2025-10-04T22:59:46+00:00\",\"status_changed_at\":\"2025-10-04T22:59:46+00:00\",\"sla_deadline\":\"2025-10-04T23:19:24+00:00\",\"items\":[{\"id\":6,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:59:46+00:00\"}', '2025-10-04 19:59:46'),
(14, 7, 1, 'order.created', 'pending', '{\"order\":{\"id\":7,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:59:52+00:00\",\"updated_at\":\"2025-10-04T22:59:52+00:00\",\"status_changed_at\":\"2025-10-04T22:59:52+00:00\",\"sla_deadline\":\"2025-10-04T23:19:52+00:00\",\"items\":[{\"id\":7,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T22:59:52+00:00\"}', '2025-10-04 19:59:52'),
(15, 8, 1, 'order.created', 'pending', '{\"order\":{\"id\":8,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T23:00:14+00:00\",\"updated_at\":\"2025-10-04T23:00:14+00:00\",\"status_changed_at\":\"2025-10-04T23:00:14+00:00\",\"sla_deadline\":\"2025-10-04T23:20:14+00:00\",\"items\":[{\"id\":8,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T23:00:14+00:00\"}', '2025-10-04 20:00:14'),
(16, 9, 1, 'order.created', 'pending', '{\"order\":{\"id\":9,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T23:54:47+00:00\",\"updated_at\":\"2025-10-04T23:54:47+00:00\",\"status_changed_at\":\"2025-10-04T23:54:47+00:00\",\"sla_deadline\":\"2025-10-05T00:14:47+00:00\",\"items\":[{\"id\":9,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T23:54:47+00:00\"}', '2025-10-04 20:54:47'),
(17, 10, 1, 'order.created', 'pending', '{\"order\":{\"id\":10,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T23:59:52+00:00\",\"updated_at\":\"2025-10-04T23:59:52+00:00\",\"status_changed_at\":\"2025-10-04T23:59:52+00:00\",\"sla_deadline\":\"2025-10-05T00:19:52+00:00\",\"items\":[{\"id\":10,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-04T23:59:52+00:00\"}', '2025-10-04 20:59:52'),
(18, 10, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":10,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T23:59:52+00:00\",\"updated_at\":\"2025-10-05T00:00:02+00:00\",\"status_changed_at\":\"2025-10-05T00:00:02+00:00\",\"sla_deadline\":\"2025-10-05T00:19:52+00:00\",\"items\":[{\"id\":10,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:00:02+00:00\"}', '2025-10-04 21:00:02'),
(19, 9, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":9,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T23:54:47+00:00\",\"updated_at\":\"2025-10-05T00:00:05+00:00\",\"status_changed_at\":\"2025-10-05T00:00:05+00:00\",\"sla_deadline\":\"2025-10-05T00:14:47+00:00\",\"items\":[{\"id\":9,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:00:05+00:00\"}', '2025-10-04 21:00:05'),
(20, 8, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":8,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T23:00:14+00:00\",\"updated_at\":\"2025-10-05T00:00:08+00:00\",\"status_changed_at\":\"2025-10-05T00:00:08+00:00\",\"sla_deadline\":\"2025-10-04T23:20:14+00:00\",\"items\":[{\"id\":8,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:00:08+00:00\"}', '2025-10-04 21:00:08'),
(21, 7, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":7,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-04T22:59:52+00:00\",\"updated_at\":\"2025-10-05T00:00:09+00:00\",\"status_changed_at\":\"2025-10-05T00:00:09+00:00\",\"sla_deadline\":\"2025-10-04T23:19:52+00:00\",\"items\":[{\"id\":7,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:00:09+00:00\"}', '2025-10-04 21:00:09'),
(22, 11, 1, 'order.created', 'pending', '{\"order\":{\"id\":11,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:00:13+00:00\",\"updated_at\":\"2025-10-05T00:00:13+00:00\",\"status_changed_at\":\"2025-10-05T00:00:13+00:00\",\"sla_deadline\":\"2025-10-05T00:20:13+00:00\",\"items\":[{\"id\":11,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:00:13+00:00\"}', '2025-10-04 21:00:13'),
(23, 12, 1, 'order.created', 'pending', '{\"order\":{\"id\":12,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:01:26+00:00\",\"updated_at\":\"2025-10-05T00:01:26+00:00\",\"status_changed_at\":\"2025-10-05T00:01:26+00:00\",\"sla_deadline\":\"2025-10-05T00:21:26+00:00\",\"items\":[{\"id\":12,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:01:26+00:00\"}', '2025-10-04 21:01:26'),
(24, 13, 1, 'order.created', 'pending', '{\"order\":{\"id\":13,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:03:37+00:00\",\"updated_at\":\"2025-10-05T00:03:37+00:00\",\"status_changed_at\":\"2025-10-05T00:03:37+00:00\",\"sla_deadline\":\"2025-10-05T00:23:37+00:00\",\"items\":[{\"id\":13,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:03:37+00:00\"}', '2025-10-04 21:03:37'),
(25, 14, 1, 'order.created', 'pending', '{\"order\":{\"id\":14,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:06:00+00:00\",\"updated_at\":\"2025-10-05T00:06:00+00:00\",\"status_changed_at\":\"2025-10-05T00:06:00+00:00\",\"sla_deadline\":\"2025-10-05T00:26:00+00:00\",\"items\":[{\"id\":14,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:00+00:00\"}', '2025-10-04 21:06:00'),
(26, 14, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":14,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:06:00+00:00\",\"updated_at\":\"2025-10-05T00:06:11+00:00\",\"status_changed_at\":\"2025-10-05T00:06:11+00:00\",\"sla_deadline\":\"2025-10-05T00:26:00+00:00\",\"items\":[{\"id\":14,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:11+00:00\"}', '2025-10-04 21:06:11'),
(27, 12, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":12,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:01:26+00:00\",\"updated_at\":\"2025-10-05T00:06:13+00:00\",\"status_changed_at\":\"2025-10-05T00:06:13+00:00\",\"sla_deadline\":\"2025-10-05T00:21:26+00:00\",\"items\":[{\"id\":12,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:13+00:00\"}', '2025-10-04 21:06:13'),
(28, 11, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":11,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:00:13+00:00\",\"updated_at\":\"2025-10-05T00:06:15+00:00\",\"status_changed_at\":\"2025-10-05T00:06:15+00:00\",\"sla_deadline\":\"2025-10-05T00:20:13+00:00\",\"items\":[{\"id\":11,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:15+00:00\"}', '2025-10-04 21:06:15'),
(29, 13, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":13,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:03:37+00:00\",\"updated_at\":\"2025-10-05T00:06:19+00:00\",\"status_changed_at\":\"2025-10-05T00:06:19+00:00\",\"sla_deadline\":\"2025-10-05T00:23:37+00:00\",\"items\":[{\"id\":13,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:19+00:00\"}', '2025-10-04 21:06:19'),
(30, 15, 1, 'order.created', 'pending', '{\"order\":{\"id\":15,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:06:35+00:00\",\"updated_at\":\"2025-10-05T00:06:35+00:00\",\"status_changed_at\":\"2025-10-05T00:06:35+00:00\",\"sla_deadline\":\"2025-10-05T00:26:35+00:00\",\"items\":[{\"id\":15,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:35+00:00\"}', '2025-10-04 21:06:35'),
(31, 16, 1, 'order.created', 'pending', '{\"order\":{\"id\":16,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:06:53+00:00\",\"updated_at\":\"2025-10-05T00:06:53+00:00\",\"status_changed_at\":\"2025-10-05T00:06:53+00:00\",\"sla_deadline\":\"2025-10-05T00:26:53+00:00\",\"items\":[{\"id\":16,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:06:53+00:00\"}', '2025-10-04 21:06:53'),
(32, 17, 1, 'order.created', 'pending', '{\"order\":{\"id\":17,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:10:28+00:00\",\"updated_at\":\"2025-10-05T00:10:28+00:00\",\"status_changed_at\":\"2025-10-05T00:10:28+00:00\",\"sla_deadline\":\"2025-10-05T00:30:28+00:00\",\"items\":[{\"id\":17,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:10:28+00:00\"}', '2025-10-04 21:10:28'),
(33, 18, 1, 'order.created', 'pending', '{\"order\":{\"id\":18,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:13:45+00:00\",\"updated_at\":\"2025-10-05T00:13:45+00:00\",\"status_changed_at\":\"2025-10-05T00:13:45+00:00\",\"sla_deadline\":\"2025-10-05T00:33:45+00:00\",\"items\":[{\"id\":18,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:13:45+00:00\"}', '2025-10-04 21:13:45'),
(34, 19, 1, 'order.created', 'pending', '{\"order\":{\"id\":19,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:23:30+00:00\",\"updated_at\":\"2025-10-05T00:23:30+00:00\",\"status_changed_at\":\"2025-10-05T00:23:30+00:00\",\"sla_deadline\":\"2025-10-05T00:43:30+00:00\",\"items\":[{\"id\":19,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:23:30+00:00\"}', '2025-10-04 21:23:30'),
(35, 20, 1, 'order.created', 'pending', '{\"order\":{\"id\":20,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:47:54+00:00\",\"updated_at\":\"2025-10-05T00:47:54+00:00\",\"status_changed_at\":\"2025-10-05T00:47:54+00:00\",\"sla_deadline\":\"2025-10-05T01:07:54+00:00\",\"items\":[{\"id\":20,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:47:54+00:00\"}', '2025-10-04 21:47:54'),
(36, 19, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":19,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:23:30+00:00\",\"updated_at\":\"2025-10-05T00:48:10+00:00\",\"status_changed_at\":\"2025-10-05T00:48:10+00:00\",\"sla_deadline\":\"2025-10-05T00:43:30+00:00\",\"items\":[{\"id\":19,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:10+00:00\"}', '2025-10-04 21:48:10'),
(37, 18, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":18,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:13:45+00:00\",\"updated_at\":\"2025-10-05T00:48:13+00:00\",\"status_changed_at\":\"2025-10-05T00:48:13+00:00\",\"sla_deadline\":\"2025-10-05T00:33:45+00:00\",\"items\":[{\"id\":18,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:13+00:00\"}', '2025-10-04 21:48:13'),
(38, 17, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":17,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:10:28+00:00\",\"updated_at\":\"2025-10-05T00:48:17+00:00\",\"status_changed_at\":\"2025-10-05T00:48:17+00:00\",\"sla_deadline\":\"2025-10-05T00:30:28+00:00\",\"items\":[{\"id\":17,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:17+00:00\"}', '2025-10-04 21:48:17'),
(39, 16, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":16,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:06:53+00:00\",\"updated_at\":\"2025-10-05T00:48:18+00:00\",\"status_changed_at\":\"2025-10-05T00:48:18+00:00\",\"sla_deadline\":\"2025-10-05T00:26:53+00:00\",\"items\":[{\"id\":16,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:18+00:00\"}', '2025-10-04 21:48:18'),
(40, 15, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":15,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:06:35+00:00\",\"updated_at\":\"2025-10-05T00:48:20+00:00\",\"status_changed_at\":\"2025-10-05T00:48:20+00:00\",\"sla_deadline\":\"2025-10-05T00:26:35+00:00\",\"items\":[{\"id\":15,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:20+00:00\"}', '2025-10-04 21:48:20'),
(41, 20, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":20,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:47:54+00:00\",\"updated_at\":\"2025-10-05T00:48:23+00:00\",\"status_changed_at\":\"2025-10-05T00:48:23+00:00\",\"sla_deadline\":\"2025-10-05T01:07:54+00:00\",\"items\":[{\"id\":20,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:23+00:00\"}', '2025-10-04 21:48:23'),
(42, 21, 1, 'order.created', 'pending', '{\"order\":{\"id\":21,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:48:26+00:00\",\"updated_at\":\"2025-10-05T00:48:26+00:00\",\"status_changed_at\":\"2025-10-05T00:48:26+00:00\",\"sla_deadline\":\"2025-10-05T01:08:26+00:00\",\"items\":[{\"id\":21,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:26+00:00\"}', '2025-10-04 21:48:26'),
(43, 22, 1, 'order.created', 'pending', '{\"order\":{\"id\":22,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:48:53+00:00\",\"updated_at\":\"2025-10-05T00:48:53+00:00\",\"status_changed_at\":\"2025-10-05T00:48:53+00:00\",\"sla_deadline\":\"2025-10-05T01:08:53+00:00\",\"items\":[{\"id\":22,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T00:48:53+00:00\"}', '2025-10-04 21:48:53'),
(44, 23, 1, 'order.created', 'pending', '{\"order\":{\"id\":23,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:52:12+00:00\",\"updated_at\":\"2025-10-05T01:52:12+00:00\",\"status_changed_at\":\"2025-10-05T01:52:12+00:00\",\"sla_deadline\":\"2025-10-05T02:12:12+00:00\",\"items\":[{\"id\":23,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T01:52:13+00:00\"}', '2025-10-04 22:52:13'),
(45, 24, 1, 'order.created', 'pending', '{\"order\":{\"id\":24,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:52:26+00:00\",\"updated_at\":\"2025-10-05T01:52:26+00:00\",\"status_changed_at\":\"2025-10-05T01:52:26+00:00\",\"sla_deadline\":\"2025-10-05T02:12:26+00:00\",\"items\":[{\"id\":24,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T01:52:26+00:00\"}', '2025-10-04 22:52:26'),
(46, 25, 1, 'order.created', 'pending', '{\"order\":{\"id\":25,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:52:41+00:00\",\"updated_at\":\"2025-10-05T01:52:41+00:00\",\"status_changed_at\":\"2025-10-05T01:52:41+00:00\",\"sla_deadline\":\"2025-10-05T02:12:41+00:00\",\"items\":[{\"id\":25,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T01:52:41+00:00\"}', '2025-10-04 22:52:41'),
(47, 26, 1, 'order.created', 'pending', '{\"order\":{\"id\":26,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:59:41+00:00\",\"updated_at\":\"2025-10-05T01:59:41+00:00\",\"status_changed_at\":\"2025-10-05T01:59:41+00:00\",\"sla_deadline\":\"2025-10-05T02:19:41+00:00\",\"items\":[{\"id\":26,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T01:59:41+00:00\"}', '2025-10-04 22:59:41'),
(48, 27, 1, 'order.created', 'pending', '{\"order\":{\"id\":27,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:59:53+00:00\",\"updated_at\":\"2025-10-05T01:59:53+00:00\",\"status_changed_at\":\"2025-10-05T01:59:53+00:00\",\"sla_deadline\":\"2025-10-05T02:19:53+00:00\",\"items\":[{\"id\":27,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T01:59:53+00:00\"}', '2025-10-04 22:59:53'),
(49, 28, 1, 'order.created', 'pending', '{\"order\":{\"id\":28,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:02:39+00:00\",\"updated_at\":\"2025-10-05T02:02:39+00:00\",\"status_changed_at\":\"2025-10-05T02:02:39+00:00\",\"sla_deadline\":\"2025-10-05T02:22:39+00:00\",\"items\":[{\"id\":28,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:39+00:00\"}', '2025-10-04 23:02:39'),
(50, 28, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":28,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:02:39+00:00\",\"updated_at\":\"2025-10-05T02:02:50+00:00\",\"status_changed_at\":\"2025-10-05T02:02:50+00:00\",\"sla_deadline\":\"2025-10-05T02:22:39+00:00\",\"items\":[{\"id\":28,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:50+00:00\"}', '2025-10-04 23:02:50'),
(51, 27, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":27,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:59:53+00:00\",\"updated_at\":\"2025-10-05T02:02:52+00:00\",\"status_changed_at\":\"2025-10-05T02:02:52+00:00\",\"sla_deadline\":\"2025-10-05T02:19:53+00:00\",\"items\":[{\"id\":27,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:52+00:00\"}', '2025-10-04 23:02:52'),
(52, 26, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":26,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:59:41+00:00\",\"updated_at\":\"2025-10-05T02:02:53+00:00\",\"status_changed_at\":\"2025-10-05T02:02:53+00:00\",\"sla_deadline\":\"2025-10-05T02:19:41+00:00\",\"items\":[{\"id\":26,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:53+00:00\"}', '2025-10-04 23:02:53'),
(53, 25, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":25,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:52:41+00:00\",\"updated_at\":\"2025-10-05T02:02:55+00:00\",\"status_changed_at\":\"2025-10-05T02:02:55+00:00\",\"sla_deadline\":\"2025-10-05T02:12:41+00:00\",\"items\":[{\"id\":25,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:55+00:00\"}', '2025-10-04 23:02:55'),
(54, 24, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":24,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:52:26+00:00\",\"updated_at\":\"2025-10-05T02:02:57+00:00\",\"status_changed_at\":\"2025-10-05T02:02:57+00:00\",\"sla_deadline\":\"2025-10-05T02:12:26+00:00\",\"items\":[{\"id\":24,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:57+00:00\"}', '2025-10-04 23:02:57'),
(55, 23, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":23,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T01:52:12+00:00\",\"updated_at\":\"2025-10-05T02:02:59+00:00\",\"status_changed_at\":\"2025-10-05T02:02:59+00:00\",\"sla_deadline\":\"2025-10-05T02:12:12+00:00\",\"items\":[{\"id\":23,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:02:59+00:00\"}', '2025-10-04 23:02:59'),
(56, 22, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":22,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:48:53+00:00\",\"updated_at\":\"2025-10-05T02:03:01+00:00\",\"status_changed_at\":\"2025-10-05T02:03:01+00:00\",\"sla_deadline\":\"2025-10-05T01:08:53+00:00\",\"items\":[{\"id\":22,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:03:01+00:00\"}', '2025-10-04 23:03:01'),
(57, 29, 1, 'order.created', 'pending', '{\"order\":{\"id\":29,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:10:43+00:00\",\"updated_at\":\"2025-10-05T02:10:43+00:00\",\"status_changed_at\":\"2025-10-05T02:10:43+00:00\",\"sla_deadline\":\"2025-10-05T02:30:43+00:00\",\"items\":[{\"id\":29,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:10:43+00:00\"}', '2025-10-04 23:10:43'),
(58, 30, 1, 'order.created', 'pending', '{\"order\":{\"id\":30,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:11:08+00:00\",\"updated_at\":\"2025-10-05T02:11:08+00:00\",\"status_changed_at\":\"2025-10-05T02:11:08+00:00\",\"sla_deadline\":\"2025-10-05T02:31:08+00:00\",\"items\":[{\"id\":30,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:11:08+00:00\"}', '2025-10-04 23:11:08'),
(59, 31, 1, 'order.created', 'pending', '{\"order\":{\"id\":31,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:11:25+00:00\",\"updated_at\":\"2025-10-05T02:11:25+00:00\",\"status_changed_at\":\"2025-10-05T02:11:25+00:00\",\"sla_deadline\":\"2025-10-05T02:31:25+00:00\",\"items\":[{\"id\":31,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:11:25+00:00\"}', '2025-10-04 23:11:25'),
(60, 30, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":30,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:11:08+00:00\",\"updated_at\":\"2025-10-05T02:11:39+00:00\",\"status_changed_at\":\"2025-10-05T02:11:39+00:00\",\"sla_deadline\":\"2025-10-05T02:31:08+00:00\",\"items\":[{\"id\":30,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:11:39+00:00\"}', '2025-10-04 23:11:39'),
(61, 29, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":29,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:10:43+00:00\",\"updated_at\":\"2025-10-05T02:11:41+00:00\",\"status_changed_at\":\"2025-10-05T02:11:41+00:00\",\"sla_deadline\":\"2025-10-05T02:30:43+00:00\",\"items\":[{\"id\":29,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:11:41+00:00\"}', '2025-10-04 23:11:41'),
(62, 21, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":21,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T00:48:26+00:00\",\"updated_at\":\"2025-10-05T02:11:43+00:00\",\"status_changed_at\":\"2025-10-05T02:11:43+00:00\",\"sla_deadline\":\"2025-10-05T01:08:26+00:00\",\"items\":[{\"id\":21,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:11:43+00:00\"}', '2025-10-04 23:11:43'),
(63, 31, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":31,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:11:25+00:00\",\"updated_at\":\"2025-10-05T02:11:45+00:00\",\"status_changed_at\":\"2025-10-05T02:11:45+00:00\",\"sla_deadline\":\"2025-10-05T02:31:25+00:00\",\"items\":[{\"id\":31,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:11:45+00:00\"}', '2025-10-04 23:11:45'),
(64, 32, 1, 'order.created', 'pending', '{\"order\":{\"id\":32,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:12:15+00:00\",\"updated_at\":\"2025-10-05T02:12:15+00:00\",\"status_changed_at\":\"2025-10-05T02:12:15+00:00\",\"sla_deadline\":\"2025-10-05T02:32:15+00:00\",\"items\":[{\"id\":32,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:12:15+00:00\"}', '2025-10-04 23:12:15'),
(65, 32, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":32,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:12:15+00:00\",\"updated_at\":\"2025-10-05T02:13:07+00:00\",\"status_changed_at\":\"2025-10-05T02:13:07+00:00\",\"sla_deadline\":\"2025-10-05T02:32:15+00:00\",\"items\":[{\"id\":32,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:13:07+00:00\"}', '2025-10-04 23:13:07'),
(66, 32, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":32,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:12:15+00:00\",\"updated_at\":\"2025-10-05T02:13:25+00:00\",\"status_changed_at\":\"2025-10-05T02:13:25+00:00\",\"sla_deadline\":\"2025-10-05T02:32:15+00:00\",\"items\":[{\"id\":32,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:13:25+00:00\"}', '2025-10-04 23:13:25'),
(67, 33, 1, 'order.created', 'pending', '{\"order\":{\"id\":33,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:13:46+00:00\",\"updated_at\":\"2025-10-05T02:13:46+00:00\",\"status_changed_at\":\"2025-10-05T02:13:46+00:00\",\"sla_deadline\":\"2025-10-05T02:33:46+00:00\",\"items\":[{\"id\":33,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:13:46+00:00\"}', '2025-10-04 23:13:46'),
(68, 34, 1, 'order.created', 'pending', '{\"order\":{\"id\":34,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:14:07+00:00\",\"updated_at\":\"2025-10-05T02:14:07+00:00\",\"status_changed_at\":\"2025-10-05T02:14:07+00:00\",\"sla_deadline\":\"2025-10-05T02:34:07+00:00\",\"items\":[{\"id\":34,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:14:07+00:00\"}', '2025-10-04 23:14:07'),
(69, 35, 1, 'order.created', 'pending', '{\"order\":{\"id\":35,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:20:46+00:00\",\"updated_at\":\"2025-10-05T02:20:46+00:00\",\"status_changed_at\":\"2025-10-05T02:20:46+00:00\",\"sla_deadline\":\"2025-10-05T02:40:46+00:00\",\"items\":[{\"id\":35,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:20:46+00:00\"}', '2025-10-04 23:20:46'),
(70, 36, 1, 'order.created', 'pending', '{\"order\":{\"id\":36,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:21:05+00:00\",\"updated_at\":\"2025-10-05T02:21:05+00:00\",\"status_changed_at\":\"2025-10-05T02:21:05+00:00\",\"sla_deadline\":\"2025-10-05T02:41:05+00:00\",\"items\":[{\"id\":36,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:21:05+00:00\"}', '2025-10-04 23:21:05'),
(71, 37, 1, 'order.created', 'pending', '{\"order\":{\"id\":37,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:21:25+00:00\",\"updated_at\":\"2025-10-05T02:21:25+00:00\",\"status_changed_at\":\"2025-10-05T02:21:25+00:00\",\"sla_deadline\":\"2025-10-05T02:41:25+00:00\",\"items\":[{\"id\":37,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:21:25+00:00\"}', '2025-10-04 23:21:25'),
(72, 38, 1, 'order.created', 'pending', '{\"order\":{\"id\":38,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:26:52+00:00\",\"updated_at\":\"2025-10-05T02:26:52+00:00\",\"status_changed_at\":\"2025-10-05T02:26:52+00:00\",\"sla_deadline\":\"2025-10-05T02:46:52+00:00\",\"items\":[{\"id\":38,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:26:52+00:00\"}', '2025-10-04 23:26:52'),
(73, 39, 1, 'order.created', 'pending', '{\"order\":{\"id\":39,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:30:14+00:00\",\"updated_at\":\"2025-10-05T02:30:14+00:00\",\"status_changed_at\":\"2025-10-05T02:30:14+00:00\",\"sla_deadline\":\"2025-10-05T02:50:14+00:00\",\"items\":[{\"id\":39,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:30:14+00:00\"}', '2025-10-04 23:30:14');
INSERT INTO `order_events` (`id`, `order_id`, `company_id`, `event_type`, `status`, `payload`, `created_at`) VALUES
(74, 40, 1, 'order.created', 'pending', '{\"order\":{\"id\":40,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:30:31+00:00\",\"updated_at\":\"2025-10-05T02:30:31+00:00\",\"status_changed_at\":\"2025-10-05T02:30:31+00:00\",\"sla_deadline\":\"2025-10-05T02:50:31+00:00\",\"items\":[{\"id\":40,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:30:31+00:00\"}', '2025-10-04 23:30:31'),
(75, 41, 1, 'order.created', 'pending', '{\"order\":{\"id\":41,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:34:09+00:00\",\"updated_at\":\"2025-10-05T02:34:09+00:00\",\"status_changed_at\":\"2025-10-05T02:34:09+00:00\",\"sla_deadline\":\"2025-10-05T02:54:09+00:00\",\"items\":[{\"id\":41,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:34:09+00:00\"}', '2025-10-04 23:34:09'),
(76, 42, 1, 'order.created', 'pending', '{\"order\":{\"id\":42,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:34:25+00:00\",\"updated_at\":\"2025-10-05T02:34:25+00:00\",\"status_changed_at\":\"2025-10-05T02:34:25+00:00\",\"sla_deadline\":\"2025-10-05T02:54:25+00:00\",\"items\":[{\"id\":42,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:34:25+00:00\"}', '2025-10-04 23:34:25'),
(77, 33, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":33,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:13:46+00:00\",\"updated_at\":\"2025-10-05T02:34:52+00:00\",\"status_changed_at\":\"2025-10-05T02:34:52+00:00\",\"sla_deadline\":\"2025-10-05T02:33:46+00:00\",\"items\":[{\"id\":33,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:34:52+00:00\"}', '2025-10-04 23:34:52'),
(78, 34, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":34,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:14:07+00:00\",\"updated_at\":\"2025-10-05T02:34:54+00:00\",\"status_changed_at\":\"2025-10-05T02:34:54+00:00\",\"sla_deadline\":\"2025-10-05T02:34:07+00:00\",\"items\":[{\"id\":34,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:34:54+00:00\"}', '2025-10-04 23:34:54'),
(79, 35, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":35,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:20:46+00:00\",\"updated_at\":\"2025-10-05T02:34:55+00:00\",\"status_changed_at\":\"2025-10-05T02:34:55+00:00\",\"sla_deadline\":\"2025-10-05T02:40:46+00:00\",\"items\":[{\"id\":35,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:34:55+00:00\"}', '2025-10-04 23:34:55'),
(80, 36, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":36,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:21:05+00:00\",\"updated_at\":\"2025-10-05T02:34:58+00:00\",\"status_changed_at\":\"2025-10-05T02:34:58+00:00\",\"sla_deadline\":\"2025-10-05T02:41:05+00:00\",\"items\":[{\"id\":36,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:34:58+00:00\"}', '2025-10-04 23:34:58'),
(81, 37, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":37,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:21:25+00:00\",\"updated_at\":\"2025-10-05T02:35:01+00:00\",\"status_changed_at\":\"2025-10-05T02:35:01+00:00\",\"sla_deadline\":\"2025-10-05T02:41:25+00:00\",\"items\":[{\"id\":37,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:35:01+00:00\"}', '2025-10-04 23:35:01'),
(82, 38, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":38,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:26:52+00:00\",\"updated_at\":\"2025-10-05T02:35:03+00:00\",\"status_changed_at\":\"2025-10-05T02:35:03+00:00\",\"sla_deadline\":\"2025-10-05T02:46:52+00:00\",\"items\":[{\"id\":38,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:35:03+00:00\"}', '2025-10-04 23:35:03'),
(83, 39, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":39,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:30:14+00:00\",\"updated_at\":\"2025-10-05T02:35:05+00:00\",\"status_changed_at\":\"2025-10-05T02:35:05+00:00\",\"sla_deadline\":\"2025-10-05T02:50:14+00:00\",\"items\":[{\"id\":39,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:35:05+00:00\"}', '2025-10-04 23:35:05'),
(84, 40, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":40,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:30:31+00:00\",\"updated_at\":\"2025-10-05T02:35:06+00:00\",\"status_changed_at\":\"2025-10-05T02:35:06+00:00\",\"sla_deadline\":\"2025-10-05T02:50:31+00:00\",\"items\":[{\"id\":40,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:35:06+00:00\"}', '2025-10-04 23:35:06'),
(85, 41, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":41,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:34:09+00:00\",\"updated_at\":\"2025-10-05T02:35:09+00:00\",\"status_changed_at\":\"2025-10-05T02:35:09+00:00\",\"sla_deadline\":\"2025-10-05T02:54:09+00:00\",\"items\":[{\"id\":41,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:35:09+00:00\"}', '2025-10-04 23:35:09'),
(86, 42, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":42,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:34:25+00:00\",\"updated_at\":\"2025-10-05T02:35:11+00:00\",\"status_changed_at\":\"2025-10-05T02:35:11+00:00\",\"sla_deadline\":\"2025-10-05T02:54:25+00:00\",\"items\":[{\"id\":42,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:35:11+00:00\"}', '2025-10-04 23:35:11'),
(87, 43, 1, 'order.created', 'pending', '{\"order\":{\"id\":43,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:38:34+00:00\",\"updated_at\":\"2025-10-05T02:38:34+00:00\",\"status_changed_at\":\"2025-10-05T02:38:34+00:00\",\"sla_deadline\":\"2025-10-05T02:58:34+00:00\",\"items\":[{\"id\":43,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:38:34+00:00\"}', '2025-10-04 23:38:34'),
(88, 44, 1, 'order.created', 'pending', '{\"order\":{\"id\":44,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:39:44+00:00\",\"updated_at\":\"2025-10-05T02:39:44+00:00\",\"status_changed_at\":\"2025-10-05T02:39:44+00:00\",\"sla_deadline\":\"2025-10-05T02:59:44+00:00\",\"items\":[{\"id\":44,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:39:44+00:00\"}', '2025-10-04 23:39:44'),
(89, 45, 1, 'order.created', 'pending', '{\"order\":{\"id\":45,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:41:22+00:00\",\"updated_at\":\"2025-10-05T02:41:22+00:00\",\"status_changed_at\":\"2025-10-05T02:41:22+00:00\",\"sla_deadline\":\"2025-10-05T03:01:22+00:00\",\"items\":[{\"id\":45,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:41:22+00:00\"}', '2025-10-04 23:41:22'),
(90, 46, 1, 'order.created', 'pending', '{\"order\":{\"id\":46,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:42:07+00:00\",\"updated_at\":\"2025-10-05T02:42:07+00:00\",\"status_changed_at\":\"2025-10-05T02:42:07+00:00\",\"sla_deadline\":\"2025-10-05T03:02:07+00:00\",\"items\":[{\"id\":46,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:42:07+00:00\"}', '2025-10-04 23:42:07'),
(91, 47, 1, 'order.created', 'pending', '{\"order\":{\"id\":47,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:42:55+00:00\",\"updated_at\":\"2025-10-05T02:42:55+00:00\",\"status_changed_at\":\"2025-10-05T02:42:55+00:00\",\"sla_deadline\":\"2025-10-05T03:02:55+00:00\",\"items\":[{\"id\":47,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:42:55+00:00\"}', '2025-10-04 23:42:55'),
(92, 48, 1, 'order.created', 'pending', '{\"order\":{\"id\":48,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:46:13+00:00\",\"updated_at\":\"2025-10-05T02:46:13+00:00\",\"status_changed_at\":\"2025-10-05T02:46:13+00:00\",\"sla_deadline\":\"2025-10-05T03:06:13+00:00\",\"items\":[{\"id\":48,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:46:13+00:00\"}', '2025-10-04 23:46:13'),
(93, 49, 1, 'order.created', 'pending', '{\"order\":{\"id\":49,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:46:43+00:00\",\"updated_at\":\"2025-10-05T02:46:43+00:00\",\"status_changed_at\":\"2025-10-05T02:46:43+00:00\",\"sla_deadline\":\"2025-10-05T03:06:43+00:00\",\"items\":[{\"id\":49,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:46:43+00:00\"}', '2025-10-04 23:46:43'),
(94, 50, 1, 'order.created', 'pending', '{\"order\":{\"id\":50,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:57:40+00:00\",\"updated_at\":\"2025-10-05T02:57:40+00:00\",\"status_changed_at\":\"2025-10-05T02:57:40+00:00\",\"sla_deadline\":\"2025-10-05T03:17:40+00:00\",\"items\":[{\"id\":50,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:57:40+00:00\"}', '2025-10-04 23:57:40'),
(95, 51, 1, 'order.created', 'pending', '{\"order\":{\"id\":51,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T02:58:06+00:00\",\"updated_at\":\"2025-10-05T02:58:06+00:00\",\"status_changed_at\":\"2025-10-05T02:58:06+00:00\",\"sla_deadline\":\"2025-10-05T03:18:06+00:00\",\"items\":[{\"id\":51,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T02:58:06+00:00\"}', '2025-10-04 23:58:06'),
(96, 52, 1, 'order.created', 'pending', '{\"order\":{\"id\":52,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:06:51+00:00\",\"updated_at\":\"2025-10-05T03:06:51+00:00\",\"status_changed_at\":\"2025-10-05T03:06:51+00:00\",\"sla_deadline\":\"2025-10-05T03:26:51+00:00\",\"items\":[{\"id\":52,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T03:06:51+00:00\"}', '2025-10-05 00:06:51'),
(97, 53, 1, 'order.created', 'pending', '{\"order\":{\"id\":53,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:07:11+00:00\",\"updated_at\":\"2025-10-05T03:07:11+00:00\",\"status_changed_at\":\"2025-10-05T03:07:11+00:00\",\"sla_deadline\":\"2025-10-05T03:27:11+00:00\",\"items\":[{\"id\":53,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T03:07:11+00:00\"}', '2025-10-05 00:07:11'),
(98, 52, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":52,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:06:51+00:00\",\"updated_at\":\"2025-10-05T03:08:01+00:00\",\"status_changed_at\":\"2025-10-05T03:08:01+00:00\",\"sla_deadline\":\"2025-10-05T03:26:51+00:00\",\"items\":[{\"id\":52,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T03:08:01+00:00\"}', '2025-10-05 00:08:01'),
(99, 53, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":53,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:07:11+00:00\",\"updated_at\":\"2025-10-05T03:08:02+00:00\",\"status_changed_at\":\"2025-10-05T03:08:02+00:00\",\"sla_deadline\":\"2025-10-05T03:27:11+00:00\",\"items\":[{\"id\":53,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T03:08:02+00:00\"}', '2025-10-05 00:08:02'),
(100, 54, 1, 'order.created', 'pending', '{\"order\":{\"id\":54,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:08:05+00:00\",\"updated_at\":\"2025-10-05T03:08:05+00:00\",\"status_changed_at\":\"2025-10-05T03:08:05+00:00\",\"sla_deadline\":\"2025-10-05T03:28:05+00:00\",\"items\":[{\"id\":54,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T03:08:05+00:00\"}', '2025-10-05 00:08:05'),
(101, 55, 1, 'order.created', 'pending', '{\"order\":{\"id\":55,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:14:02+00:00\",\"updated_at\":\"2025-10-05T03:14:02+00:00\",\"status_changed_at\":\"2025-10-05T03:14:02+00:00\",\"sla_deadline\":\"2025-10-05T03:34:02+00:00\",\"items\":[{\"id\":55,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T03:14:02+00:00\"}', '2025-10-05 00:14:02'),
(102, 56, 1, 'order.created', 'pending', '{\"order\":{\"id\":56,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:05:20+00:00\",\"updated_at\":\"2025-10-05T04:05:20+00:00\",\"status_changed_at\":\"2025-10-05T04:05:20+00:00\",\"sla_deadline\":\"2025-10-05T04:25:20+00:00\",\"items\":[{\"id\":56,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:05:20+00:00\"}', '2025-10-05 01:05:20'),
(103, 57, 1, 'order.created', 'pending', '{\"order\":{\"id\":57,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:15:50+00:00\",\"updated_at\":\"2025-10-05T04:15:50+00:00\",\"status_changed_at\":\"2025-10-05T04:15:50+00:00\",\"sla_deadline\":\"2025-10-05T04:35:50+00:00\",\"items\":[{\"id\":57,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:15:50+00:00\"}', '2025-10-05 01:15:50'),
(104, 58, 1, 'order.created', 'pending', '{\"order\":{\"id\":58,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:18:15+00:00\",\"updated_at\":\"2025-10-05T04:18:15+00:00\",\"status_changed_at\":\"2025-10-05T04:18:15+00:00\",\"sla_deadline\":\"2025-10-05T04:38:15+00:00\",\"items\":[{\"id\":58,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:15+00:00\"}', '2025-10-05 01:18:15'),
(105, 54, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":54,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:08:05+00:00\",\"updated_at\":\"2025-10-05T04:18:29+00:00\",\"status_changed_at\":\"2025-10-05T04:18:29+00:00\",\"sla_deadline\":\"2025-10-05T03:28:05+00:00\",\"items\":[{\"id\":54,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:29+00:00\"}', '2025-10-05 01:18:29'),
(106, 55, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":55,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T03:14:02+00:00\",\"updated_at\":\"2025-10-05T04:18:31+00:00\",\"status_changed_at\":\"2025-10-05T04:18:31+00:00\",\"sla_deadline\":\"2025-10-05T03:34:02+00:00\",\"items\":[{\"id\":55,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:31+00:00\"}', '2025-10-05 01:18:31'),
(107, 56, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":56,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:05:20+00:00\",\"updated_at\":\"2025-10-05T04:18:34+00:00\",\"status_changed_at\":\"2025-10-05T04:18:34+00:00\",\"sla_deadline\":\"2025-10-05T04:25:20+00:00\",\"items\":[{\"id\":56,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:34+00:00\"}', '2025-10-05 01:18:34'),
(108, 57, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":57,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:15:50+00:00\",\"updated_at\":\"2025-10-05T04:18:36+00:00\",\"status_changed_at\":\"2025-10-05T04:18:36+00:00\",\"sla_deadline\":\"2025-10-05T04:35:50+00:00\",\"items\":[{\"id\":57,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:36+00:00\"}', '2025-10-05 01:18:36'),
(109, 58, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":58,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:18:15+00:00\",\"updated_at\":\"2025-10-05T04:18:39+00:00\",\"status_changed_at\":\"2025-10-05T04:18:39+00:00\",\"sla_deadline\":\"2025-10-05T04:38:15+00:00\",\"items\":[{\"id\":58,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:39+00:00\"}', '2025-10-05 01:18:39'),
(110, 59, 1, 'order.created', 'pending', '{\"order\":{\"id\":59,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:18:42+00:00\",\"updated_at\":\"2025-10-05T04:18:42+00:00\",\"status_changed_at\":\"2025-10-05T04:18:42+00:00\",\"sla_deadline\":\"2025-10-05T04:38:42+00:00\",\"items\":[{\"id\":59,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:18:42+00:00\"}', '2025-10-05 01:18:42'),
(111, 60, 1, 'order.created', 'pending', '{\"order\":{\"id\":60,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:37:32+00:00\",\"updated_at\":\"2025-10-05T04:37:32+00:00\",\"status_changed_at\":\"2025-10-05T04:37:32+00:00\",\"sla_deadline\":\"2025-10-05T04:57:32+00:00\",\"items\":[{\"id\":60,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:37:32+00:00\"}', '2025-10-05 01:37:32'),
(112, 61, 1, 'order.created', 'pending', '{\"order\":{\"id\":61,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:38:10+00:00\",\"updated_at\":\"2025-10-05T04:38:10+00:00\",\"status_changed_at\":\"2025-10-05T04:38:10+00:00\",\"sla_deadline\":\"2025-10-05T04:58:10+00:00\",\"items\":[{\"id\":61,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:38:10+00:00\"}', '2025-10-05 01:38:10'),
(113, 62, 1, 'order.created', 'pending', '{\"order\":{\"id\":62,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T04:38:36+00:00\",\"updated_at\":\"2025-10-05T04:38:36+00:00\",\"status_changed_at\":\"2025-10-05T04:38:36+00:00\",\"sla_deadline\":\"2025-10-05T04:58:36+00:00\",\"items\":[{\"id\":62,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T04:38:36+00:00\"}', '2025-10-05 01:38:36'),
(114, 63, 1, 'order.created', 'pending', '{\"order\":{\"id\":63,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:10:20+00:00\",\"updated_at\":\"2025-10-05T15:10:20+00:00\",\"status_changed_at\":\"2025-10-05T15:10:20+00:00\",\"sla_deadline\":\"2025-10-05T15:30:20+00:00\",\"items\":[{\"id\":63,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:10:20+00:00\"}', '2025-10-05 12:10:20'),
(115, 64, 1, 'order.created', 'pending', '{\"order\":{\"id\":64,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:19:14+00:00\",\"updated_at\":\"2025-10-05T15:19:14+00:00\",\"status_changed_at\":\"2025-10-05T15:19:14+00:00\",\"sla_deadline\":\"2025-10-05T15:39:14+00:00\",\"items\":[{\"id\":64,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:19:14+00:00\"}', '2025-10-05 12:19:14'),
(116, 65, 1, 'order.created', 'pending', '{\"order\":{\"id\":65,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:19:32+00:00\",\"updated_at\":\"2025-10-05T15:19:32+00:00\",\"status_changed_at\":\"2025-10-05T15:19:32+00:00\",\"sla_deadline\":\"2025-10-05T15:39:32+00:00\",\"items\":[{\"id\":65,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:19:32+00:00\"}', '2025-10-05 12:19:32'),
(117, 66, 1, 'order.created', 'pending', '{\"order\":{\"id\":66,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":20,\"delivery_fee\":0,\"discount\":0,\"total\":20,\"created_at\":\"2025-10-05T15:22:15+00:00\",\"updated_at\":\"2025-10-05T15:22:15+00:00\",\"status_changed_at\":\"2025-10-05T15:22:15+00:00\",\"sla_deadline\":\"2025-10-05T15:42:15+00:00\",\"items\":[{\"id\":66,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10},{\"id\":67,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:22:15+00:00\"}', '2025-10-05 12:22:15'),
(118, 67, 1, 'order.created', 'pending', '{\"order\":{\"id\":67,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:22:30+00:00\",\"updated_at\":\"2025-10-05T15:22:30+00:00\",\"status_changed_at\":\"2025-10-05T15:22:30+00:00\",\"sla_deadline\":\"2025-10-05T15:42:30+00:00\",\"items\":[{\"id\":68,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:22:30+00:00\"}', '2025-10-05 12:22:30'),
(119, 68, 1, 'order.created', 'pending', '{\"order\":{\"id\":68,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:22:45+00:00\",\"updated_at\":\"2025-10-05T15:22:45+00:00\",\"status_changed_at\":\"2025-10-05T15:22:45+00:00\",\"sla_deadline\":\"2025-10-05T15:42:45+00:00\",\"items\":[{\"id\":69,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:22:45+00:00\"}', '2025-10-05 12:22:45'),
(120, 69, 1, 'order.created', 'pending', '{\"order\":{\"id\":69,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:25:36+00:00\",\"updated_at\":\"2025-10-05T15:25:36+00:00\",\"status_changed_at\":\"2025-10-05T15:25:36+00:00\",\"sla_deadline\":\"2025-10-05T15:45:36+00:00\",\"items\":[{\"id\":70,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:25:37+00:00\"}', '2025-10-05 12:25:37'),
(121, 70, 1, 'order.created', 'pending', '{\"order\":{\"id\":70,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:25:54+00:00\",\"updated_at\":\"2025-10-05T15:25:54+00:00\",\"status_changed_at\":\"2025-10-05T15:25:54+00:00\",\"sla_deadline\":\"2025-10-05T15:45:54+00:00\",\"items\":[{\"id\":71,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:25:54+00:00\"}', '2025-10-05 12:25:54'),
(122, 71, 1, 'order.created', 'pending', '{\"order\":{\"id\":71,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T15:26:20+00:00\",\"updated_at\":\"2025-10-05T15:26:20+00:00\",\"status_changed_at\":\"2025-10-05T15:26:20+00:00\",\"sla_deadline\":\"2025-10-05T15:46:20+00:00\",\"items\":[{\"id\":72,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T15:26:20+00:00\"}', '2025-10-05 12:26:20'),
(123, 72, 1, 'order.created', 'pending', '{\"order\":{\"id\":72,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fgrthnr, 546 - gdesrfg\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T16:14:00+00:00\",\"updated_at\":\"2025-10-05T16:14:00+00:00\",\"status_changed_at\":\"2025-10-05T16:14:00+00:00\",\"sla_deadline\":\"2025-10-05T16:34:00+00:00\",\"items\":[{\"id\":73,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T16:14:00+00:00\"}', '2025-10-05 13:14:00'),
(124, 73, 1, 'order.created', 'pending', '{\"order\":{\"id\":73,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste\",\"customer_phone\":\"5511999999999\",\"customer_address\":\"Rua A, 123\",\"notes\":\"Observações: Teste pedido\",\"subtotal\":20,\"delivery_fee\":0,\"discount\":0,\"total\":20,\"created_at\":\"2025-10-05T20:30:07+00:00\",\"updated_at\":\"2025-10-05T20:30:07+00:00\",\"status_changed_at\":\"2025-10-05T20:30:07+00:00\",\"sla_deadline\":\"2025-10-05T20:50:07+00:00\",\"items\":[{\"id\":74,\"product_id\":1,\"name\":\"herick\",\"qty\":2,\"quantity\":2,\"unit_price\":10,\"line_total\":20}]},\"created_at\":\"2025-10-05T20:30:07+00:00\"}', '2025-10-05 17:30:07'),
(125, 74, 1, 'order.created', 'pending', '{\"order\":{\"id\":74,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"sdfgsdf, fgf\",\"notes\":\"\",\"subtotal\":10,\"delivery_fee\":0,\"discount\":0,\"total\":10,\"created_at\":\"2025-10-05T20:59:53+00:00\",\"updated_at\":\"2025-10-05T20:59:53+00:00\",\"status_changed_at\":\"2025-10-05T20:59:53+00:00\",\"sla_deadline\":\"2025-10-05T21:19:53+00:00\",\"items\":[{\"id\":75,\"product_id\":1,\"name\":\"herick\",\"qty\":1,\"quantity\":1,\"unit_price\":10,\"line_total\":10}]},\"created_at\":\"2025-10-05T20:59:53+00:00\"}', '2025-10-05 17:59:53'),
(126, 75, 1, 'order.created', 'pending', '{\"order\":{\"id\":75,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:13:55+00:00\",\"updated_at\":\"2025-10-06T18:13:55+00:00\",\"status_changed_at\":\"2025-10-06T18:13:55+00:00\",\"sla_deadline\":\"2025-10-06T18:33:55+00:00\",\"items\":[{\"id\":76,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:13:55+00:00\"}', '2025-10-06 15:13:55'),
(127, 75, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":75,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:13:55+00:00\",\"updated_at\":\"2025-10-06T18:14:20+00:00\",\"status_changed_at\":\"2025-10-06T18:14:20+00:00\",\"sla_deadline\":\"2025-10-06T18:33:55+00:00\",\"items\":[{\"id\":76,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:14:20+00:00\"}', '2025-10-06 15:14:20'),
(128, 76, 1, 'order.created', 'pending', '{\"order\":{\"id\":76,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:14:23+00:00\",\"updated_at\":\"2025-10-06T18:14:23+00:00\",\"status_changed_at\":\"2025-10-06T18:14:23+00:00\",\"sla_deadline\":\"2025-10-06T18:34:23+00:00\",\"items\":[{\"id\":77,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:14:23+00:00\"}', '2025-10-06 15:14:23'),
(129, 77, 1, 'order.created', 'pending', '{\"order\":{\"id\":77,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:14:44+00:00\",\"updated_at\":\"2025-10-06T18:14:44+00:00\",\"status_changed_at\":\"2025-10-06T18:14:44+00:00\",\"sla_deadline\":\"2025-10-06T18:34:44+00:00\",\"items\":[{\"id\":78,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:14:44+00:00\"}', '2025-10-06 15:14:44'),
(130, 78, 1, 'order.created', 'pending', '{\"order\":{\"id\":78,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:14:53+00:00\",\"updated_at\":\"2025-10-06T18:14:53+00:00\",\"status_changed_at\":\"2025-10-06T18:14:53+00:00\",\"sla_deadline\":\"2025-10-06T18:34:53+00:00\",\"items\":[{\"id\":79,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:14:53+00:00\"}', '2025-10-06 15:14:53'),
(131, 79, 1, 'order.created', 'pending', '{\"order\":{\"id\":79,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:15:11+00:00\",\"updated_at\":\"2025-10-06T18:15:11+00:00\",\"status_changed_at\":\"2025-10-06T18:15:11+00:00\",\"sla_deadline\":\"2025-10-06T18:35:11+00:00\",\"items\":[{\"id\":80,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:15:11+00:00\"}', '2025-10-06 15:15:11'),
(132, 80, 1, 'order.created', 'pending', '{\"order\":{\"id\":80,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 665\",\"notes\":\"Pagamento: Elo\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-06T18:15:21+00:00\",\"updated_at\":\"2025-10-06T18:15:21+00:00\",\"status_changed_at\":\"2025-10-06T18:15:21+00:00\",\"sla_deadline\":\"2025-10-06T18:35:21+00:00\",\"items\":[{\"id\":81,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-06T18:15:21+00:00\"}', '2025-10-06 15:15:21'),
(133, 81, 1, 'order.created', 'pending', '{\"order\":{\"id\":81,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dsvs, s\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-09T03:27:26+00:00\",\"updated_at\":\"2025-10-09T03:27:26+00:00\",\"status_changed_at\":\"2025-10-09T03:27:26+00:00\",\"sla_deadline\":\"2025-10-09T03:47:26+00:00\",\"items\":[{\"id\":82,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-09T03:27:26+00:00\"}', '2025-10-09 00:27:26'),
(134, 82, 1, 'order.created', 'pending', '{\"order\":{\"id\":82,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687.\",\"customer_address\":\"sdfgvsdf, sdgsd\",\"notes\":\"\",\"subtotal\":24.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":24.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-09T20:16:05+00:00\",\"updated_at\":\"2025-10-09T20:16:05+00:00\",\"status_changed_at\":\"2025-10-09T20:16:05+00:00\",\"sla_deadline\":\"2025-10-09T20:36:05+00:00\",\"items\":[{\"id\":83,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":24.89999999999999857891452847979962825775146484375,\"line_total\":24.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-09T20:16:05+00:00\"}', '2025-10-09 17:16:05'),
(135, 83, 1, 'order.created', 'pending', '{\"order\":{\"id\":83,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:53:17+00:00\",\"updated_at\":\"2025-10-11T18:53:17+00:00\",\"status_changed_at\":\"2025-10-11T18:53:17+00:00\",\"sla_deadline\":\"2025-10-11T19:13:17+00:00\",\"items\":[{\"id\":84,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:53:17+00:00\"}', '2025-10-11 15:53:17'),
(136, 83, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":83,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:53:17+00:00\",\"updated_at\":\"2025-10-11T18:53:52+00:00\",\"status_changed_at\":\"2025-10-11T18:53:52+00:00\",\"sla_deadline\":\"2025-10-11T19:13:17+00:00\",\"items\":[{\"id\":84,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:53:52+00:00\"}', '2025-10-11 15:53:52'),
(137, 83, 1, 'order.status_changed', 'completed', '{\"order\":{\"id\":83,\"company_id\":1,\"status\":\"completed\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:53:17+00:00\",\"updated_at\":\"2025-10-11T18:53:54+00:00\",\"status_changed_at\":\"2025-10-11T18:53:54+00:00\",\"sla_deadline\":\"2025-10-11T19:13:17+00:00\",\"items\":[{\"id\":84,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:53:54+00:00\"}', '2025-10-11 15:53:54'),
(138, 83, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":83,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:53:17+00:00\",\"updated_at\":\"2025-10-11T18:54:00+00:00\",\"status_changed_at\":\"2025-10-11T18:54:00+00:00\",\"sla_deadline\":\"2025-10-11T19:13:17+00:00\",\"items\":[{\"id\":84,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:54:00+00:00\"}', '2025-10-11 15:54:00'),
(139, 84, 1, 'order.created', 'pending', '{\"order\":{\"id\":84,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:54:14+00:00\",\"updated_at\":\"2025-10-11T18:54:14+00:00\",\"status_changed_at\":\"2025-10-11T18:54:14+00:00\",\"sla_deadline\":\"2025-10-11T19:14:14+00:00\",\"items\":[{\"id\":85,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:54:14+00:00\"}', '2025-10-11 15:54:14'),
(140, 84, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":84,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:54:14+00:00\",\"updated_at\":\"2025-10-11T18:54:50+00:00\",\"status_changed_at\":\"2025-10-11T18:54:50+00:00\",\"sla_deadline\":\"2025-10-11T19:14:14+00:00\",\"items\":[{\"id\":85,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:54:50+00:00\"}', '2025-10-11 15:54:50'),
(141, 85, 1, 'order.created', 'pending', '{\"order\":{\"id\":85,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:54:58+00:00\",\"updated_at\":\"2025-10-11T18:54:58+00:00\",\"status_changed_at\":\"2025-10-11T18:54:58+00:00\",\"sla_deadline\":\"2025-10-11T19:14:58+00:00\",\"items\":[{\"id\":86,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:54:58+00:00\"}', '2025-10-11 15:54:58');
INSERT INTO `order_events` (`id`, `order_id`, `company_id`, `event_type`, `status`, `payload`, `created_at`) VALUES
(142, 86, 1, 'order.created', 'pending', '{\"order\":{\"id\":86,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:56:22+00:00\",\"updated_at\":\"2025-10-11T18:56:22+00:00\",\"status_changed_at\":\"2025-10-11T18:56:22+00:00\",\"sla_deadline\":\"2025-10-11T19:16:22+00:00\",\"items\":[{\"id\":87,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:56:22+00:00\"}', '2025-10-11 15:56:22'),
(143, 87, 1, 'order.created', 'pending', '{\"order\":{\"id\":87,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:57:49+00:00\",\"updated_at\":\"2025-10-11T18:57:49+00:00\",\"status_changed_at\":\"2025-10-11T18:57:49+00:00\",\"sla_deadline\":\"2025-10-11T19:17:49+00:00\",\"items\":[{\"id\":88,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T18:57:49+00:00\"}', '2025-10-11 15:57:49'),
(144, 88, 1, 'order.created', 'pending', '{\"order\":{\"id\":88,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":39.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":39.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:01:52+00:00\",\"updated_at\":\"2025-10-11T19:01:52+00:00\",\"status_changed_at\":\"2025-10-11T19:01:52+00:00\",\"sla_deadline\":\"2025-10-11T19:21:52+00:00\",\"items\":[{\"id\":89,\"product_id\":5,\"name\":\"Double Cheeseburger\",\"qty\":1,\"quantity\":1,\"unit_price\":39.89999999999999857891452847979962825775146484375,\"line_total\":39.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:01:52+00:00\"}', '2025-10-11 16:01:52'),
(145, 89, 1, 'order.created', 'pending', '{\"order\":{\"id\":89,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:03:57+00:00\",\"updated_at\":\"2025-10-11T19:03:57+00:00\",\"status_changed_at\":\"2025-10-11T19:03:57+00:00\",\"sla_deadline\":\"2025-10-11T19:23:57+00:00\",\"items\":[{\"id\":90,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:03:57+00:00\"}', '2025-10-11 16:03:57'),
(146, 85, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":85,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:54:58+00:00\",\"updated_at\":\"2025-10-11T19:04:08+00:00\",\"status_changed_at\":\"2025-10-11T19:04:08+00:00\",\"sla_deadline\":\"2025-10-11T19:14:58+00:00\",\"items\":[{\"id\":86,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:08+00:00\"}', '2025-10-11 16:04:08'),
(147, 86, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":86,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:56:22+00:00\",\"updated_at\":\"2025-10-11T19:04:10+00:00\",\"status_changed_at\":\"2025-10-11T19:04:10+00:00\",\"sla_deadline\":\"2025-10-11T19:16:22+00:00\",\"items\":[{\"id\":87,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:10+00:00\"}', '2025-10-11 16:04:10'),
(148, 87, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":87,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T18:57:49+00:00\",\"updated_at\":\"2025-10-11T19:04:11+00:00\",\"status_changed_at\":\"2025-10-11T19:04:11+00:00\",\"sla_deadline\":\"2025-10-11T19:17:49+00:00\",\"items\":[{\"id\":88,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:11+00:00\"}', '2025-10-11 16:04:11'),
(149, 88, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":88,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":39.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":39.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:01:52+00:00\",\"updated_at\":\"2025-10-11T19:04:14+00:00\",\"status_changed_at\":\"2025-10-11T19:04:14+00:00\",\"sla_deadline\":\"2025-10-11T19:21:52+00:00\",\"items\":[{\"id\":89,\"product_id\":5,\"name\":\"Double Cheeseburger\",\"qty\":1,\"quantity\":1,\"unit_price\":39.89999999999999857891452847979962825775146484375,\"line_total\":39.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:14+00:00\"}', '2025-10-11 16:04:14'),
(150, 89, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":89,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:03:57+00:00\",\"updated_at\":\"2025-10-11T19:04:16+00:00\",\"status_changed_at\":\"2025-10-11T19:04:16+00:00\",\"sla_deadline\":\"2025-10-11T19:23:57+00:00\",\"items\":[{\"id\":90,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:16+00:00\"}', '2025-10-11 16:04:16'),
(151, 90, 1, 'order.created', 'pending', '{\"order\":{\"id\":90,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:04:20+00:00\",\"updated_at\":\"2025-10-11T19:04:20+00:00\",\"status_changed_at\":\"2025-10-11T19:04:20+00:00\",\"sla_deadline\":\"2025-10-11T19:24:20+00:00\",\"items\":[{\"id\":91,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:20+00:00\"}', '2025-10-11 16:04:20'),
(152, 91, 1, 'order.created', 'pending', '{\"order\":{\"id\":91,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:04:27+00:00\",\"updated_at\":\"2025-10-11T19:04:27+00:00\",\"status_changed_at\":\"2025-10-11T19:04:27+00:00\",\"sla_deadline\":\"2025-10-11T19:24:27+00:00\",\"items\":[{\"id\":92,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:27+00:00\"}', '2025-10-11 16:04:27'),
(153, 92, 1, 'order.created', 'pending', '{\"order\":{\"id\":92,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:04:44+00:00\",\"updated_at\":\"2025-10-11T19:04:44+00:00\",\"status_changed_at\":\"2025-10-11T19:04:44+00:00\",\"sla_deadline\":\"2025-10-11T19:24:44+00:00\",\"items\":[{\"id\":93,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:04:44+00:00\"}', '2025-10-11 16:04:44'),
(154, 93, 1, 'order.created', 'pending', '{\"order\":{\"id\":93,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:05:01+00:00\",\"updated_at\":\"2025-10-11T19:05:01+00:00\",\"status_changed_at\":\"2025-10-11T19:05:01+00:00\",\"sla_deadline\":\"2025-10-11T19:25:01+00:00\",\"items\":[{\"id\":94,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:05:01+00:00\"}', '2025-10-11 16:05:01'),
(155, 94, 1, 'order.created', 'pending', '{\"order\":{\"id\":94,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:08:35+00:00\",\"updated_at\":\"2025-10-11T19:08:35+00:00\",\"status_changed_at\":\"2025-10-11T19:08:35+00:00\",\"sla_deadline\":\"2025-10-11T19:28:35+00:00\",\"items\":[{\"id\":95,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:08:35+00:00\"}', '2025-10-11 16:08:35'),
(156, 95, 1, 'order.created', 'pending', '{\"order\":{\"id\":95,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:08:47+00:00\",\"updated_at\":\"2025-10-11T19:08:47+00:00\",\"status_changed_at\":\"2025-10-11T19:08:47+00:00\",\"sla_deadline\":\"2025-10-11T19:28:47+00:00\",\"items\":[{\"id\":96,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:08:47+00:00\"}', '2025-10-11 16:08:47'),
(157, 96, 1, 'order.created', 'pending', '{\"order\":{\"id\":96,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:09:14+00:00\",\"updated_at\":\"2025-10-11T19:09:14+00:00\",\"status_changed_at\":\"2025-10-11T19:09:14+00:00\",\"sla_deadline\":\"2025-10-11T19:29:14+00:00\",\"items\":[{\"id\":97,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:09:14+00:00\"}', '2025-10-11 16:09:14'),
(158, 97, 1, 'order.created', 'pending', '{\"order\":{\"id\":97,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:13:20+00:00\",\"updated_at\":\"2025-10-11T19:13:20+00:00\",\"status_changed_at\":\"2025-10-11T19:13:20+00:00\",\"sla_deadline\":\"2025-10-11T19:33:20+00:00\",\"items\":[{\"id\":98,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:20+00:00\"}', '2025-10-11 16:13:20'),
(159, 90, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":90,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:04:20+00:00\",\"updated_at\":\"2025-10-11T19:13:45+00:00\",\"status_changed_at\":\"2025-10-11T19:13:45+00:00\",\"sla_deadline\":\"2025-10-11T19:24:20+00:00\",\"items\":[{\"id\":91,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:45+00:00\"}', '2025-10-11 16:13:45'),
(160, 91, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":91,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:04:27+00:00\",\"updated_at\":\"2025-10-11T19:13:46+00:00\",\"status_changed_at\":\"2025-10-11T19:13:46+00:00\",\"sla_deadline\":\"2025-10-11T19:24:27+00:00\",\"items\":[{\"id\":92,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:46+00:00\"}', '2025-10-11 16:13:46'),
(161, 92, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":92,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:04:44+00:00\",\"updated_at\":\"2025-10-11T19:13:49+00:00\",\"status_changed_at\":\"2025-10-11T19:13:49+00:00\",\"sla_deadline\":\"2025-10-11T19:24:44+00:00\",\"items\":[{\"id\":93,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:49+00:00\"}', '2025-10-11 16:13:49'),
(162, 93, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":93,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:05:01+00:00\",\"updated_at\":\"2025-10-11T19:13:51+00:00\",\"status_changed_at\":\"2025-10-11T19:13:51+00:00\",\"sla_deadline\":\"2025-10-11T19:25:01+00:00\",\"items\":[{\"id\":94,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:51+00:00\"}', '2025-10-11 16:13:51'),
(163, 94, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":94,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:08:35+00:00\",\"updated_at\":\"2025-10-11T19:13:53+00:00\",\"status_changed_at\":\"2025-10-11T19:13:53+00:00\",\"sla_deadline\":\"2025-10-11T19:28:35+00:00\",\"items\":[{\"id\":95,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:53+00:00\"}', '2025-10-11 16:13:53'),
(164, 95, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":95,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:08:47+00:00\",\"updated_at\":\"2025-10-11T19:13:55+00:00\",\"status_changed_at\":\"2025-10-11T19:13:55+00:00\",\"sla_deadline\":\"2025-10-11T19:28:47+00:00\",\"items\":[{\"id\":96,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:55+00:00\"}', '2025-10-11 16:13:55'),
(165, 96, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":96,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:09:14+00:00\",\"updated_at\":\"2025-10-11T19:13:57+00:00\",\"status_changed_at\":\"2025-10-11T19:13:57+00:00\",\"sla_deadline\":\"2025-10-11T19:29:14+00:00\",\"items\":[{\"id\":97,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:57+00:00\"}', '2025-10-11 16:13:57'),
(166, 97, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":97,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:13:20+00:00\",\"updated_at\":\"2025-10-11T19:13:59+00:00\",\"status_changed_at\":\"2025-10-11T19:13:59+00:00\",\"sla_deadline\":\"2025-10-11T19:33:20+00:00\",\"items\":[{\"id\":98,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:13:59+00:00\"}', '2025-10-11 16:13:59'),
(167, 98, 1, 'order.created', 'pending', '{\"order\":{\"id\":98,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:14:04+00:00\",\"updated_at\":\"2025-10-11T19:14:04+00:00\",\"status_changed_at\":\"2025-10-11T19:14:04+00:00\",\"sla_deadline\":\"2025-10-11T19:34:04+00:00\",\"items\":[{\"id\":99,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:14:04+00:00\"}', '2025-10-11 16:14:04'),
(168, 98, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":98,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:14:04+00:00\",\"updated_at\":\"2025-10-11T19:14:13+00:00\",\"status_changed_at\":\"2025-10-11T19:14:13+00:00\",\"sla_deadline\":\"2025-10-11T19:34:04+00:00\",\"items\":[{\"id\":99,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:14:13+00:00\"}', '2025-10-11 16:14:13'),
(169, 99, 1, 'order.created', 'pending', '{\"order\":{\"id\":99,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:14:34+00:00\",\"updated_at\":\"2025-10-11T19:14:34+00:00\",\"status_changed_at\":\"2025-10-11T19:14:34+00:00\",\"sla_deadline\":\"2025-10-11T19:34:34+00:00\",\"items\":[{\"id\":100,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:14:34+00:00\"}', '2025-10-11 16:14:34'),
(170, 100, 1, 'order.created', 'pending', '{\"order\":{\"id\":100,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:14:46+00:00\",\"updated_at\":\"2025-10-11T19:14:46+00:00\",\"status_changed_at\":\"2025-10-11T19:14:46+00:00\",\"sla_deadline\":\"2025-10-11T19:34:46+00:00\",\"items\":[{\"id\":101,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:14:46+00:00\"}', '2025-10-11 16:14:46'),
(171, 101, 1, 'order.created', 'pending', '{\"order\":{\"id\":101,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:17:03+00:00\",\"updated_at\":\"2025-10-11T19:17:03+00:00\",\"status_changed_at\":\"2025-10-11T19:17:03+00:00\",\"sla_deadline\":\"2025-10-11T19:37:03+00:00\",\"items\":[{\"id\":102,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:17:03+00:00\"}', '2025-10-11 16:17:03'),
(172, 102, 1, 'order.created', 'pending', '{\"order\":{\"id\":102,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:17:31+00:00\",\"updated_at\":\"2025-10-11T19:17:31+00:00\",\"status_changed_at\":\"2025-10-11T19:17:31+00:00\",\"sla_deadline\":\"2025-10-11T19:37:31+00:00\",\"items\":[{\"id\":103,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:17:31+00:00\"}', '2025-10-11 16:17:31'),
(173, 103, 1, 'order.created', 'pending', '{\"order\":{\"id\":103,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:19:16+00:00\",\"updated_at\":\"2025-10-11T19:19:16+00:00\",\"status_changed_at\":\"2025-10-11T19:19:16+00:00\",\"sla_deadline\":\"2025-10-11T19:39:16+00:00\",\"items\":[{\"id\":104,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:19:16+00:00\"}', '2025-10-11 16:19:16'),
(174, 104, 1, 'order.created', 'pending', '{\"order\":{\"id\":104,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:20:21+00:00\",\"updated_at\":\"2025-10-11T19:20:21+00:00\",\"status_changed_at\":\"2025-10-11T19:20:21+00:00\",\"sla_deadline\":\"2025-10-11T19:40:21+00:00\",\"items\":[{\"id\":105,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:20:21+00:00\"}', '2025-10-11 16:20:21'),
(175, 105, 1, 'order.created', 'pending', '{\"order\":{\"id\":105,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:20:40+00:00\",\"updated_at\":\"2025-10-11T19:20:40+00:00\",\"status_changed_at\":\"2025-10-11T19:20:40+00:00\",\"sla_deadline\":\"2025-10-11T19:40:40+00:00\",\"items\":[{\"id\":106,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:20:40+00:00\"}', '2025-10-11 16:20:40'),
(176, 106, 1, 'order.created', 'pending', '{\"order\":{\"id\":106,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:22:47+00:00\",\"updated_at\":\"2025-10-11T19:22:47+00:00\",\"status_changed_at\":\"2025-10-11T19:22:47+00:00\",\"sla_deadline\":\"2025-10-11T19:42:47+00:00\",\"items\":[{\"id\":107,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:22:47+00:00\"}', '2025-10-11 16:22:47'),
(177, 107, 1, 'order.created', 'pending', '{\"order\":{\"id\":107,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:23:07+00:00\",\"updated_at\":\"2025-10-11T19:23:07+00:00\",\"status_changed_at\":\"2025-10-11T19:23:07+00:00\",\"sla_deadline\":\"2025-10-11T19:43:07+00:00\",\"items\":[{\"id\":108,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:23:07+00:00\"}', '2025-10-11 16:23:07'),
(178, 108, 1, 'order.created', 'pending', '{\"order\":{\"id\":108,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:25:09+00:00\",\"updated_at\":\"2025-10-11T19:25:09+00:00\",\"status_changed_at\":\"2025-10-11T19:25:09+00:00\",\"sla_deadline\":\"2025-10-11T19:45:09+00:00\",\"items\":[{\"id\":109,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:25:09+00:00\"}', '2025-10-11 16:25:09'),
(179, 109, 1, 'order.created', 'pending', '{\"order\":{\"id\":109,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:25:28+00:00\",\"updated_at\":\"2025-10-11T19:25:28+00:00\",\"status_changed_at\":\"2025-10-11T19:25:28+00:00\",\"sla_deadline\":\"2025-10-11T19:45:28+00:00\",\"items\":[{\"id\":110,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:25:28+00:00\"}', '2025-10-11 16:25:28'),
(180, 110, 1, 'order.created', 'pending', '{\"order\":{\"id\":110,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:25:43+00:00\",\"updated_at\":\"2025-10-11T19:25:43+00:00\",\"status_changed_at\":\"2025-10-11T19:25:43+00:00\",\"sla_deadline\":\"2025-10-11T19:45:43+00:00\",\"items\":[{\"id\":111,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:25:43+00:00\"}', '2025-10-11 16:25:43'),
(181, 111, 1, 'order.created', 'pending', '{\"order\":{\"id\":111,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:28:08+00:00\",\"updated_at\":\"2025-10-11T19:28:08+00:00\",\"status_changed_at\":\"2025-10-11T19:28:08+00:00\",\"sla_deadline\":\"2025-10-11T19:48:08+00:00\",\"items\":[{\"id\":112,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:28:08+00:00\"}', '2025-10-11 16:28:08'),
(182, 112, 1, 'order.created', 'pending', '{\"order\":{\"id\":112,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:28:20+00:00\",\"updated_at\":\"2025-10-11T19:28:20+00:00\",\"status_changed_at\":\"2025-10-11T19:28:20+00:00\",\"sla_deadline\":\"2025-10-11T19:48:20+00:00\",\"items\":[{\"id\":113,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:28:20+00:00\"}', '2025-10-11 16:28:20'),
(183, 113, 1, 'order.created', 'pending', '{\"order\":{\"id\":113,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:29:52+00:00\",\"updated_at\":\"2025-10-11T19:29:52+00:00\",\"status_changed_at\":\"2025-10-11T19:29:52+00:00\",\"sla_deadline\":\"2025-10-11T19:49:52+00:00\",\"items\":[{\"id\":114,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:29:52+00:00\"}', '2025-10-11 16:29:52'),
(184, 99, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":99,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:14:34+00:00\",\"updated_at\":\"2025-10-11T19:30:01+00:00\",\"status_changed_at\":\"2025-10-11T19:30:01+00:00\",\"sla_deadline\":\"2025-10-11T19:34:34+00:00\",\"items\":[{\"id\":100,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:01+00:00\"}', '2025-10-11 16:30:01'),
(185, 100, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":100,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:14:46+00:00\",\"updated_at\":\"2025-10-11T19:30:03+00:00\",\"status_changed_at\":\"2025-10-11T19:30:03+00:00\",\"sla_deadline\":\"2025-10-11T19:34:46+00:00\",\"items\":[{\"id\":101,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:03+00:00\"}', '2025-10-11 16:30:03'),
(186, 101, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":101,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:17:03+00:00\",\"updated_at\":\"2025-10-11T19:30:04+00:00\",\"status_changed_at\":\"2025-10-11T19:30:04+00:00\",\"sla_deadline\":\"2025-10-11T19:37:03+00:00\",\"items\":[{\"id\":102,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:04+00:00\"}', '2025-10-11 16:30:04'),
(187, 102, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":102,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:17:31+00:00\",\"updated_at\":\"2025-10-11T19:30:04+00:00\",\"status_changed_at\":\"2025-10-11T19:30:04+00:00\",\"sla_deadline\":\"2025-10-11T19:37:31+00:00\",\"items\":[{\"id\":103,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:04+00:00\"}', '2025-10-11 16:30:04'),
(188, 103, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":103,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:19:16+00:00\",\"updated_at\":\"2025-10-11T19:30:04+00:00\",\"status_changed_at\":\"2025-10-11T19:30:04+00:00\",\"sla_deadline\":\"2025-10-11T19:39:16+00:00\",\"items\":[{\"id\":104,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:04+00:00\"}', '2025-10-11 16:30:04'),
(189, 104, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":104,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:20:21+00:00\",\"updated_at\":\"2025-10-11T19:30:05+00:00\",\"status_changed_at\":\"2025-10-11T19:30:05+00:00\",\"sla_deadline\":\"2025-10-11T19:40:21+00:00\",\"items\":[{\"id\":105,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:05+00:00\"}', '2025-10-11 16:30:05'),
(190, 105, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":105,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:20:40+00:00\",\"updated_at\":\"2025-10-11T19:30:05+00:00\",\"status_changed_at\":\"2025-10-11T19:30:05+00:00\",\"sla_deadline\":\"2025-10-11T19:40:40+00:00\",\"items\":[{\"id\":106,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:05+00:00\"}', '2025-10-11 16:30:05'),
(191, 106, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":106,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:22:47+00:00\",\"updated_at\":\"2025-10-11T19:30:05+00:00\",\"status_changed_at\":\"2025-10-11T19:30:05+00:00\",\"sla_deadline\":\"2025-10-11T19:42:47+00:00\",\"items\":[{\"id\":107,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:05+00:00\"}', '2025-10-11 16:30:05'),
(192, 107, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":107,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:23:07+00:00\",\"updated_at\":\"2025-10-11T19:30:05+00:00\",\"status_changed_at\":\"2025-10-11T19:30:05+00:00\",\"sla_deadline\":\"2025-10-11T19:43:07+00:00\",\"items\":[{\"id\":108,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:05+00:00\"}', '2025-10-11 16:30:05'),
(193, 108, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":108,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:25:09+00:00\",\"updated_at\":\"2025-10-11T19:30:05+00:00\",\"status_changed_at\":\"2025-10-11T19:30:05+00:00\",\"sla_deadline\":\"2025-10-11T19:45:09+00:00\",\"items\":[{\"id\":109,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:05+00:00\"}', '2025-10-11 16:30:05'),
(194, 109, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":109,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:25:28+00:00\",\"updated_at\":\"2025-10-11T19:30:06+00:00\",\"status_changed_at\":\"2025-10-11T19:30:06+00:00\",\"sla_deadline\":\"2025-10-11T19:45:28+00:00\",\"items\":[{\"id\":110,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:06+00:00\"}', '2025-10-11 16:30:06'),
(195, 110, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":110,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:25:43+00:00\",\"updated_at\":\"2025-10-11T19:30:06+00:00\",\"status_changed_at\":\"2025-10-11T19:30:06+00:00\",\"sla_deadline\":\"2025-10-11T19:45:43+00:00\",\"items\":[{\"id\":111,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:06+00:00\"}', '2025-10-11 16:30:06'),
(196, 111, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":111,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:28:08+00:00\",\"updated_at\":\"2025-10-11T19:30:07+00:00\",\"status_changed_at\":\"2025-10-11T19:30:07+00:00\",\"sla_deadline\":\"2025-10-11T19:48:08+00:00\",\"items\":[{\"id\":112,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:07+00:00\"}', '2025-10-11 16:30:07'),
(197, 112, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":112,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:28:20+00:00\",\"updated_at\":\"2025-10-11T19:30:13+00:00\",\"status_changed_at\":\"2025-10-11T19:30:13+00:00\",\"sla_deadline\":\"2025-10-11T19:48:20+00:00\",\"items\":[{\"id\":113,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:13+00:00\"}', '2025-10-11 16:30:13');
INSERT INTO `order_events` (`id`, `order_id`, `company_id`, `event_type`, `status`, `payload`, `created_at`) VALUES
(198, 113, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":113,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:29:52+00:00\",\"updated_at\":\"2025-10-11T19:30:14+00:00\",\"status_changed_at\":\"2025-10-11T19:30:14+00:00\",\"sla_deadline\":\"2025-10-11T19:49:52+00:00\",\"items\":[{\"id\":114,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:14+00:00\"}', '2025-10-11 16:30:14'),
(199, 114, 1, 'order.created', 'pending', '{\"order\":{\"id\":114,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:30:20+00:00\",\"updated_at\":\"2025-10-11T19:30:20+00:00\",\"status_changed_at\":\"2025-10-11T19:30:20+00:00\",\"sla_deadline\":\"2025-10-11T19:50:20+00:00\",\"items\":[{\"id\":115,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T19:30:20+00:00\"}', '2025-10-11 16:30:20'),
(200, 115, 1, 'order.created', 'pending', '{\"order\":{\"id\":115,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:05+00:00\",\"updated_at\":\"2025-10-11T23:02:05+00:00\",\"status_changed_at\":\"2025-10-11T23:02:05+00:00\",\"sla_deadline\":\"2025-10-11T23:22:05+00:00\",\"items\":[{\"id\":116,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:02:05+00:00\"}', '2025-10-11 20:02:05'),
(201, 116, 1, 'order.created', 'pending', '{\"order\":{\"id\":116,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:18+00:00\",\"updated_at\":\"2025-10-11T23:02:18+00:00\",\"status_changed_at\":\"2025-10-11T23:02:18+00:00\",\"sla_deadline\":\"2025-10-11T23:22:18+00:00\",\"items\":[{\"id\":117,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:02:18+00:00\"}', '2025-10-11 20:02:18'),
(202, 117, 1, 'order.created', 'pending', '{\"order\":{\"id\":117,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:29+00:00\",\"updated_at\":\"2025-10-11T23:02:29+00:00\",\"status_changed_at\":\"2025-10-11T23:02:29+00:00\",\"sla_deadline\":\"2025-10-11T23:22:29+00:00\",\"items\":[{\"id\":118,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:02:29+00:00\"}', '2025-10-11 20:02:29'),
(203, 114, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":114,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T19:30:20+00:00\",\"updated_at\":\"2025-10-11T23:02:48+00:00\",\"status_changed_at\":\"2025-10-11T23:02:48+00:00\",\"sla_deadline\":\"2025-10-11T19:50:20+00:00\",\"items\":[{\"id\":115,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:02:48+00:00\"}', '2025-10-11 20:02:48'),
(204, 115, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":115,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:05+00:00\",\"updated_at\":\"2025-10-11T23:02:50+00:00\",\"status_changed_at\":\"2025-10-11T23:02:50+00:00\",\"sla_deadline\":\"2025-10-11T23:22:05+00:00\",\"items\":[{\"id\":116,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:02:50+00:00\"}', '2025-10-11 20:02:50'),
(205, 116, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":116,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:18+00:00\",\"updated_at\":\"2025-10-11T23:02:52+00:00\",\"status_changed_at\":\"2025-10-11T23:02:52+00:00\",\"sla_deadline\":\"2025-10-11T23:22:18+00:00\",\"items\":[{\"id\":117,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:02:52+00:00\"}', '2025-10-11 20:02:52'),
(206, 117, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":117,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:29+00:00\",\"updated_at\":\"2025-10-11T23:05:57+00:00\",\"status_changed_at\":\"2025-10-11T23:05:57+00:00\",\"sla_deadline\":\"2025-10-11T23:22:29+00:00\",\"items\":[{\"id\":118,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:05:57+00:00\"}', '2025-10-11 20:05:57'),
(207, 118, 1, 'order.created', 'pending', '{\"order\":{\"id\":118,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:06:41+00:00\",\"updated_at\":\"2025-10-11T23:06:41+00:00\",\"status_changed_at\":\"2025-10-11T23:06:41+00:00\",\"sla_deadline\":\"2025-10-11T23:26:41+00:00\",\"items\":[{\"id\":119,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:06:41+00:00\"}', '2025-10-11 20:06:41'),
(208, 118, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":118,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:06:41+00:00\",\"updated_at\":\"2025-10-11T23:06:47+00:00\",\"status_changed_at\":\"2025-10-11T23:06:47+00:00\",\"sla_deadline\":\"2025-10-11T23:26:41+00:00\",\"items\":[{\"id\":119,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:06:47+00:00\"}', '2025-10-11 20:06:47'),
(209, 118, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":118,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:06:41+00:00\",\"updated_at\":\"2025-10-11T23:06:51+00:00\",\"status_changed_at\":\"2025-10-11T23:06:51+00:00\",\"sla_deadline\":\"2025-10-11T23:26:41+00:00\",\"items\":[{\"id\":119,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:06:51+00:00\"}', '2025-10-11 20:06:51'),
(210, 119, 1, 'order.created', 'pending', '{\"order\":{\"id\":119,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:18:04+00:00\",\"updated_at\":\"2025-10-11T23:18:04+00:00\",\"status_changed_at\":\"2025-10-11T23:18:04+00:00\",\"sla_deadline\":\"2025-10-11T23:38:04+00:00\",\"items\":[{\"id\":120,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:18:04+00:00\"}', '2025-10-11 20:18:04'),
(211, 119, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":119,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:18:04+00:00\",\"updated_at\":\"2025-10-11T23:18:26+00:00\",\"status_changed_at\":\"2025-10-11T23:18:26+00:00\",\"sla_deadline\":\"2025-10-11T23:38:04+00:00\",\"items\":[{\"id\":120,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:18:26+00:00\"}', '2025-10-11 20:18:26'),
(212, 119, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":119,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:18:04+00:00\",\"updated_at\":\"2025-10-11T23:18:35+00:00\",\"status_changed_at\":\"2025-10-11T23:18:35+00:00\",\"sla_deadline\":\"2025-10-11T23:38:04+00:00\",\"items\":[{\"id\":120,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:18:35+00:00\"}', '2025-10-11 20:18:35'),
(213, 120, 1, 'order.created', 'pending', '{\"order\":{\"id\":120,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:18:44+00:00\",\"updated_at\":\"2025-10-11T23:18:44+00:00\",\"status_changed_at\":\"2025-10-11T23:18:44+00:00\",\"sla_deadline\":\"2025-10-11T23:38:44+00:00\",\"items\":[{\"id\":121,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-11T23:18:44+00:00\"}', '2025-10-11 20:18:44'),
(214, 120, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":120,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:18:44+00:00\",\"updated_at\":\"2025-10-12T00:08:44+00:00\",\"status_changed_at\":\"2025-10-12T00:08:44+00:00\",\"sla_deadline\":\"2025-10-11T23:38:44+00:00\",\"items\":[{\"id\":121,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T00:08:44+00:00\"}', '2025-10-11 21:08:44'),
(215, 121, 1, 'order.created', 'pending', '{\"order\":{\"id\":121,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T00:09:16+00:00\",\"updated_at\":\"2025-10-12T00:09:16+00:00\",\"status_changed_at\":\"2025-10-12T00:09:16+00:00\",\"sla_deadline\":\"2025-10-12T00:29:16+00:00\",\"items\":[{\"id\":122,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T00:09:16+00:00\"}', '2025-10-11 21:09:16'),
(216, 122, 1, 'order.created', 'pending', '{\"order\":{\"id\":122,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T02:53:45+00:00\",\"updated_at\":\"2025-10-12T02:53:45+00:00\",\"status_changed_at\":\"2025-10-12T02:53:45+00:00\",\"sla_deadline\":\"2025-10-12T03:13:45+00:00\",\"items\":[{\"id\":123,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T02:53:45+00:00\"}', '2025-10-11 23:53:45'),
(217, 117, 1, 'order.status_changed', 'completed', '{\"order\":{\"id\":117,\"company_id\":1,\"status\":\"completed\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"oi, 123\",\"notes\":\"Pagamento: Mastercard\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-11T23:02:29+00:00\",\"updated_at\":\"2025-10-12T03:25:01+00:00\",\"status_changed_at\":\"2025-10-12T03:25:01+00:00\",\"sla_deadline\":\"2025-10-11T23:22:29+00:00\",\"items\":[{\"id\":118,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T03:25:01+00:00\"}', '2025-10-12 00:25:01'),
(218, 122, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":122,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T02:53:45+00:00\",\"updated_at\":\"2025-10-12T04:15:25+00:00\",\"status_changed_at\":\"2025-10-12T04:15:25+00:00\",\"sla_deadline\":\"2025-10-12T03:13:45+00:00\",\"items\":[{\"id\":123,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:15:25+00:00\"}', '2025-10-12 01:15:25'),
(219, 122, 1, 'order.status_changed', 'completed', '{\"order\":{\"id\":122,\"company_id\":1,\"status\":\"completed\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T02:53:45+00:00\",\"updated_at\":\"2025-10-12T04:15:29+00:00\",\"status_changed_at\":\"2025-10-12T04:15:29+00:00\",\"sla_deadline\":\"2025-10-12T03:13:45+00:00\",\"items\":[{\"id\":123,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:15:29+00:00\"}', '2025-10-12 01:15:29'),
(220, 121, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":121,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T00:09:16+00:00\",\"updated_at\":\"2025-10-12T04:15:35+00:00\",\"status_changed_at\":\"2025-10-12T04:15:35+00:00\",\"sla_deadline\":\"2025-10-12T00:29:16+00:00\",\"items\":[{\"id\":122,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:15:35+00:00\"}', '2025-10-12 01:15:35'),
(221, 82, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":82,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687.\",\"customer_address\":\"sdfgvsdf, sdgsd\",\"notes\":\"\",\"subtotal\":24.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":24.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-09T20:16:05+00:00\",\"updated_at\":\"2025-10-12T04:36:42+00:00\",\"status_changed_at\":\"2025-10-12T04:36:42+00:00\",\"sla_deadline\":\"2025-10-09T20:36:05+00:00\",\"items\":[{\"id\":83,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":24.89999999999999857891452847979962825775146484375,\"line_total\":24.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:36:42+00:00\"}', '2025-10-12 01:36:42'),
(222, 81, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":81,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dsvs, s\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-09T03:27:26+00:00\",\"updated_at\":\"2025-10-12T04:36:46+00:00\",\"status_changed_at\":\"2025-10-12T04:36:46+00:00\",\"sla_deadline\":\"2025-10-09T03:47:26+00:00\",\"items\":[{\"id\":82,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:36:46+00:00\"}', '2025-10-12 01:36:46'),
(223, 123, 1, 'order.created', 'pending', '{\"order\":{\"id\":123,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:38:10+00:00\",\"updated_at\":\"2025-10-12T04:38:10+00:00\",\"status_changed_at\":\"2025-10-12T04:38:10+00:00\",\"sla_deadline\":\"2025-10-12T04:58:10+00:00\",\"items\":[{\"id\":124,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:38:10+00:00\"}', '2025-10-12 01:38:10'),
(224, 123, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":123,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:38:10+00:00\",\"updated_at\":\"2025-10-12T04:39:41+00:00\",\"status_changed_at\":\"2025-10-12T04:39:41+00:00\",\"sla_deadline\":\"2025-10-12T04:58:10+00:00\",\"items\":[{\"id\":124,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:39:41+00:00\"}', '2025-10-12 01:39:41'),
(225, 123, 1, 'order.status_changed', 'completed', '{\"order\":{\"id\":123,\"company_id\":1,\"status\":\"completed\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:38:10+00:00\",\"updated_at\":\"2025-10-12T04:39:47+00:00\",\"status_changed_at\":\"2025-10-12T04:39:47+00:00\",\"sla_deadline\":\"2025-10-12T04:58:10+00:00\",\"items\":[{\"id\":124,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:39:47+00:00\"}', '2025-10-12 01:39:47'),
(226, 124, 1, 'order.created', 'pending', '{\"order\":{\"id\":124,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":55.7999999999999971578290569595992565155029296875,\"delivery_fee\":0,\"discount\":0,\"total\":55.7999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-12T04:40:42+00:00\",\"updated_at\":\"2025-10-12T04:40:42+00:00\",\"status_changed_at\":\"2025-10-12T04:40:42+00:00\",\"sla_deadline\":\"2025-10-12T05:00:42+00:00\",\"items\":[{\"id\":125,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375},{\"id\":126,\"product_id\":3,\"name\":\"Cheeseburger\",\"qty\":1,\"quantity\":1,\"unit_price\":29.89999999999999857891452847979962825775146484375,\"line_total\":29.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:40:42+00:00\"}', '2025-10-12 01:40:42'),
(227, 125, 1, 'order.created', 'pending', '{\"order\":{\"id\":125,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:41:05+00:00\",\"updated_at\":\"2025-10-12T04:41:05+00:00\",\"status_changed_at\":\"2025-10-12T04:41:05+00:00\",\"sla_deadline\":\"2025-10-12T05:01:05+00:00\",\"items\":[{\"id\":127,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:41:05+00:00\"}', '2025-10-12 01:41:05'),
(228, 126, 1, 'order.created', 'pending', '{\"order\":{\"id\":126,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":54.2999999999999971578290569595992565155029296875,\"delivery_fee\":0,\"discount\":0,\"total\":54.2999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-12T04:42:48+00:00\",\"updated_at\":\"2025-10-12T04:42:48+00:00\",\"status_changed_at\":\"2025-10-12T04:42:48+00:00\",\"sla_deadline\":\"2025-10-12T05:02:48+00:00\",\"items\":[{\"id\":128,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375},{\"id\":129,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":28.39999999999999857891452847979962825775146484375,\"line_total\":28.39999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:42:48+00:00\"}', '2025-10-12 01:42:48'),
(229, 127, 1, 'order.created', 'pending', '{\"order\":{\"id\":127,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:50:01+00:00\",\"updated_at\":\"2025-10-12T04:50:01+00:00\",\"status_changed_at\":\"2025-10-12T04:50:01+00:00\",\"sla_deadline\":\"2025-10-12T05:10:01+00:00\",\"items\":[{\"id\":130,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:50:01+00:00\"}', '2025-10-12 01:50:01'),
(230, 128, 1, 'order.created', 'pending', '{\"order\":{\"id\":128,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:50:13+00:00\",\"updated_at\":\"2025-10-12T04:50:13+00:00\",\"status_changed_at\":\"2025-10-12T04:50:13+00:00\",\"sla_deadline\":\"2025-10-12T05:10:13+00:00\",\"items\":[{\"id\":131,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:50:13+00:00\"}', '2025-10-12 01:50:13'),
(231, 129, 1, 'order.created', 'pending', '{\"order\":{\"id\":129,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:51:55+00:00\",\"updated_at\":\"2025-10-12T04:51:55+00:00\",\"status_changed_at\":\"2025-10-12T04:51:55+00:00\",\"sla_deadline\":\"2025-10-12T05:11:55+00:00\",\"items\":[{\"id\":132,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:51:55+00:00\"}', '2025-10-12 01:51:55'),
(232, 130, 1, 'order.created', 'pending', '{\"order\":{\"id\":130,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:53:10+00:00\",\"updated_at\":\"2025-10-12T04:53:10+00:00\",\"status_changed_at\":\"2025-10-12T04:53:10+00:00\",\"sla_deadline\":\"2025-10-12T05:13:10+00:00\",\"items\":[{\"id\":133,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:53:10+00:00\"}', '2025-10-12 01:53:10'),
(233, 131, 1, 'order.created', 'pending', '{\"order\":{\"id\":131,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:54:37+00:00\",\"updated_at\":\"2025-10-12T04:54:37+00:00\",\"status_changed_at\":\"2025-10-12T04:54:37+00:00\",\"sla_deadline\":\"2025-10-12T05:14:37+00:00\",\"items\":[{\"id\":134,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:54:37+00:00\"}', '2025-10-12 01:54:37'),
(234, 132, 1, 'order.created', 'pending', '{\"order\":{\"id\":132,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:55:43+00:00\",\"updated_at\":\"2025-10-12T04:55:43+00:00\",\"status_changed_at\":\"2025-10-12T04:55:43+00:00\",\"sla_deadline\":\"2025-10-12T05:15:43+00:00\",\"items\":[{\"id\":135,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:55:43+00:00\"}', '2025-10-12 01:55:43'),
(235, 133, 1, 'order.created', 'pending', '{\"order\":{\"id\":133,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:55:59+00:00\",\"updated_at\":\"2025-10-12T04:55:59+00:00\",\"status_changed_at\":\"2025-10-12T04:55:59+00:00\",\"sla_deadline\":\"2025-10-12T05:15:59+00:00\",\"items\":[{\"id\":136,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T04:55:59+00:00\"}', '2025-10-12 01:55:59'),
(236, 134, 1, 'order.created', 'pending', '{\"order\":{\"id\":134,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T05:11:20+00:00\",\"updated_at\":\"2025-10-12T05:11:20+00:00\",\"status_changed_at\":\"2025-10-12T05:11:20+00:00\",\"sla_deadline\":\"2025-10-12T05:31:20+00:00\",\"items\":[{\"id\":137,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T05:11:20+00:00\"}', '2025-10-12 02:11:20'),
(237, 135, 1, 'order.created', 'pending', '{\"order\":{\"id\":135,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T05:11:36+00:00\",\"updated_at\":\"2025-10-12T05:11:36+00:00\",\"status_changed_at\":\"2025-10-12T05:11:36+00:00\",\"sla_deadline\":\"2025-10-12T05:31:36+00:00\",\"items\":[{\"id\":138,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T05:11:36+00:00\"}', '2025-10-12 02:11:36'),
(238, 126, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":126,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":54.2999999999999971578290569595992565155029296875,\"delivery_fee\":0,\"discount\":0,\"total\":54.2999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-12T04:42:48+00:00\",\"updated_at\":\"2025-10-12T05:37:07+00:00\",\"status_changed_at\":\"2025-10-12T05:37:07+00:00\",\"sla_deadline\":\"2025-10-12T05:02:48+00:00\",\"items\":[{\"id\":128,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375},{\"id\":129,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":28.39999999999999857891452847979962825775146484375,\"line_total\":28.39999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T05:37:07+00:00\"}', '2025-10-12 02:37:07'),
(239, 127, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":127,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T04:50:01+00:00\",\"updated_at\":\"2025-10-12T05:37:09+00:00\",\"status_changed_at\":\"2025-10-12T05:37:09+00:00\",\"sla_deadline\":\"2025-10-12T05:10:01+00:00\",\"items\":[{\"id\":130,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T05:37:09+00:00\"}', '2025-10-12 02:37:09'),
(240, 136, 1, 'order.created', 'pending', '{\"order\":{\"id\":136,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T05:37:15+00:00\",\"updated_at\":\"2025-10-12T05:37:15+00:00\",\"status_changed_at\":\"2025-10-12T05:37:15+00:00\",\"sla_deadline\":\"2025-10-12T05:57:15+00:00\",\"items\":[{\"id\":139,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T05:37:15+00:00\"}', '2025-10-12 02:37:15'),
(241, 137, 1, 'order.created', 'pending', '{\"order\":{\"id\":137,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T05:37:27+00:00\",\"updated_at\":\"2025-10-12T05:37:27+00:00\",\"status_changed_at\":\"2025-10-12T05:37:27+00:00\",\"sla_deadline\":\"2025-10-12T05:57:27+00:00\",\"items\":[{\"id\":140,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T05:37:27+00:00\"}', '2025-10-12 02:37:27'),
(242, 138, 1, 'order.created', 'pending', '{\"order\":{\"id\":138,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T07:11:57+00:00\",\"updated_at\":\"2025-10-12T07:11:57+00:00\",\"status_changed_at\":\"2025-10-12T07:11:57+00:00\",\"sla_deadline\":\"2025-10-12T07:31:57+00:00\",\"items\":[{\"id\":141,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T07:11:57+00:00\"}', '2025-10-12 04:11:57'),
(243, 142, 1, 'order.created', 'pending', '{\"order\":{\"id\":142,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T04:13:22+00:00\",\"updated_at\":\"2025-10-12T04:13:22+00:00\",\"status_changed_at\":\"2025-10-12T04:13:22+00:00\",\"sla_deadline\":\"2025-10-12T04:33:22+00:00\",\"items\":[{\"id\":145,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-12T07:13:22+00:00\"}', '2025-10-12 04:13:22'),
(244, 143, 1, 'order.created', 'pending', '{\"order\":{\"id\":143,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T07:22:15+00:00\",\"updated_at\":\"2025-10-12T07:22:15+00:00\",\"status_changed_at\":\"2025-10-12T07:22:15+00:00\",\"sla_deadline\":\"2025-10-12T07:42:15+00:00\",\"items\":[{\"id\":146,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T07:22:15+00:00\"}', '2025-10-12 04:22:15'),
(245, 144, 1, 'order.created', 'pending', '{\"order\":{\"id\":144,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T04:23:48+00:00\",\"updated_at\":\"2025-10-12T04:23:48+00:00\",\"status_changed_at\":\"2025-10-12T04:23:48+00:00\",\"sla_deadline\":\"2025-10-12T04:43:48+00:00\",\"items\":[{\"id\":147,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-12T07:23:48+00:00\"}', '2025-10-12 04:23:48'),
(246, 145, 1, 'order.created', 'pending', '{\"order\":{\"id\":145,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T04:25:02+00:00\",\"updated_at\":\"2025-10-12T04:25:02+00:00\",\"status_changed_at\":\"2025-10-12T04:25:02+00:00\",\"sla_deadline\":\"2025-10-12T04:45:02+00:00\",\"items\":[{\"id\":148,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-12T07:25:02+00:00\"}', '2025-10-12 04:25:02'),
(247, 146, 1, 'order.created', 'pending', '{\"order\":{\"id\":146,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T04:25:58+00:00\",\"updated_at\":\"2025-10-12T04:25:58+00:00\",\"status_changed_at\":\"2025-10-12T04:25:58+00:00\",\"sla_deadline\":\"2025-10-12T04:45:58+00:00\",\"items\":[{\"id\":149,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-12T07:25:58+00:00\"}', '2025-10-12 04:25:58'),
(248, 147, 1, 'order.created', 'pending', '{\"order\":{\"id\":147,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T04:26:18+00:00\",\"updated_at\":\"2025-10-12T04:26:18+00:00\",\"status_changed_at\":\"2025-10-12T04:26:18+00:00\",\"sla_deadline\":\"2025-10-12T04:46:18+00:00\",\"items\":[{\"id\":150,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-12T07:26:18+00:00\"}', '2025-10-12 04:26:18'),
(249, 148, 1, 'order.created', 'pending', '{\"order\":{\"id\":148,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T04:27:02+00:00\",\"updated_at\":\"2025-10-12T04:27:02+00:00\",\"status_changed_at\":\"2025-10-12T04:27:02+00:00\",\"sla_deadline\":\"2025-10-12T04:47:02+00:00\",\"items\":[{\"id\":151,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-12T07:27:02+00:00\"}', '2025-10-12 04:27:02'),
(250, 149, 1, 'order.created', 'pending', '{\"order\":{\"id\":149,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dfsd, 65\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T07:27:18+00:00\",\"updated_at\":\"2025-10-12T07:27:18+00:00\",\"status_changed_at\":\"2025-10-12T07:27:18+00:00\",\"sla_deadline\":\"2025-10-12T07:47:18+00:00\",\"items\":[{\"id\":152,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T07:27:18+00:00\"}', '2025-10-12 04:27:18'),
(251, 150, 1, 'order.created', 'pending', '{\"order\":{\"id\":150,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"2375, 5463\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":28.39999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":28.39999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T07:50:33+00:00\",\"updated_at\":\"2025-10-12T07:50:33+00:00\",\"status_changed_at\":\"2025-10-12T07:50:33+00:00\",\"sla_deadline\":\"2025-10-12T08:10:33+00:00\",\"items\":[{\"id\":153,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":28.39999999999999857891452847979962825775146484375,\"line_total\":28.39999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T07:50:33+00:00\"}', '2025-10-12 04:50:33'),
(252, 151, 1, 'order.created', 'pending', '{\"order\":{\"id\":151,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":62.2999999999999971578290569595992565155029296875,\"delivery_fee\":0,\"discount\":0,\"total\":62.2999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-12T14:59:43+00:00\",\"updated_at\":\"2025-10-12T14:59:43+00:00\",\"status_changed_at\":\"2025-10-12T14:59:43+00:00\",\"sla_deadline\":\"2025-10-12T15:19:43+00:00\",\"items\":[{\"id\":154,\"product_id\":3,\"name\":\"Cheeseburger\",\"qty\":1,\"quantity\":1,\"unit_price\":29.89999999999999857891452847979962825775146484375,\"line_total\":29.89999999999999857891452847979962825775146484375},{\"id\":155,\"product_id\":3,\"name\":\"Cheeseburger\",\"qty\":1,\"quantity\":1,\"unit_price\":32.39999999999999857891452847979962825775146484375,\"line_total\":32.39999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T14:59:43+00:00\"}', '2025-10-12 11:59:43');
INSERT INTO `order_events` (`id`, `order_id`, `company_id`, `event_type`, `status`, `payload`, `created_at`) VALUES
(253, 152, 1, 'order.created', 'pending', '{\"order\":{\"id\":152,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T16:38:46+00:00\",\"updated_at\":\"2025-10-12T16:38:46+00:00\",\"status_changed_at\":\"2025-10-12T16:38:46+00:00\",\"sla_deadline\":\"2025-10-12T16:58:46+00:00\",\"items\":[{\"id\":156,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T16:38:46+00:00\"}', '2025-10-12 13:38:46'),
(254, 154, 1, 'order.created', 'pending', '{\"order\":{\"id\":154,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-12T18:55:32+00:00\",\"updated_at\":\"2025-10-12T18:55:32+00:00\",\"status_changed_at\":\"2025-10-12T18:55:32+00:00\",\"sla_deadline\":\"2025-10-12T19:15:32+00:00\",\"items\":[{\"id\":157,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-12T18:55:32+00:00\"}', '2025-10-12 15:55:32'),
(255, 155, 1, 'order.created', 'pending', '{\"order\":{\"id\":155,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-13T02:07:30+00:00\",\"updated_at\":\"2025-10-13T02:07:30+00:00\",\"status_changed_at\":\"2025-10-13T02:07:30+00:00\",\"sla_deadline\":\"2025-10-13T02:27:30+00:00\",\"items\":[{\"id\":158,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-13T02:07:30+00:00\"}', '2025-10-12 23:07:30'),
(256, 156, 1, 'order.created', 'pending', '{\"order\":{\"id\":156,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-13T02:16:26+00:00\",\"updated_at\":\"2025-10-13T02:16:26+00:00\",\"status_changed_at\":\"2025-10-13T02:16:26+00:00\",\"sla_deadline\":\"2025-10-13T02:36:26+00:00\",\"items\":[{\"id\":159,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-13T02:16:26+00:00\"}', '2025-10-12 23:16:26'),
(257, 157, 1, 'order.created', 'pending', '{\"order\":{\"id\":157,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T23:27:34+00:00\",\"updated_at\":\"2025-10-12T23:27:34+00:00\",\"status_changed_at\":\"2025-10-12T23:27:34+00:00\",\"sla_deadline\":\"2025-10-12T23:47:34+00:00\",\"items\":[{\"id\":160,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-13T02:27:34+00:00\"}', '2025-10-12 23:27:34'),
(258, 158, 1, 'order.created', 'pending', '{\"order\":{\"id\":158,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T23:36:53+00:00\",\"updated_at\":\"2025-10-12T23:36:53+00:00\",\"status_changed_at\":\"2025-10-12T23:36:53+00:00\",\"sla_deadline\":\"2025-10-12T23:56:53+00:00\",\"items\":[{\"id\":161,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-13T02:36:53+00:00\"}', '2025-10-12 23:36:53'),
(259, 159, 1, 'order.created', 'pending', '{\"order\":{\"id\":159,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-13T02:46:18+00:00\",\"updated_at\":\"2025-10-13T02:46:18+00:00\",\"status_changed_at\":\"2025-10-13T02:46:18+00:00\",\"sla_deadline\":\"2025-10-13T03:06:18+00:00\",\"items\":[{\"id\":162,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-13T02:46:18+00:00\"}', '2025-10-12 23:46:18'),
(260, 144, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":144,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T07:23:48+00:00\",\"updated_at\":\"2025-10-14T03:26:32+00:00\",\"status_changed_at\":\"2025-10-14T03:26:32+00:00\",\"sla_deadline\":\"2025-10-12T07:43:48+00:00\",\"items\":[{\"id\":147,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-14T03:26:32+00:00\"}', '2025-10-14 00:26:32'),
(261, 144, 1, 'order.status_changed', 'completed', '{\"order\":{\"id\":144,\"company_id\":1,\"status\":\"completed\",\"customer_name\":\"Teste Bot\",\"customer_phone\":\"+559999999999\",\"customer_address\":\"Rua Falsa, 123\\nBairro\\nCidade\",\"notes\":\"Pedido de teste enviado pelo script\",\"subtotal\":20,\"delivery_fee\":5,\"discount\":0,\"total\":25,\"created_at\":\"2025-10-12T07:23:48+00:00\",\"updated_at\":\"2025-10-14T03:26:35+00:00\",\"status_changed_at\":\"2025-10-14T03:26:35+00:00\",\"sla_deadline\":\"2025-10-12T07:43:48+00:00\",\"items\":[{\"id\":147,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":20,\"line_total\":20}]},\"created_at\":\"2025-10-14T03:26:35+00:00\"}', '2025-10-14 00:26:35'),
(262, 159, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":159,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"vmvb, bvnmvhb\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-13T02:46:18+00:00\",\"updated_at\":\"2025-10-14T03:27:36+00:00\",\"status_changed_at\":\"2025-10-14T03:27:36+00:00\",\"sla_deadline\":\"2025-10-13T03:06:18+00:00\",\"items\":[{\"id\":162,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-14T03:27:36+00:00\"}', '2025-10-14 00:27:36'),
(263, 160, 1, 'order.created', 'pending', '{\"order\":{\"id\":160,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-15T02:58:25+00:00\",\"updated_at\":\"2025-10-15T02:58:25+00:00\",\"status_changed_at\":\"2025-10-15T02:58:25+00:00\",\"sla_deadline\":\"2025-10-15T03:18:25+00:00\",\"items\":[{\"id\":163,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-15T02:58:25+00:00\"}', '2025-10-14 23:58:25'),
(264, 161, 1, 'order.created', 'pending', '{\"order\":{\"id\":161,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T03:57:16+00:00\",\"updated_at\":\"2025-10-16T03:57:16+00:00\",\"status_changed_at\":\"2025-10-16T03:57:16+00:00\",\"sla_deadline\":\"2025-10-16T04:17:16+00:00\",\"items\":[{\"id\":164,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T03:57:16+00:00\"}', '2025-10-16 00:57:16'),
(265, 162, 1, 'order.created', 'pending', '{\"order\":{\"id\":162,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T03:59:03+00:00\",\"updated_at\":\"2025-10-16T03:59:03+00:00\",\"status_changed_at\":\"2025-10-16T03:59:03+00:00\",\"sla_deadline\":\"2025-10-16T04:19:03+00:00\",\"items\":[{\"id\":165,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T03:59:03+00:00\"}', '2025-10-16 00:59:03'),
(266, 163, 1, 'order.created', 'pending', '{\"order\":{\"id\":163,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T04:07:13+00:00\",\"updated_at\":\"2025-10-16T04:07:13+00:00\",\"status_changed_at\":\"2025-10-16T04:07:13+00:00\",\"sla_deadline\":\"2025-10-16T04:27:13+00:00\",\"items\":[{\"id\":166,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:07:13+00:00\"}', '2025-10-16 01:07:13'),
(267, 161, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":161,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T03:57:16+00:00\",\"updated_at\":\"2025-10-16T04:08:44+00:00\",\"status_changed_at\":\"2025-10-16T04:08:44+00:00\",\"sla_deadline\":\"2025-10-16T04:17:16+00:00\",\"items\":[{\"id\":164,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:08:44+00:00\"}', '2025-10-16 01:08:44'),
(268, 162, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":162,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T03:59:03+00:00\",\"updated_at\":\"2025-10-16T04:08:51+00:00\",\"status_changed_at\":\"2025-10-16T04:08:51+00:00\",\"sla_deadline\":\"2025-10-16T04:19:03+00:00\",\"items\":[{\"id\":165,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:08:51+00:00\"}', '2025-10-16 01:08:51'),
(269, 163, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":163,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T04:07:13+00:00\",\"updated_at\":\"2025-10-16T04:08:54+00:00\",\"status_changed_at\":\"2025-10-16T04:08:54+00:00\",\"sla_deadline\":\"2025-10-16T04:27:13+00:00\",\"items\":[{\"id\":166,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:08:54+00:00\"}', '2025-10-16 01:08:54'),
(270, 164, 1, 'order.created', 'pending', '{\"order\":{\"id\":164,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T04:13:34+00:00\",\"updated_at\":\"2025-10-16T04:13:34+00:00\",\"status_changed_at\":\"2025-10-16T04:13:34+00:00\",\"sla_deadline\":\"2025-10-16T04:33:34+00:00\",\"items\":[{\"id\":167,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:13:34+00:00\"}', '2025-10-16 01:13:34'),
(271, 164, 1, 'order.status_changed', 'paid', '{\"order\":{\"id\":164,\"company_id\":1,\"status\":\"paid\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T04:13:34+00:00\",\"updated_at\":\"2025-10-16T04:20:54+00:00\",\"status_changed_at\":\"2025-10-16T04:20:54+00:00\",\"sla_deadline\":\"2025-10-16T04:33:34+00:00\",\"items\":[{\"id\":167,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:20:54+00:00\"}', '2025-10-16 01:20:54'),
(272, 161, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":161,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"fdfd, sdfsdf\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T03:57:16+00:00\",\"updated_at\":\"2025-10-16T04:21:09+00:00\",\"status_changed_at\":\"2025-10-16T04:21:09+00:00\",\"sla_deadline\":\"2025-10-16T04:17:16+00:00\",\"items\":[{\"id\":164,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T04:21:09+00:00\"}', '2025-10-16 01:21:09'),
(273, 165, 1, 'order.created', 'pending', '{\"order\":{\"id\":165,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:06:54+00:00\",\"updated_at\":\"2025-10-16T05:06:54+00:00\",\"status_changed_at\":\"2025-10-16T05:06:54+00:00\",\"sla_deadline\":\"2025-10-16T05:26:54+00:00\",\"items\":[{\"id\":168,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T05:06:54+00:00\"}', '2025-10-16 02:06:54'),
(274, 166, 1, 'order.created', 'pending', '{\"order\":{\"id\":166,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:10:43+00:00\",\"updated_at\":\"2025-10-16T05:10:43+00:00\",\"status_changed_at\":\"2025-10-16T05:10:43+00:00\",\"sla_deadline\":\"2025-10-16T05:30:43+00:00\",\"items\":[{\"id\":169,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T05:10:43+00:00\"}', '2025-10-16 02:10:43'),
(275, 167, 1, 'order.created', 'pending', '{\"order\":{\"id\":167,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:16:38+00:00\",\"updated_at\":\"2025-10-16T05:16:38+00:00\",\"status_changed_at\":\"2025-10-16T05:16:38+00:00\",\"sla_deadline\":\"2025-10-16T05:36:38+00:00\",\"items\":[{\"id\":170,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T05:16:38+00:00\"}', '2025-10-16 02:16:38'),
(276, 168, 1, 'order.created', 'pending', '{\"order\":{\"id\":168,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"ddv, dfd\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:20:52+00:00\",\"updated_at\":\"2025-10-16T05:20:52+00:00\",\"status_changed_at\":\"2025-10-16T05:20:52+00:00\",\"sla_deadline\":\"2025-10-16T05:40:52+00:00\",\"items\":[{\"id\":171,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T05:20:52+00:00\"}', '2025-10-16 02:20:52'),
(277, 169, 1, 'order.created', 'pending', '{\"order\":{\"id\":169,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"Rua 7, 538\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Dinheiro (genérico) — Valor informado: R$ 50,00 (Troco: R$ 18,10)\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":31.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T22:14:49+00:00\",\"updated_at\":\"2025-10-16T22:14:49+00:00\",\"status_changed_at\":\"2025-10-16T22:14:49+00:00\",\"sla_deadline\":\"2025-10-16T22:34:49+00:00\",\"items\":[{\"id\":172,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-16T22:14:49+00:00\"}', '2025-10-16 19:14:49'),
(278, 170, 1, 'order.created', 'pending', '{\"order\":{\"id\":170,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":51.7999999999999971578290569595992565155029296875,\"delivery_fee\":6,\"discount\":0,\"total\":57.7999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-17T01:09:39+00:00\",\"updated_at\":\"2025-10-17T01:09:39+00:00\",\"status_changed_at\":\"2025-10-17T01:09:39+00:00\",\"sla_deadline\":\"2025-10-17T01:29:39+00:00\",\"items\":[{\"id\":173,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375},{\"id\":174,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:09:39+00:00\"}', '2025-10-16 22:09:39'),
(279, 171, 1, 'order.created', 'pending', '{\"order\":{\"id\":171,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":31.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-17T01:16:44+00:00\",\"updated_at\":\"2025-10-17T01:16:44+00:00\",\"status_changed_at\":\"2025-10-17T01:16:44+00:00\",\"sla_deadline\":\"2025-10-17T01:36:44+00:00\",\"items\":[{\"id\":175,\"product_id\":2,\"name\":\"Classic Burger\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:16:44+00:00\"}', '2025-10-16 22:16:44'),
(280, 172, 1, 'order.created', 'pending', '{\"order\":{\"id\":172,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":63.60000000000000142108547152020037174224853515625,\"delivery_fee\":6,\"discount\":0,\"total\":69.599999999999994315658113919198513031005859375,\"created_at\":\"2025-10-17T01:24:58+00:00\",\"updated_at\":\"2025-10-17T01:24:58+00:00\",\"status_changed_at\":\"2025-10-17T01:24:58+00:00\",\"sla_deadline\":\"2025-10-17T01:44:58+00:00\",\"items\":[{\"id\":176,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":63.60000000000000142108547152020037174224853515625,\"line_total\":63.60000000000000142108547152020037174224853515625}]},\"created_at\":\"2025-10-17T01:24:58+00:00\"}', '2025-10-16 22:24:58'),
(281, 171, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":171,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":31.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-17T01:16:44+00:00\",\"updated_at\":\"2025-10-17T01:25:31+00:00\",\"status_changed_at\":\"2025-10-17T01:25:31+00:00\",\"sla_deadline\":\"2025-10-17T01:36:44+00:00\",\"items\":[{\"id\":175,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:31+00:00\"}', '2025-10-16 22:25:31'),
(282, 165, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":165,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:06:54+00:00\",\"updated_at\":\"2025-10-17T01:25:49+00:00\",\"status_changed_at\":\"2025-10-17T01:25:49+00:00\",\"sla_deadline\":\"2025-10-16T05:26:54+00:00\",\"items\":[{\"id\":168,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:49+00:00\"}', '2025-10-16 22:25:49'),
(283, 166, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":166,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:10:43+00:00\",\"updated_at\":\"2025-10-17T01:25:51+00:00\",\"status_changed_at\":\"2025-10-17T01:25:51+00:00\",\"sla_deadline\":\"2025-10-16T05:30:43+00:00\",\"items\":[{\"id\":169,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:51+00:00\"}', '2025-10-16 22:25:51'),
(284, 167, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":167,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"hfgh, fghfg\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:16:38+00:00\",\"updated_at\":\"2025-10-17T01:25:53+00:00\",\"status_changed_at\":\"2025-10-17T01:25:53+00:00\",\"sla_deadline\":\"2025-10-16T05:36:38+00:00\",\"items\":[{\"id\":170,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:53+00:00\"}', '2025-10-16 22:25:53'),
(285, 168, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":168,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"ddv, dfd\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":0,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T05:20:52+00:00\",\"updated_at\":\"2025-10-17T01:25:54+00:00\",\"status_changed_at\":\"2025-10-17T01:25:54+00:00\",\"sla_deadline\":\"2025-10-16T05:40:52+00:00\",\"items\":[{\"id\":171,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:54+00:00\"}', '2025-10-16 22:25:54'),
(286, 169, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":169,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"Rua 7, 538\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Dinheiro (genérico) — Valor informado: R$ 50,00 (Troco: R$ 18,10)\",\"subtotal\":25.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":31.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-16T22:14:49+00:00\",\"updated_at\":\"2025-10-17T01:25:57+00:00\",\"status_changed_at\":\"2025-10-17T01:25:57+00:00\",\"sla_deadline\":\"2025-10-16T22:34:49+00:00\",\"items\":[{\"id\":172,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:57+00:00\"}', '2025-10-16 22:25:57'),
(287, 170, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":170,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":51.7999999999999971578290569595992565155029296875,\"delivery_fee\":6,\"discount\":0,\"total\":57.7999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-17T01:09:39+00:00\",\"updated_at\":\"2025-10-17T01:25:59+00:00\",\"status_changed_at\":\"2025-10-17T01:25:59+00:00\",\"sla_deadline\":\"2025-10-17T01:29:39+00:00\",\"items\":[{\"id\":173,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375},{\"id\":174,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":25.89999999999999857891452847979962825775146484375,\"line_total\":25.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T01:25:59+00:00\"}', '2025-10-16 22:25:59'),
(288, 173, 1, 'order.created', 'pending', '{\"order\":{\"id\":173,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":54.5,\"delivery_fee\":6,\"discount\":0,\"total\":60.5,\"created_at\":\"2025-10-17T01:32:29+00:00\",\"updated_at\":\"2025-10-17T01:32:29+00:00\",\"status_changed_at\":\"2025-10-17T01:32:29+00:00\",\"sla_deadline\":\"2025-10-17T01:52:29+00:00\",\"items\":[{\"id\":177,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":54.5,\"line_total\":54.5}]},\"created_at\":\"2025-10-17T01:32:29+00:00\"}', '2025-10-16 22:32:29'),
(289, 174, 1, 'order.created', 'pending', '{\"order\":{\"id\":174,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\",, 4\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":46,\"delivery_fee\":6,\"discount\":0,\"total\":52,\"created_at\":\"2025-10-17T01:39:19+00:00\",\"updated_at\":\"2025-10-17T01:39:19+00:00\",\"status_changed_at\":\"2025-10-17T01:39:19+00:00\",\"sla_deadline\":\"2025-10-17T01:59:19+00:00\",\"items\":[{\"id\":178,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":46,\"line_total\":46}]},\"created_at\":\"2025-10-17T01:39:19+00:00\"}', '2025-10-16 22:39:19'),
(290, 175, 1, 'order.created', 'pending', '{\"order\":{\"id\":175,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":46,\"delivery_fee\":6,\"discount\":0,\"total\":52,\"created_at\":\"2025-10-17T01:43:42+00:00\",\"updated_at\":\"2025-10-17T01:43:42+00:00\",\"status_changed_at\":\"2025-10-17T01:43:42+00:00\",\"sla_deadline\":\"2025-10-17T02:03:42+00:00\",\"items\":[{\"id\":179,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":46,\"line_total\":46}]},\"created_at\":\"2025-10-17T01:43:42+00:00\"}', '2025-10-16 22:43:42'),
(291, 176, 1, 'order.created', 'pending', '{\"order\":{\"id\":176,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-17T02:18:50+00:00\",\"updated_at\":\"2025-10-17T02:18:50+00:00\",\"status_changed_at\":\"2025-10-17T02:18:50+00:00\",\"sla_deadline\":\"2025-10-17T02:38:50+00:00\",\"items\":[{\"id\":180,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-17T02:18:50+00:00\"}', '2025-10-16 23:18:50'),
(292, 177, 1, 'order.created', 'pending', '{\"order\":{\"id\":177,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":46,\"delivery_fee\":6,\"discount\":0,\"total\":52,\"created_at\":\"2025-10-17T02:19:14+00:00\",\"updated_at\":\"2025-10-17T02:19:14+00:00\",\"status_changed_at\":\"2025-10-17T02:19:14+00:00\",\"sla_deadline\":\"2025-10-17T02:39:14+00:00\",\"items\":[{\"id\":181,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":46,\"line_total\":46}]},\"created_at\":\"2025-10-17T02:19:14+00:00\"}', '2025-10-16 23:19:14'),
(293, 178, 1, 'order.created', 'pending', '{\"order\":{\"id\":178,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 756\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":53.5,\"delivery_fee\":6,\"discount\":0,\"total\":59.5,\"created_at\":\"2025-10-17T02:33:49+00:00\",\"updated_at\":\"2025-10-17T02:33:49+00:00\",\"status_changed_at\":\"2025-10-17T02:33:49+00:00\",\"sla_deadline\":\"2025-10-17T02:53:49+00:00\",\"items\":[{\"id\":182,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":53.5,\"line_total\":53.5}]},\"created_at\":\"2025-10-17T02:33:49+00:00\"}', '2025-10-16 23:33:49'),
(294, 179, 1, 'order.created', 'pending', '{\"order\":{\"id\":179,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":23.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":29.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T02:51:12+00:00\",\"updated_at\":\"2025-10-17T02:51:12+00:00\",\"status_changed_at\":\"2025-10-17T02:51:12+00:00\",\"sla_deadline\":\"2025-10-17T03:11:12+00:00\",\"items\":[{\"id\":183,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":23.800000000000000710542735760100185871124267578125,\"line_total\":23.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T02:51:12+00:00\"}', '2025-10-16 23:51:12'),
(295, 180, 1, 'order.created', 'pending', '{\"order\":{\"id\":180,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":62.2999999999999971578290569595992565155029296875,\"delivery_fee\":6,\"discount\":0,\"total\":68.2999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-17T02:55:24+00:00\",\"updated_at\":\"2025-10-17T02:55:24+00:00\",\"status_changed_at\":\"2025-10-17T02:55:24+00:00\",\"sla_deadline\":\"2025-10-17T03:15:24+00:00\",\"items\":[{\"id\":184,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":62.2999999999999971578290569595992565155029296875,\"line_total\":62.2999999999999971578290569595992565155029296875}]},\"created_at\":\"2025-10-17T02:55:24+00:00\"}', '2025-10-16 23:55:24'),
(296, 181, 1, 'order.created', 'pending', '{\"order\":{\"id\":181,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":24.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":30.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T03:02:27+00:00\",\"updated_at\":\"2025-10-17T03:02:27+00:00\",\"status_changed_at\":\"2025-10-17T03:02:27+00:00\",\"sla_deadline\":\"2025-10-17T03:22:27+00:00\",\"items\":[{\"id\":185,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":24.800000000000000710542735760100185871124267578125,\"line_total\":24.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T03:02:27+00:00\"}', '2025-10-17 00:02:27'),
(297, 182, 1, 'order.created', 'pending', '{\"order\":{\"id\":182,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\",, 4\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":46,\"delivery_fee\":6,\"discount\":0,\"total\":52,\"created_at\":\"2025-10-17T04:10:33+00:00\",\"updated_at\":\"2025-10-17T04:10:33+00:00\",\"status_changed_at\":\"2025-10-17T04:10:33+00:00\",\"sla_deadline\":\"2025-10-17T04:30:33+00:00\",\"items\":[{\"id\":186,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":46,\"line_total\":46}]},\"created_at\":\"2025-10-17T04:10:33+00:00\"}', '2025-10-17 01:10:33'),
(298, 183, 1, 'order.created', 'pending', '{\"order\":{\"id\":183,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":23.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":29.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T04:24:33+00:00\",\"updated_at\":\"2025-10-17T04:24:33+00:00\",\"status_changed_at\":\"2025-10-17T04:24:33+00:00\",\"sla_deadline\":\"2025-10-17T04:44:33+00:00\",\"items\":[{\"id\":187,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":23.800000000000000710542735760100185871124267578125,\"line_total\":23.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T04:24:33+00:00\"}', '2025-10-17 01:24:33'),
(299, 184, 1, 'order.created', 'pending', '{\"order\":{\"id\":184,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":33.2999999999999971578290569595992565155029296875,\"delivery_fee\":6,\"discount\":0,\"total\":39.2999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-17T04:28:02+00:00\",\"updated_at\":\"2025-10-17T04:28:02+00:00\",\"status_changed_at\":\"2025-10-17T04:28:02+00:00\",\"sla_deadline\":\"2025-10-17T04:48:02+00:00\",\"items\":[{\"id\":188,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16},{\"id\":189,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":17.300000000000000710542735760100185871124267578125,\"line_total\":17.300000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T04:28:02+00:00\"}', '2025-10-17 01:28:02'),
(300, 185, 1, 'order.created', 'pending', '{\"order\":{\"id\":185,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":32.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":38.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-17T04:37:59+00:00\",\"updated_at\":\"2025-10-17T04:37:59+00:00\",\"status_changed_at\":\"2025-10-17T04:37:59+00:00\",\"sla_deadline\":\"2025-10-17T04:57:59+00:00\",\"items\":[{\"id\":190,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":32.89999999999999857891452847979962825775146484375,\"line_total\":32.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T04:37:59+00:00\"}', '2025-10-17 01:37:59'),
(301, 186, 1, 'order.created', 'pending', '{\"order\":{\"id\":186,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":24.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":30.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T04:42:31+00:00\",\"updated_at\":\"2025-10-17T04:42:31+00:00\",\"status_changed_at\":\"2025-10-17T04:42:31+00:00\",\"sla_deadline\":\"2025-10-17T05:02:31+00:00\",\"items\":[{\"id\":191,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":24.800000000000000710542735760100185871124267578125,\"line_total\":24.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T04:42:31+00:00\"}', '2025-10-17 01:42:31'),
(302, 187, 1, 'order.created', 'pending', '{\"order\":{\"id\":187,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":19.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":25.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-17T04:48:34+00:00\",\"updated_at\":\"2025-10-17T04:48:34+00:00\",\"status_changed_at\":\"2025-10-17T04:48:34+00:00\",\"sla_deadline\":\"2025-10-17T05:08:34+00:00\",\"items\":[{\"id\":192,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":19.89999999999999857891452847979962825775146484375,\"line_total\":19.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T04:48:34+00:00\"}', '2025-10-17 01:48:34'),
(303, 181, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":181,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":24.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":30.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T03:02:27+00:00\",\"updated_at\":\"2025-10-17T06:29:55+00:00\",\"status_changed_at\":\"2025-10-17T06:29:55+00:00\",\"sla_deadline\":\"2025-10-17T03:22:27+00:00\",\"items\":[{\"id\":185,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":24.800000000000000710542735760100185871124267578125,\"line_total\":24.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T06:29:55+00:00\"}', '2025-10-17 03:29:55'),
(304, 182, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":182,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\",, 4\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":46,\"delivery_fee\":6,\"discount\":0,\"total\":52,\"created_at\":\"2025-10-17T04:10:33+00:00\",\"updated_at\":\"2025-10-17T06:29:57+00:00\",\"status_changed_at\":\"2025-10-17T06:29:57+00:00\",\"sla_deadline\":\"2025-10-17T04:30:33+00:00\",\"items\":[{\"id\":186,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":46,\"line_total\":46}]},\"created_at\":\"2025-10-17T06:29:57+00:00\"}', '2025-10-17 03:29:57'),
(305, 183, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":183,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":23.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":29.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T04:24:33+00:00\",\"updated_at\":\"2025-10-17T06:30:00+00:00\",\"status_changed_at\":\"2025-10-17T06:30:00+00:00\",\"sla_deadline\":\"2025-10-17T04:44:33+00:00\",\"items\":[{\"id\":187,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":23.800000000000000710542735760100185871124267578125,\"line_total\":23.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T06:30:00+00:00\"}', '2025-10-17 03:30:00'),
(306, 184, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":184,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":33.2999999999999971578290569595992565155029296875,\"delivery_fee\":6,\"discount\":0,\"total\":39.2999999999999971578290569595992565155029296875,\"created_at\":\"2025-10-17T04:28:02+00:00\",\"updated_at\":\"2025-10-17T06:30:17+00:00\",\"status_changed_at\":\"2025-10-17T06:30:17+00:00\",\"sla_deadline\":\"2025-10-17T04:48:02+00:00\",\"items\":[{\"id\":188,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16},{\"id\":189,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":17.300000000000000710542735760100185871124267578125,\"line_total\":17.300000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T06:30:17+00:00\"}', '2025-10-17 03:30:17'),
(307, 185, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":185,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":32.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":38.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-17T04:37:59+00:00\",\"updated_at\":\"2025-10-17T06:30:19+00:00\",\"status_changed_at\":\"2025-10-17T06:30:19+00:00\",\"sla_deadline\":\"2025-10-17T04:57:59+00:00\",\"items\":[{\"id\":190,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":32.89999999999999857891452847979962825775146484375,\"line_total\":32.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-17T06:30:19+00:00\"}', '2025-10-17 03:30:19');
INSERT INTO `order_events` (`id`, `order_id`, `company_id`, `event_type`, `status`, `payload`, `created_at`) VALUES
(308, 186, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":186,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":24.800000000000000710542735760100185871124267578125,\"delivery_fee\":6,\"discount\":0,\"total\":30.800000000000000710542735760100185871124267578125,\"created_at\":\"2025-10-17T04:42:31+00:00\",\"updated_at\":\"2025-10-17T06:30:21+00:00\",\"status_changed_at\":\"2025-10-17T06:30:21+00:00\",\"sla_deadline\":\"2025-10-17T05:02:31+00:00\",\"items\":[{\"id\":191,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":24.800000000000000710542735760100185871124267578125,\"line_total\":24.800000000000000710542735760100185871124267578125}]},\"created_at\":\"2025-10-17T06:30:21+00:00\"}', '2025-10-17 03:30:21'),
(309, 188, 1, 'order.created', 'pending', '{\"order\":{\"id\":188,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":32,\"delivery_fee\":6,\"discount\":0,\"total\":38,\"created_at\":\"2025-10-18T03:31:08+00:00\",\"updated_at\":\"2025-10-18T03:31:08+00:00\",\"status_changed_at\":\"2025-10-18T03:31:08+00:00\",\"sla_deadline\":\"2025-10-18T03:51:08+00:00\",\"items\":[{\"id\":193,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16},{\"id\":194,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T03:31:08+00:00\"}', '2025-10-18 00:31:08'),
(310, 189, 1, 'order.created', 'pending', '{\"order\":{\"id\":189,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":29.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":35.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-18T04:32:38+00:00\",\"updated_at\":\"2025-10-18T04:32:38+00:00\",\"status_changed_at\":\"2025-10-18T04:32:38+00:00\",\"sla_deadline\":\"2025-10-18T04:52:38+00:00\",\"items\":[{\"id\":195,\"product_id\":3,\"name\":\"Woll Smash Triplo\",\"qty\":1,\"quantity\":1,\"unit_price\":29.89999999999999857891452847979962825775146484375,\"line_total\":29.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-18T04:32:38+00:00\"}', '2025-10-18 01:32:38'),
(311, 190, 1, 'order.created', 'pending', '{\"order\":{\"id\":190,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":29.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":35.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-18T04:39:38+00:00\",\"updated_at\":\"2025-10-18T04:39:38+00:00\",\"status_changed_at\":\"2025-10-18T04:39:38+00:00\",\"sla_deadline\":\"2025-10-18T04:59:38+00:00\",\"items\":[{\"id\":196,\"product_id\":3,\"name\":\"Woll Smash Triplo\",\"qty\":1,\"quantity\":1,\"unit_price\":29.89999999999999857891452847979962825775146484375,\"line_total\":29.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-18T04:39:38+00:00\"}', '2025-10-18 01:39:38'),
(312, 191, 1, 'order.created', 'pending', '{\"order\":{\"id\":191,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-18T04:41:58+00:00\",\"updated_at\":\"2025-10-18T04:41:58+00:00\",\"status_changed_at\":\"2025-10-18T04:41:58+00:00\",\"sla_deadline\":\"2025-10-18T05:01:58+00:00\",\"items\":[{\"id\":197,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T04:41:58+00:00\"}', '2025-10-18 01:41:58'),
(313, 192, 1, 'order.created', 'pending', '{\"order\":{\"id\":192,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-18T04:42:49+00:00\",\"updated_at\":\"2025-10-18T04:42:49+00:00\",\"status_changed_at\":\"2025-10-18T04:42:49+00:00\",\"sla_deadline\":\"2025-10-18T05:02:49+00:00\",\"items\":[{\"id\":198,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T04:42:49+00:00\"}', '2025-10-18 01:42:49'),
(314, 193, 1, 'order.created', 'pending', '{\"order\":{\"id\":193,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-18T04:43:10+00:00\",\"updated_at\":\"2025-10-18T04:43:10+00:00\",\"status_changed_at\":\"2025-10-18T04:43:10+00:00\",\"sla_deadline\":\"2025-10-18T05:03:10+00:00\",\"items\":[{\"id\":199,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T04:43:10+00:00\"}', '2025-10-18 01:43:10'),
(315, 188, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":188,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":32,\"delivery_fee\":6,\"discount\":0,\"total\":38,\"created_at\":\"2025-10-18T03:31:08+00:00\",\"updated_at\":\"2025-10-18T04:51:11+00:00\",\"status_changed_at\":\"2025-10-18T04:51:11+00:00\",\"sla_deadline\":\"2025-10-18T03:51:08+00:00\",\"items\":[{\"id\":193,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16},{\"id\":194,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T04:51:11+00:00\"}', '2025-10-18 01:51:11'),
(316, 189, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":189,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":29.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":35.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-18T04:32:38+00:00\",\"updated_at\":\"2025-10-18T04:51:13+00:00\",\"status_changed_at\":\"2025-10-18T04:51:13+00:00\",\"sla_deadline\":\"2025-10-18T04:52:38+00:00\",\"items\":[{\"id\":195,\"product_id\":3,\"name\":\"Woll Smash Triplo\",\"qty\":1,\"quantity\":1,\"unit_price\":29.89999999999999857891452847979962825775146484375,\"line_total\":29.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-18T04:51:13+00:00\"}', '2025-10-18 01:51:13'),
(317, 190, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":190,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":29.89999999999999857891452847979962825775146484375,\"delivery_fee\":6,\"discount\":0,\"total\":35.89999999999999857891452847979962825775146484375,\"created_at\":\"2025-10-18T04:39:38+00:00\",\"updated_at\":\"2025-10-18T04:51:14+00:00\",\"status_changed_at\":\"2025-10-18T04:51:14+00:00\",\"sla_deadline\":\"2025-10-18T04:59:38+00:00\",\"items\":[{\"id\":196,\"product_id\":3,\"name\":\"Woll Smash Triplo\",\"qty\":1,\"quantity\":1,\"unit_price\":29.89999999999999857891452847979962825775146484375,\"line_total\":29.89999999999999857891452847979962825775146484375}]},\"created_at\":\"2025-10-18T04:51:14+00:00\"}', '2025-10-18 01:51:14'),
(318, 191, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":191,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-18T04:41:58+00:00\",\"updated_at\":\"2025-10-18T04:51:17+00:00\",\"status_changed_at\":\"2025-10-18T04:51:17+00:00\",\"sla_deadline\":\"2025-10-18T05:01:58+00:00\",\"items\":[{\"id\":197,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T04:51:17+00:00\"}', '2025-10-18 01:51:17'),
(319, 192, 1, 'order.canceled', 'canceled', '{\"order\":{\"id\":192,\"company_id\":1,\"status\":\"canceled\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"rua, 7\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-18T04:42:49+00:00\",\"updated_at\":\"2025-10-18T04:51:20+00:00\",\"status_changed_at\":\"2025-10-18T04:51:20+00:00\",\"sla_deadline\":\"2025-10-18T05:02:49+00:00\",\"items\":[{\"id\":198,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-18T04:51:20+00:00\"}', '2025-10-18 01:51:20'),
(337, 211, 1, 'order.created', 'pending', '{\"order\":{\"id\":211,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T04:39:07+00:00\",\"updated_at\":\"2025-10-21T04:39:07+00:00\",\"status_changed_at\":\"2025-10-21T04:39:07+00:00\",\"sla_deadline\":\"2025-10-21T04:59:07+00:00\",\"items\":[{\"id\":218,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T04:39:07+00:00\"}', '2025-10-21 01:39:07'),
(338, 212, 1, 'order.created', 'pending', '{\"order\":{\"id\":212,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T04:42:23+00:00\",\"updated_at\":\"2025-10-21T04:42:23+00:00\",\"status_changed_at\":\"2025-10-21T04:42:23+00:00\",\"sla_deadline\":\"2025-10-21T05:02:23+00:00\",\"items\":[{\"id\":219,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T04:42:23+00:00\"}', '2025-10-21 01:42:23'),
(339, 213, 1, 'order.created', 'pending', '{\"order\":{\"id\":213,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T05:14:29+00:00\",\"updated_at\":\"2025-10-21T05:14:29+00:00\",\"status_changed_at\":\"2025-10-21T05:14:29+00:00\",\"sla_deadline\":\"2025-10-21T05:34:29+00:00\",\"items\":[{\"id\":220,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T05:14:29+00:00\"}', '2025-10-21 02:14:29'),
(340, 214, 1, 'order.created', 'pending', '{\"order\":{\"id\":214,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T05:15:13+00:00\",\"updated_at\":\"2025-10-21T05:15:13+00:00\",\"status_changed_at\":\"2025-10-21T05:15:13+00:00\",\"sla_deadline\":\"2025-10-21T05:35:13+00:00\",\"items\":[{\"id\":221,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T05:15:13+00:00\"}', '2025-10-21 02:15:13'),
(341, 215, 1, 'order.created', 'pending', '{\"order\":{\"id\":215,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T05:20:29+00:00\",\"updated_at\":\"2025-10-21T05:20:29+00:00\",\"status_changed_at\":\"2025-10-21T05:20:29+00:00\",\"sla_deadline\":\"2025-10-21T05:40:29+00:00\",\"items\":[{\"id\":222,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T05:20:29+00:00\"}', '2025-10-21 02:20:29'),
(342, 216, 1, 'order.created', 'pending', '{\"order\":{\"id\":216,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T05:49:41+00:00\",\"updated_at\":\"2025-10-21T05:49:41+00:00\",\"status_changed_at\":\"2025-10-21T05:49:41+00:00\",\"sla_deadline\":\"2025-10-21T06:09:41+00:00\",\"items\":[{\"id\":223,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T05:49:41+00:00\"}', '2025-10-21 02:49:41'),
(343, 217, 1, 'order.created', 'pending', '{\"order\":{\"id\":217,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"dcdc, 54\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T05:50:07+00:00\",\"updated_at\":\"2025-10-21T05:50:07+00:00\",\"status_changed_at\":\"2025-10-21T05:50:07+00:00\",\"sla_deadline\":\"2025-10-21T06:10:07+00:00\",\"items\":[{\"id\":224,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T05:50:07+00:00\"}', '2025-10-21 02:50:07'),
(344, 218, 1, 'order.created', 'pending', '{\"order\":{\"id\":218,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"Rua 7, 538\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T05:52:58+00:00\",\"updated_at\":\"2025-10-21T05:52:58+00:00\",\"status_changed_at\":\"2025-10-21T05:52:58+00:00\",\"sla_deadline\":\"2025-10-21T06:12:58+00:00\",\"items\":[{\"id\":225,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T05:52:58+00:00\"}', '2025-10-21 02:52:58'),
(345, 219, 1, 'order.created', 'pending', '{\"order\":{\"id\":219,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"herick\",\"customer_phone\":\"51920017687\",\"customer_address\":\"Rua 7, 538\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":31,\"delivery_fee\":6,\"discount\":0,\"total\":37,\"created_at\":\"2025-10-21T13:34:11+00:00\",\"updated_at\":\"2025-10-21T13:34:11+00:00\",\"status_changed_at\":\"2025-10-21T13:34:11+00:00\",\"sla_deadline\":\"2025-10-21T13:54:11+00:00\",\"items\":[{\"id\":226,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":31,\"line_total\":31}]},\"created_at\":\"2025-10-21T13:34:11+00:00\"}', '2025-10-21 10:34:11'),
(346, 220, 1, 'order.created', 'pending', '{\"order\":{\"id\":220,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Victor Gabriel Duarte\",\"customer_phone\":\"51993032200\",\"customer_address\":\"DKASDKAS, 123\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":16,\"delivery_fee\":6,\"discount\":0,\"total\":22,\"created_at\":\"2025-10-21T22:55:20+00:00\",\"updated_at\":\"2025-10-21T22:55:20+00:00\",\"status_changed_at\":\"2025-10-21T22:55:20+00:00\",\"sla_deadline\":\"2025-10-21T23:15:20+00:00\",\"items\":[{\"id\":227,\"product_id\":2,\"name\":\"Woll Smash\",\"qty\":1,\"quantity\":1,\"unit_price\":16,\"line_total\":16}]},\"created_at\":\"2025-10-21T22:55:20+00:00\"}', '2025-10-21 19:55:20'),
(347, 221, 1, 'order.created', 'pending', '{\"order\":{\"id\":221,\"company_id\":1,\"status\":\"pending\",\"customer_name\":\"Victor Gabriel Duarte\",\"customer_phone\":\"51993032200\",\"customer_address\":\"DKASDKAS, 123\\nParque Emboaba - Tramandai\",\"notes\":\"Pagamento: Pix — Mandar comprovante após o pagamento\",\"subtotal\":26,\"delivery_fee\":6,\"discount\":0,\"total\":32,\"created_at\":\"2025-10-22T19:32:01+00:00\",\"updated_at\":\"2025-10-22T19:32:01+00:00\",\"status_changed_at\":\"2025-10-22T19:32:01+00:00\",\"sla_deadline\":\"2025-10-22T19:52:01+00:00\",\"items\":[{\"id\":228,\"product_id\":4,\"name\":\"Woll Smash + Batata Frita + Refri\",\"qty\":1,\"quantity\":1,\"unit_price\":26,\"line_total\":26}]},\"created_at\":\"2025-10-22T19:32:01+00:00\"}', '2025-10-22 16:32:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `combo_data` text COLLATE utf8mb4_general_ci COMMENT 'Dados de combo selecionado (JSON)',
  `customization_data` text COLLATE utf8mb4_general_ci COMMENT 'Dados de personalização (JSON)',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'Observações do item'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `line_total`, `combo_data`, `customization_data`, `notes`) VALUES
(128, 126, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(129, 126, 2, 1, 28.40, 28.40, NULL, NULL, NULL),
(130, 127, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(131, 128, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(132, 129, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(133, 130, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(134, 131, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(135, 132, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(136, 133, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(137, 134, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(138, 135, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(139, 136, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(140, 137, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(141, 138, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(145, 142, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(146, 143, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(147, 144, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(148, 145, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(149, 146, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(150, 147, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(151, 148, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(152, 149, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(153, 150, 2, 1, 28.40, 28.40, NULL, NULL, NULL),
(154, 151, 3, 1, 29.90, 29.90, NULL, NULL, NULL),
(155, 151, 3, 1, 32.40, 32.40, NULL, NULL, NULL),
(156, 152, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(157, 154, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(158, 155, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(159, 156, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(160, 157, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(161, 158, 2, 1, 20.00, 20.00, NULL, NULL, NULL),
(162, 159, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(163, 160, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(164, 161, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(165, 162, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(166, 163, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(167, 164, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(168, 165, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(169, 166, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(170, 167, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(171, 168, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(172, 169, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(173, 170, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(174, 170, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(175, 171, 2, 1, 25.90, 25.90, NULL, NULL, NULL),
(176, 172, 2, 1, 63.60, 63.60, NULL, NULL, NULL),
(177, 173, 2, 1, 54.50, 54.50, NULL, NULL, NULL),
(178, 174, 2, 1, 46.00, 46.00, NULL, NULL, NULL),
(179, 175, 2, 1, 46.00, 46.00, NULL, NULL, NULL),
(180, 176, 2, 1, 16.00, 16.00, NULL, NULL, NULL),
(181, 177, 2, 1, 46.00, 46.00, NULL, NULL, NULL),
(182, 178, 2, 1, 53.50, 53.50, NULL, NULL, NULL),
(183, 179, 2, 1, 23.80, 23.80, NULL, NULL, NULL),
(184, 180, 2, 1, 62.30, 62.30, NULL, NULL, NULL),
(185, 181, 2, 1, 24.80, 24.80, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":2,\"unit_price\":7.5,\"price\":7.5,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Maionese\",\"qty\":4,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":3},{\"name\":\"Cebola\",\"qty\":2,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0.299999999999999988897769753748434595763683319091796875,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Queijo Cheddar\",\"qty\":2,\"unit_price\":1,\"price\":1,\"default_qty\":1,\"delta_qty\":1}]}],\"total_delta\":8.800000000000000710542735760100185871124267578125,\"has_customization\":true}', NULL),
(186, 182, 2, 1, 46.00, 46.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":5,\"unit_price\":7.5,\"price\":30,\"default_qty\":1,\"delta_qty\":4},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":30,\"has_customization\":true}', NULL),
(187, 183, 2, 1, 23.80, 23.80, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":2,\"unit_price\":7.5,\"price\":7.5,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Maionese\",\"qty\":2,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Cebola\",\"qty\":2,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0.299999999999999988897769753748434595763683319091796875,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":7.79999999999999982236431605997495353221893310546875,\"has_customization\":true}', NULL),
(188, 184, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(189, 184, 2, 1, 17.30, 17.30, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":3,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":2},{\"name\":\"Cebola\",\"qty\":2,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0.299999999999999988897769753748434595763683319091796875,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Queijo Cheddar\",\"qty\":2,\"unit_price\":1,\"price\":1,\"default_qty\":1,\"delta_qty\":1}]}],\"total_delta\":1.3000000000000000444089209850062616169452667236328125,\"has_customization\":true}', NULL),
(190, 185, 2, 1, 32.90, 32.90, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":3,\"unit_price\":7.5,\"price\":15,\"default_qty\":1,\"delta_qty\":2},{\"name\":\"Maionese\",\"qty\":4,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":3},{\"name\":\"Cebola\",\"qty\":4,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0.899999999999999911182158029987476766109466552734375,\"default_qty\":1,\"delta_qty\":3},{\"name\":\"Queijo Cheddar\",\"qty\":2,\"unit_price\":1,\"price\":1,\"default_qty\":1,\"delta_qty\":1}]}],\"total_delta\":16.89999999999999857891452847979962825775146484375,\"has_customization\":true}', NULL),
(191, 186, 2, 1, 24.80, 24.80, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":2,\"unit_price\":7.5,\"price\":7.5,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Maionese\",\"qty\":2,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Cebola\",\"qty\":2,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0.299999999999999988897769753748434595763683319091796875,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Queijo Cheddar\",\"qty\":2,\"unit_price\":1,\"price\":1,\"default_qty\":1,\"delta_qty\":1}]}],\"total_delta\":8.800000000000000710542735760100185871124267578125,\"has_customization\":true}', NULL),
(192, 187, 2, 1, 19.90, 19.90, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":2,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":1},{\"name\":\"Cebola\",\"qty\":4,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0.899999999999999911182158029987476766109466552734375,\"default_qty\":1,\"delta_qty\":3},{\"name\":\"Queijo Cheddar\",\"qty\":4,\"unit_price\":1,\"price\":3,\"default_qty\":1,\"delta_qty\":3}]}],\"total_delta\":3.899999999999999911182158029987476766109466552734375,\"has_customization\":true}', NULL),
(193, 188, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(194, 188, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(195, 189, 3, 1, 29.90, 29.90, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":28.89999999999999857891452847979962825775146484375,\"sum_delta\":0,\"total\":28.89999999999999857891452847979962825775146484375}}', '{\"groups\":[{\"name\":\"Queijos\",\"type\":\"single\",\"items\":[{\"name\":\"Queijo Cheddar\",\"price\":1}]}],\"total_delta\":1,\"has_customization\":true}', NULL),
(196, 190, 3, 1, 29.90, 29.90, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":28.89999999999999857891452847979962825775146484375,\"sum_delta\":0,\"total\":28.89999999999999857891452847979962825775146484375}}', '{\"groups\":[{\"name\":\"Queijos\",\"type\":\"single\",\"items\":[{\"name\":\"Queijo Cheddar\",\"price\":1}]}],\"total_delta\":1,\"has_customization\":true}', NULL),
(197, 191, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(198, 192, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(199, 193, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.299999999999999988897769753748434595763683319091796875,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(218, 211, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(219, 212, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(220, 213, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(221, 214, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(222, 215, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(223, 216, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(224, 217, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(225, 218, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(226, 219, 2, 1, 31.00, 31.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":3,\"unit_price\":7.5,\"price\":15,\"default_qty\":1,\"delta_qty\":2},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":15,\"has_customization\":true}', NULL),
(227, 220, 2, 1, 16.00, 16.00, '{\"groups\":[],\"selected_items\":[],\"pricing_map\":[],\"pricing\":{\"base\":16,\"sum_delta\":0,\"total\":16}}', '{\"groups\":[{\"name\":\"Personalize os ingredientes\",\"type\":\"qty\",\"items\":[{\"name\":\"P\\u00e3o Brioche\",\"qty\":1,\"unit_price\":1.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Bled Costela 90 (carne)\",\"qty\":1,\"unit_price\":7.5,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Maionese\",\"qty\":1,\"unit_price\":0,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Cebola\",\"qty\":1,\"unit_price\":0.3,\"price\":0,\"default_qty\":1,\"delta_qty\":0},{\"name\":\"Queijo Cheddar\",\"qty\":1,\"unit_price\":1,\"price\":0,\"default_qty\":1,\"delta_qty\":0}]}],\"total_delta\":0,\"has_customization\":true}', NULL),
(228, 221, 4, 1, 26.00, 26.00, '{\"groups\":[{\"id\":15,\"name\":\"Burger\",\"items\":[{\"simple_id\":2,\"combo_item_id\":25,\"name\":\"Woll Smash\",\"delta\":0,\"image\":\"uploads\\/p_1760073497_2617.webp\",\"customizable\":true,\"base_price\":16,\"is_default\":true,\"default\":true}]}],\"selected_items\":[{\"simple_id\":2,\"combo_item_id\":25,\"name\":\"Woll Smash\",\"delta\":0,\"image\":\"uploads\\/p_1760073497_2617.webp\",\"customizable\":true,\"base_price\":16,\"is_default\":true,\"default\":true}],\"pricing_map\":{\"15\":2},\"pricing\":{\"base\":26,\"sum_delta\":0,\"total\":26}}', '{\"groups\":[],\"total_delta\":0,\"has_customization\":false}', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `instructions` text COLLATE utf8mb4_general_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'others',
  `pix_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `icon` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

--
-- Despejando dados para a tabela `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `company_id`, `name`, `instructions`, `sort_order`, `active`, `created_at`, `updated_at`, `type`, `pix_key`, `meta`, `icon`) VALUES
(1, 1, 'Elo', NULL, 20, 1, '2025-10-08 23:23:27', '2025-10-10 02:43:03', 'credit', NULL, '{\"icon\":\"\\/assets\\/card-brands\\/elo.svg\"}', '/assets/card-brands/elo.svg'),
(2, 1, 'Elo', NULL, 21, 1, '2025-10-08 23:26:25', '2025-10-10 02:43:03', 'debit', NULL, '{\"icon\":\"\\/assets\\/card-brands\\/elo.svg\"}', '/assets/card-brands/elo.svg'),
(3, 1, 'Hipercard', NULL, 22, 1, '2025-10-09 01:57:05', '2025-10-10 02:43:03', 'credit', NULL, '{\"icon\":\"\\/assets\\/card-brands\\/hipercard.svg\"}', '/assets/card-brands/hipercard.svg'),
(5, 1, 'Pix', 'Mandar comprovante após o pagamento', 1, 1, '2025-10-06 02:08:19', '2025-10-10 02:43:03', 'pix', 'herick260223@gmail.com', '{\"px_key\":\"herick260223@gmail.com\",\"px_provider\":\"RecargaPay\",\"px_holder_name\":\"Herick Rafael\",\"px_key_type\":\"email\"}', NULL),
(21, 1, 'Diners Club', NULL, 4, 1, '2025-10-08 01:03:33', '2025-10-10 02:43:03', 'others', NULL, '{\"icon\":\"\\/assets\\/card-brands\\/diners.svg\"}', '/assets/card-brands/diners.svg'),
(55, 1, 'Mastercard', NULL, 18, 1, '2025-10-08 22:41:23', '2025-10-10 02:22:45', 'credit', NULL, '{\"icon\":\"\\/assets\\/card-brands\\/mastercard.svg\"}', '/assets/card-brands/mastercard.svg'),
(58, 1, 'Dinheiro', 'Pagamento na entrega em dinheiro', 23, 1, '2025-10-16 02:52:03', '2025-10-16 02:52:03', 'cash', NULL, '{\"icon\":\"\\/assets\\/card-brands\\/cash.svg\"}', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `promo_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('simple','combo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'simple',
  `price_mode` enum('fixed','sum') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'fixed',
  `allow_customize` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `products`
--

INSERT INTO `products` (`id`, `company_id`, `category_id`, `name`, `description`, `price`, `promo_price`, `sku`, `image`, `type`, `price_mode`, `allow_customize`, `active`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 1, 2, 'Woll Smash', 'Pão brioche, hambúrguer 150g, alface, tomate, cebola e picles.', 16.00, NULL, '1', 'uploads/p_1760073497_2617.webp', 'simple', 'fixed', 1, 1, 1, '2025-10-05 18:04:43', '2025-10-16 22:23:46', NULL),
(3, 1, 2, 'Woll Smash Triplo', 'Pão brioche, hambúrguer 150g, queijo cheddar, alface e molho especial.', 28.90, NULL, '2', 'uploads/p_1761026098_7071.jpg', 'simple', 'fixed', 1, 1, 2, '2025-10-05 18:04:43', '2025-10-21 03:00:27', NULL),
(4, 1, 2, 'Woll Smash + Batata Frita + Refri', 'Pão brioche, Burger 100% costela, queijo cheddar, cebola caramelizada, & maionese da woll.', 28.00, 26.00, '3', 'uploads/p_1761105732_3021.jpg', 'combo', 'fixed', 0, 1, 3, '2025-10-05 18:04:43', '2025-10-22 01:10:35', NULL),
(5, 1, 2, 'Double Cheeseburger', 'Pão brioche, dois hambúrgueres 150g, dois queijos cheddar, alface.', 38.90, NULL, NULL, NULL, 'simple', 'fixed', 1, 1, 4, '2025-10-05 18:04:43', NULL, NULL),
(6, 1, 2, 'Veggie Burger', 'Pão brioche, hambúrguer veggie, alface, tomate e maionese.', 26.90, NULL, NULL, NULL, 'simple', 'fixed', 1, 1, 5, '2025-10-05 18:04:43', NULL, NULL),
(7, 1, 1, 'Fanta - Laranja', '', 3.50, NULL, '4', 'uploads/p_1761106108_7393.jpg', 'simple', 'fixed', 0, 1, 0, '2025-10-22 01:08:28', NULL, NULL),
(8, 1, 1, 'Sprite', '', 3.50, NULL, '5', 'uploads/p_1761106137_4332.jpg', 'simple', 'fixed', 0, 1, 0, '2025-10-22 01:08:57', '2025-10-22 01:09:06', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_combinations`
--

CREATE TABLE `product_combinations` (
  `id` int NOT NULL,
  `company_id` int NOT NULL,
  `product_a_id` int NOT NULL,
  `product_b_id` int NOT NULL,
  `combination_count` int DEFAULT '1',
  `last_ordered_together` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_custom_groups`
--

CREATE TABLE `product_custom_groups` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('single','extra','addon','component') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'extra',
  `min_qty` int NOT NULL DEFAULT '0',
  `max_qty` int NOT NULL DEFAULT '99',
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_custom_groups`
--

INSERT INTO `product_custom_groups` (`id`, `product_id`, `name`, `type`, `min_qty`, `max_qty`, `sort_order`) VALUES
(10, 5, 'Queijos', 'single', 0, 1, 0),
(11, 5, 'Molhos', 'extra', 0, 5, 1),
(12, 5, 'Extras', 'addon', 0, 5, 2),
(13, 6, 'Queijos', 'single', 0, 1, 0),
(14, 6, 'Molhos', 'extra', 0, 5, 1),
(15, 6, 'Extras', 'addon', 0, 5, 2),
(28, 2, 'Personalize os ingredientes', 'extra', 0, 99, 0),
(51, 3, 'Queijos', 'single', 0, 1, 0),
(52, 3, 'Extras', 'extra', 0, 99, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_custom_items`
--

CREATE TABLE `product_custom_items` (
  `id` int NOT NULL,
  `group_id` int NOT NULL,
  `ingredient_id` int DEFAULT NULL,
  `label` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `delta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `default_qty` int NOT NULL DEFAULT '1',
  `min_qty` int NOT NULL DEFAULT '0',
  `max_qty` int NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_custom_items`
--

INSERT INTO `product_custom_items` (`id`, `group_id`, `ingredient_id`, `label`, `delta`, `is_default`, `default_qty`, `min_qty`, `max_qty`, `sort_order`) VALUES
(14, 10, 3, 'Queijo Cheddar', 0.00, 1, 1, 0, 1, 0),
(15, 11, 9, 'Ketchup', 0.00, 1, 0, 0, 5, 0),
(16, 11, 11, 'Maionese', 0.00, 0, 0, 0, 5, 1),
(17, 12, 2, 'Extra Hambúrguer', 10.00, 0, 0, 0, 2, 0),
(18, 13, 12, 'Hambúrguer Veggie', 0.00, 1, 1, 0, 1, 0),
(19, 14, 11, 'Maionese', 0.00, 1, 0, 0, 5, 0),
(20, 15, 3, 'Queijo Cheddar', 0.00, 0, 0, 0, 1, 0),
(40, 28, 1, 'Pão Brioche', 0.00, 1, 1, 1, 1, 0),
(41, 28, 2, 'Bled Costela 90 (carne)', 0.00, 1, 1, 1, 10, 1),
(42, 28, 11, 'Maionese', 0.00, 1, 1, 0, 10, 2),
(43, 28, 7, 'Cebola', 0.00, 1, 1, 0, 10, 3),
(44, 28, 3, 'Queijo Cheddar', 0.00, 1, 1, 0, 10, 4),
(75, 51, 3, 'Queijo Cheddar', 0.00, 1, 1, 0, 1, 0),
(76, 51, 9, 'Queijo Mussarela', 0.00, 0, 0, 0, 1, 1),
(77, 52, 4, 'Bacon', 0.00, 0, 0, 0, 10, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_popularity`
--

CREATE TABLE `product_popularity` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `company_id` int NOT NULL,
  `total_orders` int DEFAULT '0',
  `total_quantity` int DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `company_id` int DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('root','owner','staff') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'owner',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `password_hash`, `role`, `active`, `created_at`) VALUES
(1, NULL, 'Super Admin', 'herick260223@gmail.com', '$2y$10$0tKQUnS3p4YFFoXEIew7b.E27E9P2HRzk2nTW4/gyBcBIu630BfRe', 'root', 1, '2025-09-11 01:49:38'),
(2, 1, 'Dono Wollburger', 'owner@wollburger.local', '$2y$10$158FQ5TJcjl7T.pO.6dYKuNKY9E6dWANNYqmnSKIdydshQZgpkNqe', 'owner', 1, '2025-09-11 01:49:38'),
(3, 1, 'Atendente 1', 'staff1@wollburger.local', '$2y$10$2LxL1b0Jr3m6y8oE0EJk2uYw7s5qf7o8x7mY4O1mF0b4oE2Y5eTZu', 'staff', 1, '2025-09-11 01:49:38'),
(10, 4, 'Admin Teste', 'admin@teste.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 1, '2025-10-15 02:01:35');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Índices de tabela `combo_groups`
--
ALTER TABLE `combo_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Índices de tabela `combo_group_items`
--
ALTER TABLE `combo_group_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `simple_product_id` (`simple_product_id`);

--
-- Índices de tabela `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices de tabela `company_favorites_settings`
--
ALTER TABLE `company_favorites_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_favorites` (`company_id`);

--
-- Índices de tabela `company_hours`
--
ALTER TABLE `company_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_day` (`company_id`,`weekday`);

--
-- Índices de tabela `company_map_settings`
--
ALTER TABLE `company_map_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_map_settings_company_unique` (`company_id`);

--
-- Índices de tabela `company_meta`
--
ALTER TABLE `company_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_meta_unique` (`company_id`,`meta_key`),
  ADD KEY `idx_company` (`company_id`);

--
-- Índices de tabela `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_whatsapp` (`company_id`,`whatsapp_e164`);

--
-- Índices de tabela `customer_order_history`
--
ALTER TABLE `customer_order_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_product_history` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Índices de tabela `customer_product_views`
--
ALTER TABLE `customer_product_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_company` (`customer_id`,`company_id`),
  ADD KEY `idx_product_views` (`product_id`,`view_count`),
  ADD KEY `idx_last_viewed` (`last_viewed_at`),
  ADD KEY `company_id` (`company_id`);

--
-- Índices de tabela `customer_recommendations`
--
ALTER TABLE `customer_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_product_recommendation` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Índices de tabela `delivery_cities`
--
ALTER TABLE `delivery_cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_cities_company_name_unique` (`company_id`,`name`),
  ADD KEY `delivery_cities_company_fk` (`company_id`);

--
-- Índices de tabela `delivery_zones`
--
ALTER TABLE `delivery_zones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_zones_company_city_neighborhood_unique` (`company_id`,`city_id`,`neighborhood`),
  ADD KEY `delivery_zones_city_fk` (`city_id`),
  ADD KEY `delivery_zones_company_fk` (`company_id`);

--
-- Índices de tabela `delivery_zones_by_distance`
--
ALTER TABLE `delivery_zones_by_distance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_zones_distance_company_range_unique` (`company_id`,`min_distance`,`max_distance`),
  ADD KEY `delivery_zones_distance_company_fk` (`company_id`);

--
-- Índices de tabela `evolution_blocks`
--
ALTER TABLE `evolution_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_instance` (`company_id`,`instance_name`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `evolution_instances`
--
ALTER TABLE `evolution_instances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evolution_company_idx` (`company_id`),
  ADD KEY `idx_company_main` (`company_id`,`is_main`);

--
-- Índices de tabela `evolution_instance_settings`
--
ALTER TABLE `evolution_instance_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`instance_id`,`setting_name`);

--
-- Índices de tabela `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ingredient_company_idx` (`company_id`);

--
-- Índices de tabela `instance_configs`
--
ALTER TABLE `instance_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_config` (`company_id`,`instance_name`,`config_key`),
  ADD KEY `idx_company_instance` (`company_id`,`instance_name`),
  ADD KEY `idx_config_key` (`config_key`);

--
-- Índices de tabela `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_status` (`company_id`,`status`);

--
-- Índices de tabela `order_events`
--
ALTER TABLE `order_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_events_order_idx` (`order_id`),
  ADD KEY `order_events_company_idx` (`company_id`),
  ADD KEY `order_events_created_idx` (`created_at`);

--
-- Índices de tabela `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Índices de tabela `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_methods_company_fk` (`company_id`);

--
-- Índices de tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Índices de tabela `product_combinations`
--
ALTER TABLE `product_combinations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_combination` (`company_id`,`product_a_id`,`product_b_id`),
  ADD KEY `idx_combinations` (`company_id`,`combination_count`),
  ADD KEY `product_a_id` (`product_a_id`),
  ADD KEY `product_b_id` (`product_b_id`);

--
-- Índices de tabela `product_custom_groups`
--
ALTER TABLE `product_custom_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pcg_product_idx` (`product_id`);

--
-- Índices de tabela `product_custom_items`
--
ALTER TABLE `product_custom_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pci_group_idx` (`group_id`),
  ADD KEY `pci_ingredient_idx` (`ingredient_id`);

--
-- Índices de tabela `product_popularity`
--
ALTER TABLE `product_popularity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_company` (`product_id`,`company_id`),
  ADD KEY `idx_popularity` (`company_id`,`total_orders`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `company_id` (`company_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `combo_groups`
--
ALTER TABLE `combo_groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `combo_group_items`
--
ALTER TABLE `combo_group_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `company_favorites_settings`
--
ALTER TABLE `company_favorites_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `company_hours`
--
ALTER TABLE `company_hours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `company_map_settings`
--
ALTER TABLE `company_map_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `company_meta`
--
ALTER TABLE `company_meta`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `customer_order_history`
--
ALTER TABLE `customer_order_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `customer_product_views`
--
ALTER TABLE `customer_product_views`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `customer_recommendations`
--
ALTER TABLE `customer_recommendations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `delivery_cities`
--
ALTER TABLE `delivery_cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `delivery_zones`
--
ALTER TABLE `delivery_zones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `delivery_zones_by_distance`
--
ALTER TABLE `delivery_zones_by_distance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `evolution_blocks`
--
ALTER TABLE `evolution_blocks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `evolution_instances`
--
ALTER TABLE `evolution_instances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de tabela `evolution_instance_settings`
--
ALTER TABLE `evolution_instance_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `instance_configs`
--
ALTER TABLE `instance_configs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT de tabela `order_events`
--
ALTER TABLE `order_events`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT de tabela `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `product_combinations`
--
ALTER TABLE `product_combinations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `product_custom_groups`
--
ALTER TABLE `product_custom_groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de tabela `product_custom_items`
--
ALTER TABLE `product_custom_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `product_popularity`
--
ALTER TABLE `product_popularity`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `combo_groups`
--
ALTER TABLE `combo_groups`
  ADD CONSTRAINT `combo_groups_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `combo_group_items`
--
ALTER TABLE `combo_group_items`
  ADD CONSTRAINT `combo_group_items_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `combo_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `combo_group_items_ibfk_2` FOREIGN KEY (`simple_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `company_favorites_settings`
--
ALTER TABLE `company_favorites_settings`
  ADD CONSTRAINT `company_favorites_settings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `company_hours`
--
ALTER TABLE `company_hours`
  ADD CONSTRAINT `company_hours_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `company_map_settings`
--
ALTER TABLE `company_map_settings`
  ADD CONSTRAINT `company_map_settings_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `customer_order_history`
--
ALTER TABLE `customer_order_history`
  ADD CONSTRAINT `customer_order_history_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_order_history_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_order_history_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `customer_product_views`
--
ALTER TABLE `customer_product_views`
  ADD CONSTRAINT `customer_product_views_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_product_views_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `customer_recommendations`
--
ALTER TABLE `customer_recommendations`
  ADD CONSTRAINT `customer_recommendations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_recommendations_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_recommendations_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `delivery_cities`
--
ALTER TABLE `delivery_cities`
  ADD CONSTRAINT `delivery_cities_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `delivery_zones`
--
ALTER TABLE `delivery_zones`
  ADD CONSTRAINT `delivery_zones_city_fk` FOREIGN KEY (`city_id`) REFERENCES `delivery_cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_zones_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `delivery_zones_by_distance`
--
ALTER TABLE `delivery_zones_by_distance`
  ADD CONSTRAINT `delivery_zones_distance_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `evolution_instance_settings`
--
ALTER TABLE `evolution_instance_settings`
  ADD CONSTRAINT `evolution_instance_settings_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `evolution_instances` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ingredients`
--
ALTER TABLE `ingredients`
  ADD CONSTRAINT `fk_ingredients_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `payment_methods_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `product_combinations`
--
ALTER TABLE `product_combinations`
  ADD CONSTRAINT `product_combinations_ibfk_1` FOREIGN KEY (`product_a_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_combinations_ibfk_2` FOREIGN KEY (`product_b_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_combinations_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `product_custom_groups`
--
ALTER TABLE `product_custom_groups`
  ADD CONSTRAINT `fk_pcg_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `product_custom_items`
--
ALTER TABLE `product_custom_items`
  ADD CONSTRAINT `fk_pci_group` FOREIGN KEY (`group_id`) REFERENCES `product_custom_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pci_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `product_popularity`
--
ALTER TABLE `product_popularity`
  ADD CONSTRAINT `product_popularity_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_popularity_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ---------------------------------------------------------------------------
-- Super admins (painel global MultiMenu — separado de users de loja)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `super_admins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


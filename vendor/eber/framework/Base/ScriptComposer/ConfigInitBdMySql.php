<?php

require 'vendor/autoload.php';

use Base\Builder\Builder;
use Base\ErrorHandler;

function configInitBd()
{

  $builder = new Builder;

  // --- Database Configuration ---
  $dbName = BD; // Nombre de tu base de datos desde tus constantes

  try {
    echo '<pre>';

    // 1. Seleccionar la base de datos para las operaciones subsiguientes
    $sqlUseDb = "USE `" . $dbName . "`";
    $builder->query_foreign($sqlUseDb);
    echo "Usando la base de datos '$dbName'.\n";

    $sqlSetNames = "SET NAMES 'utf8mb4'";
    $builder->query_foreign($sqlSetNames);
    echo "SET NAMES 'utf8mb4' ejecutado.\n\n";

    // 2. Definiciones de Tablas (SQL para CREATE TABLE)
    // --- Script optimizado con mejoras de integridad y rendimiento ---
    $tablesSQL = [
      "RateLimits" => "CREATE TABLE IF NOT EXISTS `RateLimits` (
        `rate_limit_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `ip` VARCHAR(45) NOT NULL,
        `action_key` VARCHAR(255) NOT NULL,
        `attempts` INT UNSIGNED DEFAULT 0,
        `blocked_until` DATETIME NULL DEFAULT NULL,
        `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`rate_limit_id`),
        UNIQUE KEY `uq_ip_action` (`ip`, `action_key`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "SiteSettings" => "CREATE TABLE IF NOT EXISTS `SiteSettings` (
        `setting_key` VARCHAR(255) NOT NULL,
        `setting_value` TEXT,
        `data_type` VARCHAR(50) DEFAULT 'string',
        `setting_group` VARCHAR(100) DEFAULT 'General',
        `description` TEXT,
        `created_at_site_setting` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at_site_setting` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`setting_key`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Users" => "CREATE TABLE IF NOT EXISTS `Users` (
        `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `index_user` VARCHAR(100) NOT NULL,
        `username` VARCHAR(100) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255) NULL,
        `bio` TEXT NULL COMMENT 'Descripción o biografía del usuario',
        `avatar_media_id` BIGINT UNSIGNED NULL COMMENT 'FK to Media for quick avatar access',
        `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_login` TIMESTAMP NULL,
        `user_status` ENUM('active', 'inactive', 'pending_verification', 'banned') DEFAULT 'pending_verification',
        `email_verification_token` VARCHAR(100) NULL,
        `email_verification_token_expires_at` TIMESTAMP NULL,
        `password_reset_token` VARCHAR(100) NULL,
        `password_reset_token_expires_at` TIMESTAMP NULL,
        `updated_at_user` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `uq_username` (`username`),
        UNIQUE KEY `uq_email` (`email`),
        UNIQUE KEY `uq_index_user` (`index_user`),
        INDEX `idx_avatar_media_id` (`avatar_media_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Roles" => "CREATE TABLE IF NOT EXISTS `Roles` (
        `role_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `role_name` VARCHAR(100) NOT NULL,
        `role_description` TEXT NULL,
        `created_at_role` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`role_id`),
        UNIQUE KEY `uq_role_name` (`role_name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "UserRoles" => "CREATE TABLE IF NOT EXISTS `UserRoles` (
        `user_role_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT UNSIGNED NOT NULL,
        `id_role` INT UNSIGNED NOT NULL,
        `assigned_at_user_role` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_role_id`),
        UNIQUE KEY `uq_user_role` (`user_id`, `id_role`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Media" => "CREATE TABLE IF NOT EXISTS `Media` (
        `media_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `uploader_user_id` BIGINT UNSIGNED NULL COMMENT 'User who uploaded the file. Can be NULL if user is deleted.',
        `referenced_post_id` BIGINT NOT NULL,
        `referenced_table` VARCHAR(50) NOT NULL,
        `original_filename` VARCHAR(255) NOT NULL,
        `server_file_path` VARCHAR(512) NOT NULL,
        `file_url` VARCHAR(512) NOT NULL,
        `mime_type` VARCHAR(100) NOT NULL,
        `file_size_bytes` BIGINT NOT NULL,
        `media_alt_text` VARCHAR(255) NULL,
        `uploaded_at_media` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`media_id`),
        INDEX `idx_uploader_user_id_media` (`uploader_user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Mediables" => "CREATE TABLE IF NOT EXISTS `Mediables` (
        `ref_media_id` BIGINT UNSIGNED NOT NULL,
        `mediable_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID of the parent model (e.g., user_id, post_id)',
        `mediable_type` VARCHAR(100) NOT NULL COMMENT 'Name of the parent table (e.g., Users, BlogPosts)',
        `tag` VARCHAR(100) NOT NULL COMMENT 'Usage tag for the media (e.g., avatar, featured_image, gallery)',
        PRIMARY KEY (`ref_media_id`, `mediable_id`, `mediable_type`, `tag`),
        INDEX `idx_mediable` (`mediable_id`, `mediable_type`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Pages" => "CREATE TABLE IF NOT EXISTS `Pages` (
        `page_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `author_user_id` BIGINT UNSIGNED NULL,
        `author_display_name` VARCHAR(255) NULL COMMENT 'Snapshot of author name for attribution preservation',
        `page_title` VARCHAR(255) NOT NULL,
        `page_slug` VARCHAR(255) NOT NULL,
        `page_content` LONGTEXT,
        `content_type` ENUM('html', 'markdown', 'json_builder') DEFAULT 'html',
        `page_template` VARCHAR(100) NULL,
        `page_status` ENUM('published', 'draft', 'private', 'trash') DEFAULT 'draft',
        `page_visibility` ENUM('public', 'private', 'password_protected') DEFAULT 'public',
        `page_password` VARCHAR(255) NULL,
        `meta_title_seo` VARCHAR(255) NULL,
        `meta_description_seo` TEXT NULL,
        `allow_comments` BOOLEAN DEFAULT FALSE,
        `page_order` INT DEFAULT 0,
        `created_at_page` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `published_at_page` TIMESTAMP NULL,
        `updated_at_page` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`page_id`),
        UNIQUE KEY `uq_page_slug` (`page_slug`),
        INDEX `idx_author_user_id_page` (`author_user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "BlogPosts" => "CREATE TABLE IF NOT EXISTS `BlogPosts` (
        `post_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `author_user_id` BIGINT UNSIGNED NULL,
        `index_post` VARCHAR(100) NOT NULL,
        `author_display_name` VARCHAR(255) NULL COMMENT 'Snapshot of author name for attribution preservation',
        `post_title` VARCHAR(255) NOT NULL,
        `post_slug` VARCHAR(255) NOT NULL,
        `post_type` ENUM('post', 'survey', 'publicity', 'resource', 'tutorial', 'opinion') DEFAULT 'post',
        `post_summary` TEXT NULL,
        `post_content` LONGTEXT,
        `content_type` ENUM('html', 'markdown', 'json_builder') DEFAULT 'html',
        `post_status` ENUM('published', 'draft', 'scheduled', 'private', 'trash') DEFAULT 'draft',
        `post_visibility` ENUM('public', 'private', 'password_protected') DEFAULT 'public',
        `post_password` VARCHAR(255) NULL,
        `meta_title_seo` VARCHAR(255) NULL,
        `meta_description_seo` TEXT NULL,
        `allow_comments` BOOLEAN DEFAULT TRUE,
        `created_at_blog_post` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `published_at_blog_post` TIMESTAMP NULL,
        `updated_at_blog_post` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`post_id`),
        UNIQUE KEY `uq_post_slug` (`post_slug`),
        INDEX `idx_author_user_id_post` (`author_user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Categories" => "CREATE TABLE IF NOT EXISTS `Categories` (
        `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `category_name` VARCHAR(150) NOT NULL,
        `category_slug` VARCHAR(150) NOT NULL,
        `category_description` TEXT NULL,
        `parent_category_id` INT UNSIGNED NULL,
        `created_at_category` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`category_id`),
        UNIQUE KEY `uq_category_slug` (`category_slug`),
        INDEX `idx_parent_category_id` (`parent_category_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Tags" => "CREATE TABLE IF NOT EXISTS `Tags` (
        `tag_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `tag_name` VARCHAR(100) NOT NULL,
        `tag_slug` VARCHAR(100) NOT NULL,
        `created_at_tag` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`tag_id`),
        UNIQUE KEY `uq_tag_slug` (`tag_slug`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "BlogPostCategories" => "CREATE TABLE IF NOT EXISTS `BlogPostCategories` (
        `post_id` BIGINT UNSIGNED NOT NULL,
        `category_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`post_id`, `category_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "BlogPostTags" => "CREATE TABLE IF NOT EXISTS `BlogPostTags` (
        `post_id` BIGINT UNSIGNED NOT NULL,
        `tag_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`post_id`, `tag_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Comments" => "CREATE TABLE IF NOT EXISTS `Comments` (
        `comment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `index_comment` VARCHAR(100) NOT NULL,
        `associated_content_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID of the post, page, product, etc.',
        `associated_content_type` VARCHAR(100) NOT NULL,
        `commenter_user_id` BIGINT UNSIGNED NULL,
        `guest_commenter_name` VARCHAR(150) NULL,
        `guest_commenter_email` VARCHAR(255) NULL,
        `comment_content` TEXT NOT NULL,
        `comment_status` ENUM('approved', 'pending', 'spam', 'trash') DEFAULT 'pending',
        `parent_comment_id` BIGINT UNSIGNED NULL,
        `commented_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `commenter_ip` VARCHAR(45) NULL,
        `user_agent` TEXT NULL COMMENT 'User agent of the commenter for moderation purposes',
        PRIMARY KEY (`comment_id`),
        INDEX `idx_associated_content` (`associated_content_id`, `associated_content_type`),
        INDEX `idx_commenter_user_id_comment` (`commenter_user_id`),
        INDEX `idx_parent_comment_id` (`parent_comment_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      // --- CORREGIDO: Se eliminó la coma final que causaba el error de sintaxis ---
      "Survey" => "CREATE TABLE IF NOT EXISTS `Survey` (
        `id_survey` BIGINT NOT NULL AUTO_INCREMENT,
        `id_blogpost` BIGINT unsigned NOT NULL,
        `question` TEXT NOT NULL,
        `option_label` JSON NOT NULL,
        `option_number` JSON NOT NULL,
        PRIMARY KEY (`id_survey`),
        UNIQUE KEY `rel_blogpost` (`id_blogpost`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      // --- AÑADIDO Y MEJORADO: Tabla para almacenar las respuestas de las encuestas ---
      "SurveyAnswers" => "CREATE TABLE IF NOT EXISTS `SurveyAnswers` (
        `survey_answer_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `survey_id` BIGINT NOT NULL,
        `survey_user_id` BIGINT UNSIGNED NULL COMMENT 'The user who answered, if logged in',
        `guest_identifier` VARCHAR(255) NULL COMMENT 'Identifier for anonymous users (e.g., IP hash)',
        `ip_guest` VARCHAR(255) NULL,
        `selected_option_index` INT UNSIGNED NOT NULL COMMENT 'Index of the selected option from the JSON array',
        `survey_answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`survey_answer_id`),
        INDEX `idx_survey_id_answer` (`survey_id`),
        INDEX `idx_user_id_answer` (`survey_user_id`),
        UNIQUE KEY `uq_user_survey_answer` (`survey_id`, `survey_user_id`),
        UNIQUE KEY `uq_guest_survey_answer` (`survey_id`, `guest_identifier`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Products" => "CREATE TABLE IF NOT EXISTS `Products` (
        `product_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `index_product` VARCHAR(100) NOT NULL,
        `featured_media_id` BIGINT UNSIGNED NULL COMMENT 'FK to Media for quick featured image access',
        `product_name` VARCHAR(255) NOT NULL,
        `product_slug` VARCHAR(255) NOT NULL,
        `short_description` TEXT NULL,
        `long_description` LONGTEXT NULL,
        `sku` VARCHAR(100) NULL,
        `regular_price` DECIMAL(10, 2) NOT NULL,
        `sale_price` DECIMAL(10, 2) NULL,
        `sale_start_date` TIMESTAMP NULL,
        `sale_end_date` TIMESTAMP NULL,
        `stock_quantity` INT DEFAULT 0,
        `manage_stock` BOOLEAN DEFAULT TRUE,
        `stock_status` ENUM('in_stock', 'out_of_stock', 'on_backorder') DEFAULT 'in_stock',
        `product_status` ENUM('published', 'draft', 'private', 'trash') DEFAULT 'draft',
        `allow_reviews` BOOLEAN DEFAULT TRUE,
        `average_rating` DECIMAL(3, 2) DEFAULT 0.00,
        `total_sales` INT DEFAULT 0,
        `created_at_product` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at_product` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`product_id`),
        UNIQUE KEY `uq_product_slug` (`product_slug`),
        UNIQUE KEY `uq_product_sku` (`sku`),
        INDEX `idx_featured_media_id` (`featured_media_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "ProductCategories" => "CREATE TABLE IF NOT EXISTS `ProductCategories` (
        `product_id` BIGINT UNSIGNED NOT NULL,
        `category_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`product_id`, `category_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "ProductTags" => "CREATE TABLE IF NOT EXISTS `ProductTags` (
        `product_id` BIGINT UNSIGNED NOT NULL,
        `tag_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`product_id`, `tag_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Orders" => "CREATE TABLE IF NOT EXISTS `Orders` (
        `order_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `customer_user_id` BIGINT UNSIGNED NULL,
        `guest_customer_email` VARCHAR(255) NULL,
        `order_number` VARCHAR(50) NOT NULL,
        `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `order_status` ENUM('pending_payment', 'processing', 'shipped', 'completed', 'cancelled', 'refunded', 'failed') DEFAULT 'pending_payment',
        `order_total` DECIMAL(12, 2) NOT NULL,
        `order_subtotal` DECIMAL(12, 2) NOT NULL,
        `total_tax` DECIMAL(10, 2) DEFAULT 0.00,
        `total_shipping` DECIMAL(10, 2) DEFAULT 0.00,
        `payment_method` VARCHAR(100) NULL,
        `payment_transaction_id` VARCHAR(255) NULL,
        `billing_address_json` JSON NULL,
        `shipping_address_json` JSON NULL,
        `customer_notes` TEXT NULL,
        `currency` VARCHAR(3) DEFAULT 'USD',
        `updated_at_order` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`order_id`),
        UNIQUE KEY `uq_order_number` (`order_number`),
        INDEX `idx_customer_user_id_order` (`customer_user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "OrderItems" => "CREATE TABLE IF NOT EXISTS `OrderItems` (
        `order_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_id` BIGINT UNSIGNED NOT NULL,
        `product_id` BIGINT UNSIGNED NOT NULL,
        `product_variation_id` BIGINT UNSIGNED NULL COMMENT 'For future product variations',
        `product_name_snapshot` VARCHAR(255) NOT NULL,
        `product_sku_snapshot` VARCHAR(100) NULL COMMENT 'Snapshot of SKU for historical integrity',
        `quantity` INT NOT NULL,
        `unit_price_snapshot` DECIMAL(10, 2) NOT NULL,
        `item_subtotal` DECIMAL(12, 2) NOT NULL,
        `item_metadata_json` JSON NULL,
        PRIMARY KEY (`order_item_id`),
        INDEX `idx_order_id_item` (`order_id`),
        INDEX `idx_product_id_item` (`product_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "NavigationMenus" => "CREATE TABLE IF NOT EXISTS `NavigationMenus` (
        `menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `menu_name` VARCHAR(150) NOT NULL,
        `theme_location` VARCHAR(100) NULL,
        `menu_description` TEXT NULL,
        `created_at_menu` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`menu_id`),
        UNIQUE KEY `uq_menu_name` (`menu_name`),
        UNIQUE KEY `uq_theme_location` (`theme_location`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "MenuItems" => "CREATE TABLE IF NOT EXISTS `MenuItems` (
        `menu_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `menu_id` INT UNSIGNED NOT NULL,
        `link_title` VARCHAR(200) NOT NULL,
        `link_type` ENUM('custom_url', 'page', 'blog_post', 'category', 'product', 'tag') DEFAULT 'custom_url',
        `linked_object_id` BIGINT UNSIGNED NULL COMMENT 'FK to Pages, BlogPosts, etc.',
        `custom_url` VARCHAR(512) NULL,
        `parent_menu_item_id` BIGINT UNSIGNED NULL,
        `item_order` INT DEFAULT 0,
        `link_target` ENUM('_self', '_blank') DEFAULT '_self',
        `additional_css_classes` VARCHAR(255) NULL,
        `created_at_menu_item` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`menu_item_id`),
        INDEX `idx_menu_id_item` (`menu_id`),
        INDEX `idx_parent_menu_item_id` (`parent_menu_item_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "AuditTrail" => "CREATE TABLE IF NOT EXISTS `AuditTrail` (
        `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT UNSIGNED NULL,
        `action_type` VARCHAR(100) NOT NULL,
        `target_id` BIGINT UNSIGNED NULL,
        `target_table` VARCHAR(100) NULL,
        `details` JSON NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at_audit` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`),
        INDEX `idx_action_type` (`action_type`),
        INDEX `idx_target` (`target_id`, `target_table`),
        INDEX `idx_user_id_audit` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "ProductVariants" => "CREATE TABLE IF NOT EXISTS `ProductVariants` (
        `variant_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `product_id` BIGINT UNSIGNED NOT NULL,
        `variant_name` VARCHAR(255) NOT NULL,
        `sku` VARCHAR(100) NULL,
        `price_modifier` DECIMAL(10, 2) DEFAULT 0.00,
        `stock_quantity` INT NOT NULL DEFAULT 0,
        `created_at_variant` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`variant_id`),
        UNIQUE KEY `uq_variant_sku` (`sku`),
        INDEX `idx_product_id_variant` (`product_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Addresses" => "CREATE TABLE IF NOT EXISTS `Addresses` (
        `address_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT UNSIGNED NOT NULL,
        `address_type` ENUM('shipping', 'billing') NOT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `street` VARCHAR(255) NOT NULL,
        `apartment` VARCHAR(100) NULL,
        `city` VARCHAR(100) NOT NULL,
        `state` VARCHAR(100) NOT NULL,
        `country` VARCHAR(100) NOT NULL,
        `postal_code` VARCHAR(20) NOT NULL,
        `phone_number` VARCHAR(50) NULL,
        `is_default` BOOLEAN DEFAULT FALSE,
        `created_at_addresses` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`address_id`),
        INDEX `idx_user_id_address` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Payments" => "CREATE TABLE IF NOT EXISTS `Payments` (
        `payment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_id` BIGINT UNSIGNED NOT NULL,
        `transaction_id` VARCHAR(255) NOT NULL,
        `payment_method` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(12, 2) NOT NULL,
        `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
        `payment_status` ENUM('completed', 'pending', 'failed', 'refunded') NOT NULL,
        `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`payment_id`),
        UNIQUE KEY `uq_transaction_id` (`transaction_id`),
        INDEX `idx_order_id_payment` (`order_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Notifications" => "CREATE TABLE IF NOT EXISTS `Notifications` (
        `notification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT UNSIGNED NOT NULL,
        `type` VARCHAR(100) NOT NULL,
        `content` TEXT NOT NULL,
        `link_url` VARCHAR(512) NULL,
        `is_read` BOOLEAN DEFAULT FALSE,
        `created_at_notification` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`notification_id`),
        INDEX `idx_user_id_notification` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "UserPreferences" => "CREATE TABLE IF NOT EXISTS `UserPreferences` (
        `preference_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT UNSIGNED NOT NULL,
        `preference_key` VARCHAR(100) NOT NULL,
        `preference_value` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`preference_id`),
        UNIQUE KEY `uq_user_preference` (`user_id`, `preference_key`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "VisitorLog" => "CREATE TABLE IF NOT EXISTS `visitorlog` (
        `id_visitor` bigint NOT NULL AUTO_INCREMENT,
        `visitor_ip` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_url` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_post_id` bigint unsigned DEFAULT NULL,
        `visitor_method` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_country` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_code_country` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_state` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_identifier` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_user` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
        `visitor_visitor` tinyint(1) DEFAULT NULL,
        `visitor_time_record` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_visitor`),
        KEY `INDEX_visitor_post_id` (`visitor_post_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "EmailRegister" => "CREATE TABLE IF NOT EXISTS `EmailRegister` (
        `id_email_reg` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `email_user_reg` VARCHAR(255) NOT NULL,
        `name_user_reg` VARCHAR(255) NULL,
        `phone_user_reg` VARCHAR(50) NULL,
        `message_user_reg` TEXT NULL,
        `ip_user_reg` VARCHAR(100) NULL,
        `country_user_reg` VARCHAR(100) NULL,
        `campaign_type` VARCHAR(100) NULL,
        `created_at_email_reg` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_email_reg`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      // --- NUEVO: Tabla unificada de interacciones (likes, favoritos, guardados, shares) ---
      "Interactions" => "CREATE TABLE IF NOT EXISTS `Interactions` (
        `id_interaction` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `post_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID del post o contenido relacionado',
        `user_id` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la interacción (si está logueado)',
        `guest_identifier` VARCHAR(255) NULL COMMENT 'Identificador para usuarios anónimos (IP hash o similar)',
        `interaction_type` ENUM('like', 'favorite', 'save', 'share') NOT NULL COMMENT 'Tipo de interacción',
        `created_at_interaction` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_interaction`),
        UNIQUE KEY `uq_user_interaction` (`post_id`, `user_id`, `interaction_type`),
        UNIQUE KEY `uq_guest_interaction` (`post_id`, `guest_identifier`, `interaction_type`),
        INDEX `idx_post_id_interaction` (`post_id`),
        INDEX `idx_user_id_interaction` (`user_id`),
        INDEX `idx_interaction_type` (`interaction_type`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

      "Visits" => "CREATE TABLE IF NOT EXISTS `visits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `visited_user` VARCHAR(100) NOT NULL,
        `ip` VARCHAR(45) DEFAULT NULL,
        `country` VARCHAR(100) DEFAULT NULL,
        `city` VARCHAR(100) DEFAULT NULL,
        `region` VARCHAR(100) DEFAULT NULL,
        `codigo` VARCHAR(20) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `referer` TEXT DEFAULT NULL,
        `visited_at` DATETIME DEFAULT NULL,
        `timestamp` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_visited_user` (`visited_user`),
        INDEX `idx_visited_at` (`visited_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ];

    echo "--- Creando Tablas ---\n";
    foreach ($tablesSQL as $tableName => $sql) {
      $builder->query_foreign($sql);
      echo "Tabla '$tableName' creada o ya existente.\n";
    }
    echo "\n--- Fin de Creación de Tablas ---\n\n";

    // 2.5. Insertar datos por defecto
    $defaultDataSQL = [
      "Default_Roles" => "INSERT INTO `Roles` (`role_id`, `role_name`, `role_description`) VALUES 
        (1, 'administrator', 'Acceso completo al sistema. Puede gestionar usuarios, configuraciones y todo el contenido.'),
        (2, 'editor', 'Puede editar y publicar contenido de cualquier autor. Gestiona categorías y etiquetas.'),
        (3, 'author', 'Puede crear, editar y publicar su propio contenido.'),
        (4, 'collaborator', 'Puede crear y editar su propio contenido, pero no puede publicarlo.'),
        (5, 'subscriber', 'Puede leer contenido y gestionar su perfil. Sin permisos de creación.')
      ON DUPLICATE KEY UPDATE role_description = VALUES(role_description);"
    ];

    echo "--- Insertando Datos por Defecto ---\n";
    foreach ($defaultDataSQL as $dataName => $sql) {
      try {
        $builder->query_foreign($sql);
        echo "Datos '$dataName' insertados o actualizados.\n";
      } catch (Exception $e) {
        echo "Advertencia insertando datos '$dataName': " . $e->getMessage() . "\n";
      }
    }
    echo "\n--- Fin de Inserción de Datos por Defecto ---\n\n";

    // 3. Definición de Alteraciones de Tablas (Añadir columnas, índices, etc.)
    $alterationsSQL = [
      "Alter_Users_Add_SoftDelete_2FA" => "ALTER TABLE `Users` 
        ADD COLUMN `deleted_at_user` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at_user`,
        ADD COLUMN `is_two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE AFTER `password_reset_token_expires_at`,
        ADD COLUMN `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `is_two_factor_enabled`,
        ADD INDEX `idx_deleted_at_user` (`deleted_at_user`);",

      "Alter_Users_Add_Bio" => "ALTER TABLE `Users` ADD COLUMN `bio` TEXT NULL COMMENT 'Descripción o biografía del usuario' AFTER `full_name`;",

      "Alter_Pages_Add_SoftDelete" => "ALTER TABLE `Pages` ADD COLUMN `deleted_at_page` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at_page`, ADD INDEX `idx_deleted_at_page` (`deleted_at_page`);",
      "Alter_BlogPosts_Add_SoftDelete" => "ALTER TABLE `BlogPosts` ADD COLUMN `deleted_at_blog_post` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at_blog_post`, ADD INDEX `idx_deleted_at_blog_post` (`deleted_at_blog_post`);",
      "Alter_Comments_Add_SoftDelete" => "ALTER TABLE `Comments` ADD COLUMN `deleted_at_comment` TIMESTAMP NULL DEFAULT NULL AFTER `user_agent`, ADD INDEX `idx_deleted_at_comment` (`deleted_at_comment`);",
      "Alter_Products_Add_SoftDelete" => "ALTER TABLE `Products` ADD COLUMN `deleted_at_product` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at_product`, ADD INDEX `idx_deleted_at_product` (`deleted_at_product`);"
    ];

    echo "--- Alterando Tablas ---\n";
    foreach ($alterationsSQL as $alterName => $sql) {
      try {
        $builder->query_foreign($sql);
        echo "Alteración '$alterName' aplicada exitosamente.\n";
      } catch (Exception $e) {
        echo "Error o advertencia aplicando alteración '$alterName': " . $e->getMessage() . ". Esto podría ser normal si la alteración ya existe.\n";
      }
    }
    echo "\n--- Fin de Alteración de Tablas ---\n\n";


    // 4. Definición y Creación de Claves Foráneas
    $foreignKeysSQL = [
      "FK_UserRoles_Users" => "ALTER TABLE `UserRoles` ADD CONSTRAINT `FK_UserRoles_Users` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_UserRoles_Roles" => "ALTER TABLE `UserRoles` ADD CONSTRAINT `FK_UserRoles_Roles` FOREIGN KEY (`id_role`) REFERENCES `Roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_Pages_Users" => "ALTER TABLE `Pages` ADD CONSTRAINT `FK_Pages_Users` FOREIGN KEY (`author_user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      "FK_BlogPosts_Users" => "ALTER TABLE `BlogPosts` ADD CONSTRAINT `FK_BlogPosts_Users` FOREIGN KEY (`author_user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      "FK_Categories_Self" => "ALTER TABLE `Categories` ADD CONSTRAINT `FK_Categories_Self` FOREIGN KEY (`parent_category_id`) REFERENCES `Categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      "FK_BlogPostCategories_BlogPosts" => "ALTER TABLE `BlogPostCategories` ADD CONSTRAINT `FK_BlogPostCat_BlogPosts` FOREIGN KEY (`post_id`) REFERENCES `BlogPosts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_BlogPostCategories_Categories" => "ALTER TABLE `BlogPostCategories` ADD CONSTRAINT `FK_BlogPostCat_Categories` FOREIGN KEY (`category_id`) REFERENCES `Categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_BlogPostTags_BlogPosts" => "ALTER TABLE `BlogPostTags` ADD CONSTRAINT `FK_BlogPostTags_BlogPosts` FOREIGN KEY (`post_id`) REFERENCES `BlogPosts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_BlogPostTags_Tags" => "ALTER TABLE `BlogPostTags` ADD CONSTRAINT `FK_BlogPostTags_Tags` FOREIGN KEY (`tag_id`) REFERENCES `Tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_Comments_Users" => "ALTER TABLE `Comments` ADD CONSTRAINT `FK_Comments_Users` FOREIGN KEY (`commenter_user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",
      "FK_Comments_Self" => "ALTER TABLE `Comments` ADD CONSTRAINT `FK_Comments_Self` FOREIGN KEY (`parent_comment_id`) REFERENCES `Comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_ProductCategories_Products" => "ALTER TABLE `ProductCategories` ADD CONSTRAINT `FK_ProductCat_Products` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_ProductCategories_Categories" => "ALTER TABLE `ProductCategories` ADD CONSTRAINT `FK_ProductCat_Categories` FOREIGN KEY (`category_id`) REFERENCES `Categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_ProductTags_Products" => "ALTER TABLE `ProductTags` ADD CONSTRAINT `FK_ProductTags_Products` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_ProductTags_Tags" => "ALTER TABLE `ProductTags` ADD CONSTRAINT `FK_ProductTags_Tags` FOREIGN KEY (`tag_id`) REFERENCES `Tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_Orders_Users" => "ALTER TABLE `Orders` ADD CONSTRAINT `FK_Orders_Users` FOREIGN KEY (`customer_user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      "FK_OrderItems_Orders" => "ALTER TABLE `OrderItems` ADD CONSTRAINT `FK_OrderItems_Orders` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_OrderItems_Products" => "ALTER TABLE `OrderItems` ADD CONSTRAINT `FK_OrderItems_Products` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE RESTRICT ON UPDATE CASCADE;",

      "FK_Media_Users" => "ALTER TABLE `Media` ADD CONSTRAINT `FK_Media_Users` FOREIGN KEY (`uploader_user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",
      "FK_Mediables_Media" => "ALTER TABLE `Mediables` ADD CONSTRAINT `FK_Mediables_Media` FOREIGN KEY (`ref_media_id`) REFERENCES `Media` (`media_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_Users_Media_Avatar" => "ALTER TABLE `Users` ADD CONSTRAINT `FK_Users_Media_Avatar` FOREIGN KEY (`avatar_media_id`) REFERENCES `Media` (`media_id`) ON DELETE SET NULL ON UPDATE CASCADE;",
      "FK_Products_Media_Featured" => "ALTER TABLE `Products` ADD CONSTRAINT `FK_Products_Media_Featured` FOREIGN KEY (`featured_media_id`) REFERENCES `Media` (`media_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      "FK_MenuItems_NavigationMenus" => "ALTER TABLE `MenuItems` ADD CONSTRAINT `FK_MenuItems_NavMenus` FOREIGN KEY (`menu_id`) REFERENCES `NavigationMenus` (`menu_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_MenuItems_Self" => "ALTER TABLE `MenuItems` ADD CONSTRAINT `FK_MenuItems_Self` FOREIGN KEY (`parent_menu_item_id`) REFERENCES `MenuItems` (`menu_item_id`) ON DELETE CASCADE ON UPDATE CASCADE;",

      "FK_AuditTrail_Users" => "ALTER TABLE `AuditTrail` ADD CONSTRAINT `FK_AuditTrail_Users` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",
      "FK_ProductVariants_Products" => "ALTER TABLE `ProductVariants` ADD CONSTRAINT `FK_ProductVariants_Products` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_Addresses_Users" => "ALTER TABLE `Addresses` ADD CONSTRAINT `FK_Addresses_Users` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_Payments_Orders" => "ALTER TABLE `Payments` ADD CONSTRAINT `FK_Payments_Orders` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`) ON DELETE RESTRICT ON UPDATE CASCADE;",
      "FK_Notifications_Users" => "ALTER TABLE `Notifications` ADD CONSTRAINT `FK_Notifications_Users` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_UserPreferences_Users" => "ALTER TABLE `UserPreferences` ADD CONSTRAINT `FK_UserPreferences_Users` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_VisitorLog_BlogPosts" => "ALTER TABLE `visitorlog` ADD CONSTRAINT `FK_visitorlog_blogposts` FOREIGN KEY (`visitor_post_id`) REFERENCES `blogposts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      // --- AÑADIDO: Claves foráneas para las nuevas tablas de encuestas ---
      "FK_Survey_BlogPosts" => "ALTER TABLE `Survey` ADD CONSTRAINT `FK_Survey_BlogPosts` FOREIGN KEY (`id_blogpost`) REFERENCES `BlogPosts` (`post_id`) ON DELETE CASCADE;",
      "FK_SurveyAnswers_Survey" => "ALTER TABLE `SurveyAnswers` ADD CONSTRAINT `FK_SurveyAnswers_Survey` FOREIGN KEY (`survey_id`) REFERENCES `Survey` (`id_survey`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_SurveyAnswers_Users" => "ALTER TABLE `SurveyAnswers` ADD CONSTRAINT `FK_SurveyAnswers_Users` FOREIGN KEY (`survey_user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;",

      // --- AÑADIDO: Claves foráneas para la tabla Interactions ---
      "FK_Interactions_BlogPosts" => "ALTER TABLE `Interactions` ADD CONSTRAINT `FK_Interactions_BlogPosts` FOREIGN KEY (`post_id`) REFERENCES `BlogPosts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
      "FK_Interactions_Users" => "ALTER TABLE `Interactions` ADD CONSTRAINT `FK_Interactions_Users` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;"
    ];

    echo "--- Añadiendo Claves Foráneas ---\n";
    foreach ($foreignKeysSQL as $fkName => $sql) {
      try {
        $builder->query_foreign($sql);
        echo "Clave foránea '$fkName' añadida (o ya existía).\n";
      } catch (Exception $e) {
        echo "Error o advertencia añadiendo clave foránea '$fkName': " . $e->getMessage() . ". Esto podría ser normal si la clave ya existe.\n";
      }
    }
    echo "\n--- Fin de Adición de Claves Foráneas ---\n\n";

    echo "Proceso de configuración de base de datos completado.\n";
  } catch (Exception $e) {
    echo "Error general en el proceso de configuración de la base de datos: " . $e->getMessage() . "\n";
  } finally {
    echo "</pre>";
  }
}

// Descomenta la siguiente línea para ejecutar la función cuando llames a este script
configInitBd();

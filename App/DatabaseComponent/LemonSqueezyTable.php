<?php

namespace App\DatabaseComponent;

use Base\Builder\Builder;

/**
 * Componente LemonSqueezyTable
 * 
 * Genera la estructura de tablas necesarias en la base de datos para registrar
 * las órdenes y suscripciones procesadas a través de Lemon Squeezy.
 */
class LemonSqueezyTable
{
  /**
   * Inicializa las tablas 'lemon_squeezy_orders' y 'lemon_squeezy_subscriptions'.
   * 
   * @return array Resultado de la creación de las tablas.
   */
  public static function setupTables(): array
  {
    $builder = new Builder();
    $log     = [];

    $sqlOrders = "CREATE TABLE IF NOT EXISTS `lemon_squeezy_orders` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `lemon_order_id` VARCHAR(100) NOT NULL,
      `store_id` VARCHAR(100) NULL,
      `customer_id` VARCHAR(100) NULL,
      `user_id` VARCHAR(100) NULL COMMENT 'ID o Username del usuario en el sistema',
      `customer_name` VARCHAR(255) NULL,
      `customer_email` VARCHAR(255) NULL,
      `order_number` VARCHAR(100) NULL,
      `status` VARCHAR(50) DEFAULT 'pending',
      `currency` VARCHAR(10) DEFAULT 'USD',
      `total_cents` INT UNSIGNED DEFAULT 0,
      `variant_id` VARCHAR(100) NULL,
      `product_name` VARCHAR(255) NULL,
      `raw_payload` LONGTEXT NULL,
      `created_at_record` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at_record` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_lemon_order_id` (`lemon_order_id`),
      INDEX `idx_user_id` (`user_id`),
      INDEX `idx_customer_email` (`customer_email`),
      INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $sqlSubscriptions = "CREATE TABLE IF NOT EXISTS `lemon_squeezy_subscriptions` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `lemon_subscription_id` VARCHAR(100) NOT NULL,
      `store_id` VARCHAR(100) NULL,
      `customer_id` VARCHAR(100) NULL,
      `order_id` VARCHAR(100) NULL,
      `product_id` VARCHAR(100) NULL,
      `variant_id` VARCHAR(100) NULL,
      `user_id` VARCHAR(100) NULL,
      `user_email` VARCHAR(255) NULL,
      `status` VARCHAR(50) DEFAULT 'active',
      `trial_ends_at` TIMESTAMP NULL DEFAULT NULL,
      `renews_at` TIMESTAMP NULL DEFAULT NULL,
      `ends_at` TIMESTAMP NULL DEFAULT NULL,
      `raw_payload` LONGTEXT NULL,
      `created_at_sub` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at_sub` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_lemon_sub_id` (`lemon_subscription_id`),
      INDEX `idx_sub_user_id` (`user_id`),
      INDEX `idx_sub_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    try {
      $builder->query_foreign($sqlOrders);
      $log[] = "Tabla 'lemon_squeezy_orders' verificada/creada exitosamente.";

      $builder->query_foreign($sqlSubscriptions);
      $log[] = "Tabla 'lemon_squeezy_subscriptions' verificada/creada exitosamente.";

      // Asegurar tipo LONGTEXT en tablas existentes para evitar conflictos de htmlentities() del Builder con JSON estricto
      @$builder->query_foreign("ALTER TABLE `lemon_squeezy_orders` MODIFY `raw_payload` LONGTEXT NULL;");
      @$builder->query_foreign("ALTER TABLE `lemon_squeezy_subscriptions` MODIFY `raw_payload` LONGTEXT NULL;");

      return [
        "success" => true,
        "log"     => $log
      ];
    } catch (\Exception $e) {


      return [
        "success" => true,
        "log"     => $log
      ];
    } catch (\Exception $e) {
      return [
        "success" => false,
        "error"   => $e->getMessage(),
        "log"     => $log
      ];
    }
  }
}

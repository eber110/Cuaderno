<?php

namespace App\DatabaseComponent;

use Base\Builder\Builder;

/**
 * Componente LemonSqueezyTable
 * 
 * Genera la estructura de tablas necesarias en la base de datos SQLite para registrar
 * las órdenes y suscripciones procesadas a través de Lemon Squeezy.
 */
class LemonSqueezyTable
{
  /**
   * Inicializa las tablas 'lemon_squeezy_orders' y 'lemon_squeezy_subscriptions' en SQLite.
   * 
   * @return array Resultado de la creación de las tablas.
   */
  public static function setupTables(): array
  {
    $builder = new Builder();
    $log     = [];

    $sqlOrders = "CREATE TABLE IF NOT EXISTS lemon_squeezy_orders (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      lemon_order_id VARCHAR(100) NOT NULL UNIQUE,
      store_id VARCHAR(100) NULL,
      customer_id VARCHAR(100) NULL,
      user_id VARCHAR(100) NULL,
      customer_name VARCHAR(255) NULL,
      customer_email VARCHAR(255) NULL,
      order_number VARCHAR(100) NULL,
      status VARCHAR(50) DEFAULT 'pending',
      currency VARCHAR(10) DEFAULT 'USD',
      total_cents INTEGER DEFAULT 0,
      variant_id VARCHAR(100) NULL,
      product_name VARCHAR(255) NULL,
      raw_payload TEXT NULL,
      created_at_record DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at_record DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    $sqlSubscriptions = "CREATE TABLE IF NOT EXISTS lemon_squeezy_subscriptions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      lemon_subscription_id VARCHAR(100) NOT NULL UNIQUE,
      store_id VARCHAR(100) NULL,
      customer_id VARCHAR(100) NULL,
      order_id VARCHAR(100) NULL,
      product_id VARCHAR(100) NULL,
      variant_id VARCHAR(100) NULL,
      user_id VARCHAR(100) NULL,
      user_email VARCHAR(255) NULL,
      status VARCHAR(50) DEFAULT 'active',
      trial_ends_at DATETIME NULL,
      renews_at DATETIME NULL,
      ends_at DATETIME NULL,
      raw_payload TEXT NULL,
      created_at_sub DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at_sub DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    try {
      $builder->query_foreign($sqlOrders);
      $log[] = "Tabla 'lemon_squeezy_orders' verificada/creada exitosamente en SQLite.";

      $builder->query_foreign($sqlSubscriptions);
      $log[] = "Tabla 'lemon_squeezy_subscriptions' verificada/creada exitosamente en SQLite.";

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

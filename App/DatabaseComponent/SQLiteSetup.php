<?php

namespace App\DatabaseComponent;

use Base\Builder\Builder;
use PDO;

/**
 * Componente SQLiteSetup
 * 
 * Se encarga de inicializar la estructura completa de tablas en SQLite
 * (users, roles, userroles, user_designs, lemon_squeezy_orders, lemon_squeezy_subscriptions, ratelimits).
 */
class SQLiteSetup
{
  /**
   * Ejecuta las sentencias DDL para crear todas las tablas del sistema en SQLite.
   * 
   * @return array Registros del estado de creación.
   */
  public static function setupTables(): array
  {
    $builder = new Builder();
    $log     = [];

    // 1. Tabla users
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
      user_id INTEGER PRIMARY KEY AUTOINCREMENT,
      index_user INT NULL,
      username VARCHAR(100) NOT NULL UNIQUE,
      email VARCHAR(255) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      full_name VARCHAR(255) NULL,
      bio TEXT NULL,
      avatar_media_id INT NULL,
      registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      last_login DATETIME NULL,
      user_status VARCHAR(50) DEFAULT 'active',
      email_verification_token VARCHAR(100) NULL,
      email_verification_token_expires_at DATETIME NULL,
      password_reset_token VARCHAR(100) NULL,
      password_reset_token_expires_at DATETIME NULL,
      is_two_factor_enabled INTEGER DEFAULT 0,
      two_factor_secret VARCHAR(255) NULL,
      updated_at_user DATETIME DEFAULT CURRENT_TIMESTAMP,
      deleted_at_user DATETIME NULL
    );";

    // 2. Tabla roles
    $sqlRoles = "CREATE TABLE IF NOT EXISTS roles (
      role_id INTEGER PRIMARY KEY AUTOINCREMENT,
      role_name VARCHAR(50) NOT NULL UNIQUE,
      role_description TEXT NULL,
      created_at_role DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    // 3. Tabla userroles
    $sqlUserRoles = "CREATE TABLE IF NOT EXISTS userroles (
      user_role_id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      id_role INTEGER NOT NULL,
      assigned_at_user_role DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    // 4. Tabla user_designs (Sustituye Cache/UserData/*.json)
    $sqlUserDesigns = "CREATE TABLE IF NOT EXISTS user_designs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username VARCHAR(100) NOT NULL,
      is_draft INTEGER DEFAULT 0,
      active INTEGER DEFAULT 0,
      hide INTEGER DEFAULT 1,
      profile VARCHAR(100) NOT NULL,
      avatar TEXT DEFAULT 'no-user.webp',
      title TEXT DEFAULT 'Titulo',
      title_color VARCHAR(50) DEFAULT '#383838',
      desc TEXT DEFAULT 'Descripción del usuario',
      header VARCHAR(100) DEFAULT 'regularHero',
      void_space INTEGER DEFAULT 70,
      back_perfil VARCHAR(50) DEFAULT '#a0a0a0',
      style_back VARCHAR(50) DEFAULT 'solid',
      back_video TEXT NULL,
      back_video_public_id VARCHAR(150) NULL,
      back_video_overlay VARCHAR(50) DEFAULT '#000000',
      back_video_opacity INTEGER DEFAULT 45,
      color_text VARCHAR(50) DEFAULT '#383838',
      style VARCHAR(100) DEFAULT 'buttonRegular',
      borders TEXT DEFAULT '[\"br0\", \"br0\"]',
      shadow VARCHAR(50) DEFAULT 'shadow-1',
      back VARCHAR(50) DEFAULT '#d6d6d6',
      hover INTEGER DEFAULT 0,
      color VARCHAR(50) DEFAULT '#494949',
      color_shadow3 VARCHAR(50) DEFAULT '#000000',
      rrss TEXT DEFAULT '[]',
      content TEXT DEFAULT '[]',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE(username, is_draft)
    );";

    // 5. Tabla lemon_squeezy_orders
    $sqlLemonOrders = "CREATE TABLE IF NOT EXISTS lemon_squeezy_orders (
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

    // 6. Tabla lemon_squeezy_subscriptions
    $sqlLemonSubscriptions = "CREATE TABLE IF NOT EXISTS lemon_squeezy_subscriptions (
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

    // 7. Tabla ratelimits
    $sqlRateLimits = "CREATE TABLE IF NOT EXISTS ratelimits (
      rate_limit_id INTEGER PRIMARY KEY AUTOINCREMENT,
      ip VARCHAR(100) NOT NULL,
      action_key VARCHAR(150) NOT NULL,
      attempts INTEGER DEFAULT 1,
      blocked_until DATETIME NULL,
      last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE(ip, action_key)
    );";

    // 8. Tabla user_subscriptions_cache
    $sqlUserSubscriptionsCache = "CREATE TABLE IF NOT EXISTS user_subscriptions_cache (
      user_id TEXT PRIMARY KEY,
      username TEXT,
      is_premium INTEGER DEFAULT 0,
      status TEXT,
      renews_at TEXT,
      ends_at TEXT,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    // 9. Tabla profile_views
    $sqlProfileViews = "CREATE TABLE IF NOT EXISTS profile_views (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      profile_id TEXT NOT NULL,
      ip_address TEXT,
      country_code TEXT,
      country_name TEXT,
      city_name TEXT,
      device_type TEXT,
      os TEXT,
      browser TEXT,
      referrer TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    // 10. Tabla link_clicks
    $sqlLinkClicks = "CREATE TABLE IF NOT EXISTS link_clicks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      link_id TEXT,
      profile_id TEXT NOT NULL,
      country_code TEXT,
      device_type TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    // 11. Tabla active_sessions
    $sqlActiveSessions = "CREATE TABLE IF NOT EXISTS active_sessions (
      session_token TEXT PRIMARY KEY,
      profile_id TEXT NOT NULL,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );";

    $tables = [
      "users"                       => $sqlUsers,
      "roles"                       => $sqlRoles,
      "userroles"                   => $sqlUserRoles,
      "user_designs"                => $sqlUserDesigns,
      "lemon_squeezy_orders"        => $sqlLemonOrders,
      "lemon_squeezy_subscriptions" => $sqlLemonSubscriptions,
      "ratelimits"                  => $sqlRateLimits,
      "user_subscriptions_cache"    => $sqlUserSubscriptionsCache,
      "profile_views"               => $sqlProfileViews,
      "link_clicks"                 => $sqlLinkClicks,
      "active_sessions"             => $sqlActiveSessions
    ];

    foreach ($tables as $tableName => $sql) {
      try {
        $builder->query_foreign($sql);
        $log[] = "Tabla '{$tableName}' lista en SQLite.";
      } catch (\Throwable $e) {
        $log[] = "Error al crear la tabla '{$tableName}': " . $e->getMessage();
      }
    }

    // Migraciones automáticas para columnas nuevas en user_designs
    try {
      $cols = (new Builder())->query_foreign("PRAGMA table_info(user_designs);")->get();
      $existingCols = [];
      if (is_array($cols)) {
        foreach ($cols as $col) {
          if (!empty($col["name"])) {
            $existingCols[] = $col["name"];
          }
        }
      }
      if (!in_array("back_video", $existingCols, true)) {
        $builder->query_foreign("ALTER TABLE user_designs ADD COLUMN back_video TEXT NULL;");
      }
      if (!in_array("back_video_public_id", $existingCols, true)) {
        $builder->query_foreign("ALTER TABLE user_designs ADD COLUMN back_video_public_id VARCHAR(150) NULL;");
      }
      if (!in_array("back_video_overlay", $existingCols, true)) {
        $builder->query_foreign("ALTER TABLE user_designs ADD COLUMN back_video_overlay VARCHAR(50) DEFAULT '#000000';");
      }
      if (!in_array("back_video_opacity", $existingCols, true)) {
        $builder->query_foreign("ALTER TABLE user_designs ADD COLUMN back_video_opacity INTEGER DEFAULT 45;");
      }
    } catch (\Throwable $e) {}

    // Insertar roles base si no existen
    try {
      $rolesCount = (new Builder("roles"))->count("role_id", "total")->get_one();
      if (empty($rolesCount[0]["total"])) {
        (new Builder("roles"))->create(["role_name" => "admin", "role_description" => "Administrador del sistema"]);
        (new Builder("roles"))->create(["role_name" => "user", "role_description" => "Usuario regular"]);
        $log[] = "Roles por defecto (admin, user) insertados.";
      }
    } catch (\Throwable $e) {}

    return [
      "success" => true,
      "log"     => $log
    ];
  }
}

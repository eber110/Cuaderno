<?php

require 'vendor/autoload.php';

use Base\Builder\Builder;
use Base\ErrorHandler;

/**
 * Script de inicialización de base de datos para PostgreSQL
 * Equivalente a configInitBdMySql.php pero con sintaxis PostgreSQL
 */
function configInitBdPostgreSql()
{

  $builder = new Builder;

  // --- Database Configuration ---
  $dbName = BD; // Nombre de tu base de datos desde tus constantes

  try {
    echo '<pre>';

    // 1. PostgreSQL no necesita USE, la conexión ya apunta a la BD
    echo "Conectado a la base de datos '$dbName' (PostgreSQL).\n";

    // Configurar encoding
    $sqlSetEncoding = "SET client_encoding = 'UTF8'";
    $builder->query_foreign($sqlSetEncoding);
    echo "SET client_encoding = 'UTF8' ejecutado.\n\n";

    // 2. Crear tipos ENUM personalizados (PostgreSQL requiere tipos definidos)
    $enumTypes = [
      "user_status_type" => "DO $$ BEGIN CREATE TYPE user_status_type AS ENUM ('active', 'inactive', 'pending_verification', 'banned'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "content_type_type" => "DO $$ BEGIN CREATE TYPE content_type_type AS ENUM ('html', 'markdown', 'json_builder'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "page_status_type" => "DO $$ BEGIN CREATE TYPE page_status_type AS ENUM ('published', 'draft', 'private', 'trash'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "visibility_type" => "DO $$ BEGIN CREATE TYPE visibility_type AS ENUM ('public', 'private', 'password_protected'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "post_type_type" => "DO $$ BEGIN CREATE TYPE post_type_type AS ENUM ('post', 'survey', 'publicity', 'resource', 'tutorial', 'opinion'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "post_status_type" => "DO $$ BEGIN CREATE TYPE post_status_type AS ENUM ('published', 'draft', 'scheduled', 'private', 'trash'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "comment_status_type" => "DO $$ BEGIN CREATE TYPE comment_status_type AS ENUM ('approved', 'pending', 'spam', 'trash'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "stock_status_type" => "DO $$ BEGIN CREATE TYPE stock_status_type AS ENUM ('in_stock', 'out_of_stock', 'on_backorder'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "order_status_type" => "DO $$ BEGIN CREATE TYPE order_status_type AS ENUM ('pending_payment', 'processing', 'shipped', 'completed', 'cancelled', 'refunded', 'failed'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "link_type_type" => "DO $$ BEGIN CREATE TYPE link_type_type AS ENUM ('custom_url', 'page', 'blog_post', 'category', 'product', 'tag'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "link_target_type" => "DO $$ BEGIN CREATE TYPE link_target_type AS ENUM ('_self', '_blank'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "address_type_type" => "DO $$ BEGIN CREATE TYPE address_type_type AS ENUM ('shipping', 'billing'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "payment_status_type" => "DO $$ BEGIN CREATE TYPE payment_status_type AS ENUM ('completed', 'pending', 'failed', 'refunded'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "email_status_type" => "DO $$ BEGIN CREATE TYPE email_status_type AS ENUM ('pending', 'verified', 'unsubscribed'); EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "interaction_type_type" => "DO $$ BEGIN CREATE TYPE interaction_type_type AS ENUM ('like', 'favorite', 'save', 'share'); EXCEPTION WHEN duplicate_object THEN null; END $$;"
    ];

    echo "--- Creando Tipos ENUM ---\n";
    foreach ($enumTypes as $typeName => $sql) {
      $builder->query_foreign($sql);
      echo "Tipo ENUM '$typeName' creado o ya existente.\n";
    }
    echo "\n--- Fin de Creación de Tipos ENUM ---\n\n";

    // 3. Definiciones de Tablas (SQL para CREATE TABLE - PostgreSQL)
    $tablesSQL = [
      "RateLimits" => "CREATE TABLE IF NOT EXISTS ratelimits (
        rate_limit_id BIGSERIAL PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        action_key VARCHAR(255) NOT NULL,
        attempts INT DEFAULT 0,
        blocked_until TIMESTAMP NULL DEFAULT NULL,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_ip_action UNIQUE (ip, action_key)
      );",

      "SiteSettings" => "CREATE TABLE IF NOT EXISTS sitesettings (
        setting_key VARCHAR(255) NOT NULL,
        setting_value TEXT,
        data_type VARCHAR(50) DEFAULT 'string',
        setting_group VARCHAR(100) DEFAULT 'General',
        description TEXT,
        created_at_site_setting TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at_site_setting TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (setting_key)
      );",

      "Users" => "CREATE TABLE IF NOT EXISTS users (
        user_id BIGSERIAL PRIMARY KEY,
        index_user VARCHAR(100) NOT NULL,
        username VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NULL,
        bio TEXT NULL,
        avatar_media_id BIGINT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        user_status user_status_type DEFAULT 'pending_verification',
        email_verification_token VARCHAR(100) NULL,
        email_verification_token_expires_at TIMESTAMP NULL,
        password_reset_token VARCHAR(100) NULL,
        password_reset_token_expires_at TIMESTAMP NULL,
        updated_at_user TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_username UNIQUE (username),
        CONSTRAINT uq_email UNIQUE (email),
        CONSTRAINT uq_index_user UNIQUE (index_user)
      );",

      "Roles" => "CREATE TABLE IF NOT EXISTS roles (
        role_id SERIAL PRIMARY KEY,
        role_name VARCHAR(100) NOT NULL,
        role_description TEXT NULL,
        created_at_role TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_role_name UNIQUE (role_name)
      );",

      "UserRoles" => "CREATE TABLE IF NOT EXISTS userroles (
        user_role_id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL,
        id_role INT NOT NULL,
        assigned_at_user_role TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_user_role UNIQUE (user_id, id_role)
      );",

      "Media" => "CREATE TABLE IF NOT EXISTS media (
        media_id BIGSERIAL PRIMARY KEY,
        uploader_user_id BIGINT NULL,
        referenced_post_id BIGINT NOT NULL,
        referenced_table VARCHAR(50) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        server_file_path VARCHAR(512) NOT NULL,
        file_url VARCHAR(512) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size_bytes BIGINT NOT NULL,
        media_alt_text VARCHAR(255) NULL,
        uploaded_at_media TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );",

      "Mediables" => "CREATE TABLE IF NOT EXISTS mediables (
        ref_media_id BIGINT NOT NULL,
        mediable_id BIGINT NOT NULL,
        mediable_type VARCHAR(100) NOT NULL,
        tag VARCHAR(100) NOT NULL,
        PRIMARY KEY (ref_media_id, mediable_id, mediable_type, tag)
      );",

      "Pages" => "CREATE TABLE IF NOT EXISTS pages (
        page_id BIGSERIAL PRIMARY KEY,
        author_user_id BIGINT NULL,
        author_display_name VARCHAR(255) NULL,
        page_title VARCHAR(255) NOT NULL,
        page_slug VARCHAR(255) NOT NULL,
        page_content TEXT,
        content_type content_type_type DEFAULT 'html',
        page_template VARCHAR(100) NULL,
        page_status page_status_type DEFAULT 'draft',
        page_visibility visibility_type DEFAULT 'public',
        page_password VARCHAR(255) NULL,
        meta_title_seo VARCHAR(255) NULL,
        meta_description_seo TEXT NULL,
        allow_comments BOOLEAN DEFAULT FALSE,
        page_order INT DEFAULT 0,
        created_at_page TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        published_at_page TIMESTAMP NULL,
        updated_at_page TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_page_slug UNIQUE (page_slug)
      );",

      "BlogPosts" => "CREATE TABLE IF NOT EXISTS blogposts (
        post_id BIGSERIAL PRIMARY KEY,
        author_user_id BIGINT NULL,
        index_post VARCHAR(100) NOT NULL,
        author_display_name VARCHAR(255) NULL,
        post_title VARCHAR(255) NOT NULL,
        post_slug VARCHAR(255) NOT NULL,
        post_type post_type_type DEFAULT 'post',
        post_summary TEXT NULL,
        post_content TEXT,
        content_type content_type_type DEFAULT 'html',
        post_status post_status_type DEFAULT 'draft',
        post_visibility visibility_type DEFAULT 'public',
        post_password VARCHAR(255) NULL,
        meta_title_seo VARCHAR(255) NULL,
        meta_description_seo TEXT NULL,
        allow_comments BOOLEAN DEFAULT TRUE,
        created_at_blog_post TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        published_at_blog_post TIMESTAMP NULL,
        updated_at_blog_post TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_post_slug UNIQUE (post_slug)
      );",

      "Categories" => "CREATE TABLE IF NOT EXISTS categories (
        category_id SERIAL PRIMARY KEY,
        category_name VARCHAR(150) NOT NULL,
        category_slug VARCHAR(150) NOT NULL,
        category_description TEXT NULL,
        parent_category_id INT NULL,
        created_at_category TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_category_slug UNIQUE (category_slug)
      );",

      "Tags" => "CREATE TABLE IF NOT EXISTS tags (
        tag_id SERIAL PRIMARY KEY,
        tag_name VARCHAR(100) NOT NULL,
        tag_slug VARCHAR(100) NOT NULL,
        created_at_tag TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_tag_slug UNIQUE (tag_slug)
      );",

      "BlogPostCategories" => "CREATE TABLE IF NOT EXISTS blogpostcategories (
        post_id BIGINT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (post_id, category_id)
      );",

      "BlogPostTags" => "CREATE TABLE IF NOT EXISTS blogposttags (
        post_id BIGINT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (post_id, tag_id)
      );",

      "Comments" => "CREATE TABLE IF NOT EXISTS comments (
        comment_id BIGSERIAL PRIMARY KEY,
        index_comment VARCHAR(100) NOT NULL,
        associated_content_id BIGINT NOT NULL,
        associated_content_type VARCHAR(100) NOT NULL,
        commenter_user_id BIGINT NULL,
        guest_commenter_name VARCHAR(150) NULL,
        guest_commenter_email VARCHAR(255) NULL,
        comment_content TEXT NOT NULL,
        comment_status comment_status_type DEFAULT 'pending',
        parent_comment_id BIGINT NULL,
        commented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        commenter_ip VARCHAR(45) NULL,
        user_agent TEXT NULL
      );",

      "Survey" => "CREATE TABLE IF NOT EXISTS survey (
        id_survey BIGSERIAL PRIMARY KEY,
        id_blogpost BIGINT NOT NULL,
        question TEXT NOT NULL,
        option_label JSONB NOT NULL,
        option_number JSONB NOT NULL,
        CONSTRAINT rel_blogpost UNIQUE (id_blogpost)
      );",

      "SurveyAnswers" => "CREATE TABLE IF NOT EXISTS surveyanswers (
        survey_answer_id BIGSERIAL PRIMARY KEY,
        survey_id BIGINT NOT NULL,
        survey_user_id BIGINT NULL,
        guest_identifier VARCHAR(255) NULL,
        ip_guest VARCHAR(255) NULL,
        selected_option_index INT NOT NULL,
        survey_answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_user_survey_answer UNIQUE (survey_id, survey_user_id),
        CONSTRAINT uq_guest_survey_answer UNIQUE (survey_id, guest_identifier)
      );",

      "Products" => "CREATE TABLE IF NOT EXISTS products (
        product_id BIGSERIAL PRIMARY KEY,
        index_product VARCHAR(100) NOT NULL,
        featured_media_id BIGINT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_slug VARCHAR(255) NOT NULL,
        short_description TEXT NULL,
        long_description TEXT NULL,
        sku VARCHAR(100) NULL,
        regular_price DECIMAL(10, 2) NOT NULL,
        sale_price DECIMAL(10, 2) NULL,
        sale_start_date TIMESTAMP NULL,
        sale_end_date TIMESTAMP NULL,
        stock_quantity INT DEFAULT 0,
        manage_stock BOOLEAN DEFAULT TRUE,
        stock_status stock_status_type DEFAULT 'in_stock',
        product_status page_status_type DEFAULT 'draft',
        allow_reviews BOOLEAN DEFAULT TRUE,
        average_rating DECIMAL(3, 2) DEFAULT 0.00,
        total_sales INT DEFAULT 0,
        created_at_product TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at_product TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_product_slug UNIQUE (product_slug),
        CONSTRAINT uq_product_sku UNIQUE (sku)
      );",

      "ProductCategories" => "CREATE TABLE IF NOT EXISTS productcategories (
        product_id BIGINT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (product_id, category_id)
      );",

      "ProductTags" => "CREATE TABLE IF NOT EXISTS producttags (
        product_id BIGINT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (product_id, tag_id)
      );",

      "Orders" => "CREATE TABLE IF NOT EXISTS orders (
        order_id BIGSERIAL PRIMARY KEY,
        customer_user_id BIGINT NULL,
        guest_customer_email VARCHAR(255) NULL,
        order_number VARCHAR(50) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        order_status order_status_type DEFAULT 'pending_payment',
        order_total DECIMAL(12, 2) NOT NULL,
        order_subtotal DECIMAL(12, 2) NOT NULL,
        total_tax DECIMAL(10, 2) DEFAULT 0.00,
        total_shipping DECIMAL(10, 2) DEFAULT 0.00,
        payment_method VARCHAR(100) NULL,
        payment_transaction_id VARCHAR(255) NULL,
        billing_address_json JSONB NULL,
        shipping_address_json JSONB NULL,
        customer_notes TEXT NULL,
        currency VARCHAR(3) DEFAULT 'USD',
        updated_at_order TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_order_number UNIQUE (order_number)
      );",

      "OrderItems" => "CREATE TABLE IF NOT EXISTS orderitems (
        order_item_id BIGSERIAL PRIMARY KEY,
        order_id BIGINT NOT NULL,
        product_id BIGINT NOT NULL,
        product_variation_id BIGINT NULL,
        product_name_snapshot VARCHAR(255) NOT NULL,
        product_sku_snapshot VARCHAR(100) NULL,
        quantity INT NOT NULL,
        unit_price_snapshot DECIMAL(10, 2) NOT NULL,
        item_subtotal DECIMAL(12, 2) NOT NULL,
        item_metadata_json JSONB NULL
      );",

      "NavigationMenus" => "CREATE TABLE IF NOT EXISTS navigationmenus (
        menu_id SERIAL PRIMARY KEY,
        menu_name VARCHAR(150) NOT NULL,
        theme_location VARCHAR(100) NULL,
        menu_description TEXT NULL,
        created_at_menu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_menu_name UNIQUE (menu_name),
        CONSTRAINT uq_theme_location UNIQUE (theme_location)
      );",

      "MenuItems" => "CREATE TABLE IF NOT EXISTS menuitems (
        menu_item_id BIGSERIAL PRIMARY KEY,
        menu_id INT NOT NULL,
        link_title VARCHAR(200) NOT NULL,
        link_type link_type_type DEFAULT 'custom_url',
        linked_object_id BIGINT NULL,
        custom_url VARCHAR(512) NULL,
        parent_menu_item_id BIGINT NULL,
        item_order INT DEFAULT 0,
        link_target link_target_type DEFAULT '_self',
        additional_css_classes VARCHAR(255) NULL,
        created_at_menu_item TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );",

      "AuditTrail" => "CREATE TABLE IF NOT EXISTS audittrail (
        log_id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NULL,
        action_type VARCHAR(100) NOT NULL,
        target_id BIGINT NULL,
        target_table VARCHAR(100) NULL,
        details JSONB NULL,
        ip_address VARCHAR(45) NULL,
        created_at_audit TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );",

      "ProductVariants" => "CREATE TABLE IF NOT EXISTS productvariants (
        variant_id BIGSERIAL PRIMARY KEY,
        product_id BIGINT NOT NULL,
        variant_name VARCHAR(255) NOT NULL,
        sku VARCHAR(100) NULL,
        price_modifier DECIMAL(10, 2) DEFAULT 0.00,
        stock_quantity INT NOT NULL DEFAULT 0,
        created_at_variant TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_variant_sku UNIQUE (sku)
      );",

      "Addresses" => "CREATE TABLE IF NOT EXISTS addresses (
        address_id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL,
        address_type address_type_type NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        street VARCHAR(255) NOT NULL,
        apartment VARCHAR(100) NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100) NOT NULL,
        country VARCHAR(100) NOT NULL,
        postal_code VARCHAR(20) NOT NULL,
        phone_number VARCHAR(50) NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at_addresses TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );",

      "Payments" => "CREATE TABLE IF NOT EXISTS payments (
        payment_id BIGSERIAL PRIMARY KEY,
        order_id BIGINT NOT NULL,
        transaction_id VARCHAR(255) NOT NULL,
        payment_method VARCHAR(100) NOT NULL,
        amount DECIMAL(12, 2) NOT NULL,
        currency VARCHAR(10) NOT NULL DEFAULT 'USD',
        payment_status payment_status_type NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_transaction_id UNIQUE (transaction_id)
      );",

      "Notifications" => "CREATE TABLE IF NOT EXISTS notifications (
        notification_id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL,
        type VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        link_url VARCHAR(512) NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at_notification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );",

      "UserPreferences" => "CREATE TABLE IF NOT EXISTS userpreferences (
        preference_id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL,
        preference_key VARCHAR(100) NOT NULL,
        preference_value VARCHAR(255) NOT NULL,
        CONSTRAINT uq_user_preference UNIQUE (user_id, preference_key)
      );",

      "VisitorLog" => "CREATE TABLE IF NOT EXISTS visitorlog (
        id_visitor BIGSERIAL PRIMARY KEY,
        visitor_ip VARCHAR(50) NOT NULL,
        visitor_url VARCHAR(1000) NOT NULL,
        visitor_post_id BIGINT NULL,
        visitor_method VARCHAR(10) NOT NULL,
        visitor_country VARCHAR(50) NOT NULL,
        visitor_code_country VARCHAR(10) NOT NULL,
        visitor_state VARCHAR(50) NOT NULL,
        visitor_identifier VARCHAR(100) NOT NULL,
        visitor_user VARCHAR(100) NOT NULL,
        visitor_visitor SMALLINT NULL,
        visitor_time_record TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      );",

      "EmailRegister" => "CREATE TABLE IF NOT EXISTS emailregister (
        id_email_reg BIGSERIAL PRIMARY KEY,
        email_user_reg VARCHAR(255) NOT NULL,
        name_user_reg VARCHAR(255) NULL,
        phone_user_reg VARCHAR(50) NULL,
        message_user_reg TEXT NULL,
        ip_user_reg VARCHAR(100) NULL,
        country_user_reg VARCHAR(100) NULL,
        campaign_type VARCHAR(100) NULL,
        created_at_email_reg TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );",

      // --- NUEVO: Tabla unificada de interacciones (likes, favoritos, guardados, shares) ---
      "Interactions" => "CREATE TABLE IF NOT EXISTS interactions (
        id_interaction BIGSERIAL PRIMARY KEY,
        post_id BIGINT NOT NULL,
        user_id BIGINT NULL,
        guest_identifier VARCHAR(255) NULL,
        interaction_type interaction_type_type NOT NULL,
        created_at_interaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_user_interaction UNIQUE (post_id, user_id, interaction_type),
        CONSTRAINT uq_guest_interaction UNIQUE (post_id, guest_identifier, interaction_type)
      );",

      "Visits" => "CREATE TABLE IF NOT EXISTS visits (
        id SERIAL PRIMARY KEY,
        visited_user VARCHAR(100) NOT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        region VARCHAR(100) DEFAULT NULL,
        codigo VARCHAR(20) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        referer TEXT DEFAULT NULL,
        visited_at TIMESTAMP DEFAULT NULL,
        timestamp INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );"
    ];

    echo "--- Creando Tablas ---\n";
    foreach ($tablesSQL as $tableName => $sql) {
      $builder->query_foreign($sql);
      echo "Tabla '$tableName' creada o ya existente.\n";
    }
    echo "\n--- Fin de Creación de Tablas ---\n\n";

    // 3.5. Insertar datos por defecto
    $defaultDataSQL = [
      "Default_Roles" => "INSERT INTO roles (role_id, role_name, role_description) VALUES 
        (1, 'administrator', 'Acceso completo al sistema. Puede gestionar usuarios, configuraciones y todo el contenido.'),
        (2, 'editor', 'Puede editar y publicar contenido de cualquier autor. Gestiona categorías y etiquetas.'),
        (3, 'author', 'Puede crear, editar y publicar su propio contenido.'),
        (4, 'collaborator', 'Puede crear y editar su propio contenido, pero no puede publicarlo.'),
        (5, 'subscriber', 'Puede leer contenido y gestionar su perfil. Sin permisos de creación.')
      ON CONFLICT (role_id) DO UPDATE SET role_description = EXCLUDED.role_description;"
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

    // 4. Crear índices
    $indexesSQL = [
      "idx_avatar_media_id" => "CREATE INDEX IF NOT EXISTS idx_avatar_media_id ON users (avatar_media_id);",
      "idx_uploader_user_id_media" => "CREATE INDEX IF NOT EXISTS idx_uploader_user_id_media ON media (uploader_user_id);",
      "idx_mediable" => "CREATE INDEX IF NOT EXISTS idx_mediable ON mediables (mediable_id, mediable_type);",
      "idx_author_user_id_page" => "CREATE INDEX IF NOT EXISTS idx_author_user_id_page ON pages (author_user_id);",
      "idx_author_user_id_post" => "CREATE INDEX IF NOT EXISTS idx_author_user_id_post ON blogposts (author_user_id);",
      "idx_parent_category_id" => "CREATE INDEX IF NOT EXISTS idx_parent_category_id ON categories (parent_category_id);",
      "idx_associated_content" => "CREATE INDEX IF NOT EXISTS idx_associated_content ON comments (associated_content_id, associated_content_type);",
      "idx_commenter_user_id_comment" => "CREATE INDEX IF NOT EXISTS idx_commenter_user_id_comment ON comments (commenter_user_id);",
      "idx_parent_comment_id" => "CREATE INDEX IF NOT EXISTS idx_parent_comment_id ON comments (parent_comment_id);",
      "idx_survey_id_answer" => "CREATE INDEX IF NOT EXISTS idx_survey_id_answer ON surveyanswers (survey_id);",
      "idx_user_id_answer" => "CREATE INDEX IF NOT EXISTS idx_user_id_answer ON surveyanswers (survey_user_id);",
      "idx_featured_media_id" => "CREATE INDEX IF NOT EXISTS idx_featured_media_id ON products (featured_media_id);",
      "idx_customer_user_id_order" => "CREATE INDEX IF NOT EXISTS idx_customer_user_id_order ON orders (customer_user_id);",
      "idx_order_id_item" => "CREATE INDEX IF NOT EXISTS idx_order_id_item ON orderitems (order_id);",
      "idx_product_id_item" => "CREATE INDEX IF NOT EXISTS idx_product_id_item ON orderitems (product_id);",
      "idx_menu_id_item" => "CREATE INDEX IF NOT EXISTS idx_menu_id_item ON menuitems (menu_id);",
      "idx_parent_menu_item_id" => "CREATE INDEX IF NOT EXISTS idx_parent_menu_item_id ON menuitems (parent_menu_item_id);",
      "idx_action_type" => "CREATE INDEX IF NOT EXISTS idx_action_type ON audittrail (action_type);",
      "idx_target" => "CREATE INDEX IF NOT EXISTS idx_target ON audittrail (target_id, target_table);",
      "idx_user_id_audit" => "CREATE INDEX IF NOT EXISTS idx_user_id_audit ON audittrail (user_id);",
      "idx_product_id_variant" => "CREATE INDEX IF NOT EXISTS idx_product_id_variant ON productvariants (product_id);",
      "idx_user_id_address" => "CREATE INDEX IF NOT EXISTS idx_user_id_address ON addresses (user_id);",
      "idx_order_id_payment" => "CREATE INDEX IF NOT EXISTS idx_order_id_payment ON payments (order_id);",
      "idx_user_id_notification" => "CREATE INDEX IF NOT EXISTS idx_user_id_notification ON notifications (user_id);",
      "idx_visitor_post_id" => "CREATE INDEX IF NOT EXISTS idx_visitor_post_id ON visitorlog (visitor_post_id);",

      // --- Índices para la tabla Interactions ---
      "idx_post_id_interaction" => "CREATE INDEX IF NOT EXISTS idx_post_id_interaction ON interactions (post_id);",
      "idx_user_id_interaction" => "CREATE INDEX IF NOT EXISTS idx_user_id_interaction ON interactions (user_id);",
      "idx_interaction_type" => "CREATE INDEX IF NOT EXISTS idx_interaction_type ON interactions (interaction_type);"
    ];

    echo "--- Creando Índices ---\n";
    foreach ($indexesSQL as $indexName => $sql) {
      try {
        $builder->query_foreign($sql);
        echo "Índice '$indexName' creado o ya existente.\n";
      } catch (Exception $e) {
        echo "Advertencia en índice '$indexName': " . $e->getMessage() . "\n";
      }
    }
    echo "\n--- Fin de Creación de Índices ---\n\n";

    // 5. Definición de Alteraciones de Tablas (Añadir columnas para soft delete y 2FA)
    $alterationsSQL = [
      "Alter_Users_Add_SoftDelete_2FA" => "
        DO $$ BEGIN
          ALTER TABLE users ADD COLUMN deleted_at_user TIMESTAMP NULL DEFAULT NULL;
        EXCEPTION WHEN duplicate_column THEN null; END $$;
        DO $$ BEGIN
          ALTER TABLE users ADD COLUMN is_two_factor_enabled BOOLEAN NOT NULL DEFAULT FALSE;
        EXCEPTION WHEN duplicate_column THEN null; END $$;
        DO $$ BEGIN
          ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL DEFAULT NULL;
        EXCEPTION WHEN duplicate_column THEN null; END $$;",

      "Alter_Users_Add_Bio" => "DO $$ BEGIN ALTER TABLE users ADD COLUMN bio TEXT NULL; EXCEPTION WHEN duplicate_column THEN null; END $$;",

      "Alter_Pages_Add_SoftDelete" => "DO $$ BEGIN ALTER TABLE pages ADD COLUMN deleted_at_page TIMESTAMP NULL DEFAULT NULL; EXCEPTION WHEN duplicate_column THEN null; END $$;",
      "Alter_BlogPosts_Add_SoftDelete" => "DO $$ BEGIN ALTER TABLE blogposts ADD COLUMN deleted_at_blog_post TIMESTAMP NULL DEFAULT NULL; EXCEPTION WHEN duplicate_column THEN null; END $$;",
      "Alter_Comments_Add_SoftDelete" => "DO $$ BEGIN ALTER TABLE comments ADD COLUMN deleted_at_comment TIMESTAMP NULL DEFAULT NULL; EXCEPTION WHEN duplicate_column THEN null; END $$;",
      "Alter_Products_Add_SoftDelete" => "DO $$ BEGIN ALTER TABLE products ADD COLUMN deleted_at_product TIMESTAMP NULL DEFAULT NULL; EXCEPTION WHEN duplicate_column THEN null; END $$;"
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

    // Índices para soft delete
    $softDeleteIndexes = [
      "idx_deleted_at_user" => "CREATE INDEX IF NOT EXISTS idx_deleted_at_user ON users (deleted_at_user);",
      "idx_deleted_at_page" => "CREATE INDEX IF NOT EXISTS idx_deleted_at_page ON pages (deleted_at_page);",
      "idx_deleted_at_blog_post" => "CREATE INDEX IF NOT EXISTS idx_deleted_at_blog_post ON blogposts (deleted_at_blog_post);",
      "idx_deleted_at_comment" => "CREATE INDEX IF NOT EXISTS idx_deleted_at_comment ON comments (deleted_at_comment);",
      "idx_deleted_at_product" => "CREATE INDEX IF NOT EXISTS idx_deleted_at_product ON products (deleted_at_product);"
    ];

    foreach ($softDeleteIndexes as $indexName => $sql) {
      try {
        $builder->query_foreign($sql);
      } catch (Exception $e) {
        // Ignorar si ya existe
      }
    }

    // 6. Definición y Creación de Claves Foráneas
    $foreignKeysSQL = [
      "FK_UserRoles_Users" => "DO $$ BEGIN ALTER TABLE userroles ADD CONSTRAINT fk_userroles_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_UserRoles_Roles" => "DO $$ BEGIN ALTER TABLE userroles ADD CONSTRAINT fk_userroles_roles FOREIGN KEY (id_role) REFERENCES roles (role_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_Pages_Users" => "DO $$ BEGIN ALTER TABLE pages ADD CONSTRAINT fk_pages_users FOREIGN KEY (author_user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_BlogPosts_Users" => "DO $$ BEGIN ALTER TABLE blogposts ADD CONSTRAINT fk_blogposts_users FOREIGN KEY (author_user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_Categories_Self" => "DO $$ BEGIN ALTER TABLE categories ADD CONSTRAINT fk_categories_self FOREIGN KEY (parent_category_id) REFERENCES categories (category_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_BlogPostCategories_BlogPosts" => "DO $$ BEGIN ALTER TABLE blogpostcategories ADD CONSTRAINT fk_blogpostcat_blogposts FOREIGN KEY (post_id) REFERENCES blogposts (post_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_BlogPostCategories_Categories" => "DO $$ BEGIN ALTER TABLE blogpostcategories ADD CONSTRAINT fk_blogpostcat_categories FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_BlogPostTags_BlogPosts" => "DO $$ BEGIN ALTER TABLE blogposttags ADD CONSTRAINT fk_blogposttags_blogposts FOREIGN KEY (post_id) REFERENCES blogposts (post_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_BlogPostTags_Tags" => "DO $$ BEGIN ALTER TABLE blogposttags ADD CONSTRAINT fk_blogposttags_tags FOREIGN KEY (tag_id) REFERENCES tags (tag_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_Comments_Users" => "DO $$ BEGIN ALTER TABLE comments ADD CONSTRAINT fk_comments_users FOREIGN KEY (commenter_user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Comments_Self" => "DO $$ BEGIN ALTER TABLE comments ADD CONSTRAINT fk_comments_self FOREIGN KEY (parent_comment_id) REFERENCES comments (comment_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_ProductCategories_Products" => "DO $$ BEGIN ALTER TABLE productcategories ADD CONSTRAINT fk_productcat_products FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_ProductCategories_Categories" => "DO $$ BEGIN ALTER TABLE productcategories ADD CONSTRAINT fk_productcat_categories FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_ProductTags_Products" => "DO $$ BEGIN ALTER TABLE producttags ADD CONSTRAINT fk_producttags_products FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_ProductTags_Tags" => "DO $$ BEGIN ALTER TABLE producttags ADD CONSTRAINT fk_producttags_tags FOREIGN KEY (tag_id) REFERENCES tags (tag_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_Orders_Users" => "DO $$ BEGIN ALTER TABLE orders ADD CONSTRAINT fk_orders_users FOREIGN KEY (customer_user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_OrderItems_Orders" => "DO $$ BEGIN ALTER TABLE orderitems ADD CONSTRAINT fk_orderitems_orders FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_OrderItems_Products" => "DO $$ BEGIN ALTER TABLE orderitems ADD CONSTRAINT fk_orderitems_products FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE RESTRICT ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_Media_Users" => "DO $$ BEGIN ALTER TABLE media ADD CONSTRAINT fk_media_users FOREIGN KEY (uploader_user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Mediables_Media" => "DO $$ BEGIN ALTER TABLE mediables ADD CONSTRAINT fk_mediables_media FOREIGN KEY (ref_media_id) REFERENCES media (media_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_Users_Media_Avatar" => "DO $$ BEGIN ALTER TABLE users ADD CONSTRAINT fk_users_media_avatar FOREIGN KEY (avatar_media_id) REFERENCES media (media_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Products_Media_Featured" => "DO $$ BEGIN ALTER TABLE products ADD CONSTRAINT fk_products_media_featured FOREIGN KEY (featured_media_id) REFERENCES media (media_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_MenuItems_NavigationMenus" => "DO $$ BEGIN ALTER TABLE menuitems ADD CONSTRAINT fk_menuitems_navmenus FOREIGN KEY (menu_id) REFERENCES navigationmenus (menu_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_MenuItems_Self" => "DO $$ BEGIN ALTER TABLE menuitems ADD CONSTRAINT fk_menuitems_self FOREIGN KEY (parent_menu_item_id) REFERENCES menuitems (menu_item_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      "FK_AuditTrail_Users" => "DO $$ BEGIN ALTER TABLE audittrail ADD CONSTRAINT fk_audittrail_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_ProductVariants_Products" => "DO $$ BEGIN ALTER TABLE productvariants ADD CONSTRAINT fk_productvariants_products FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Addresses_Users" => "DO $$ BEGIN ALTER TABLE addresses ADD CONSTRAINT fk_addresses_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Payments_Orders" => "DO $$ BEGIN ALTER TABLE payments ADD CONSTRAINT fk_payments_orders FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE RESTRICT ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Notifications_Users" => "DO $$ BEGIN ALTER TABLE notifications ADD CONSTRAINT fk_notifications_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_UserPreferences_Users" => "DO $$ BEGIN ALTER TABLE userpreferences ADD CONSTRAINT fk_userpreferences_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_VisitorLog_BlogPosts" => "DO $$ BEGIN ALTER TABLE visitorlog ADD CONSTRAINT fk_visitorlog_blogposts FOREIGN KEY (visitor_post_id) REFERENCES blogposts (post_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      // Claves foráneas para las tablas de encuestas
      "FK_Survey_BlogPosts" => "DO $$ BEGIN ALTER TABLE survey ADD CONSTRAINT fk_survey_blogposts FOREIGN KEY (id_blogpost) REFERENCES blogposts (post_id) ON DELETE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_SurveyAnswers_Survey" => "DO $$ BEGIN ALTER TABLE surveyanswers ADD CONSTRAINT fk_surveyanswers_survey FOREIGN KEY (survey_id) REFERENCES survey (id_survey) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_SurveyAnswers_Users" => "DO $$ BEGIN ALTER TABLE surveyanswers ADD CONSTRAINT fk_surveyanswers_users FOREIGN KEY (survey_user_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",

      // --- AÑADIDO: Claves foráneas para la tabla Interactions ---
      "FK_Interactions_BlogPosts" => "DO $$ BEGIN ALTER TABLE interactions ADD CONSTRAINT fk_interactions_blogposts FOREIGN KEY (post_id) REFERENCES blogposts (post_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;",
      "FK_Interactions_Users" => "DO $$ BEGIN ALTER TABLE interactions ADD CONSTRAINT fk_interactions_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE; EXCEPTION WHEN duplicate_object THEN null; END $$;"
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

    // 7. Crear funciones de trigger para updated_at (PostgreSQL no tiene ON UPDATE CURRENT_TIMESTAMP)
    $triggerFunction = "
      CREATE OR REPLACE FUNCTION update_updated_at_column()
      RETURNS TRIGGER AS \$\$
      BEGIN
        NEW.updated_at_user = CURRENT_TIMESTAMP;
        RETURN NEW;
      END;
      \$\$ language 'plpgsql';
    ";

    try {
      $builder->query_foreign($triggerFunction);
      echo "Función de trigger 'update_updated_at_column' creada.\n";
    } catch (Exception $e) {
      echo "Advertencia creando función de trigger: " . $e->getMessage() . "\n";
    }

    // Crear triggers para tablas con updated_at
    $triggers = [
      "users" => "updated_at_user",
      "sitesettings" => "updated_at_site_setting",
      "pages" => "updated_at_page",
      "blogposts" => "updated_at_blog_post",
      "products" => "updated_at_product",
      "orders" => "updated_at_order"
    ];

    echo "\n--- Creando Triggers para updated_at ---\n";
    foreach ($triggers as $table => $column) {
      $triggerName = "trigger_update_{$table}_timestamp";
      $triggerSQL = "
        DROP TRIGGER IF EXISTS $triggerName ON $table;
        CREATE TRIGGER $triggerName
        BEFORE UPDATE ON $table
        FOR EACH ROW
        EXECUTE FUNCTION update_updated_at_column();
      ";
      try {
        $builder->query_foreign($triggerSQL);
        echo "Trigger '$triggerName' creado para tabla '$table'.\n";
      } catch (Exception $e) {
        echo "Advertencia en trigger '$triggerName': " . $e->getMessage() . "\n";
      }
    }
    echo "--- Fin de Creación de Triggers ---\n\n";

    echo "Proceso de configuración de base de datos PostgreSQL completado.\n";
  } catch (Exception $e) {
    echo "Error general en el proceso de configuración de la base de datos: " . $e->getMessage() . "\n";
  } finally {
    echo "</pre>";
  }
}

// Descomenta la siguiente línea para ejecutar la función cuando llames a este script
configInitBdPostgreSql();

-- TIO Perfume Collection — Database Schema
-- Import this once via phpMyAdmin (XAMPP) or the mysql CLI:
--   mysql -u root -p < schema.sql
-- Safe to re-import — every statement uses IF NOT EXISTS.

CREATE DATABASE IF NOT EXISTS tio_perfume
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tio_perfume;

-- Customer accounts. Logging in unlocks the account icon in the header
-- and lets a customer check out and see their own order history.
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)    NOT NULL,
    email           VARCHAR(190)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Staff accounts. Separate from `users` (customers) — logging in here
-- does not log you in as a customer and vice versa. Create the first
-- admin with create_admin.php (delete that file afterward).
CREATE TABLE IF NOT EXISTS admins (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(60)     NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- The perfume catalog shown on the Shop and Best Sellers pages.
-- `category` matches the pill filters on the Shop page (All / Floral /
-- Woody / Fresh / Gift Sets). `bestseller_rank` (1-5) drives the
-- numbered ribbon shown on the Best Sellers page; leave it NULL for a
-- product that isn't currently a bestseller.
CREATE TABLE IF NOT EXISTS products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                VARCHAR(150)    NOT NULL UNIQUE,
    name                VARCHAR(120)    NOT NULL,
    category            ENUM('floral', 'woody', 'fresh', 'gift-sets') NOT NULL DEFAULT 'floral',
    description         TEXT            NULL,
    price               DECIMAL(10,2)   NOT NULL,
    image               VARCHAR(190)    NOT NULL,
    is_bestseller       TINYINT(1)      NOT NULL DEFAULT 0,
    bestseller_rank     TINYINT UNSIGNED NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Per-product, per-size stock (e.g. 30ml / 50ml / 100ml). Only
-- admin.php (which requires an admin login) ever writes to this table
-- — the public site only reads from it, same as vehicle_colors did on
-- the rental site.
CREATE TABLE IF NOT EXISTS product_variants (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED    NOT NULL,
    size_label      VARCHAR(30)     NOT NULL,
    price_override  DECIMAL(10,2)   NULL,
    stock_qty       INT UNSIGNED    NOT NULL DEFAULT 0,
    in_stock        TINYINT(1)      NOT NULL DEFAULT 1,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_product_size (product_id, size_label),
    CONSTRAINT fk_variants_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- One open cart per logged-in customer, filled via the "Add to cart"
-- icon in the header. Removing an item deletes its row outright —
-- unlike an order, a cart has no history worth keeping.
CREATE TABLE IF NOT EXISTS carts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL UNIQUE,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_carts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id     INT UNSIGNED    NOT NULL,
    variant_id  INT UNSIGNED    NOT NULL,
    quantity    INT UNSIGNED    NOT NULL DEFAULT 1,
    added_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cart_variant (cart_id, variant_id),
    CONSTRAINT fk_cart_items_cart
        FOREIGN KEY (cart_id) REFERENCES carts(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_variant
        FOREIGN KEY (variant_id) REFERENCES product_variants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Orders placed at checkout. Unlike a cart, an order's rows are kept
-- forever for order history — cancelling sets order_status to
-- 'cancelled' rather than deleting anything.
CREATE TABLE IF NOT EXISTS orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED    NOT NULL,
    order_status        ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status      ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
    payment_method      VARCHAR(40)     NOT NULL DEFAULT 'cod',
    shipping_name       VARCHAR(120)    NOT NULL,
    shipping_phone      VARCHAR(30)     NOT NULL,
    shipping_address    VARCHAR(255)    NOT NULL,
    subtotal            DECIMAL(10,2)   NOT NULL DEFAULT 0,
    shipping_fee        DECIMAL(10,2)   NOT NULL DEFAULT 0,
    total               DECIMAL(10,2)   NOT NULL DEFAULT 0,
    placed_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at             DATETIME        NULL,
    cancelled_at        DATETIME        NULL,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Adds the columns above to an `orders` table created before this
-- update. Each column is only added if it's missing, using a dynamic-SQL
-- check against INFORMATION_SCHEMA instead of "ADD COLUMN IF NOT EXISTS"
-- — that shorthand needs MySQL 8.0.29+ / MariaDB 10.0+, and silently (or
-- loudly) fails on older versions, which is how a live site can end up
-- missing `payment_status`/`paid_at` even after "successfully" importing
-- this file. This version works on any MySQL 5.6+ / MariaDB version.
DELIMITER $$
CREATE PROCEDURE tio_add_column_if_missing(
    IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE ', p_table, ' ADD COLUMN ', p_column, ' ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL tio_add_column_if_missing('orders', 'payment_status', "ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid' AFTER order_status");
CALL tio_add_column_if_missing('orders', 'payment_method', "VARCHAR(40) NOT NULL DEFAULT 'cod' AFTER payment_status");
CALL tio_add_column_if_missing('orders', 'paid_at', "DATETIME NULL AFTER placed_at");
CALL tio_add_column_if_missing('orders', 'cancelled_at', "DATETIME NULL AFTER paid_at");

DROP PROCEDURE tio_add_column_if_missing;

-- Line items for an order. Product name, size, and price are copied in
-- at checkout time (a "snapshot") so an order still reads correctly
-- even if a product's price or catalog listing changes later.
CREATE TABLE IF NOT EXISTS order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED    NOT NULL,
    product_name    VARCHAR(120)    NOT NULL,
    size_label      VARCHAR(30)     NOT NULL,
    unit_price      DECIMAL(10,2)   NOT NULL,
    quantity        INT UNSIGNED    NOT NULL DEFAULT 1,
    line_total      DECIMAL(10,2)   NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Newsletter subscribers submitted through the footer form.
CREATE TABLE IF NOT EXISTS subscribers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(190)    NOT NULL UNIQUE,
    subscribed_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Messages submitted through the Contact page's form.
CREATE TABLE IF NOT EXISTS messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)    NOT NULL,
    email       VARCHAR(190)    NOT NULL,
    subject     VARCHAR(190)    NOT NULL,
    message     TEXT            NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed the 5 perfumes already shown on the Home / Shop / Best Sellers
-- pages, each ranked 1-5 to match the ribbon order on Best Sellers.
-- Safe to re-run: INSERT IGNORE skips rows that already exist.
INSERT IGNORE INTO products (slug, name, category, price, image, is_bestseller, bestseller_rank) VALUES
    ('lavender-bliss',     'Lavender Bliss',     'floral', 1290.00, 'img/perfume-lavender.png',   1, 1),
    ('velvet-temptation',  'Velvet Temptation',  'woody',  1450.00, 'img/velvet-temptation.png',  1, 2),
    ('mystic-love',        'Mystic Love',        'floral', 1350.00, 'img/mystic-love.png',        1, 3),
    ('glamorous-nectar',   'Glamorous Nectar',   'fresh',  1390.00, 'img/glamorous-nectar.png',   1, 4),
    ('serene-ocean',       'Serene Ocean',       'fresh',  1290.00, 'img/serene-ocean.png',       1, 5);

-- Seed one default size per perfume so the catalog has stock to sell
-- against right away. Add more rows (e.g. '30ml', '100ml') per product
-- as real sizes are confirmed.
INSERT IGNORE INTO product_variants (product_id, size_label, stock_qty, in_stock)
SELECT id, '50ml', 25, 1 FROM products;
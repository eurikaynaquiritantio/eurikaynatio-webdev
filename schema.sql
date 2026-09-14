-- TIO Perfume Collection — rebuilt database schema
-- Import in phpMyAdmin. Existing data can be kept; config.php also performs small compatibility upgrades.

CREATE DATABASE IF NOT EXISTS tio_perfume CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tio_perfume;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    category ENUM('floral','woody','fresh','gift-sets') NOT NULL DEFAULT 'floral',
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(190) NOT NULL,
    is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
    bestseller_rank TINYINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    size_label VARCHAR(30) NOT NULL,
    price_override DECIMAL(10,2) NULL,
    stock_qty INT UNSIGNED NOT NULL DEFAULT 0,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_product_size (product_id, size_label),
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS carts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cart_variant (cart_id, variant_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    order_status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid','for_verification','paid','refunded') NOT NULL DEFAULT 'unpaid',
    payment_method VARCHAR(40) NOT NULL DEFAULT 'cod',
    payment_reference VARCHAR(120) NULL,
    bank_name VARCHAR(120) NULL,
    shipping_name VARCHAR(120) NOT NULL,
    shipping_phone VARCHAR(30) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    placed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    variant_id INT UNSIGNED NULL,
    product_name VARCHAR(120) NOT NULL,
    size_label VARCHAR(30) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vip_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    birthday DATE NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO products (slug, name, category, price, image, is_bestseller, bestseller_rank) VALUES
('lavender-bliss','Lavender Bliss','floral',1290.00,'img/perfume-lavender.png',1,1),
('velvet-temptation','Velvet Temptation','woody',1450.00,'img/velvet-temptation.png',1,2),
('mystic-love','Mystic Love','floral',1350.00,'img/mystic-love.png',1,3),
('glamorous-nectar','Glamorous Nectar','fresh',1390.00,'img/glamorous-nectar.png',1,4),
('serene-ocean','Serene Ocean','fresh',1290.00,'img/serene-ocean.png',1,5);

INSERT IGNORE INTO product_variants (product_id, size_label, stock_qty, in_stock)
SELECT id, '50ml', 25, 1 FROM products;

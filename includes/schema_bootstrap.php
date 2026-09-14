<?php
/** Small, non-destructive compatibility migration for older copies of the project. */
function tio_column_info(PDO $pdo, string $table, string $column): ?array {
    $stmt = $pdo->prepare(
        'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $stmt->execute([$table, $column]);
    $row = $stmt->fetch();
    return $row ?: null;
}
function tio_column_exists(PDO $pdo, string $table, string $column): bool {
    return tio_column_info($pdo, $table, $column) !== null;
}
function ensure_tio_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    if (!tio_column_exists($pdo, 'users', 'role')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER email");
    }

    if (!tio_column_exists($pdo, 'orders', 'payment_status')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','for_verification','paid','refunded') NOT NULL DEFAULT 'unpaid' AFTER order_status");
    } else {
        $info = tio_column_info($pdo, 'orders', 'payment_status');
        if ($info && stripos((string)$info['COLUMN_TYPE'], 'for_verification') === false) {
            try {
                $pdo->exec("ALTER TABLE orders MODIFY payment_status ENUM('unpaid','for_verification','paid','refunded') NOT NULL DEFAULT 'unpaid'");
            } catch (Throwable $e) {
                // Fresh schema.sql already has the correct type; older restricted hosts can continue with their current type.
            }
        }
    }

    $orderColumns = [
        'payment_method' => "VARCHAR(40) NOT NULL DEFAULT 'cod' AFTER payment_status",
        'payment_reference' => "VARCHAR(120) NULL AFTER payment_method",
        'bank_name' => "VARCHAR(120) NULL AFTER payment_reference",
    ];
    foreach ($orderColumns as $name => $definition) {
        if (!tio_column_exists($pdo, 'orders', $name)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN {$name} {$definition}");
        }
    }

    if (!tio_column_exists($pdo, 'order_items', 'product_id')) {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN product_id INT UNSIGNED NULL AFTER order_id");
    }
    if (!tio_column_exists($pdo, 'order_items', 'variant_id')) {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN variant_id INT UNSIGNED NULL AFTER product_id");
    }
}

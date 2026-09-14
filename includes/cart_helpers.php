<?php
function cart_id_for_user(PDO $pdo, int $userId, bool $create = true): ?int {
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;
    if (!$create) return null;
    $stmt = $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)');
    $stmt->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

function cart_items_for_user(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        "SELECT ci.id AS cart_item_id, ci.quantity, pv.id AS variant_id, pv.size_label, pv.stock_qty, pv.in_stock,
                p.id AS product_id, p.name, p.image, p.price,
                COALESCE(pv.price_override, p.price) AS unit_price
         FROM carts c
         JOIN cart_items ci ON ci.cart_id = c.id
         JOIN product_variants pv ON pv.id = ci.variant_id
         JOIN products p ON p.id = pv.product_id
         WHERE c.user_id = ?
         ORDER BY ci.added_at DESC"
    );
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        $item['quantity'] = (int)$item['quantity'];
        $item['stock_qty'] = (int)$item['stock_qty'];
        $item['unit_price'] = (float)$item['unit_price'];
        $item['line_total'] = $item['unit_price'] * $item['quantity'];
    }
    unset($item);
    return $items;
}

function cart_totals(array $items): array {
    $subtotal = 0.0;
    $count = 0;
    foreach ($items as $item) {
        $subtotal += (float)$item['line_total'];
        $count += (int)$item['quantity'];
    }
    return ['subtotal'=>$subtotal,'count'=>$count];
}

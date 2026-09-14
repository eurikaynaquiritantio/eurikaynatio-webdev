<?php
function get_all_products(PDO $pdo, $category = null): array {
    $sql = "SELECT id, slug, name, category, description, price, image, is_bestseller, bestseller_rank FROM products";
    $params = [];
    if ($category && $category !== 'all') {
        $sql .= ' WHERE category = :category';
        $params[':category'] = $category;
    }
    $sql .= ' ORDER BY name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return attach_variants($pdo, $stmt->fetchAll());
}

function get_bestsellers(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, slug, name, category, description, price, image, is_bestseller, bestseller_rank FROM products WHERE is_bestseller = 1 ORDER BY bestseller_rank, name");
    return attach_variants($pdo, $stmt->fetchAll());
}

function attach_variants(PDO $pdo, array $products): array {
    if (!$products) return [];
    $ids = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, product_id, size_label, price_override, in_stock, stock_qty FROM product_variants WHERE product_id IN ($placeholders) ORDER BY size_label");
    $stmt->execute($ids);
    $byProduct = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['effective_price'] = $row['price_override'] !== null ? (float)$row['price_override'] : null;
        $byProduct[$row['product_id']][] = $row;
    }
    foreach ($products as &$product) {
        $variants = $byProduct[$product['id']] ?? [];
        foreach ($variants as &$variant) {
            if ($variant['effective_price'] === null) $variant['effective_price'] = (float)$product['price'];
        }
        unset($variant);
        $product['variants'] = $variants;
        $product['available_variants'] = array_values(array_filter($variants, fn($v) => (int)$v['in_stock'] === 1 && (int)$v['stock_qty'] > 0));
        $product['available_sizes'] = array_map(fn($v) => $v['size_label'], $product['available_variants']);
        $product['is_out_of_stock'] = empty($product['available_variants']);
    }
    unset($product);
    return $products;
}

function category_labels(): array {
    return ['floral'=>'Floral','woody'=>'Woody','fresh'=>'Fresh','gift-sets'=>'Gift Sets'];
}

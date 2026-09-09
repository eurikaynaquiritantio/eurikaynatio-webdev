<?php
/**
 * product_functions.php — shared helpers for reading the perfume
 * catalog (products + product_variants). Used by shop.php and
 * best-sellers.php so the same stock-aware query logic doesn't get
 * copy-pasted into every page. Only admin.php/adminupdatestock.php
 * ever WRITE to product_variants — every page here only reads.
 */

/**
 * All products, optionally filtered by category, each with its size
 * variants attached (see attach_variants()).
 */
function get_all_products(PDO $pdo, $category = null) {
    $sql = "SELECT id, slug, name, category, price, image, is_bestseller, bestseller_rank FROM products";
    $params = [];

    if ($category && $category !== 'all') {
        $sql .= " WHERE category = :category";
        $params[':category'] = $category;
    }

    $sql .= " ORDER BY name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return attach_variants($pdo, $stmt->fetchAll());
}

/** Only the bestsellers, ordered by their rank (1-5). */
function get_bestsellers(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT id, slug, name, category, price, image, is_bestseller, bestseller_rank
         FROM products
         WHERE is_bestseller = 1
         ORDER BY bestseller_rank"
    );

    return attach_variants($pdo, $stmt->fetchAll());
}

/**
 * Attaches each product's size variants, plus two convenience fields:
 *   available_sizes  — the size labels currently in stock
 *   is_out_of_stock  — true when no size is in stock
 */
function attach_variants(PDO $pdo, $products) {
    if (empty($products)) {
        return [];
    }

    $ids = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT product_id, size_label, in_stock, stock_qty
         FROM product_variants
         WHERE product_id IN ($placeholders)
         ORDER BY size_label"
    );

    $stmt->execute($ids);

    $variantsByProduct = [];

    foreach ($stmt->fetchAll() as $row) {
        $variantsByProduct[$row['product_id']][] = $row;
    }

    foreach ($products as &$product) {
        $variants = $variantsByProduct[$product['id']] ?? [];

        $product['variants'] = $variants;

        $product['available_sizes'] = array_values(
            array_filter(
                array_map(
                    function ($v) {
                        return $v['in_stock'] ? $v['size_label'] : null;
                    },
                    $variants
                )
            )
        );

        $product['is_out_of_stock'] = empty($product['available_sizes']);
    }

    unset($product);

    return $products;
}

/** Category keys -> display labels, used for the Shop page filter pills. */
function category_labels() {
    return [
        "floral" => "Floral",
        "woody"  => "Woody",
        "fresh"  => "Fresh",
    ];
}
<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/cart_helpers.php';
$user = auth_require_api_role('user');
$items = cart_items_for_user($pdo, (int)$user['id']);
$totals = cart_totals($items);
echo json_encode(['ok'=>true,'items'=>$items,'subtotal'=>$totals['subtotal'],'count'=>$totals['count']]);

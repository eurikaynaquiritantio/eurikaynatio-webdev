<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
$admin = auth_require_api_role('admin');

function scalar(PDO $pdo, string $sql) { return $pdo->query($sql)->fetchColumn(); }
$stats = [
    'orders' => (int)scalar($pdo, 'SELECT COUNT(*) FROM orders'),
    'sales' => (float)scalar($pdo, "SELECT COALESCE(SUM(total),0) FROM orders WHERE order_status <> 'cancelled'"),
    'customers' => (int)scalar($pdo, "SELECT COUNT(*) FROM users WHERE role='user'"),
    'products' => (int)scalar($pdo, 'SELECT COUNT(*) FROM products'),
    'vip' => (int)scalar($pdo, 'SELECT COUNT(*) FROM vip_members'),
    'messages' => (int)scalar($pdo, 'SELECT COUNT(*) FROM messages'),
    'low_stock' => (int)scalar($pdo, 'SELECT COUNT(*) FROM product_variants WHERE stock_qty <= 5'),
];

$orders = $pdo->query(
    "SELECT o.id,o.order_status,o.payment_status,o.payment_method,o.payment_reference,o.bank_name,o.total,o.placed_at,u.name AS customer_name,u.email AS customer_email
     FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.placed_at DESC LIMIT 12"
)->fetchAll();

$rows = $pdo->query(
    "SELECT p.id,p.slug,p.name,p.category,p.description,p.price,p.image,p.is_bestseller,p.bestseller_rank,
            pv.id AS variant_id,pv.size_label,pv.price_override,pv.stock_qty,pv.in_stock
     FROM products p LEFT JOIN product_variants pv ON pv.product_id=p.id
     ORDER BY p.name,pv.size_label"
)->fetchAll();
$products=[];
foreach($rows as $r){
    $id=(int)$r['id'];
    if(!isset($products[$id])) $products[$id]=[
        'id'=>$id,'slug'=>$r['slug'],'name'=>$r['name'],'category'=>$r['category'],'description'=>$r['description'],'price'=>(float)$r['price'],'image'=>$r['image'],'is_bestseller'=>(int)$r['is_bestseller'],'bestseller_rank'=>$r['bestseller_rank'],'variants'=>[]
    ];
    if($r['variant_id']!==null)$products[$id]['variants'][]=['id'=>(int)$r['variant_id'],'size_label'=>$r['size_label'],'price_override'=>$r['price_override'],'stock_qty'=>(int)$r['stock_qty'],'in_stock'=>(int)$r['in_stock']];
}

echo json_encode(['ok'=>true,'admin'=>['name'=>$admin['name'],'email'=>$admin['email']],'stats'=>$stats,'orders'=>$orders,'products'=>array_values($products)]);

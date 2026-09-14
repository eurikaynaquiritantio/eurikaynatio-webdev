<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
$admin=auth_require_api_role('admin');
if($_SERVER['REQUEST_METHOD']!=='POST')auth_json_error('Method not allowed.',405);
$action=$_POST['action']??'';

function slugify_tio(string $text): string {
    $text=strtolower(trim($text));$text=preg_replace('/[^a-z0-9]+/','-',$text);return trim($text,'-')?:'product';
}
function save_product_image(): ?string {
    if(empty($_FILES['image_file'])||$_FILES['image_file']['error']===UPLOAD_ERR_NO_FILE)return null;
    $f=$_FILES['image_file'];
    if($f['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
    if($f['size']>5*1024*1024) throw new RuntimeException('Image must be 5 MB or smaller.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if(!$ext) throw new RuntimeException('Use JPG, PNG, or WEBP images.');
    $name='product-'.bin2hex(random_bytes(6)).'.'.$ext;
    $dir=__DIR__.'/img/uploads';if(!is_dir($dir))mkdir($dir,0775,true);
    if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Could not save image.');
    return 'img/uploads/'.$name;
}

try{
    if($action==='add_product'){
        $name=trim($_POST['name']??'');$category=$_POST['category']??'floral';$price=(float)($_POST['price']??0);$description=trim($_POST['description']??'');$size=trim($_POST['size_label']??'50ml');$stock=max(0,(int)($_POST['stock_qty']??0));
        if($name===''||$price<=0)throw new RuntimeException('Enter a product name and valid price.');
        if(!in_array($category,['floral','woody','fresh','gift-sets'],true))$category='floral';
        $image=save_product_image() ?: 'img/perfume-lavender.png';
        $slug=slugify_tio($name);$base=$slug;$n=2;
        $check=$pdo->prepare('SELECT COUNT(*) FROM products WHERE slug=?');while(true){$check->execute([$slug]);if(!(int)$check->fetchColumn())break;$slug=$base.'-'.$n++;}
        $pdo->beginTransaction();
        $stmt=$pdo->prepare('INSERT INTO products (slug,name,category,description,price,image,is_bestseller,bestseller_rank) VALUES (?,?,?,?,?,?,?,?)');
        $rank=trim($_POST['bestseller_rank']??'');$best=!empty($_POST['is_bestseller'])?1:0;
        $stmt->execute([$slug,$name,$category,$description?:null,$price,$image,$best,$rank!==''?(int)$rank:null]);
        $pid=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO product_variants (product_id,size_label,stock_qty,in_stock) VALUES (?,?,?,?)')->execute([$pid,$size?:'50ml',$stock,$stock>0?1:0]);
        $pdo->commit();echo json_encode(['ok'=>true,'message'=>'Product added.']);exit;
    }
    if($action==='edit_product'){
        $id=(int)($_POST['product_id']??0);$name=trim($_POST['name']??'');$category=$_POST['category']??'floral';$price=(float)($_POST['price']??0);$description=trim($_POST['description']??'');
        if(!$id||$name===''||$price<=0)throw new RuntimeException('Invalid product information.');
        $image=save_product_image();$best=!empty($_POST['is_bestseller'])?1:0;$rank=trim($_POST['bestseller_rank']??'');
        $sql='UPDATE products SET name=?,category=?,description=?,price=?,is_bestseller=?,bestseller_rank=?'.($image?',image=?':'').' WHERE id=?';
        $params=[$name,$category,$description?:null,$price,$best,$rank!==''?(int)$rank:null];if($image)$params[]=$image;$params[]=$id;$pdo->prepare($sql)->execute($params);
        echo json_encode(['ok'=>true,'message'=>'Product updated.']);exit;
    }
    if($action==='delete_product'){
        $id=(int)($_POST['product_id']??0);if(!$id)throw new RuntimeException('Invalid product.');$pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);echo json_encode(['ok'=>true,'message'=>'Product deleted.']);exit;
    }
    if($action==='update_variant'){
        $id=(int)($_POST['variant_id']??0);$stock=max(0,(int)($_POST['stock_qty']??0));$size=trim($_POST['size_label']??'');if(!$id||$size==='')throw new RuntimeException('Invalid stock information.');
        $pdo->prepare('UPDATE product_variants SET size_label=?,stock_qty=?,in_stock=? WHERE id=?')->execute([$size,$stock,$stock>0?1:0,$id]);echo json_encode(['ok'=>true,'message'=>'Stock updated.']);exit;
    }
    if($action==='add_variant'){
        $pid=(int)($_POST['product_id']??0);$size=trim($_POST['size_label']??'');$stock=max(0,(int)($_POST['stock_qty']??0));if(!$pid||$size==='')throw new RuntimeException('Enter a size.');
        $pdo->prepare('INSERT INTO product_variants (product_id,size_label,stock_qty,in_stock) VALUES (?,?,?,?)')->execute([$pid,$size,$stock,$stock>0?1:0]);echo json_encode(['ok'=>true,'message'=>'Size added.']);exit;
    }
    if($action==='update_order'){
        $id=(int)($_POST['order_id']??0);$os=$_POST['order_status']??'pending';$ps=$_POST['payment_status']??'unpaid';
        if(!in_array($os,['pending','processing','shipped','delivered','cancelled'],true)||!in_array($ps,['unpaid','for_verification','paid','refunded'],true))throw new RuntimeException('Invalid order status.');
        $paidAt=$ps==='paid'?date('Y-m-d H:i:s'):null;$cancelled=$os==='cancelled'?date('Y-m-d H:i:s'):null;
        $pdo->prepare('UPDATE orders SET order_status=?,payment_status=?,paid_at=?,cancelled_at=? WHERE id=?')->execute([$os,$ps,$paidAt,$cancelled,$id]);echo json_encode(['ok'=>true,'message'=>'Order updated.']);exit;
    }
    throw new RuntimeException('Unknown action.');
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();auth_json_error($e instanceof RuntimeException?$e->getMessage():'Could not save changes.',422);}

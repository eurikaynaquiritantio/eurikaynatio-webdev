<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/validation.php';
auth_require_api_role('user');
if($_SERVER['REQUEST_METHOD']!=='POST')auth_json_error('Method not allowed.',405);
$name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$birthday=trim($_POST['birthday']??'');$errors=[];
if(($e=validate_name($name))!=='')$errors[]=$e;if(($e=validate_email($email))!=='')$errors[]=$e;if(($e=validate_birthday($birthday))!=='')$errors[]=$e;if($errors)auth_json_error(implode(' ',$errors),422);
try{$stmt=$pdo->prepare('INSERT INTO vip_members (name,email,birthday) VALUES (?,?,?)');$stmt->execute([$name,$email,$birthday!==''?$birthday:null]);echo json_encode(['ok'=>true,'message'=>'Welcome to the VIP TIO Perfume Club, '.$name.'!']);}
catch(PDOException $e){if($e->getCode()==='23000')echo json_encode(['ok'=>true,'message'=>'You are already a VIP member.']);else auth_json_error('Could not complete your signup.',500);}

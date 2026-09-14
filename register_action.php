<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/validation.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') auth_json_error('Method not allowed.', 405);

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$errors = [];
if (($e = validate_name($name)) !== '') $errors[] = $e;
if (($e = validate_email($email)) !== '') $errors[] = $e;
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm) $errors[] = 'Passwords do not match.';
if ($errors) auth_json_error(implode(' ', $errors), 422);

$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) auth_json_error('An account with that email already exists.', 409);

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, role, password_hash) VALUES (?, ?, 'user', ?)");
$stmt->execute([$name, $email, $hash]);
$account = ['id'=>(int)$pdo->lastInsertId(), 'name'=>$name, 'email'=>$email, 'role'=>'user'];
$ctx = auth_create_context($account);
echo json_encode(['ok'=>true,'ctx'=>$ctx,'role'=>'user','redirect'=>'index.php','user'=>$account]);

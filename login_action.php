<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_json_error('Method not allowed.', 405);
}

$identifier = trim($_POST['identifier'] ?? $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if ($identifier === '' || $password === '') {
    auth_json_error('Please enter your email/username and password.', 422);
}

$stmt = $pdo->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$identifier]);
$account = $stmt->fetch();

// Compatibility with the original separate admins table.
if (!$account) {
    try {
        $legacy = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
        $legacy->execute([$identifier]);
        $oldAdmin = $legacy->fetch();
        if ($oldAdmin && password_verify($password, $oldAdmin['password_hash'])) {
            $legacyEmail = strtolower(preg_replace('/[^a-z0-9._-]/i', '', $oldAdmin['username'])) . '@admin.local';
            $insert = $pdo->prepare("INSERT INTO users (name, email, role, password_hash) VALUES (?, ?, 'admin', ?)");
            try {
                $insert->execute([$oldAdmin['username'], $legacyEmail, $oldAdmin['password_hash']]);
                $newId = (int)$pdo->lastInsertId();
            } catch (PDOException $e) {
                $find = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $find->execute([$legacyEmail]);
                $newId = (int)$find->fetchColumn();
            }
            $account = [
                'id' => $newId,
                'name' => $oldAdmin['username'],
                'email' => $legacyEmail,
                'role' => 'admin',
                'password_hash' => $oldAdmin['password_hash'],
            ];
        }
    } catch (Throwable $e) {
        // The old admins table may not exist in a fresh rebuilt database.
    }
}

if (!$account || !password_verify($password, $account['password_hash'])) {
    auth_json_error('Incorrect login information.', 401);
}

$ctx = auth_create_context($account);
echo json_encode([
    'ok' => true,
    'ctx' => $ctx,
    'role' => $account['role'],
    'redirect' => $account['role'] === 'admin' ? 'admin.php' : 'index.php',
    'user' => ['id'=>(int)$account['id'], 'name'=>$account['name'], 'email'=>$account['email'], 'role'=>$account['role']],
]);

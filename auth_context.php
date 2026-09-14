<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';

$account = auth_context();
if (!$account) {
    echo json_encode(['ok' => true, 'loggedIn' => false, 'redirect' => 'login.php']);
    exit;
}

echo json_encode([
    'ok' => true,
    'loggedIn' => true,
    'role' => $account['role'],
    'user' => [
        'id' => (int)$account['id'],
        'name' => $account['name'],
        'email' => $account['email'],
        'role' => $account['role'],
    ],
    'redirect' => $account['role'] === 'admin' ? 'admin.php' : 'index.php',
]);

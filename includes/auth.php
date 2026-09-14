<?php
require_once __DIR__ . '/../config.php';

const TIO_CONTEXT_KEY = 'tio_tab_ctx';
const TIO_CONTEXT_TTL = 60 * 60 * 24 * 30;

function auth_request_context(): string {
    $header = $_SERVER['HTTP_X_TAB_CONTEXT'] ?? '';
    if ($header !== '') return trim($header);
    if (!empty($_POST['tab_ctx'])) return trim((string)$_POST['tab_ctx']);
    if (!empty($_GET['tab_ctx'])) return trim((string)$_GET['tab_ctx']);
    return '';
}

function auth_cleanup_contexts(): void {
    if (empty($_SESSION['tab_contexts']) || !is_array($_SESSION['tab_contexts'])) return;
    $cutoff = time() - TIO_CONTEXT_TTL;
    foreach ($_SESSION['tab_contexts'] as $token => $ctx) {
        if (($ctx['last_seen'] ?? 0) < $cutoff) {
            unset($_SESSION['tab_contexts'][$token]);
        }
    }
}

function auth_create_context(array $account): string {
    auth_cleanup_contexts();
    $token = bin2hex(random_bytes(32));
    $_SESSION['tab_contexts'][$token] = [
        'user_id' => (int)$account['id'],
        'role' => $account['role'],
        'created_at' => time(),
        'last_seen' => time(),
    ];
    return $token;
}

function auth_context(?string $token = null): ?array {
    global $pdo;
    auth_cleanup_contexts();
    $token = $token ?? auth_request_context();
    if ($token === '' || empty($_SESSION['tab_contexts'][$token])) return null;

    $ctx = $_SESSION['tab_contexts'][$token];
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$ctx['user_id']]);
    $account = $stmt->fetch();
    if (!$account || $account['role'] !== $ctx['role']) {
        unset($_SESSION['tab_contexts'][$token]);
        return null;
    }
    $_SESSION['tab_contexts'][$token]['last_seen'] = time();
    $account['ctx'] = $token;
    return $account;
}

function auth_destroy_context(string $token): void {
    if ($token !== '' && isset($_SESSION['tab_contexts'][$token])) {
        unset($_SESSION['tab_contexts'][$token]);
    }
}

function auth_json_error(string $message, int $status = 401, array $extra = []): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => false, 'error' => $message], $extra));
    exit;
}

function auth_require_api_role(string $role): array {
    $account = auth_context();
    if (!$account) {
        auth_json_error('Please log in first.', 401, ['redirect' => 'login.php']);
    }
    if ($account['role'] !== $role) {
        auth_json_error('This tab belongs to a different dashboard.', 403, [
            'redirect' => $account['role'] === 'admin' ? 'admin.php' : 'index.php',
            'role' => $account['role'],
        ]);
    }
    return $account;
}

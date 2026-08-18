<?php
require __DIR__ . '/../_bootstrap.php';

require_method('POST');

if (count(load_admin_accounts()) > 0) {
    respond(['error' => 'Admin account already exists'], 409);
}

$body = json_input();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || strlen($username) < 3) {
    respond(['error' => 'Username must be at least 3 characters'], 400);
}
if (strlen($password) < 8) {
    respond(['error' => 'Password must be at least 8 characters'], 400);
}

$account = default_account($username, password_hash($password, PASSWORD_DEFAULT));
save_admin_accounts([$account]);
log_activity($username, 'account_created', 'Initial admin account created');

session_regenerate_id(true);
$_SESSION['admin_authenticated'] = true;
$_SESSION['admin_id'] = $account['id'];

respond(['ok' => true]);

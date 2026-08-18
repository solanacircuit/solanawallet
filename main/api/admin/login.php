<?php
require __DIR__ . '/../_bootstrap.php';

require_method('POST');

$accounts = load_admin_accounts();
if (count($accounts) === 0) {
    respond(['error' => 'No admin account exists yet'], 409);
}

$body = json_input();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

$account = null;
foreach ($accounts as $acc) {
    if (hash_equals((string)$acc['username'], $username)) { $account = $acc; break; }
}

if ($account === null) {
    respond(['error' => 'Invalid username or password'], 401);
}

$now = time();
if (!empty($account['lockedUntil']) && $account['lockedUntil'] > $now) {
    $wait = $account['lockedUntil'] - $now;
    respond(['error' => "Too many failed attempts. Try again in {$wait}s."], 429);
}

if (!password_verify($password, $account['passwordHash'])) {
    $account['failedAttempts'] = (int)($account['failedAttempts'] ?? 0) + 1;
    if ($account['failedAttempts'] >= 5) {
        $account['lockedUntil'] = $now + 300;
        $account['failedAttempts'] = 0;
    }
    save_admin_accounts(replace_account($accounts, $account));
    log_activity($username, 'login_failed');
    respond(['error' => 'Invalid username or password'], 401);
}

$account['failedAttempts'] = 0;
$account['lockedUntil'] = 0;
save_admin_accounts(replace_account($accounts, $account));

session_regenerate_id(true);

if (!empty($account['totpEnabled'])) {
    $_SESSION['pending_admin_id'] = $account['id'];
    log_activity($username, 'login_password_ok_awaiting_totp');
    respond(['ok' => true, 'totpRequired' => true]);
}

$_SESSION['admin_authenticated'] = true;
$_SESSION['admin_id'] = $account['id'];
log_activity($username, 'login');

respond(['ok' => true, 'totpRequired' => false]);

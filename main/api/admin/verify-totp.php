<?php
require __DIR__ . '/../_bootstrap.php';
require __DIR__ . '/../lib/totp.php';

require_method('POST');

$pendingId = $_SESSION['pending_admin_id'] ?? null;
if (!$pendingId) {
    respond(['error' => 'No login is awaiting a 2FA code'], 401);
}

$accounts = load_admin_accounts();
$account = find_account($accounts, 'id', $pendingId);
if ($account === null || empty($account['totpEnabled']) || empty($account['totpSecret'])) {
    unset($_SESSION['pending_admin_id']);
    respond(['error' => 'Invalid login state — please log in again'], 401);
}

$body = json_input();
$code = (string)($body['code'] ?? '');

if (!totp_verify($account['totpSecret'], $code)) {
    log_activity($account['username'], 'totp_failed');
    respond(['error' => 'Invalid or expired code'], 401);
}

unset($_SESSION['pending_admin_id']);
session_regenerate_id(true);
$_SESSION['admin_authenticated'] = true;
$_SESSION['admin_id'] = $account['id'];
log_activity($account['username'], 'login_totp_ok');

respond(['ok' => true]);

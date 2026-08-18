<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();
require_method('POST');

$accounts = load_admin_accounts();
$account = find_account($accounts, 'id', (string)current_account_id());
if ($account === null) {
    respond(['error' => 'Your account could not be found — please log in again'], 401);
}

$body = json_input();
$currentPassword = (string)($body['currentPassword'] ?? '');

if (!password_verify($currentPassword, $account['passwordHash'])) {
    respond(['error' => 'Current password is incorrect'], 401);
}

$account['totpEnabled'] = false;
$account['totpSecret'] = null;
$account['totpPendingSecret'] = null;
save_admin_accounts(replace_account($accounts, $account));
log_activity($account['username'], 'totp_disabled');

respond(['ok' => true]);

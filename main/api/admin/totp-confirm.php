<?php
require __DIR__ . '/../_bootstrap.php';
require __DIR__ . '/../lib/totp.php';

require_admin();
require_method('POST');

$accounts = load_admin_accounts();
$account = find_account($accounts, 'id', (string)current_account_id());
if ($account === null) {
    respond(['error' => 'Your account could not be found — please log in again'], 401);
}
if (empty($account['totpPendingSecret'])) {
    respond(['error' => 'No 2FA enrollment is in progress — start setup first'], 409);
}

$body = json_input();
$code = (string)($body['code'] ?? '');

if (!totp_verify($account['totpPendingSecret'], $code)) {
    respond(['error' => 'Invalid or expired code — check your authenticator app and try again'], 401);
}

$account['totpSecret'] = $account['totpPendingSecret'];
$account['totpPendingSecret'] = null;
$account['totpEnabled'] = true;
save_admin_accounts(replace_account($accounts, $account));
log_activity($account['username'], 'totp_enabled');

respond(['ok' => true]);

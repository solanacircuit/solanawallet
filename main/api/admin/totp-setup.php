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
if (!empty($account['totpEnabled'])) {
    respond(['error' => '2FA is already enabled — disable it first to re-enroll'], 409);
}

$secret = totp_random_secret();
$account['totpPendingSecret'] = $secret;
save_admin_accounts(replace_account($accounts, $account));

$issuer = load_config()['branding']['portalName'] . ' Admin';

respond([
    'secret' => $secret,
    'otpauthUri' => totp_provisioning_uri($secret, $account['username'], $issuer),
]);

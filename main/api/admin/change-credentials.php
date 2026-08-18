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
$newUsername = trim((string)($body['newUsername'] ?? ''));
$newPassword = (string)($body['newPassword'] ?? '');

if (!password_verify($currentPassword, $account['passwordHash'])) {
    respond(['error' => 'Current password is incorrect'], 401);
}

if ($newUsername === '' && $newPassword === '') {
    respond(['error' => 'Provide a new username and/or a new password'], 400);
}
if ($newUsername !== '' && strlen($newUsername) < 3) {
    respond(['error' => 'Username must be at least 3 characters'], 400);
}
if ($newPassword !== '' && strlen($newPassword) < 8) {
    respond(['error' => 'New password must be at least 8 characters'], 400);
}
if ($newUsername !== '' && find_account($accounts, 'username', $newUsername) !== null && $newUsername !== $account['username']) {
    respond(['error' => 'That username is already in use by another admin account'], 409);
}

$oldUsername = $account['username'];
if ($newUsername !== '') {
    $account['username'] = $newUsername;
}
if ($newPassword !== '') {
    $account['passwordHash'] = password_hash($newPassword, PASSWORD_DEFAULT);
}
save_admin_accounts(replace_account($accounts, $account));
log_activity($account['username'], 'credentials_updated', $oldUsername !== $account['username'] ? "username changed from {$oldUsername}" : 'password changed');

respond(['ok' => true, 'username' => $account['username']]);

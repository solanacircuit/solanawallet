<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();
require_method('POST');

$accounts = load_admin_accounts();
$body = json_input();
$id = (string)($body['id'] ?? '');

$target = find_account($accounts, 'id', $id);
if ($target === null) {
    respond(['error' => 'Account not found'], 404);
}
if (count($accounts) <= 1) {
    respond(['error' => 'Cannot remove the last remaining admin account'], 400);
}
if ($id === (string)current_account_id()) {
    respond(['error' => 'You cannot remove your own account while logged in as it — log in as another admin to remove this one'], 400);
}

$remaining = array_values(array_filter($accounts, fn($a) => $a['id'] !== $id));
save_admin_accounts($remaining);

$actingAccount = find_account($remaining, 'id', (string)current_account_id());
log_activity($actingAccount['username'] ?? 'unknown', 'account_removed', "removed admin \"{$target['username']}\"");

$currentId = (string)current_account_id();
respond(['accounts' => array_map(fn($a) => [
    'id' => $a['id'], 'username' => $a['username'], 'totpEnabled' => !empty($a['totpEnabled']), 'isYou' => $a['id'] === $currentId,
], $remaining)]);

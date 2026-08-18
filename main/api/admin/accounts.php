<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();

function public_account(array $a, $currentId): array {
    return [
        'id' => $a['id'],
        'username' => $a['username'],
        'totpEnabled' => !empty($a['totpEnabled']),
        'isYou' => $a['id'] === $currentId,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accounts = load_admin_accounts();
    $currentId = (string)current_account_id();
    respond(['accounts' => array_map(fn($a) => public_account($a, $currentId), $accounts)]);
}

require_method('POST');

$accounts = load_admin_accounts();
$body = json_input();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || strlen($username) < 3) {
    respond(['error' => 'Username must be at least 3 characters'], 400);
}
if (strlen($password) < 8) {
    respond(['error' => 'Password must be at least 8 characters'], 400);
}
if (find_account($accounts, 'username', $username) !== null) {
    respond(['error' => 'That username is already in use'], 409);
}

$newAccount = default_account($username, password_hash($password, PASSWORD_DEFAULT));
$accounts[] = $newAccount;
save_admin_accounts($accounts);

$actingAccount = find_account($accounts, 'id', (string)current_account_id());
log_activity($actingAccount['username'] ?? 'unknown', 'account_added', "added admin \"{$username}\"");

$currentId = (string)current_account_id();
respond(['accounts' => array_map(fn($a) => public_account($a, $currentId), $accounts)]);

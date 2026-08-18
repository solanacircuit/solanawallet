<?php
require __DIR__ . '/../_bootstrap.php';

require_method('GET');

$accounts = load_admin_accounts();
$loggedIn = !empty($_SESSION['admin_authenticated']);
$account = $loggedIn ? find_account($accounts, 'id', (string)current_account_id()) : null;

respond([
    'setupNeeded' => count($accounts) === 0,
    'loggedIn' => $loggedIn,
    'username' => $account['username'] ?? null,
    'totpRequired' => !empty($_SESSION['pending_admin_id']),
]);

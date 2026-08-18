<?php
require __DIR__ . '/../_bootstrap.php';

require_method('POST');

if (!empty($_SESSION['admin_authenticated'])) {
    $accounts = load_admin_accounts();
    $account = find_account($accounts, 'id', (string)current_account_id());
    if ($account !== null) {
        log_activity($account['username'], 'logout');
    }
}

$_SESSION = [];
session_destroy();

respond(['ok' => true]);

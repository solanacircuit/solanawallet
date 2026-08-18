<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();
require_method('POST');

write_json(ANALYTICS_FILE, ['totals' => [], 'daily' => []]);

$accounts = load_admin_accounts();
$actingAccount = find_account($accounts, 'id', (string)current_account_id());
log_activity($actingAccount['username'] ?? 'unknown', 'analytics_reset');

respond(['ok' => true]);

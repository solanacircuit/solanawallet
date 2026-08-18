<?php
declare(strict_types=1);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

header('Content-Type: application/json; charset=utf-8');

define('DATA_DIR', __DIR__ . '/data');
define('CONFIG_FILE', DATA_DIR . '/config.json');
define('ADMIN_FILE', DATA_DIR . '/admin.json');
define('ACTIVITY_LOG_FILE', DATA_DIR . '/activity-log.json');
define('ACTIVITY_LOG_MAX', 200);
define('ANALYTICS_FILE', DATA_DIR . '/analytics.json');
define('ANALYTICS_DAILY_MAX_DAYS', 90);

const VALID_THEMES = ['cyberpunk', 'bank'];

const VALID_ANALYTICS_EVENTS = [
    'connect',
    'tab_portfolio', 'tab_send', 'tab_bulksend', 'tab_swap', 'tab_bridge',
    'tab_staking', 'tab_nfts', 'tab_history', 'tab_addressbook', 'tab_settings',
];

const DEFAULT_CONFIG = [
    'enabled' => true,
    'disabledMessage' => 'This system is currently unavailable. Please check back later.',
    'rpcUrl' => 'https://api.mainnet-beta.solana.com',
    'rpcFallbackUrl' => '',
    'rpcFallbackUrl2' => '',
    'stakingValidator' => '',
    'sessionAutoLockMinutes' => 0,
    'maintenanceWindow' => [
        'enabled' => false,
        'startAt' => '',
        'endAt' => '',
    ],
    'theme' => 'cyberpunk',
    'branding' => [
        'portalName' => 'SOLANA CIRCUIT',
        'tagline' => 'SELF-CUSTODY SOLANA TERMINAL',
        'supportUrl' => 'https://official.solanacircuit.com/contactsupport/',
    ],
    'enabledTabs' => [
        'send' => true,
        'bulksend' => true,
        'swap' => true,
        'bridge' => true,
        'nfts' => true,
        'history' => true,
        'addressbook' => true,
        'staking' => true,
    ],
    'banner' => [
        'enabled' => true,
        'text' => '✦ Buy This System with Source Code ✦',
        'link' => 'https://hubcity.net',
    ],
    'socialLinks' => [
        'twitter' => '',
        'telegram' => '',
        'discord' => '',
        'youtube' => '',
        'instagram' => '',
        'facebook' => '',
        'linkedin' => '',
        'github' => '',
    ],
    'securityNotice' => [
        'enabled' => true,
        'text' => "This tool runs entirely in your browser. A pasted private key or seed phrase is used only in local memory to sign transactions and is never sent anywhere. It is not saved unless you explicitly enable encrypted local storage below.\n\n• Prefer the Browser Wallet option below when possible — your key never leaves your wallet extension.\n• Only run this app from your own machine, never on a shared/public computer.\n• Only use a burner/hot wallet with amounts you can afford to lose.\n• Review the source code yourself before entering a real key — this is unaudited software.\n• If importing a seed phrase: a mistyped word or wrong derivation path silently produces a different, valid-looking wallet. This tool does not validate the BIP39 checksum — always confirm the derived address (shown before you connect) matches the wallet you expect.",
    ],
];

function read_json(string $path, $default) {
    if (!is_file($path)) return $default;
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') return $default;
    $data = json_decode($raw, true);
    return $data === null ? $default : $data;
}

function write_json(string $path, $data): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    rename($tmp, $path);
}

function load_config(): array {
    $cfg = read_json(CONFIG_FILE, DEFAULT_CONFIG);
    return array_replace_recursive(DEFAULT_CONFIG, $cfg);
}

function save_config(array $cfg): void {
    write_json(CONFIG_FILE, $cfg);
}

// Compares "now" against the window using PHP's own clock, both pinned to UTC — the
// admin panel converts its datetime-local inputs to UTC ISO strings before saving, so
// this never depends on the server's configured default timezone.
function maintenance_window_active(array $window): bool {
    if (empty($window['enabled']) || empty($window['startAt']) || empty($window['endAt'])) return false;
    try {
        $tz = new DateTimeZone('UTC');
        $start = new DateTime($window['startAt'], $tz);
        $end = new DateTime($window['endAt'], $tz);
        $now = new DateTime('now', $tz);
        return $now >= $start && $now <= $end;
    } catch (Exception $e) {
        return false;
    }
}

function new_account_id(): string {
    return bin2hex(random_bytes(8));
}

function default_account(string $username, string $passwordHash): array {
    return [
        'id' => new_account_id(),
        'username' => $username,
        'passwordHash' => $passwordHash,
        'totpEnabled' => false,
        'totpSecret' => null,
        'totpPendingSecret' => null,
        'failedAttempts' => 0,
        'lockedUntil' => 0,
    ];
}

// Returns the account list, transparently migrating the pre-multi-account single-object
// admin.json format (just {username, passwordHash, failedAttempts, lockedUntil}) into
// {accounts: [...]} the first time it's read, and persisting the migrated shape so this
// only happens once. Same pattern rpcList.js used for its legacy single-URL format.
function load_admin_accounts(): array {
    $raw = read_json(ADMIN_FILE, null);
    if ($raw === null) return [];
    if (isset($raw['username']) && !isset($raw['accounts'])) {
        $migrated = [
            'id' => new_account_id(),
            'username' => $raw['username'],
            'passwordHash' => $raw['passwordHash'],
            'totpEnabled' => false,
            'totpSecret' => null,
            'totpPendingSecret' => null,
            'failedAttempts' => $raw['failedAttempts'] ?? 0,
            'lockedUntil' => $raw['lockedUntil'] ?? 0,
        ];
        $accounts = [$migrated];
        save_admin_accounts($accounts);
        return $accounts;
    }
    return $raw['accounts'] ?? [];
}

function save_admin_accounts(array $accounts): void {
    write_json(ADMIN_FILE, ['accounts' => $accounts]);
}

function find_account($accounts, string $field, string $value) {
    foreach ($accounts as $acc) {
        if (hash_equals((string)$acc[$field], $value)) return $acc;
    }
    return null;
}

function replace_account(array $accounts, array $updated): array {
    return array_map(fn($a) => $a['id'] === $updated['id'] ? $updated : $a, $accounts);
}

function log_activity(string $username, string $action, string $detail = ''): void {
    $log = read_json(ACTIVITY_LOG_FILE, []);
    $log[] = ['ts' => time(), 'username' => $username, 'action' => $action, 'detail' => $detail];
    if (count($log) > ACTIVITY_LOG_MAX) {
        $log = array_slice($log, -ACTIVITY_LOG_MAX);
    }
    write_json(ACTIVITY_LOG_FILE, $log);
}

// Uses real file locking (unlike read_json/write_json's plain write-then-rename) since
// this is a public, unauthenticated, high-frequency endpoint — concurrent requests are
// the normal case here, not the exception, so a lost-update race is much more likely.
function record_event(string $event): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $fp = fopen(ANALYTICS_FILE, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($data)) $data = ['totals' => [], 'daily' => []];
    if (!isset($data['totals']) || !is_array($data['totals'])) $data['totals'] = [];
    if (!isset($data['daily']) || !is_array($data['daily'])) $data['daily'] = [];

    $today = gmdate('Y-m-d');
    $data['totals'][$event] = ($data['totals'][$event] ?? 0) + 1;
    if (!isset($data['daily'][$today])) $data['daily'][$today] = [];
    $data['daily'][$today][$event] = ($data['daily'][$today][$event] ?? 0) + 1;

    if (count($data['daily']) > ANALYTICS_DAILY_MAX_DAYS) {
        krsort($data['daily']);
        $data['daily'] = array_slice($data['daily'], 0, ANALYTICS_DAILY_MAX_DAYS, true);
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function current_account_id() {
    return $_SESSION['admin_id'] ?? null;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

function respond($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function require_admin(): void {
    if (empty($_SESSION['admin_authenticated'])) {
        respond(['error' => 'Not authenticated'], 401);
    }
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        respond(['error' => 'Method not allowed'], 405);
    }
}

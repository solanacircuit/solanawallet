<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cfg = load_config();
    $cfg['maintenanceWindowActive'] = maintenance_window_active($cfg['maintenanceWindow']);
    respond($cfg);
}

require_method('POST');

$body = json_input();

$rpcUrl = trim((string)($body['rpcUrl'] ?? ''));
if ($rpcUrl === '' || !preg_match('#^https?://#i', $rpcUrl)) {
    respond(['error' => 'RPC URL must be a valid http(s) URL'], 400);
}

$rpcFallbackUrl = trim((string)($body['rpcFallbackUrl'] ?? ''));
if ($rpcFallbackUrl !== '' && !preg_match('#^https?://#i', $rpcFallbackUrl)) {
    respond(['error' => 'Fallback RPC URL must be a valid http(s) URL'], 400);
}

$rpcFallbackUrl2 = trim((string)($body['rpcFallbackUrl2'] ?? ''));
if ($rpcFallbackUrl2 !== '' && !preg_match('#^https?://#i', $rpcFallbackUrl2)) {
    respond(['error' => 'Second fallback RPC URL must be a valid http(s) URL'], 400);
}

$stakingValidator = trim((string)($body['stakingValidator'] ?? ''));
if ($stakingValidator !== '' && !preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $stakingValidator)) {
    respond(['error' => 'Validator vote account must be a valid base58 Solana address'], 400);
}

$disabledMessage = trim((string)($body['disabledMessage'] ?? ''));
if ($disabledMessage === '') {
    $disabledMessage = DEFAULT_CONFIG['disabledMessage'];
}

$theme = (string)($body['theme'] ?? DEFAULT_CONFIG['theme']);
if (!in_array($theme, VALID_THEMES, true)) {
    respond(['error' => 'Theme must be one of: ' . implode(', ', VALID_THEMES)], 400);
}

$branding = is_array($body['branding'] ?? null) ? $body['branding'] : [];
$portalName = trim((string)($branding['portalName'] ?? ''));
if ($portalName === '') {
    $portalName = DEFAULT_CONFIG['branding']['portalName'];
}
$tagline = trim((string)($branding['tagline'] ?? ''));
if ($tagline === '') {
    $tagline = DEFAULT_CONFIG['branding']['tagline'];
}
$supportUrl = trim((string)($branding['supportUrl'] ?? ''));
if ($supportUrl !== '' && !preg_match('#^https?://#i', $supportUrl)) {
    respond(['error' => 'Support URL must be a valid http(s) URL'], 400);
}
if ($supportUrl === '') {
    $supportUrl = DEFAULT_CONFIG['branding']['supportUrl'];
}

$enabledTabsIn = is_array($body['enabledTabs'] ?? null) ? $body['enabledTabs'] : [];
$enabledTabs = [];
foreach (DEFAULT_CONFIG['enabledTabs'] as $tabKey => $defaultVal) {
    $enabledTabs[$tabKey] = array_key_exists($tabKey, $enabledTabsIn) ? (bool)$enabledTabsIn[$tabKey] : $defaultVal;
}

$banner = is_array($body['banner'] ?? null) ? $body['banner'] : [];
$bannerLink = trim((string)($banner['link'] ?? ''));
if ($bannerLink !== '' && !preg_match('#^https?://#i', $bannerLink)) {
    respond(['error' => 'Banner link must be a valid http(s) URL'], 400);
}

$socialLinksIn = is_array($body['socialLinks'] ?? null) ? $body['socialLinks'] : [];
$socialLinks = [];
foreach (DEFAULT_CONFIG['socialLinks'] as $platform => $defaultVal) {
    $url = trim((string)($socialLinksIn[$platform] ?? ''));
    if ($url !== '' && !preg_match('#^https?://#i', $url)) {
        respond(['error' => ucfirst($platform) . ' link must be a valid http(s) URL'], 400);
    }
    $socialLinks[$platform] = $url;
}

$securityNotice = is_array($body['securityNotice'] ?? null) ? $body['securityNotice'] : [];
$securityNoticeText = trim((string)($securityNotice['text'] ?? ''));
if ($securityNoticeText === '') {
    $securityNoticeText = DEFAULT_CONFIG['securityNotice']['text'];
}

$sessionAutoLockMinutes = (int)($body['sessionAutoLockMinutes'] ?? 0);
if ($sessionAutoLockMinutes < 0) $sessionAutoLockMinutes = 0;

$maintenanceWindowIn = is_array($body['maintenanceWindow'] ?? null) ? $body['maintenanceWindow'] : [];
$mwEnabled = (bool)($maintenanceWindowIn['enabled'] ?? false);
$mwStart = trim((string)($maintenanceWindowIn['startAt'] ?? ''));
$mwEnd = trim((string)($maintenanceWindowIn['endAt'] ?? ''));
if ($mwEnabled) {
    if ($mwStart === '' || $mwEnd === '') {
        respond(['error' => 'Maintenance window needs both a start and end time'], 400);
    }
    try {
        $tz = new DateTimeZone('UTC');
        $mwStartDt = new DateTime($mwStart, $tz);
        $mwEndDt = new DateTime($mwEnd, $tz);
        if ($mwStartDt >= $mwEndDt) {
            respond(['error' => 'Maintenance window start must be before its end'], 400);
        }
    } catch (Exception $ex) {
        respond(['error' => 'Invalid maintenance window date(s)'], 400);
    }
}

$cfg = [
    'enabled' => (bool)($body['enabled'] ?? true),
    'disabledMessage' => $disabledMessage,
    'rpcUrl' => $rpcUrl,
    'rpcFallbackUrl' => $rpcFallbackUrl,
    'rpcFallbackUrl2' => $rpcFallbackUrl2,
    'stakingValidator' => $stakingValidator,
    'sessionAutoLockMinutes' => $sessionAutoLockMinutes,
    'theme' => $theme,
    'maintenanceWindow' => [
        'enabled' => $mwEnabled,
        'startAt' => $mwStart,
        'endAt' => $mwEnd,
    ],
    'branding' => [
        'portalName' => $portalName,
        'tagline' => $tagline,
        'supportUrl' => $supportUrl,
    ],
    'enabledTabs' => $enabledTabs,
    'banner' => [
        'enabled' => (bool)($banner['enabled'] ?? false),
        'text' => trim((string)($banner['text'] ?? '')),
        'link' => $bannerLink,
    ],
    'socialLinks' => $socialLinks,
    'securityNotice' => [
        'enabled' => (bool)($securityNotice['enabled'] ?? false),
        'text' => $securityNoticeText,
    ],
];

save_config($cfg);

$accounts = load_admin_accounts();
$actingAccount = find_account($accounts, 'id', (string)current_account_id());
log_activity($actingAccount['username'] ?? 'unknown', 'config_updated');

respond($cfg);

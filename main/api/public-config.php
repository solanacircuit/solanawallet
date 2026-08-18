<?php
require __DIR__ . '/_bootstrap.php';

require_method('GET');

$cfg = load_config();
$effectiveEnabled = $cfg['enabled'] && !maintenance_window_active($cfg['maintenanceWindow']);

respond([
    'enabled' => $effectiveEnabled,
    'disabledMessage' => $cfg['disabledMessage'],
    'rpcUrl' => $cfg['rpcUrl'],
    'rpcFallbackUrl' => $cfg['rpcFallbackUrl'],
    'rpcFallbackUrl2' => $cfg['rpcFallbackUrl2'],
    'stakingValidator' => $cfg['stakingValidator'],
    'sessionAutoLockMinutes' => $cfg['sessionAutoLockMinutes'],
    'theme' => $cfg['theme'],
    'branding' => $cfg['branding'],
    'enabledTabs' => $cfg['enabledTabs'],
    'banner' => $cfg['banner'],
    'securityNotice' => $cfg['securityNotice'],
    'socialLinks' => $cfg['socialLinks'],
]);

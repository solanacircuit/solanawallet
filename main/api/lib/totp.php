<?php
declare(strict_types=1);

// RFC 6238 TOTP (HMAC-SHA1, 6 digits, 30s step) — pure PHP, no external dependency.

function totp_random_secret(int $bytes = 20): string {
    return totp_base32_encode(random_bytes($bytes));
}

function totp_base32_encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $output = '';
    foreach (str_split($bits, 5) as $chunk) {
        if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $output .= $alphabet[bindec($chunk)];
    }
    return $output;
}

function totp_base32_decode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $data));
    $bits = '';
    foreach (str_split($data) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) < 8) continue; // drop incomplete trailing bits
        $bytes .= chr(bindec($byte));
    }
    return $bytes;
}

function totp_code_at(string $secret, int $timeStep): string {
    $key = totp_base32_decode($secret);
    $counter = pack('N', 0) . pack('N', $timeStep); // 8-byte big-endian counter
    $hash = hash_hmac('sha1', $counter, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0xf;
    $truncated = ((ord($hash[$offset]) & 0x7f) << 24)
        | ((ord($hash[$offset + 1]) & 0xff) << 16)
        | ((ord($hash[$offset + 2]) & 0xff) << 8)
        | (ord($hash[$offset + 3]) & 0xff);
    return str_pad((string)($truncated % 1000000), 6, '0', STR_PAD_LEFT);
}

// $window = how many 30s steps of clock drift to tolerate on either side.
function totp_verify(string $secret, string $code, int $window = 1, int $period = 30): bool {
    $code = preg_replace('/\s+/', '', (string)$code);
    if (!preg_match('/^\d{6}$/', $code)) return false;
    $currentStep = (int)floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code_at($secret, $currentStep + $i), $code)) return true;
    }
    return false;
}

function totp_provisioning_uri(string $secret, string $accountLabel, string $issuer): string {
    $label = rawurlencode($issuer) . ':' . rawurlencode($accountLabel);
    $params = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => 'SHA1',
        'digits' => 6,
        'period' => 30,
    ]);
    return "otpauth://totp/{$label}?{$params}";
}

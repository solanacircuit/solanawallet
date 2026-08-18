<?php
require __DIR__ . '/_bootstrap.php';

require_method('POST');

$body = json_input();
$event = (string)($body['event'] ?? '');

if (!in_array($event, VALID_ANALYTICS_EVENTS, true)) {
    respond(['ok' => false], 400);
}

record_event($event);

respond(['ok' => true]);

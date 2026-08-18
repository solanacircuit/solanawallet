<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();
require_method('GET');

$log = read_json(ACTIVITY_LOG_FILE, []);
respond(['entries' => array_reverse($log)]);

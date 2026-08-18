<?php
require __DIR__ . '/../_bootstrap.php';

require_admin();
require_method('GET');

respond(read_json(ANALYTICS_FILE, ['totals' => [], 'daily' => []]));

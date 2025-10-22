<?php

require_once __DIR__ . '/../../lib/MenuService.php';

require_admin_auth();

$service = new MenuService();
json_response($service->getTables());

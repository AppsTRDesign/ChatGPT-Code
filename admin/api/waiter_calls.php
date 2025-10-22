<?php
require_once __DIR__ . '/../../lib/OrderService.php';
require_auth();

$service = new OrderService();
$calls = $service->getWaiterCalls();
json_response(['calls' => $calls]);

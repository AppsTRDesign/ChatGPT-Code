#!/usr/bin/env php
<?php

use App\Realtime\RealtimeServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\SocketServer;

require_once __DIR__ . '/../config.php';

$port = (int) ($argv[1] ?? 6001);

$loop = LoopFactory::create();
$server = new RealtimeServer($pdo, $loop);

$socket = new SocketServer('0.0.0.0:' . $port, [], $loop);
$httpServer = new IoServer(
    new HttpServer(new WsServer($server)),
    $socket,
    $loop
);

echo "Realtime server listening on ws://0.0.0.0:{$port}" . PHP_EOL;
$loop->run();

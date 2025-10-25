<?php

session_start();

$router = require __DIR__ . '/bootstrap/app.php';

$router->dispatch();

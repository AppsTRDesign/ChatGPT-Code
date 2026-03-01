<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$redirectTarget = '/profile';
header('Location: ' . $redirectTarget);
exit;

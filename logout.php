<?php
require_once __DIR__ . '/config.php';
logout_user();
header('Location: ' . BASE_URL . '/login');
exit;

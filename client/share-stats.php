<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
header('Location: ' . BASE_URL . '/client');
exit;

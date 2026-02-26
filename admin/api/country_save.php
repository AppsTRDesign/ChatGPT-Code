<?php require_once __DIR__ . '/_init.php'; db()->prepare('INSERT INTO countries(name,is_active) VALUES(:n,1)')->execute(['n'=>trim($_POST['name'])]); json_response(true,'Ülke kaydedildi');

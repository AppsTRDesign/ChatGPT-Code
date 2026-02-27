<?php
require_once __DIR__ . '/_init.php';
$code = strtolower(trim((string)($_POST['code'] ?? '')));
if ($code === 'en') json_response(false,'Varsayılan dil silinemez');
db()->prepare('DELETE FROM languages WHERE code=:c')->execute(['c'=>$code]);
db()->prepare('DELETE FROM translations WHERE lang_code=:c')->execute(['c'=>$code]);
json_response(true,'Dil silindi');

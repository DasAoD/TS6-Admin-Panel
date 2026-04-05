<?php
// =============================================================
//  avatar.php — Avatar-Dateien ausliefern
// =============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
auth_check();

$uid = trim($_GET['uid'] ?? '');
if (!$uid) { http_response_code(404); exit; }

// Hash berechnen
$hash = strtolower(str_replace(['+', '/', '='], ['p', 'q', 'a'], $uid));
$file = '/opt/teamspeak6/files/virtualserver_1/internal/avatar_' . $hash;

if (!file_exists($file)) { http_response_code(404); exit; }

header('Content-Type: image/png');
header('Cache-Control: max-age=3600');
readfile($file);

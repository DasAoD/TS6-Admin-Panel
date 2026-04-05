<?php
// =============================================================
//  api/channel_move.php — AJAX-Endpoint für Channel-Reihenfolge
// =============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../api/TS6Api.php';

header('Content-Type: application/json');

auth_start();
if (empty($_SESSION['ts6admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Nur POST erlaubt.']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$cid   = (int)($data['cid']   ?? 0);
$cpid  = (int)($data['cpid']  ?? 0);
$order = (int)($data['order'] ?? 0);

if (!$cid) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Channel-ID.']);
    exit;
}

// channelmove via HTTP-Query
$url = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/channelmove';
$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => [
            'x-api-key: ' . TS6_API_KEY,
            'Content-Type: application/json',
        ],
        'content' => json_encode([
            'cid'   => $cid,
            'cpid'  => $cpid,
            'order' => $order,
        ]),
        'timeout' => 5,
        'ignore_errors' => true,
    ]
]);

$raw  = @file_get_contents($url, false, $ctx);
$resp = json_decode($raw, true);

if (!$resp || $resp['status']['code'] !== 0) {
    echo json_encode([
        'success' => false,
        'error'   => $resp['status']['message'] ?? 'Fehler beim Verschieben.'
    ]);
    exit;
}

echo json_encode(['success' => true]);

<?php
// =============================================================
//  api/channel_tree.php — Live Channel-Übersicht
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

if (!api()->ping()) {
    echo json_encode(['success' => false, 'error' => 'API nicht erreichbar.']);
    exit;
}

$channels = api()->channelList()['data'] ?? [];
$clients  = api()->clientList()['data']  ?? [];

// Nur echte Clients
$clients = array_filter($clients, fn($c) => ($c['client_type'] ?? 0) == 0);

// Clients nach Channel gruppieren
$clientsByChannel = [];
foreach ($clients as $c) {
    $cid = (int)($c['cid'] ?? 0);
    $clientsByChannel[$cid][] = [
        'clid'     => (int)($c['clid'] ?? 0),
        'nickname' => $c['client_nickname'] ?? '—',
        'away'     => !empty($c['client_away']),
    ];
}

// Channel-Tree aufbauen
$tree = [];
foreach ($channels as $ch) {
    $cid     = (int)($ch['cid'] ?? 0);
    $name    = $ch['channel_name'] ?? '';
    $isSpacer = is_spacer($name);

    $tree[] = [
        'cid'      => $cid,
        'name'     => $isSpacer ? preg_replace('/^\[[\*c]+spacer\d*\]/', '', $name) : $name,
        'spacer'   => $isSpacer,
        'password' => !empty($ch['channel_flag_password']),
        'clients'  => $clientsByChannel[$cid] ?? [],
    ];
}

echo json_encode([
    'success'   => true,
    'tree'      => $tree,
    'total'     => count($clients),
    'timestamp' => date('H:i:s'),
]);
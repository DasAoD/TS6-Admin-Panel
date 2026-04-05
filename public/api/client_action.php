<?php
// =============================================================
//  api/client_action.php — AJAX Client-Aktionen
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

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$clid   = (int)($data['clid'] ?? 0);

if (!$clid) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Client-ID.']);
    exit;
}

switch ($action) {

    case 'kick':
        $reason = $data['reason'] ?? '';
        $result = api()->clientKick($clid, 5, $reason);
        break;

    case 'kick_channel':
        $reason = $data['reason'] ?? '';
        $result = api()->clientKick($clid, 4, $reason);
        break;

    case 'ban':
        $reason = $data['reason'] ?? '';
        $time   = (int)($data['time'] ?? 0);
        $result = api()->banClient($clid, $time, $reason);
        break;

    case 'move':
        $cid    = (int)($data['cid'] ?? 0);
        $result = api()->clientMove($clid, $cid);
        break;

    case 'poke':
        $msg    = $data['msg'] ?? '';
        $result = api()->clientPoke($clid, $msg);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion.']);
        exit;
}

echo json_encode($result);
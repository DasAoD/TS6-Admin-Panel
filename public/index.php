<?php
ob_start();
// =============================================================
//  index.php — Zentrales Routing
// =============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/api/TS6Api.php';

// Authentifizierung prüfen
auth_check();

// Seite ermitteln
$page = current_page();

// Erlaubte Seiten
$pages = [
    'dashboard'    => 'pages/dashboard.php',
    'channels'     => 'pages/channels.php',
    'clients'      => 'pages/clients.php',
    'groups'       => 'pages/groups.php',
    'permissions'  => 'pages/permissions.php',
    'bans'         => 'pages/bans.php',
    'migration'    => 'pages/migration.php',
    'tokens'       => 'pages/tokens.php',
    'clients_db'   => 'pages/clients_db.php',
    'config'       => 'pages/config_page.php',
    'ts6ctl'       => 'pages/ts6ctl.php',
    'logs'         => 'pages/logs.php',
];

$pageFile = __DIR__ . '/' . ($pages[$page] ?? $pages['dashboard']);

if (!file_exists($pageFile)) {
    $pageFile = __DIR__ . '/pages/dashboard.php';
    $page = 'dashboard';
}

// API-Verbindungsstatus
$apiOnline = api()->ping();

// Layout laden
require_once BASE_PATH . '/includes/header.php';
require_once $pageFile;
require_once BASE_PATH . '/includes/footer.php';

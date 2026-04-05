<?php
// =============================================================
//  ts6admin — Konfiguration (Beispiel)
//  Kopiere diese Datei nach config.php und passe sie an.
// =============================================================

// ── TS6 Server API ───────────────────────────────────────────
define('TS6_API_HOST',    '127.0.0.1');
define('TS6_API_PORT',    10080);
define('TS6_API_KEY',     'DEIN_API_KEY');   // journalctl -u teamspeak6 | grep apikey
define('TS6_VSERVER_ID',  1);                // Virtual Server ID (Standard: 1)

// ── WebUI Login ──────────────────────────────────────────────
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', '');                    // Wird in config/ .admin_pass gespeichert

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME',     'ts6admin');
define('SESSION_LIFETIME', 3600);            // Sekunden (1 Stunde)

// ── Sprache ──────────────────────────────────────────────────
define('DEFAULT_LANG', 'de');                // de | en

// ── TS3 Datenbank (für Migration) ────────────────────────────
define('TS3_DB_HOST',    '127.0.0.1');
define('TS3_DB_PORT',    3306);
define('TS3_DB_USER',    'teamspeak');
define('TS3_DB_PASS',    'DEIN_DB_PASSWORT');
define('TS3_DB_NAME',    'teamspeak3');

// ── Pfade ────────────────────────────────────────────────────
define('BASE_PATH',    dirname(__DIR__));
define('CONFIG_PATH', __DIR__);
define('TS6CTL_PATH', '/usr/local/bin/ts6ctl');
define('TS6CTL_CONF', CONFIG_PATH . '/ts6ctl.conf');
define('TS6CTL_LOG',  '/var/log/ts6ctl.log');

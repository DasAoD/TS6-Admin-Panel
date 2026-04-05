<?php
// =============================================================
//  auth.php — Session- und Login-Verwaltung
// =============================================================

function auth_start(): void {
    ini_set('session.name', SESSION_NAME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_check(): void {
    auth_start();
    if (empty($_SESSION['ts6admin_logged_in'])) {
        header('Location: /login.php');
        exit;
    }
    // Session-Timeout prüfen
    if (!empty($_SESSION['ts6admin_last_activity'])) {
        if (time() - $_SESSION['ts6admin_last_activity'] > SESSION_LIFETIME) {
            auth_logout();
        }
    }
    $_SESSION['ts6admin_last_activity'] = time();
}

function auth_login(string $user, string $pass): bool {
    auth_start();
    // Hash aus separater Datei lesen falls vorhanden (überschreibt config.php)
    $hashFile = CONFIG_PATH . '/.admin_pass';
    $hash = file_exists($hashFile) ? trim(file_get_contents($hashFile)) : ADMIN_PASS;
    if ($user === ADMIN_USER && password_verify($pass, $hash)) {
        $_SESSION['ts6admin_logged_in']     = true;
        $_SESSION['ts6admin_user']          = $user;
        $_SESSION['ts6admin_last_activity'] = time();
        return true;
    }
    return false;
}

function auth_logout(): void {
    auth_start();
    $_SESSION = [];
    session_destroy();
    header('Location: /login.php');
    exit;
}

function auth_user(): string {
    return $_SESSION['ts6admin_user'] ?? 'admin';
}
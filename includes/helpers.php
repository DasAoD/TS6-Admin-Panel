<?php
// =============================================================
//  includes/helpers.php — Hilfsfunktionen
// =============================================================

// ── Sprache ───────────────────────────────────────────────────
function lang_load(): array {
    $langFile = BASE_PATH . '/lang/' . DEFAULT_LANG . '.php';
    if (!file_exists($langFile)) {
        $langFile = BASE_PATH . '/lang/de.php';
    }
    return require $langFile;
}

$GLOBALS['_lang'] = lang_load();

function t(string $key, ...$args): string {
    $str = $GLOBALS['_lang'][$key] ?? $key;
    return $args ? sprintf($str, ...$args) : $str;
}

// ── HTML-Escaping ─────────────────────────────────────────────
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

// ── Aktive Seite erkennen ─────────────────────────────────────
function is_page(string $page): bool {
    return ($_GET['page'] ?? 'dashboard') === $page;
}

function current_page(): string {
    return preg_replace('/[^a-z0-9_]/', '', $_GET['page'] ?? 'dashboard');
}

// ── Flash-Nachrichten ─────────────────────────────────────────
function flash_set(string $type, string $msg): void {
    $_SESSION['ts6admin_flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
    if (!empty($_SESSION['ts6admin_flash'])) {
        $f = $_SESSION['ts6admin_flash'];
        unset($_SESSION['ts6admin_flash']);
        return $f;
    }
    return null;
}

// ── Zeitformat ────────────────────────────────────────────────
function ts_format(int $ts): string {
    return date('d.m.Y H:i', $ts);
}

function duration_format(int $seconds): string {
    if ($seconds === 0) return 'Permanent';
    if ($seconds < 60)  return $seconds . ' Sek.';
    if ($seconds < 3600) return round($seconds / 60) . ' Min.';
    if ($seconds < 86400) return round($seconds / 3600) . ' Std.';
    return round($seconds / 86400) . ' Tage';
}

// ── TS6 API Instanz ───────────────────────────────────────────
function api(): TS6Api {
    static $instance = null;
    if ($instance === null) {
        $instance = new TS6Api();
    }
    return $instance;
}

// ── Spacer erkennen ───────────────────────────────────────────
function is_spacer(string $name): bool {
    return str_contains($name, 'spacer') || str_starts_with($name, '[c') || str_starts_with($name, '[*c');
}

// ── ts6ctl ausführen (als root via sudo) ──────────────────────
function ts6ctl_exec(string $cmd): array {
    $allowed = ['check-update', 'status', 'update'];
    if (!in_array($cmd, $allowed, true)) {
        return ['exit' => 1, 'output' => 'Unbekannter Befehl.'];
    }
    $output = [];
    $exit   = 0;
    exec('sudo ' . TS6CTL_PATH . ' ' . escapeshellarg($cmd) . ' 2>&1', $output, $exit);
    return ['exit' => $exit, 'output' => implode("\n", $output)];
}

// ── Service-Steuerung ─────────────────────────────────────────
function service_exec(string $action): array {
    $allowed = ['start', 'stop', 'restart', 'status'];
    if (!in_array($action, $allowed, true)) {
        return ['exit' => 1, 'output' => 'Unbekannte Aktion.'];
    }
    $output = [];
    $exit   = 0;
    exec('sudo systemctl ' . $action . ' teamspeak6 2>&1', $output, $exit);
    return ['exit' => $exit, 'output' => implode("\n", $output)];
}

// ── ts6ctl.conf lesen ─────────────────────────────────────────
function ts6ctl_conf_read(): array {
    $result = [];
    if (!file_exists(TS6CTL_CONF)) return $result;
    foreach (file(TS6CTL_CONF) as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $result[trim($key)] = trim($val, '"');
    }
    return $result;
}
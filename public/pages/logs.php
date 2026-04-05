<?php
// =============================================================
//  pages/logs.php — Server-Logs
// =============================================================

$lines   = (int)($_GET['lines'] ?? 100);
$filter  = trim($_GET['filter'] ?? '');
$source  = $_GET['source'] ?? 'server';

// ── Log-Quellen ───────────────────────────────────────────────
$sources = [
    'server' => 'TS6 Server-Log (journalctl)',
    'ts6ctl' => 'ts6ctl Update-Log',
    'nginx'  => 'nginx Access-Log',
    'nginx_error' => 'nginx Error-Log',
];

// ── Logs laden ────────────────────────────────────────────────
$logLines = [];
$error    = '';

switch ($source) {
    case 'server':
        $cmd    = "journalctl -u " . escapeshellarg($conf['TS6_SERVICE'] ?? 'teamspeak6')
                . " --no-pager -n " . (int)$lines . " --output=short 2>&1";
        $output = shell_exec($cmd) ?? '';
        $logLines = array_reverse(explode("\n", trim($output)));
        break;

    case 'ts6ctl':
        if (file_exists(TS6CTL_LOG)) {
            $all      = file(TS6CTL_LOG, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_reverse(array_slice($all, -$lines));
        } else {
            $error = 'Log-Datei nicht gefunden: ' . TS6CTL_LOG;
        }
        break;

    case 'nginx':
        $path = '/var/log/nginx/ts6admin.access.log';
        if (file_exists($path)) {
            $all      = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_reverse(array_slice($all, -$lines));
        } else {
            $error = 'Log-Datei nicht gefunden: ' . $path;
        }
        break;

    case 'nginx_error':
        $path = '/var/log/nginx/ts6admin.error.log';
        if (file_exists($path)) {
            $all      = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_reverse(array_slice($all, -$lines));
        } else {
            $error = 'Log-Datei nicht gefunden: ' . $path;
        }
        break;
}

// Filter anwenden
if ($filter && !empty($logLines)) {
    $logLines = array_filter($logLines, fn($l) => stripos($l, $filter) !== false);
}
$logLines = array_values($logLines);

// Zeile colorieren
function log_color(string $line): string {
    if (preg_match('/ERROR|CRITICAL|error|fatal/i', $line)) return 'var(--red)';
    if (preg_match('/WARNING|WARN/i', $line))              return 'var(--yellow)';
    if (preg_match('/INFO|ok/i', $line))                   return 'var(--text-muted)';
    if (preg_match('/JOIN|connect/i', $line))              return 'var(--green)';
    if (preg_match('/UPDATE/i', $line))                    return 'var(--accent)';
    return 'var(--text-muted)';
}

$conf = ts6ctl_conf_read();
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('nav.logs') ?></div>
    <div class="page-sub">Server- und System-Logs</div>
  </div>
  <div class="topbar-right">
    <a href="/?page=logs&source=<?= e($source) ?>&lines=<?= $lines ?>&filter=<?= urlencode($filter) ?>"
       class="btn btn-ghost">↺ Aktualisieren</a>
  </div>
</div>

<div class="content">

  <!-- Filter-Leiste -->
  <form method="get" action="/" style="margin-bottom:16px">
    <input type="hidden" name="page" value="logs">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0;flex:0 0 auto">
        <label class="form-label">Quelle</label>
        <select name="source" class="form-select" style="min-width:220px">
          <?php foreach ($sources as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $source === $key ? 'selected' : '' ?>>
            <?= e($label) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;flex:0 0 auto">
        <label class="form-label">Letzte Zeilen</label>
        <select name="lines" class="form-select">
          <?php foreach ([50, 100, 200, 500] as $n): ?>
          <option value="<?= $n ?>" <?= $lines === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:200px">
        <label class="form-label">Filter (enthält)</label>
        <input type="text" name="filter" class="form-input"
               value="<?= e($filter) ?>" placeholder="z.B. ERROR, JOIN, DasAoD...">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-bottom:0">Anwenden</button>
      <?php if ($filter): ?>
      <a href="/?page=logs&source=<?= e($source) ?>&lines=<?= $lines ?>"
         class="btn btn-ghost" style="margin-bottom:0">Filter löschen</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($error): ?>
  <div class="alert alert-warning"><?= e($error) ?></div>
  <?php elseif (empty($logLines)): ?>
  <div class="alert alert-info">Keine Log-Einträge gefunden<?= $filter ? ' (Filter aktiv)' : '' ?>.</div>
  <?php else: ?>

  <!-- Log-Anzeige -->
  <div class="form-section">
    <div class="form-section-head" style="display:flex;justify-content:space-between">
      <span><?= e($sources[$source]) ?></span>
      <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-subtle)">
        <?= count($logLines) ?> Zeilen
        <?= $filter ? '(gefiltert nach: <em>' . e($filter) . '</em>)' : '' ?>
      </span>
    </div>
    <div style="overflow-x:auto;max-height:600px;overflow-y:auto">
      <table style="width:100%;border-collapse:collapse">
        <tbody>
          <?php foreach ($logLines as $i => $line):
            if (trim($line) === '') continue;
            $color = log_color($line);
          ?>
          <tr style="<?= $i % 2 === 0 ? 'background:var(--bg-base)' : '' ?>">
            <td style="padding:3px 14px;font-size:11px;font-family:var(--font-mono);
                       color:<?= $color ?>;white-space:pre-wrap;word-break:break-all;
                       border-bottom:1px solid var(--border)">
              <?= e($line) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php endif; ?>

</div>
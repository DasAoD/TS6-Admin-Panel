<?php
// =============================================================
//  pages/ts6ctl.php — Service-Steuerung und Updates
// =============================================================

$flash  = flash_get();
$conf   = ts6ctl_conf_read();
$output = '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Aktionen ──────────────────────────────────────────────────
if ($action) {
    switch ($action) {
        case 'restart':
            $r = service_exec('restart');
            flash_set($r['exit'] === 0 ? 'success' : 'danger',
                $r['exit'] === 0 ? 'Service neu gestartet.' : 'Fehler: ' . $r['output']);
            header('Location: /?page=ts6ctl');
            exit;

        case 'stop':
            $r = service_exec('stop');
            flash_set($r['exit'] === 0 ? 'success' : 'danger',
                $r['exit'] === 0 ? 'Service gestoppt.' : 'Fehler: ' . $r['output']);
            header('Location: /?page=ts6ctl');
            exit;

        case 'start':
            $r = service_exec('start');
            flash_set($r['exit'] === 0 ? 'success' : 'danger',
                $r['exit'] === 0 ? 'Service gestartet.' : 'Fehler: ' . $r['output']);
            header('Location: /?page=ts6ctl');
            exit;

        case 'check':
            $r      = ts6ctl_exec('check-update');
            $output = $r['output'];
            $flash  = ['type' => 'info', 'msg' => 'Update-Check abgeschlossen.'];
            break;

        case 'update':
            $r      = ts6ctl_exec('update');
            $output = $r['output'];
            $flash  = ['type' => $r['exit'] === 0 ? 'success' : 'danger',
                       'msg'  => $r['exit'] === 0 ? 'Update abgeschlossen.' : 'Update fehlgeschlagen.'];
            break;
    }
}

// ── Service-Status ────────────────────────────────────────────
$statusResult = service_exec('status');
$isRunning    = str_contains($statusResult['output'], 'active (running)');

// ── Versionen ─────────────────────────────────────────────────
$installedVersion = $conf['TS6_INSTALLED_VERSION'] ?? '—';

// Log-Datei: letzte Einträge
$logEntries = [];
if (file_exists(TS6CTL_LOG)) {
    $lines      = file(TS6CTL_LOG, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logEntries = array_slice(array_reverse($lines), 0, 20);
}
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('ctl.title') ?></div>
    <div class="page-sub"><?= t('ctl.sub') ?></div>
  </div>
  <div class="topbar-right">
    <a href="/?page=ts6ctl&action=check" class="btn btn-ghost"><?= t('ctl.check') ?></a>
    <a href="/?page=ts6ctl&action=update" class="btn btn-primary"
       data-confirm="Update jetzt installieren? Der Server wird kurz offline sein.">
      Update installieren
    </a>
  </div>
</div>

<div class="content">

  <?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- Status-Karten -->
  <div class="metrics">
    <div class="metric">
      <div class="metric-label"><?= t('ctl.service_status') ?></div>
      <div class="metric-val" style="font-size:16px;margin-top:6px">
        <?php if ($isRunning): ?>
        <span style="color:var(--green)">● <?= t('status.online') ?></span>
        <?php else: ?>
        <span style="color:var(--red)">● Gestoppt</span>
        <?php endif; ?>
      </div>
      <div class="metric-sub"><?= e($conf['TS6_SERVICE'] ?? 'teamspeak6') ?>.service</div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= t('ctl.installed') ?></div>
      <div class="metric-val" style="font-size:16px;margin-top:6px;font-family:var(--font-mono)">
        <?= e($installedVersion) ?>
      </div>
      <div class="metric-sub">github.com/teamspeak/teamspeak6-server</div>
    </div>
    <div class="metric">
      <div class="metric-label">Update-Benachrichtigung</div>
      <div class="metric-val" style="font-size:14px;margin-top:8px">
        <?= $conf['TS6_MAIL_TO'] ? e($conf['TS6_MAIL_TO']) : '<span style="color:var(--text-subtle)">deaktiviert</span>' ?>
      </div>
      <div class="metric-sub">täglich 08:00 Uhr via Cron</div>
    </div>
  </div>

  <!-- Service-Steuerung -->
  <div class="form-section">
    <div class="form-section-head"><?= t('ctl.service_status') ?></div>
    <div class="form-section-body">
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/?page=ts6ctl&action=restart" class="btn btn-ghost"
           data-confirm="Service neu starten?">
          ↺ <?= t('ctl.restart') ?>
        </a>
        <?php if ($isRunning): ?>
        <a href="/?page=ts6ctl&action=stop" class="btn btn-danger"
           data-confirm="Service stoppen? Der TS6-Server wird offline gehen.">
          ■ <?= t('ctl.stop') ?>
        </a>
        <?php else: ?>
        <a href="/?page=ts6ctl&action=start" class="btn btn-success">
          ▶ <?= t('ctl.start') ?>
        </a>
        <?php endif; ?>
      </div>

      <?php if (!empty($statusResult['output'])): ?>
      <pre style="margin-top:16px;background:var(--bg-base);border:1px solid var(--border);
                  border-radius:var(--radius-md);padding:14px;font-size:12px;
                  font-family:var(--font-mono);overflow-x:auto;color:var(--text-muted);
                  max-height:200px;overflow-y:auto"><?= e($statusResult['output']) ?></pre>
      <?php endif; ?>
    </div>
  </div>

  <!-- ts6ctl-Ausgabe (nach check/update) -->
  <?php if ($output): ?>
  <div class="form-section">
    <div class="form-section-head">Ausgabe</div>
    <div class="form-section-body">
      <pre style="background:var(--bg-base);border:1px solid var(--border);
                  border-radius:var(--radius-md);padding:14px;font-size:12px;
                  font-family:var(--font-mono);overflow-x:auto;color:var(--text-primary)"><?= e($output) ?></pre>
    </div>
  </div>
  <?php endif; ?>

  <!-- Update-Log -->
  <?php if (!empty($logEntries)): ?>
  <div class="form-section">
    <div class="form-section-head">Update-Log (<?= TS6CTL_LOG ?>)</div>
    <div style="max-height:300px;overflow-y:auto">
      <table class="data">
        <tbody>
          <?php foreach ($logEntries as $line):
            $type = 'text-muted';
            if (str_contains($line, '[UPDATE]'))  $type = 'text-accent';
            if (str_contains($line, '[ERROR]'))   $type = 'text-red';
            if (str_contains($line, '[INFO]'))    $type = 'text-muted';
          ?>
          <tr>
            <td class="mono" style="font-size:12px">
              <span class="<?= $type ?>"><?= e($line) ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php
// =============================================================
//  pages/bans.php — Ban-Verwaltung
// =============================================================

// ── POST-Aktionen ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'add':
            $params = [];
            if (!empty($_POST['ban_ip']))   $params['ip']        = trim($_POST['ban_ip']);
            if (!empty($_POST['ban_name'])) $params['name']      = trim($_POST['ban_name']);
            if (!empty($_POST['ban_uid']))  $params['uid']       = trim($_POST['ban_uid']);
            if (!empty($_POST['ban_reason'])) $params['banreason'] = trim($_POST['ban_reason']);
            $params['time'] = (int)($_POST['ban_time'] ?? 0);

            if (empty($params['ip']) && empty($params['name']) && empty($params['uid'])) {
                flash_set('danger', 'Mindestens IP, Name oder UID muss angegeben werden.');
            } else {
                $result = api()->banAdd($params);
                flash_set($result['success'] ? 'success' : 'danger',
                    $result['success'] ? 'Ban eingetragen.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            }
            header('Location: /?page=bans');
            exit;

        case 'del':
            $banid  = (int)($_POST['banid'] ?? 0);
            $result = api()->banDel($banid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Ban aufgehoben.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=bans');
            exit;

        case 'delall':
            $result = api()->banDelAll();
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Alle Bans aufgehoben.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=bans');
            exit;
    }
}

// ── Daten laden ───────────────────────────────────────────────
$bans  = $apiOnline ? (api()->banList()['data'] ?? []) : [];
$flash = flash_get();

// Dauer formatieren
function ban_duration(int $seconds): string {
    if ($seconds === 0) return '<span class="badge badge-red">Permanent</span>';
    if ($seconds < 60)  return $seconds . ' Sek.';
    if ($seconds < 3600) return round($seconds / 60) . ' Min.';
    if ($seconds < 86400) return round($seconds / 3600) . ' Std.';
    return round($seconds / 86400) . ' Tage';
}
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('ban.title') ?></div>
    <div class="page-sub"><?= t('ban.sub') ?></div>
  </div>
  <div class="topbar-right">
    <?php if (!empty($bans)): ?>
    <form method="post" action="/?page=bans" style="display:inline">
      <input type="hidden" name="action" value="delall">
      <button type="submit" class="btn btn-danger"
              data-confirm="<?= t('ban.confirm_all') ?>">
        <?= t('ban.delete_all') ?>
      </button>
    </form>
    <?php endif; ?>
    <button class="btn btn-primary"
            onclick="document.getElementById('modal-add-ban').style.display='flex'">
      + <?= t('ban.new') ?>
    </button>
  </div>
</div>

<div class="content">

  <?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!$apiOnline): ?>
  <div class="alert alert-danger">Keine Verbindung zur TS6 API.</div>
  <?php elseif (empty($bans)): ?>
  <div style="text-align:center;padding:64px 0;color:var(--text-subtle)">
    <div style="font-size:48px;margin-bottom:16px;opacity:0.3">⊘</div>
    <div style="font-size:15px;margin-bottom:8px"><?= t('ban.no_bans') ?></div>
    <button class="btn btn-primary" style="margin-top:8px"
            onclick="document.getElementById('modal-add-ban').style.display='flex'">
      + <?= t('ban.new') ?>
    </button>
  </div>
  <?php else: ?>

  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Ban-ID</th>
          <th>IP-Adresse</th>
          <th>Name (Regex)</th>
          <th>UID</th>
          <th>Grund</th>
          <th>Gesperrt von</th>
          <th>Dauer</th>
          <th>Erstellt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bans as $ban):
          $banid     = $ban['banid'] ?? '—';
          $ip        = $ban['ip'] ?? '';
          $name      = $ban['name'] ?? '';
          $uid       = $ban['uid'] ?? '';
          $reason    = $ban['banreason'] ?? '';
          $invoker   = $ban['invokername'] ?? '—';
          $created   = (int)($ban['created'] ?? 0);
          $duration  = (int)($ban['duration'] ?? 0);
          $enforcements = (int)($ban['enforcements'] ?? 0);
        ?>
        <tr>
          <td class="mono"><?= e($banid) ?></td>
          <td class="mono"><?= e($ip) ?: '<span style="color:var(--text-subtle)">—</span>' ?></td>
          <td><?= e($name) ?: '<span style="color:var(--text-subtle)">—</span>' ?></td>
          <td class="mono" style="font-size:11px">
            <?= $uid ? e(substr($uid, 0, 20)) . '…' : '<span style="color:var(--text-subtle)">—</span>' ?>
          </td>
          <td><?= e($reason) ?: '<span style="color:var(--text-subtle)">kein Grund</span>' ?></td>
          <td><?= e($invoker) ?></td>
          <td><?= ban_duration($duration) ?></td>
          <td class="mono" style="font-size:11px">
            <?= $created ? date('d.m.Y H:i', $created / 1000) : '—' ?>
            <?php if ($enforcements > 0): ?>
            <span class="badge badge-red" style="margin-left:4px"><?= $enforcements ?>×</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="/?page=bans" style="display:inline">
              <input type="hidden" name="action" value="del">
              <input type="hidden" name="banid" value="<?= e($banid) ?>">
              <button type="submit" class="btn btn-danger btn-sm"
                      data-confirm="<?= t('ban.confirm_delete') ?>">
                <?= t('ban.delete') ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php endif; ?>

</div>

<!-- Modal: Ban hinzufügen -->
<div id="modal-add-ban" class="modal-overlay" style="display:none">
  <div class="modal-box" style="width:500px">
    <div class="modal-title"><?= t('ban.new') ?></div>
    <form method="post" action="/?page=bans">
      <input type="hidden" name="action" value="add">

      <div class="alert alert-info" style="margin-bottom:16px">
        Mindestens eines der Felder IP, Name oder UID muss ausgefüllt sein.
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><?= t('ban.ip') ?></label>
          <input type="text" name="ban_ip" class="form-input"
                 placeholder="z.B. 1.2.3.4">
        </div>
        <div class="form-group">
          <label class="form-label"><?= t('ban.name') ?></label>
          <input type="text" name="ban_name" class="form-input"
                 placeholder="Regex oder exakter Name">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('ban.uid') ?></label>
        <input type="text" name="ban_uid" class="form-input"
               placeholder="Client UID">
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('ban.reason') ?></label>
        <input type="text" name="ban_reason" class="form-input"
               placeholder="Grund für den Ban">
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('ban.duration') ?></label>
        <input type="number" name="ban_time" class="form-input"
               value="0" min="0">
        <div class="form-hint">Sekunden — 0 = permanent</div>
      </div>

      <div class="modal-actions">
        <button type="submit" class="btn btn-danger">Ban eintragen</button>
        <button type="button" class="btn btn-ghost"
                onclick="document.getElementById('modal-add-ban').style.display='none'">
          <?= t('gen.cancel') ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
  }
});
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) overlay.style.display = 'none';
  });
});
</script>
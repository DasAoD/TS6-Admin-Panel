<?php
// =============================================================
//  pages/tokens.php — Privilege Keys verwalten
// =============================================================

// ── POST-Aktionen ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $type        = (int)($_POST['token_type'] ?? 0);
            $id1         = (int)($_POST['token_id1']  ?? 0);
            $description = trim($_POST['token_description'] ?? '');
            $url = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/tokenadd';
            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => ['x-api-key: ' . TS6_API_KEY, 'Content-Type: application/json'],
                'content' => json_encode(['tokentype' => $type, 'tokenid1' => $id1, 'tokenid2' => 0, 'tokendescription' => $description]),
                'timeout' => 5, 'ignore_errors' => true,
            ]]);
            $resp = json_decode(@file_get_contents($url, false, $ctx), true);
            if (($resp['status']['code'] ?? -1) === 0) {
                flash_set('success', 'Token erstellt: ' . ($resp['body'][0]['token'] ?? ''));
            } else {
                flash_set('danger', t('gen.error') . ': ' . ($resp['status']['message'] ?? ''));
            }
            header('Location: /?page=tokens');
            exit;

        case 'delete':
            $token = trim($_POST['token'] ?? '');
            $url = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/tokendelete';
            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => ['x-api-key: ' . TS6_API_KEY, 'Content-Type: application/json'],
                'content' => json_encode(['token' => $token]),
                'timeout' => 5, 'ignore_errors' => true,
            ]]);
            $resp = json_decode(@file_get_contents($url, false, $ctx), true);
            flash_set(($resp['status']['code'] ?? -1) === 0 ? 'success' : 'danger',
                ($resp['status']['code'] ?? -1) === 0 ? 'Token gelöscht.' : t('gen.error') . ': ' . ($resp['status']['message'] ?? ''));
            header('Location: /?page=tokens');
            exit;
    }
}

// ── Daten laden ───────────────────────────────────────────────
$url = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/tokenlist';
$ctx = stream_context_create(['http' => [
    'method'  => 'GET',
    'header'  => 'x-api-key: ' . TS6_API_KEY,
    'timeout' => 5, 'ignore_errors' => true,
]]);
$resp   = json_decode(@file_get_contents($url, false, $ctx), true);
$tokens = $resp['body'] ?? [];

// Gruppen für Dropdown
$groups = $apiOnline ? (api()->serverGroupList()['data'] ?? []) : [];
$regularGroups = array_filter($groups, fn($g) => (int)$g['type'] === 1);

$flash = flash_get();
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title">Privilege Keys</div>
    <div class="page-sub">Tokens zum einmaligen Zuweisen von Servergruppen</div>
  </div>
  <div class="topbar-right">
    <button class="btn btn-primary"
            onclick="document.getElementById('modal-add-token').style.display='flex'">
      + Token erstellen
    </button>
  </div>
</div>

<div class="content">

  <?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (empty($tokens)): ?>
  <div style="text-align:center;padding:64px 0;color:var(--text-subtle)">
    <div style="font-size:48px;margin-bottom:16px;opacity:0.3">🔑</div>
    <div style="font-size:15px;margin-bottom:8px">Keine Tokens vorhanden.</div>
    <button class="btn btn-primary" style="margin-top:8px"
            onclick="document.getElementById('modal-add-token').style.display='flex'">
      + Token erstellen
    </button>
  </div>
  <?php else: ?>

  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Token</th>
          <th>Typ</th>
          <th>Gruppe / ID</th>
          <th>Beschreibung</th>
          <th>Erstellt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tokens as $t):
          $token    = $t['token']            ?? '—';
          $type     = (int)($t['tokentype']  ?? 0);
          $id1      = $t['tokenid1']         ?? '—';
          $desc     = $t['tokendescription'] ?? '';
          $created  = (int)($t['tokencreated'] ?? 0);

          // Gruppenname nachschlagen
          $groupName = $id1;
          foreach ($groups as $g) {
              if ((int)$g['sgid'] === (int)$id1) { $groupName = $g['name']; break; }
          }
        ?>
        <tr>
          <td class="mono" style="font-size:11px;max-width:260px;word-break:break-all"><?= e($token) ?></td>
          <td>
            <span class="badge <?= $type === 0 ? 'badge-blue' : 'badge-yellow' ?>">
              <?= $type === 0 ? 'Servergruppe' : 'Channel-Gruppe' ?>
            </span>
          </td>
          <td><?= e($groupName) ?></td>
          <td><?= e($desc) ?: '<span style="color:var(--text-subtle)">—</span>' ?></td>
          <td class="mono" style="font-size:11px">
            <?= $created ? date('d.m.Y H:i', $created) : '—' ?>
          </td>
          <td>
            <form method="post" action="/?page=tokens" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="token" value="<?= e($token) ?>">
              <button type="submit" class="btn btn-danger btn-sm"
                      data-confirm="Token wirklich löschen?">
                Löschen
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

<!-- Modal: Token erstellen -->
<div id="modal-add-token" class="modal-overlay" style="display:none">
  <div class="modal-box" style="width:480px">
    <div class="modal-title">Privilege Key erstellen</div>
    <form method="post" action="/?page=tokens">
      <input type="hidden" name="action" value="add">

      <div class="alert alert-info" style="margin-bottom:16px">
        Ein Privilege Key kann einmalig von einem Client verwendet werden um automatisch eine Servergruppe zu erhalten.
      </div>

      <div class="form-group">
        <label class="form-label">Typ</label>
        <select name="token_type" class="form-select" id="token-type-select"
                onchange="document.getElementById('token-id1-label').textContent = this.value == '0' ? 'Servergruppe' : 'Channel-Gruppe'">
          <option value="0">Servergruppe</option>
          <option value="1">Channel-Gruppe</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" id="token-id1-label">Servergruppe</label>
        <select name="token_id1" class="form-select">
          <?php foreach ($regularGroups as $g): ?>
          <option value="<?= e($g['sgid']) ?>"><?= e($g['name']) ?> (sgid <?= e($g['sgid']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Beschreibung (optional)</label>
        <input type="text" name="token_description" class="form-input"
               placeholder="z.B. Für Moriqendi">
      </div>

      <div class="modal-actions">
        <button type="submit" class="btn btn-primary">Token erstellen</button>
        <button type="button" class="btn btn-ghost"
                onclick="document.getElementById('modal-add-token').style.display='none'">
          Abbrechen
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
});
document.querySelectorAll('.modal-overlay').forEach(function(o) {
  o.addEventListener('click', function(e) { if (e.target === o) o.style.display = 'none'; });
});
</script>

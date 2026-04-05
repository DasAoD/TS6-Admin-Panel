<?php
// =============================================================
//  pages/clients_db.php — Client-Datenbank (alle bekannten Clients)
// =============================================================

// ── POST-Aktionen ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cldbid = (int)($_POST['cldbid'] ?? 0);

    switch ($action) {
        case 'add_group':
            $sgid   = (int)($_POST['sgid'] ?? 0);
            $result = api()->serverGroupAddClient($sgid, $cldbid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Gruppe zugewiesen.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients_db&cldbid=' . $cldbid);
            exit;

        case 'del_group':
            $sgid   = (int)($_POST['sgid'] ?? 0);
            $result = api()->serverGroupDelClient($sgid, $cldbid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Gruppe entfernt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients_db&cldbid=' . $cldbid);
            exit;

        case 'delete_avatar':
            // Avatar-Datei löschen
            $uid = trim($_POST['uid'] ?? '');
            if ($uid) {
                $hash     = ts3_avatar_hash($uid);
                $filepath = '/opt/teamspeak6/files/virtualserver_1/internal/avatar_' . $hash;
                if (file_exists($filepath)) {
                    unlink($filepath);
                    flash_set('success', 'Avatar gelöscht.');
                } else {
                    flash_set('warning', 'Kein Avatar gefunden.');
                }
            }
            header('Location: /?page=clients_db&cldbid=' . $cldbid);
            exit;

        case 'delete_client':
            $url = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/clientdbdelete';
            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => ['x-api-key: ' . TS6_API_KEY, 'Content-Type: application/json'],
                'content' => json_encode(['cldbid' => $cldbid]),
                'timeout' => 5, 'ignore_errors' => true,
            ]]);
            $resp = json_decode(@file_get_contents($url, false, $ctx), true);
            flash_set(($resp['status']['code'] ?? -1) === 0 ? 'success' : 'danger',
                ($resp['status']['code'] ?? -1) === 0 ? 'Client aus Datenbank gelöscht.' : t('gen.error') . ': ' . ($resp['status']['message'] ?? ''));
            header('Location: /?page=clients_db');
            exit;
    }
}

// ── Avatar-Hash berechnen (TS3-Algorithmus) ───────────────────
function ts3_avatar_hash(string $uid): string {
    return strtolower(str_replace(['+', '/', '='], ['p', 'q', 'a'], $uid));
}

// ── Daten laden ───────────────────────────────────────────────
$start    = max(0, (int)($_GET['start'] ?? 0));
$limit    = 25;
$search   = trim($_GET['search'] ?? '');

// Alle DB-Clients laden
$dbResult  = api()->clientDbList($start, $limit);
$dbClients = $dbResult['data'] ?? [];

// Gesamt-Anzahl aus Count-Field
$totalCount = (int)($dbResult['data'][0]['count'] ?? count($dbClients));

// Gruppen laden
$groups        = $apiOnline ? (api()->serverGroupList()['data'] ?? []) : [];
$regularGroups = array_filter($groups, fn($g) => (int)$g['type'] === 1);
$groupMap      = [];
foreach ($groups as $g) $groupMap[(int)$g['sgid']] = $g['name'];

// Ausgewählter Client
$selectedCldbid = isset($_GET['cldbid']) ? (int)$_GET['cldbid'] : null;
$selectedClient = null;
$clientGroups   = [];

if ($selectedCldbid && $apiOnline) {
    // Client-Info via API laden
    $infoResult = api()->clientDbInfo($selectedCldbid);
    $infoData   = $infoResult['data'] ?? [];
    // TS6 gibt manchmal ein direktes Objekt, manchmal Array zurück
    if (isset($infoData[0])) {
        $selectedClient = $infoData[0];
    } elseif (!empty($infoData)) {
        $selectedClient = $infoData;
    }

    // Fallback: aus einer breiteren DB-Liste suchen
    if (empty($selectedClient)) {
        $allDbClients = api()->clientDbList(0, 200)['data'] ?? [];
        foreach ($allDbClients as $c) {
            if ((int)($c['cldbid'] ?? 0) === $selectedCldbid) {
                $selectedClient = $c;
                break;
            }
        }
    }

    // Gruppen des Clients laden
    foreach ($regularGroups as $g) {
        $members = api()->serverGroupClientList((int)$g['sgid'])['data'] ?? [];
        foreach ($members as $m) {
            if ((int)($m['cldbid'] ?? 0) === $selectedCldbid) {
                $clientGroups[] = (int)$g['sgid'];
                break;
            }
        }
    }
}

$flash = flash_get();
$avatarDir = '/opt/teamspeak6/files/virtualserver_1/internal/';
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title">Client-Datenbank</div>
    <div class="page-sub">Alle bekannten Clients — auch offline</div>
  </div>
  <div class="topbar-right">
    <form method="get" action="/" style="display:flex;gap:8px">
      <input type="hidden" name="page" value="clients_db">
      <input type="text" name="search" class="form-input" style="width:200px"
             value="<?= e($search) ?>" placeholder="Suche...">
      <button type="submit" class="btn btn-ghost">Suchen</button>
      <?php if ($search): ?>
      <a href="/?page=clients_db" class="btn btn-ghost">✕</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($flash): ?>
<div style="padding:12px 24px 0">
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
</div>
<?php endif; ?>

<!-- Layout -->
<div class="chan-layout">

  <!-- Sidebar: Client-Liste -->
  <div class="chan-sidebar" style="width:260px">
    <?php
    // ServerQuery herausfiltern für Anzeige UND Zählung
    $displayClients = array_filter($dbClients, fn($c) => ($c['client_unique_identifier'] ?? '') !== 'ServerQuery');
    ?>
    <div class="chan-sb-head">
      <span class="chan-sb-title">Clients (<?= count($displayClients) ?>)</span>
    </div>
    <div class="chan-sb-list">
      <?php if (empty($displayClients)): ?>
      <div style="padding:16px;font-size:13px;color:var(--text-muted)">Keine Clients gefunden.</div>
      <?php else: foreach ($displayClients as $c):
        $cldbid   = (int)($c['cldbid'] ?? 0);
        $nick     = $c['client_nickname'] ?? '—';
        $uid      = $c['client_unique_identifier'] ?? '';
        $lastConn = (int)($c['client_lastconnected'] ?? 0);
        $isActive = ($selectedCldbid === $cldbid);

        // Avatar vorhanden?
        $avatarHash = ts3_avatar_hash($uid);
        $hasAvatar  = file_exists($avatarDir . 'avatar_' . $avatarHash);
      ?>
      <a href="/?page=clients_db&cldbid=<?= $cldbid ?>"
         class="chan-item <?= $isActive ? 'active' : '' ?>">
        <?php if ($hasAvatar): ?>
        <span class="chan-item-icon" style="color:var(--accent)">◉</span>
        <?php else: ?>
        <span class="chan-item-icon">@</span>
        <?php endif; ?>
        <span class="chan-item-name"><?= e($nick) ?></span>
        <span style="font-size:10px;color:var(--text-subtle);flex-shrink:0;font-family:var(--font-mono)">DB-ID <?= $cldbid ?></span>
      </a>
      <?php endforeach; endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (count($dbClients) >= $limit || $start > 0): ?>
    <div style="padding:8px;border-top:1px solid var(--border);display:flex;gap:6px;justify-content:center">
      <?php if ($start > 0): ?>
      <a href="/?page=clients_db&start=<?= max(0, $start - $limit) ?>" class="btn btn-ghost btn-sm">← Zurück</a>
      <?php endif; ?>
      <?php if (count($dbClients) >= $limit): ?>
      <a href="/?page=clients_db&start=<?= $start + $limit ?>" class="btn btn-ghost btn-sm">Weiter →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Detail -->
  <div class="chan-detail">
    <?php if ($selectedClient): ?>

    <?php
      $nick      = $selectedClient['client_nickname']          ?? '—';
      $uid       = $selectedClient['client_unique_identifier'] ?? '—';
      $cldbid    = (int)($selectedClient['cldbid']             ?? $selectedCldbid);
      $lastConn  = (int)($selectedClient['client_lastconnected'] ?? 0);
      $created   = (int)($selectedClient['client_created']     ?? 0);
      $totalConn = $selectedClient['client_totalconnections']  ?? '0';
      $lastip    = $selectedClient['client_lastip']            ?? '—';

      $avatarHash  = ts3_avatar_hash($uid);
      $avatarFile  = $avatarDir . 'avatar_' . $avatarHash;
      $hasAvatar   = file_exists($avatarFile);
    ?>

    <div class="chan-detail-head">
      <div class="chan-detail-title">
        <?= e($nick) ?>
        <span class="badge badge-gray">DB-ID <?= $cldbid ?></span>
      </div>
      <div class="chan-detail-actions">
        <form method="post" action="/?page=clients_db" style="display:inline">
          <input type="hidden" name="action" value="delete_client">
          <input type="hidden" name="cldbid" value="<?= $cldbid ?>">
          <button type="submit" class="btn btn-danger btn-sm"
                  data-confirm="Client &quot;<?= e($nick) ?>&quot; aus der Datenbank löschen? Alle zugewiesenen Gruppen gehen verloren.">
            Aus DB löschen
          </button>
        </form>
      </div>
    </div>

    <div class="chan-detail-body">

      <!-- Client-Info -->
      <div class="form-section">
        <div class="form-section-head">Client-Informationen</div>
        <div class="form-section-body">

          <?php if ($hasAvatar): ?>
          <!-- Avatar anzeigen -->
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;
                      padding:12px;background:var(--bg-base);border-radius:var(--radius-md);
                      border:1px solid var(--border)">
            <img src="/avatar.php?uid=<?= urlencode($uid) ?>"
                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid var(--border)"
                 onerror="this.style.display='none'">
            <div style="flex:1">
              <div style="font-size:13px;font-weight:500;color:var(--text-primary)">Avatar vorhanden</div>
              <div style="font-size:11px;color:var(--text-muted)">avatar_<?= e($avatarHash) ?></div>
            </div>
            <form method="post" action="/?page=clients_db">
              <input type="hidden" name="action" value="delete_avatar">
              <input type="hidden" name="cldbid" value="<?= $cldbid ?>">
              <input type="hidden" name="uid" value="<?= e($uid) ?>">
              <button type="submit" class="btn btn-danger btn-sm"
                      data-confirm="Avatar von &quot;<?= e($nick) ?>&quot; wirklich löschen?">
                Avatar löschen
              </button>
            </form>
          </div>
          <?php endif; ?>

          <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php
            $fields = [
              'Nickname'        => $nick,
              'Datenbank-ID'    => $cldbid,
              'Unique ID'       => $uid,
              'Erstellt'        => $created   ? date('d.m.Y H:i', $created)  : '—',
              'Zuletzt verbunden' => $lastConn ? date('d.m.Y H:i', $lastConn) : '—',
              'Verbindungen'    => $totalConn,
              'Letzte IP'       => $lastip,
            ];
            foreach ($fields as $label => $val): ?>
            <tr>
              <td style="padding:6px 0;color:var(--text-muted);width:160px"><?= e($label) ?></td>
              <td style="padding:6px 0;font-family:var(--font-mono);font-size:12px;color:var(--text-primary)"><?= e((string)$val) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>

      <!-- Servergruppen -->
      <div class="form-section">
        <div class="form-section-head" style="display:flex;align-items:center;justify-content:space-between">
          <span>Servergruppen</span>
          <button class="btn btn-ghost btn-sm"
                  onclick="document.getElementById('modal-add-group').style.display='flex'">
            + Gruppe zuweisen
          </button>
        </div>
        <?php if (empty($clientGroups)): ?>
        <div style="padding:14px;font-size:13px;color:var(--text-muted)">Keine Gruppen zugewiesen.</div>
        <?php else: ?>
        <table class="data">
          <thead><tr><th>Gruppe</th><th>sgid</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($clientGroups as $sgid): ?>
            <tr>
              <td><?= e($groupMap[$sgid] ?? "Gruppe $sgid") ?></td>
              <td class="mono"><?= $sgid ?></td>
              <td>
                <form method="post" action="/?page=clients_db" style="display:inline">
                  <input type="hidden" name="action" value="del_group">
                  <input type="hidden" name="cldbid" value="<?= $cldbid ?>">
                  <input type="hidden" name="sgid" value="<?= $sgid ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          data-confirm="Gruppe &quot;<?= e($groupMap[$sgid] ?? $sgid) ?>&quot; entfernen?">
                    Entfernen
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>

    <!-- Modal: Gruppe zuweisen -->
    <div id="modal-add-group" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Gruppe zuweisen an <?= e($nick) ?></div>
        <form method="post" action="/?page=clients_db">
          <input type="hidden" name="action" value="add_group">
          <input type="hidden" name="cldbid" value="<?= $cldbid ?>">
          <div class="form-group">
            <label class="form-label">Servergruppe</label>
            <select name="sgid" class="form-select">
              <?php foreach ($regularGroups as $g):
                if (in_array((int)$g['sgid'], $clientGroups)) continue; ?>
              <option value="<?= e($g['sgid']) ?>"><?= e($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary">Zuweisen</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-add-group').style.display='none'">
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

    <?php else: ?>
    <div class="chan-placeholder">
      <div style="font-size:32px;margin-bottom:12px;opacity:0.3">@</div>
      <div>Client auswählen für Details und Gruppen-Verwaltung</div>
    </div>
    <?php endif; ?>

  </div>
</div>
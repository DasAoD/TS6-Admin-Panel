<?php
// =============================================================
//  pages/clients.php — Client-Verwaltung
// =============================================================

// ── POST-Aktionen verarbeiten ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $clid   = (int)($_POST['clid'] ?? 0);

    switch ($action) {

        case 'kick':
            $msg    = trim($_POST['reason'] ?? '');
            $result = api()->clientKick($clid, 5, $msg); // reasonid 5 = kick from server
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Client gekickt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients');
            exit;

        case 'kick_channel':
            $msg    = trim($_POST['reason'] ?? '');
            $result = api()->clientKick($clid, 4, $msg); // reasonid 4 = kick from channel
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Client aus Channel gekickt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients');
            exit;

        case 'ban':
            $reason = trim($_POST['reason'] ?? '');
            $time   = (int)($_POST['ban_time'] ?? 0);
            $result = api()->banClient($clid, $time, $reason);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Client gebannt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients');
            exit;

        case 'move':
            $cid    = (int)($_POST['target_cid'] ?? 0);
            $result = api()->clientMove($clid, $cid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Client verschoben.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients');
            exit;

        case 'poke':
            $msg    = trim($_POST['poke_msg'] ?? '');
            $result = api()->clientPoke($clid, $msg);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Poke gesendet.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=clients');
            exit;
    }
}

// ── Daten laden ───────────────────────────────────────────────
$clientListRaw = $apiOnline ? (api()->clientList()['data'] ?? []) : [];
$channelList   = $apiOnline ? (api()->channelList()['data'] ?? []) : [];
$groupList     = $apiOnline ? (api()->serverGroupList()['data'] ?? []) : [];

// Nur echte Clients (keine Query-Clients)
$clients = array_filter($clientListRaw, fn($c) => ($c['client_type'] ?? 0) == 0);

// Channel-Map für schnelle Suche
$channelMap = [];
foreach ($channelList as $ch) {
    $channelMap[(int)$ch['cid']] = $ch['channel_name'] ?? '—';
}

// Gruppen-Map
$groupMap = [];
foreach ($groupList as $g) {
    $groupMap[(int)$g['sgid']] = $g['name'] ?? '—';
}

// Ausgewählter Client
$selectedClid = isset($_GET['clid']) ? (int)$_GET['clid'] : null;
$clientInfo   = null;

if ($selectedClid && $apiOnline) {
    $infoResult = api()->clientInfo($selectedClid);
    $clientInfo = $infoResult['data'][0] ?? ($infoResult['data'] ?? null);
}

$flash = flash_get();

// Gruppenname aus kommaseparierter ID-Liste
function resolve_groups(string $sgids, array $groupMap): string {
    $ids    = array_filter(explode(',', $sgids));
    $names  = array_map(fn($id) => $groupMap[(int)$id] ?? "Gruppe $id", $ids);
    return implode(', ', $names) ?: '—';
}
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('cli.title') ?></div>
    <div class="page-sub"><?= t('cli.sub') ?></div>
  </div>
  <div class="topbar-right">
    <span style="font-size:13px;color:var(--text-muted)">
      <?= count($clients) ?> / 32 Slots belegt
    </span>
  </div>
</div>

<?php if ($flash): ?>
<div style="padding:12px 24px 0">
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
</div>
<?php endif; ?>

<!-- Layout: Liste + Detail -->
<div class="chan-layout">

  <!-- Sidebar: Client-Liste -->
  <div class="chan-sidebar">
    <div class="chan-sb-head">
      <span class="chan-sb-title">Online (<?= count($clients) ?>)</span>
    </div>
    <div class="chan-sb-list">
      <?php if (empty($clients)): ?>
        <div style="padding:16px;font-size:13px;color:var(--text-muted)"><?= t('cli.no_clients') ?></div>
      <?php else: foreach ($clients as $c):
        $clid     = (int)($c['clid'] ?? 0);
        $nick     = $c['client_nickname'] ?? '—';
        $cid      = (int)($c['cid'] ?? 0);
        $chanName = $channelMap[$cid] ?? '—';
        $isActive = ($selectedClid === $clid);
        $isAway   = !empty($c['client_away']);
      ?>
        <a href="/?page=clients&clid=<?= $clid ?>"
           class="chan-item <?= $isActive ? 'active' : '' ?>">
          <span class="chan-item-icon" style="color:<?= $isAway ? 'var(--yellow)' : 'var(--green)' ?>">●</span>
          <span class="chan-item-name"><?= e($nick) ?></span>
          <span class="chan-item-cnt" title="<?= e($chanName) ?>"
                style="max-width:60px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= e(mb_strimwidth($chanName, 0, 10, '…')) ?>
          </span>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Detail -->
  <div class="chan-detail">
    <?php if ($clientInfo): ?>

    <?php
      $nick       = $clientInfo['client_nickname'] ?? '—';
      $uid        = $clientInfo['client_unique_identifier'] ?? '—';
      $cldbid     = $clientInfo['client_database_id'] ?? '—';
      $cid        = (int)($clientInfo['cid'] ?? 0);
      $chanName   = $channelMap[$cid] ?? '—';
      $sgids      = $clientInfo['client_servergroups'] ?? '';
      $groups     = resolve_groups($sgids, $groupMap);
      $connTime   = (int)($clientInfo['connection_connected_time'] ?? 0);
      $idleTime   = (int)($clientInfo['client_idle_time'] ?? 0);
      $version    = $clientInfo['client_version'] ?? '—';
      $platform   = $clientInfo['client_platform'] ?? '—';
      $ip         = $clientInfo['connection_client_ip'] ?? '—';
      $isAway     = !empty($clientInfo['client_away']);
      $awayMsg    = $clientInfo['client_away_message'] ?? '';
    ?>

    <div class="chan-detail-head">
      <div class="chan-detail-title">
        <span style="color:<?= $isAway ? 'var(--yellow)' : 'var(--green)' ?>">●</span>
        <?= e($nick) ?>
        <?php if ($isAway): ?>
        <span class="badge badge-yellow">Abwesend</span>
        <?php endif; ?>
      </div>
      <div class="chan-detail-actions">
        <!-- Poke -->
        <button class="btn btn-ghost btn-sm"
                onclick="document.getElementById('modal-poke').style.display='flex'">
          Poke
        </button>
        <!-- Move -->
        <button class="btn btn-ghost btn-sm"
                onclick="document.getElementById('modal-move').style.display='flex'">
          <?= t('cli.move') ?>
        </button>
        <!-- Kick Channel -->
        <button class="btn btn-ghost btn-sm"
                onclick="document.getElementById('modal-kick-ch').style.display='flex'">
          Kick (Channel)
        </button>
        <!-- Kick Server -->
        <button class="btn btn-danger btn-sm"
                onclick="document.getElementById('modal-kick').style.display='flex'">
          <?= t('cli.kick') ?>
        </button>
        <!-- Ban -->
        <button class="btn btn-danger btn-sm"
                onclick="document.getElementById('modal-ban').style.display='flex'">
          <?= t('cli.ban') ?>
        </button>
      </div>
    </div>

    <div class="chan-detail-body">

      <!-- Client-Info -->
      <div class="form-section">
        <div class="form-section-head">Client-Informationen</div>
        <div class="form-section-body">
          <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php
            $connSec  = intdiv($connTime, 1000);
            $idleSec  = intdiv($idleTime, 1000);
            $fields = [
              'Nickname'          => $nick,
              'Unique ID'         => $uid,
              'Datenbank-ID'      => $cldbid,
              'Aktueller Channel' => $chanName,
              'Servergruppen'     => $groups,
              'Verbunden seit'    => gmdate('H:i:s', $connSec),
              'Idle-Zeit'         => gmdate('H:i:s', $idleSec),
              'Version'           => $version,
              'Plattform'         => $platform,
              'IP-Adresse'        => $ip,
            ];
            if ($isAway && $awayMsg) $fields['Abwesenheitsnachricht'] = $awayMsg;
            foreach ($fields as $label => $val): ?>
            <tr>
              <td style="padding:6px 0;color:var(--text-muted);width:180px;vertical-align:top"><?= e($label) ?></td>
              <td style="padding:6px 0;color:var(--text-primary);font-family:var(--font-mono);font-size:12px"><?= e((string)$val) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>

    </div>

    <!-- ── Modals ─────────────────────────────────────────── -->

    <!-- Kick Server -->
    <div id="modal-kick" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Client vom Server kicken</div>
        <form method="post" action="/?page=clients">
          <input type="hidden" name="action" value="kick">
          <input type="hidden" name="clid" value="<?= $selectedClid ?>">
          <div class="form-group">
            <label class="form-label">Nachricht (optional)</label>
            <input type="text" name="reason" class="form-input" placeholder="Grund für den Kick" autofocus>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-danger">Kicken</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-kick').style.display='none'">
              <?= t('gen.cancel') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Kick Channel -->
    <div id="modal-kick-ch" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Client aus Channel kicken</div>
        <form method="post" action="/?page=clients">
          <input type="hidden" name="action" value="kick_channel">
          <input type="hidden" name="clid" value="<?= $selectedClid ?>">
          <div class="form-group">
            <label class="form-label">Nachricht (optional)</label>
            <input type="text" name="reason" class="form-input" placeholder="Grund" autofocus>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-danger">Aus Channel kicken</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-kick-ch').style.display='none'">
              <?= t('gen.cancel') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Ban -->
    <div id="modal-ban" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Client bannen</div>
        <form method="post" action="/?page=clients">
          <input type="hidden" name="action" value="ban">
          <input type="hidden" name="clid" value="<?= $selectedClid ?>">
          <div class="form-group">
            <label class="form-label"><?= t('cli.ban_reason') ?></label>
            <input type="text" name="reason" class="form-input" placeholder="Grund" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('cli.ban_duration') ?></label>
            <input type="number" name="ban_time" class="form-input" value="0" min="0">
            <div class="form-hint">Sekunden — 0 = permanent</div>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-danger">Bannen</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-ban').style.display='none'">
              <?= t('gen.cancel') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Move -->
    <div id="modal-move" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Client verschieben</div>
        <form method="post" action="/?page=clients">
          <input type="hidden" name="action" value="move">
          <input type="hidden" name="clid" value="<?= $selectedClid ?>">
          <div class="form-group">
            <label class="form-label">Ziel-Channel</label>
            <select name="target_cid" class="form-select" autofocus>
              <?php foreach ($channelList as $ch):
                if (is_spacer($ch['channel_name'] ?? '')) continue;
                $selected = ((int)$ch['cid'] === $cid) ? 'selected' : '';
              ?>
              <option value="<?= e($ch['cid']) ?>" <?= $selected ?>>
                <?= e($ch['channel_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary">Verschieben</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-move').style.display='none'">
              <?= t('gen.cancel') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Poke -->
    <div id="modal-poke" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Poke senden an <?= e($nick) ?></div>
        <form method="post" action="/?page=clients">
          <input type="hidden" name="action" value="poke">
          <input type="hidden" name="clid" value="<?= $selectedClid ?>">
          <div class="form-group">
            <label class="form-label">Nachricht</label>
            <input type="text" name="poke_msg" class="form-input"
                   placeholder="Deine Nachricht..." maxlength="100" autofocus>
          <p style="color:var(--yellow);font-size:12px;margin-top:6px">⚠ Antworten auf Pokes sind aufgrund eines TS6-Beta-Bugs nicht möglich.</p>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary">Senden</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-poke').style.display='none'">
              <?= t('gen.cancel') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal schließen mit Escape oder Klick außerhalb -->
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

    <?php elseif (empty($clients)): ?>
    <div class="chan-placeholder">
      <div style="font-size:32px;margin-bottom:12px;opacity:0.3">@</div>
      <div>Keine Clients verbunden.</div>
    </div>
    <?php else: ?>
    <div class="chan-placeholder">
      <div style="font-size:32px;margin-bottom:12px;opacity:0.3">@</div>
      <div>Client auswählen für Details und Aktionen.</div>
    </div>
    <?php endif; ?>

  </div>
</div>
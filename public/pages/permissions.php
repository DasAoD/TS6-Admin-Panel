<?php
// =============================================================
//  pages/permissions.php — Berechtigungsverwaltung
// =============================================================

// ── POST-Aktionen ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target = $_POST['target'] ?? 'group'; // group | channel

    switch ($action) {

        case 'add_perm':
            $permsid = trim($_POST['permsid'] ?? '');
            $value   = (int)($_POST['permvalue'] ?? 0);
            $neg     = (int)(!empty($_POST['permnegated']));
            $skip    = (int)(!empty($_POST['permskip']));

            if ($target === 'group') {
                $sgid   = (int)($_POST['sgid'] ?? 0);
                $result = api()->serverGroupAddPerm($sgid, $permsid, $value, $neg, $skip);
                $redirect = "/?page=permissions&target=group&sgid=$sgid";
            } else {
                $cid    = (int)($_POST['cid'] ?? 0);
                // Channel-Berechtigung setzen via direktem API-Call
                $url = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/channeladdperm';
                $ctx = stream_context_create(['http' => [
                    'method'  => 'POST',
                    'header'  => ['x-api-key: ' . TS6_API_KEY, 'Content-Type: application/json'],
                    'content' => json_encode(['cid' => $cid, 'permsid' => $permsid, 'permvalue' => $value, 'permnegated' => $neg]),
                    'timeout' => 5, 'ignore_errors' => true,
                ]]);
                $raw    = @file_get_contents($url, false, $ctx);
                $resp   = json_decode($raw, true);
                $result = ['success' => ($resp['status']['code'] ?? -1) === 0, 'error' => $resp['status']['message'] ?? ''];
                $redirect = "/?page=permissions&target=channel&cid=$cid";
            }

            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Berechtigung gesetzt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: ' . $redirect);
            exit;

        case 'del_perm':
            $permsid = trim($_POST['permsid'] ?? '');

            if ($target === 'group') {
                $sgid = (int)($_POST['sgid'] ?? 0);
                $url  = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/servergroupdelperm';
                $body = json_encode(['sgid' => $sgid, 'permsid' => $permsid]);
                $redirect = "/?page=permissions&target=group&sgid=$sgid";
            } else {
                $cid  = (int)($_POST['cid'] ?? 0);
                $url  = 'http://' . TS6_API_HOST . ':' . TS6_API_PORT . '/' . TS6_VSERVER_ID . '/channeldelperm';
                $body = json_encode(['cid' => $cid, 'permsid' => $permsid]);
                $redirect = "/?page=permissions&target=channel&cid=$cid";
            }

            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => ['x-api-key: ' . TS6_API_KEY, 'Content-Type: application/json'],
                'content' => $body,
                'timeout' => 5, 'ignore_errors' => true,
            ]]);
            $raw  = @file_get_contents($url, false, $ctx);
            $resp = json_decode($raw, true);
            $ok   = ($resp['status']['code'] ?? -1) === 0;

            flash_set($ok ? 'success' : 'danger',
                $ok ? 'Berechtigung entfernt.' : t('gen.error') . ': ' . ($resp['status']['message'] ?? ''));
            header('Location: ' . $redirect);
            exit;
    }
}

// ── Modus: group oder channel ─────────────────────────────────
$target      = $_GET['target'] ?? 'group';
$selectedSgid = isset($_GET['sgid']) ? (int)$_GET['sgid'] : null;
$selectedCid  = isset($_GET['cid'])  ? (int)$_GET['cid']  : null;

// ── Daten laden ───────────────────────────────────────────────
$groups   = $apiOnline ? (api()->serverGroupList()['data'] ?? []) : [];
$channels = $apiOnline ? (api()->channelList()['data'] ?? []) : [];

// Reguläre Gruppen
$regularGroups = array_filter($groups, fn($g) => (int)$g['type'] === 1);

// Aktuelle Berechtigungen
$currentPerms = [];
if ($target === 'group' && $selectedSgid && $apiOnline) {
    $currentPerms = api()->serverGroupPermList($selectedSgid)['data'] ?? [];
}
if ($target === 'channel' && $selectedCid && $apiOnline) {
    $currentPerms = api()->channelPermList($selectedCid)['data'] ?? [];
}

$flash = flash_get();

// Häufig genutzte Berechtigungen als Schnellauswahl
$commonPerms = [
    'Kanal-Zugang'    => ['i_channel_join_power', 'i_channel_subscribe_power', 'b_channel_join_permanent', 'b_channel_join_temporary', 'b_channel_join_semi_permanent'],
    'Kanal-Verwaltung'=> ['i_channel_modify_power', 'i_channel_delete_power', 'b_channel_create_temporary', 'b_channel_create_semi_permanent'],
    'Client-Rechte'   => ['i_client_move_power', 'i_client_kick_from_channel_power', 'i_client_kick_from_server_power', 'i_client_ban_power', 'i_client_poke_power'],
    'Gruppen-Verwaltung'=> ['i_group_needed_modify_power', 'i_group_needed_member_add_power', 'i_group_needed_member_remove_power'],
    'Chat'            => ['b_client_channel_textmessage_send', 'b_client_server_textmessage_send', 'b_client_private_textmessage_power'],
];
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('nav.permissions') ?></div>
    <div class="page-sub">Berechtigungen für Servergruppen und Channels</div>
  </div>
</div>

<!-- Tabs -->
<div style="display:flex;background:var(--bg-surface);border-bottom:1px solid var(--border);padding:0 24px;flex-shrink:0">
  <a href="/?page=permissions&target=group<?= $selectedSgid ? '&sgid='.$selectedSgid : '' ?>"
     style="padding:10px 18px;font-size:13px;cursor:pointer;color:<?= $target === 'group' ? 'var(--accent)' : 'var(--text-muted)' ?>;
            border-bottom:2px solid <?= $target === 'group' ? 'var(--accent)' : 'transparent' ?>;
            margin-bottom:-1px;text-decoration:none;transition:color 0.1s">
    Servergruppen
  </a>
  <a href="/?page=permissions&target=channel<?= $selectedCid ? '&cid='.$selectedCid : '' ?>"
     style="padding:10px 18px;font-size:13px;cursor:pointer;color:<?= $target === 'channel' ? 'var(--accent)' : 'var(--text-muted)' ?>;
            border-bottom:2px solid <?= $target === 'channel' ? 'var(--accent)' : 'transparent' ?>;
            margin-bottom:-1px;text-decoration:none;transition:color 0.1s">
    Channels
  </a>
</div>

<?php if ($flash): ?>
<div style="padding:12px 24px 0">
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
</div>
<?php endif; ?>

<!-- Layout -->
<div class="chan-layout">

  <!-- Sidebar: Auswahlliste -->
  <div class="chan-sidebar">
    <div class="chan-sb-head">
      <span class="chan-sb-title">
        <?= $target === 'group' ? 'Servergruppen' : 'Channels' ?>
      </span>
    </div>
    <div class="chan-sb-list">
      <?php if ($target === 'group'): ?>
        <?php foreach ($regularGroups as $g):
          $sgid     = (int)$g['sgid'];
          $isActive = ($selectedSgid === $sgid);
        ?>
        <a href="/?page=permissions&target=group&sgid=<?= $sgid ?>"
           class="chan-item <?= $isActive ? 'active' : '' ?>">
          <span class="chan-item-icon">◉</span>
          <span class="chan-item-name"><?= e($g['name']) ?></span>
        </a>
        <?php endforeach; ?>

      <?php else: ?>
        <?php foreach ($channels as $ch):
          $cid      = (int)$ch['cid'];
          $name     = $ch['channel_name'] ?? '—';
          $isSpacer = is_spacer($name);
          $isActive = ($selectedCid === $cid);
          if ($isSpacer): ?>
            <div class="chan-spacer"><?= e(preg_replace('/^\[[\*c]+spacer\d*\]/', '', $name)) ?></div>
          <?php else: ?>
            <a href="/?page=permissions&target=channel&cid=<?= $cid ?>"
               class="chan-item <?= $isActive ? 'active' : '' ?>">
              <span class="chan-item-icon"><?= !empty($ch['channel_flag_password']) ? '🔒' : '#' ?></span>
              <span class="chan-item-name"><?= e($name) ?></span>
            </a>
          <?php endif;
        endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Detail: Berechtigungen -->
  <div class="chan-detail">
    <?php
    $hasSelection = ($target === 'group' && $selectedSgid) || ($target === 'channel' && $selectedCid);
    $hiddenTarget = $target === 'group'
        ? '<input type="hidden" name="sgid" value="' . $selectedSgid . '">'
        : '<input type="hidden" name="cid" value="' . $selectedCid . '">';
    $titleLabel = '';
    if ($target === 'group' && $selectedSgid) {
        foreach ($regularGroups as $g) {
            if ((int)$g['sgid'] === $selectedSgid) { $titleLabel = $g['name']; break; }
        }
    }
    if ($target === 'channel' && $selectedCid) {
        foreach ($channels as $ch) {
            if ((int)$ch['cid'] === $selectedCid) { $titleLabel = $ch['channel_name']; break; }
        }
    }
    ?>

    <?php if (!$hasSelection): ?>
    <div class="chan-placeholder">
      <div style="font-size:32px;margin-bottom:12px;opacity:0.3">⊞</div>
      <div><?= $target === 'group' ? 'Servergruppe' : 'Channel' ?> auswählen</div>
    </div>

    <?php else: ?>

    <div class="chan-detail-head">
      <div class="chan-detail-title"><?= e($titleLabel) ?></div>
    </div>

    <div class="chan-detail-body">

      <!-- Berechtigung hinzufügen -->
      <div class="form-section">
        <div class="form-section-head">Berechtigung setzen</div>
        <div class="form-section-body">
          <form method="post" action="/?page=permissions">
            <input type="hidden" name="action" value="add_perm">
            <input type="hidden" name="target" value="<?= e($target) ?>">
            <?= $hiddenTarget ?>

            <div class="form-row">
              <div class="form-group" style="flex:2">
                <label class="form-label">Permission-Name (permsid)</label>
                <input type="text" name="permsid" class="form-input"
                       placeholder="z.B. i_channel_join_power"
                       list="perm-suggestions" required autofocus>
                <datalist id="perm-suggestions">
                  <?php foreach ($commonPerms as $cat => $perms): ?>
                    <?php foreach ($perms as $p): ?>
                    <option value="<?= e($p) ?>"><?= e($cat) ?>: <?= e($p) ?></option>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="form-group" style="flex:1">
                <label class="form-label">Wert</label>
                <input type="number" name="permvalue" class="form-input" value="1">
              </div>
            </div>
            <?php if ($target === 'group'): ?>
            <div style="display:flex;gap:20px;margin-bottom:12px">
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="permnegated" value="1"> Negiert
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="permskip" value="1"> Skip
              </label>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-sm">Berechtigung setzen</button>
          </form>

          <!-- Schnellauswahl -->
          <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:12px">
            <div style="font-size:11px;color:var(--text-subtle);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px">
              Schnellauswahl
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
              <?php foreach ($commonPerms as $cat => $perms): ?>
                <?php foreach ($perms as $p): ?>
                <button type="button"
                        onclick="document.querySelector('[name=permsid]').value='<?= e($p) ?>'"
                        class="btn btn-ghost btn-sm" style="font-size:11px;font-family:var(--font-mono)">
                  <?= e($p) ?>
                </button>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Aktuelle Berechtigungen -->
      <div class="form-section">
        <div class="form-section-head">
          Aktuelle Berechtigungen (<?= count($currentPerms) ?>)
        </div>
        <?php if (empty($currentPerms)): ?>
        <div style="padding:16px;font-size:13px;color:var(--text-muted)">
          Keine Berechtigungen gesetzt.
        </div>
        <?php else: ?>
        <div style="max-height:500px;overflow-y:auto">
          <table class="data">
            <thead>
              <tr>
                <th>Permission</th>
                <th style="text-align:right">Wert</th>
                <?php if ($target === 'group'): ?>
                <th style="text-align:center">Negiert</th>
                <th style="text-align:center">Skip</th>
                <?php endif; ?>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($currentPerms as $p):
                $permsid = $p['permsid'] ?? $p['permid'] ?? '—';
                $value   = $p['permvalue'] ?? '—';
                $neg     = (int)($p['permnegated'] ?? 0);
                $skip    = (int)($p['permskip']    ?? 0);
              ?>
              <tr>
                <td class="mono" style="font-size:12px"><?= e($permsid) ?></td>
                <td style="text-align:right;font-family:var(--font-mono)"><?= e($value) ?></td>
                <?php if ($target === 'group'): ?>
                <td style="text-align:center"><?= $neg  ? '<span class="badge badge-red">ja</span>'    : '—' ?></td>
                <td style="text-align:center"><?= $skip ? '<span class="badge badge-yellow">ja</span>' : '—' ?></td>
                <?php endif; ?>
                <td>
                  <form method="post" action="/?page=permissions" style="display:inline">
                    <input type="hidden" name="action" value="del_perm">
                    <input type="hidden" name="target" value="<?= e($target) ?>">
                    <input type="hidden" name="permsid" value="<?= e($permsid) ?>">
                    <?= $hiddenTarget ?>
                    <button type="submit" class="btn btn-danger btn-sm"
                            data-confirm="Berechtigung &quot;<?= e($permsid) ?>&quot; wirklich entfernen?">
                      Entfernen
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

    </div>
    <?php endif; ?>
  </div>
</div>
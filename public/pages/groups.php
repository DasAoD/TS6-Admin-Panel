<?php
// =============================================================
//  pages/groups.php — Servergruppen-Verwaltung
// =============================================================

// ── POST-Aktionen ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sgid   = (int)($_POST['sgid'] ?? 0);

    switch ($action) {

        case 'create':
            $name   = trim($_POST['group_name'] ?? '');
            $type   = (int)($_POST['group_type'] ?? 1);
            $result = api()->serverGroupAdd($name, $type);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Gruppe angelegt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups');
            exit;

        case 'rename':
            $name   = trim($_POST['group_name'] ?? '');
            $result = api()->serverGroupRename($sgid, $name);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Gruppe umbenannt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups&sgid=' . $sgid);
            exit;

        case 'delete':
            $result = api()->serverGroupDel($sgid, true);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Gruppe gelöscht.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups');
            exit;

        case 'add_perm':
            $permsid = trim($_POST['permsid'] ?? '');
            if (!preg_match('/^[a-z]_[a-z0-9_]+$/', $permsid)) {
                flash_set('danger', t('gen.error') . ': ungültiger Permission-Name.');
                header('Location: /?page=groups&sgid=' . $sgid);
                exit;
            }
            $result = api()->serverGroupAddPerm($sgid, $permsid, (int)($_POST['permvalue'] ?? 0),
                !empty($_POST['permnegated']) ? 1 : 0, !empty($_POST['permskip']) ? 1 : 0);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Recht gespeichert: ' . $permsid : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups&sgid=' . $sgid);
            exit;

        case 'del_perm':
            $permsid = trim($_POST['permsid'] ?? '');
            $result  = api()->serverGroupDelPerm($sgid, $permsid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Recht entfernt: ' . $permsid : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups&sgid=' . $sgid);
            exit;

        case 'set_icon':
            // Icon-ID als vorzeichenlose oder vorzeichenbehaftete Zahl; TS erwartet int32
            $iconId = (int)($_POST['icon_id'] ?? 0);
            if ($iconId > 2147483647) $iconId -= 4294967296;
            $result = api()->serverGroupAddPerm($sgid, 'i_icon_id', $iconId);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Gruppen-Icon gesetzt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups&sgid=' . $sgid);
            exit;

        case 'add_client':
            $cldbid = (int)($_POST['cldbid'] ?? 0);
            $result = api()->serverGroupAddClient($sgid, $cldbid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Mitglied hinzugefügt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups&sgid=' . $sgid);
            exit;

        case 'del_client':
            $cldbid = (int)($_POST['cldbid'] ?? 0);
            $result = api()->serverGroupDelClient($sgid, $cldbid);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Mitglied entfernt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=groups&sgid=' . $sgid);
            exit;
    }
}

// ── Daten laden ───────────────────────────────────────────────
$allGroups   = $apiOnline ? (api()->serverGroupList()['data'] ?? []) : [];
$selectedSgid = isset($_GET['sgid']) ? (int)$_GET['sgid'] : null;
$showNew     = isset($_GET['new']);

$groupMembers = [];
$groupPerms   = [];
$selectedGroup = null;

if ($selectedSgid && $apiOnline) {
    foreach ($allGroups as $g) {
        if ((int)$g['sgid'] === $selectedSgid) {
            $selectedGroup = $g;
            break;
        }
    }
    $membersResult = api()->serverGroupClientList($selectedSgid);
    $groupMembers  = $membersResult['data'] ?? [];
    $permsResult   = api()->serverGroupPermList($selectedSgid);
    $groupPerms    = $permsResult['data'] ?? [];
    $permNames     = array_column(api()->permissionList()['data'] ?? [], 'permname');
    $iconPerm      = null;
    foreach ($groupPerms as $gp) { if (($gp['permsid'] ?? '') === 'i_icon_id') { $iconPerm = (int)$gp['permvalue']; } }
}

// Typ-Label
function group_type_label(int $type): string {
    return match($type) {
        0 => 'Template',
        1 => 'Regulär',
        2 => 'Query',
        default => "Typ $type"
    };
}

// Typ-Badge-Klasse
function group_type_badge(int $type): string {
    return match($type) {
        0 => 'badge-gray',
        1 => 'badge-blue',
        2 => 'badge-yellow',
        default => 'badge-gray'
    };
}

// Gruppen-Farben für Dots
$colors = ['#f85149','#00b4d8','#e3b341','#3fb950','#a371f7','#ffa657','#79c0ff','#ff7b72'];

$flash = flash_get();
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('grp.title') ?></div>
    <div class="page-sub"><?= t('grp.sub') ?></div>
  </div>
  <div class="topbar-right">
    <a href="/?page=groups&new=1" class="btn btn-primary">+ <?= t('grp.new') ?></a>
  </div>
</div>

<?php if ($flash): ?>
<div style="padding:12px 24px 0">
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
</div>
<?php endif; ?>

<!-- Layout -->
<div class="chan-layout">

  <!-- Sidebar: Gruppen-Liste -->
  <div class="chan-sidebar">
    <div class="chan-sb-head">
      <span class="chan-sb-title">Gruppen (<?= count($allGroups) ?>)</span>
      <a href="/?page=groups&new=1" class="chan-sb-add" title="Gruppe anlegen">+</a>
    </div>
    <div class="chan-sb-list">
      <?php if (empty($allGroups)): ?>
        <div style="padding:16px;font-size:13px;color:var(--text-muted)">Keine Gruppen gefunden.</div>
      <?php else:
        // Gruppen nach Typ sortiert anzeigen
        $typeOrder = [1 => 'Regulär', 0 => 'Template', 2 => 'Query'];
        $grouped = [];
        foreach ($allGroups as $g) {
            $grouped[(int)$g['type']][] = $g;
        }
        $colorIdx = 0;
        foreach ($typeOrder as $type => $label):
          if (empty($grouped[$type])) continue;
      ?>
        <div class="chan-spacer"><?= e($label) ?></div>
        <?php foreach ($grouped[$type] as $g):
          $sgid     = (int)$g['sgid'];
          $name     = $g['name'] ?? '—';
          $isActive = ($selectedSgid === $sgid);
          $color    = $colors[$colorIdx++ % count($colors)];
        ?>
        <a href="/?page=groups&sgid=<?= $sgid ?>"
           class="chan-item <?= $isActive ? 'active' : '' ?>">
          <span class="chan-item-icon" style="color:<?= $color ?>">●</span>
          <span class="chan-item-name"><?= e($name) ?></span>
          <span class="badge <?= group_type_badge($type) ?>"
                style="font-size:10px;padding:1px 6px">
            <?= e(group_type_label($type)) ?>
          </span>
        </a>
        <?php endforeach;
        endforeach;
      endif; ?>
    </div>
  </div>

  <!-- Detail -->
  <div class="chan-detail">

    <?php if ($showNew): ?>
    <!-- Neue Gruppe anlegen -->
    <div class="chan-detail-head">
      <div class="chan-detail-title">Neue Servergruppe anlegen</div>
    </div>
    <div class="chan-detail-body">
      <form method="post" action="/?page=groups">
        <input type="hidden" name="action" value="create">
        <div class="form-section">
          <div class="form-section-head">Eigenschaften</div>
          <div class="form-section-body">
            <div class="form-group">
              <label class="form-label"><?= t('grp.name') ?> *</label>
              <input type="text" name="group_name" class="form-input"
                     placeholder="Gruppenname" required autofocus>
            </div>
            <div class="form-group">
              <label class="form-label"><?= t('grp.type') ?></label>
              <select name="group_type" class="form-select">
                <option value="1">Regulär (empfohlen)</option>
                <option value="0">Template</option>
              </select>
              <div class="form-hint">Reguläre Gruppen werden Clients direkt zugewiesen.</div>
            </div>
          </div>
          <div class="form-section-footer">
            <button type="submit" class="btn btn-primary">Gruppe anlegen</button>
            <a href="/?page=groups" class="btn btn-ghost"><?= t('gen.cancel') ?></a>
          </div>
        </div>
      </form>
    </div>

    <?php elseif ($selectedGroup): ?>
    <!-- Gruppe bearbeiten -->
    <?php
      $sgName = $selectedGroup['name'] ?? '—';
      $sgType = (int)($selectedGroup['type'] ?? 1);
    ?>
    <div class="chan-detail-head">
      <div class="chan-detail-title">
        <?= e($sgName) ?>
        <span class="badge <?= group_type_badge($sgType) ?>">
          <?= e(group_type_label($sgType)) ?>
        </span>
      </div>
      <div class="chan-detail-actions">
        <!-- Umbenennen -->
        <button class="btn btn-ghost btn-sm"
                onclick="document.getElementById('modal-rename').style.display='flex'">
          Umbenennen
        </button>
        <!-- Löschen (nicht für System-Gruppen) -->
        <?php if ($sgType !== 2): ?>
        <form method="post" action="/?page=groups" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="sgid" value="<?= $selectedSgid ?>">
          <button type="submit" class="btn btn-danger btn-sm"
                  data-confirm="Gruppe &quot;<?= e($sgName) ?>&quot; wirklich löschen?">
            <?= t('grp.delete') ?>
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="chan-detail-body">

      <!-- Mitglieder -->
      <div class="form-section">
        <div class="form-section-head" style="display:flex;align-items:center;justify-content:space-between">
          <span>Mitglieder (<?= count($groupMembers) ?>)</span>
          <button class="btn btn-ghost btn-sm"
                  onclick="document.getElementById('modal-add-client').style.display='flex'">
            + Mitglied hinzufügen
          </button>
        </div>
        <?php if (empty($groupMembers)): ?>
        <div style="padding:16px;font-size:13px;color:var(--text-muted)">Keine Mitglieder.</div>
        <?php else: ?>
        <table class="data">
          <thead>
            <tr>
              <th>Nickname</th>
              <th>Datenbank-ID</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($groupMembers as $m):
              $cldbid = $m['cldbid'] ?? '—';
              $nick   = $m['client_nickname'] ?? '—';
            ?>
            <tr>
              <td><?= e($nick) ?></td>
              <td class="mono"><?= e($cldbid) ?></td>
              <td>
                <form method="post" action="/?page=groups" style="display:inline">
                  <input type="hidden" name="action" value="del_client">
                  <input type="hidden" name="sgid" value="<?= $selectedSgid ?>">
                  <input type="hidden" name="cldbid" value="<?= e($cldbid) ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          data-confirm="Mitglied &quot;<?= e($nick) ?>&quot; aus Gruppe entfernen?">
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

      <!-- Gruppen-Icon -->
      <form method="post" action="/?page=groups" class="form-section">
        <input type="hidden" name="action" value="set_icon">
        <input type="hidden" name="sgid" value="<?= (int)$selectedSgid ?>">
        <div class="form-section-head">Gruppen-Icon</div>
        <div class="form-section-body">
          <div class="form-group">
            <label class="form-label">Icon-ID (Dateiname <code>icon_&lt;ID&gt;</code> im Icon-Ordner des Servers)</label>
            <input type="number" name="icon_id" class="form-input"
                   value="<?= $iconPerm !== null ? $iconPerm : '' ?>" placeholder="z. B. 1733969952">
            <div class="form-hint">Aktuell: <?= $iconPerm !== null ? (int)$iconPerm : 'keins' ?> — negative und vorzeichenlose IDs sind beide möglich.</div>
          </div>
        </div>
        <div class="form-section-footer"><button type="submit" class="btn btn-primary">Icon setzen</button></div>
      </form>

      <!-- Berechtigungen -->
      <div class="form-section">
        <div class="form-section-head">Berechtigungen (<?= count($groupPerms) ?>)</div>
        <form method="post" action="/?page=groups" class="form-section-body" id="perm-form">
          <input type="hidden" name="action" value="add_perm">
          <input type="hidden" name="sgid" value="<?= (int)$selectedSgid ?>">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Recht hinzufügen / ändern</label>
              <input type="text" name="permsid" id="perm-sid" class="form-input" list="perm-names"
                     placeholder="z. B. b_virtualserver_channel_list" autocomplete="off" required>
              <datalist id="perm-names">
                <?php foreach ($permNames as $pn): ?><option value="<?= e($pn) ?>"><?php endforeach; ?>
              </datalist>
            </div>
            <div class="form-group">
              <label class="form-label">Wert</label>
              <input type="number" name="permvalue" id="perm-val" class="form-input" value="1" required>
            </div>
          </div>
          <label style="margin-right:16px"><input type="checkbox" name="permnegated" id="perm-neg" value="1"> Negiert</label>
          <label style="margin-right:16px"><input type="checkbox" name="permskip" id="perm-skip" value="1"> Skip</label>
          <button type="submit" class="btn btn-primary">Speichern</button>
        </form>
        <?php if (!empty($groupPerms)): ?>
        <div style="max-height:300px;overflow-y:auto">
          <table class="data">
            <thead>
              <tr>
                <th>Permission</th>
                <th style="text-align:right">Wert</th>
                <th style="text-align:right">Negiert</th>
                <th style="text-align:right">Skip</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($groupPerms as $p): $ps = $p['permsid'] ?? ''; ?>
              <tr>
                <td class="mono"><?= e($ps ?: ($p['permid'] ?? '—')) ?></td>
                <td style="text-align:right"><?= e($p['permvalue'] ?? '—') ?></td>
                <td style="text-align:right"><?= ($p['permnegated'] ?? 0) ? '<span class="badge badge-red">ja</span>' : '—' ?></td>
                <td style="text-align:right"><?= ($p['permskip'] ?? 0) ? '<span class="badge badge-yellow">ja</span>' : '—' ?></td>
                <td style="text-align:right;white-space:nowrap">
                  <?php if ($ps): ?>
                  <button type="button" class="btn btn-ghost" title="Bearbeiten"
                    onclick="permEdit(<?= e(json_encode([$ps, (int)($p['permvalue'] ?? 0), (int)($p['permnegated'] ?? 0), (int)($p['permskip'] ?? 0)])) ?>)">✎</button>
                  <form method="post" action="/?page=groups" style="display:inline"
                        onsubmit="return confirm('Recht <?= e($ps) ?> entfernen?')">
                    <input type="hidden" name="action" value="del_perm">
                    <input type="hidden" name="sgid" value="<?= (int)$selectedSgid ?>">
                    <input type="hidden" name="permsid" value="<?= e($ps) ?>">
                    <button type="submit" class="btn btn-ghost" title="Entfernen">✕</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
      <script>
      function permEdit(a) {
        document.getElementById('perm-sid').value = a[0];
        document.getElementById('perm-val').value = a[1];
        document.getElementById('perm-neg').checked = !!a[2];
        document.getElementById('perm-skip').checked = !!a[3];
        document.getElementById('perm-form').scrollIntoView({behavior:'smooth', block:'center'});
      }
      </script>

    </div>

    <!-- Modal: Umbenennen -->
    <div id="modal-rename" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Gruppe umbenennen</div>
        <form method="post" action="/?page=groups">
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="sgid" value="<?= $selectedSgid ?>">
          <div class="form-group">
            <label class="form-label">Neuer Name</label>
            <input type="text" name="group_name" class="form-input"
                   value="<?= e($sgName) ?>" required autofocus>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary">Umbenennen</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-rename').style.display='none'">
              <?= t('gen.cancel') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Mitglied hinzufügen -->
    <div id="modal-add-client" class="modal-overlay" style="display:none">
      <div class="modal-box">
        <div class="modal-title">Mitglied hinzufügen</div>
        <form method="post" action="/?page=groups">
          <input type="hidden" name="action" value="add_client">
          <input type="hidden" name="sgid" value="<?= $selectedSgid ?>">

          <?php
          // Alle DB-Clients laden
          $dbClientsModal = $apiOnline ? (api()->clientDbList(0, 100)['data'] ?? []) : [];
          $dbClientsModal = array_filter($dbClientsModal, fn($c) => ($c['client_unique_identifier'] ?? '') !== 'ServerQuery');
          // Online-cldbids für Markierung
          $onlineClientsModal = $apiOnline ? (api()->clientList()['data'] ?? []) : [];
          $onlineCldbids = array_column(
              array_filter($onlineClientsModal, fn($c) => ($c['client_type'] ?? 0) == 0),
              'cldbid'
          );
          ?>

          <div class="form-group">
            <label class="form-label">Client auswählen</label>
            <select name="cldbid" class="form-select" autofocus>
              <option value="">— Bitte wählen —</option>
              <?php foreach ($dbClientsModal as $c):
                $cid  = (int)($c['cldbid'] ?? 0);
                $nick = $c['client_nickname'] ?? '—';
                if ($cid <= 0) continue;
                $online = in_array($cid, $onlineCldbids) ? ' 🟢' : '';
              ?>
              <option value="<?= $cid ?>"><?= e($nick) ?><?= $online ?> (DB-ID: <?= $cid ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">🟢 = gerade verbunden</div>
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn btn-primary">Hinzufügen</button>
            <button type="button" class="btn btn-ghost"
                    onclick="document.getElementById('modal-add-client').style.display='none'">
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

    <?php else: ?>
    <div class="chan-placeholder">
      <div style="font-size:32px;margin-bottom:12px;opacity:0.3">◉</div>
      <div>Gruppe auswählen oder</div>
      <a href="/?page=groups&new=1" style="color:var(--accent)">neue Gruppe anlegen</a>
    </div>
    <?php endif; ?>

  </div>
</div>
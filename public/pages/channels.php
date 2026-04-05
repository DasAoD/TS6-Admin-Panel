<?php
// =============================================================
//  pages/channels.php — Channel-Verwaltung
// =============================================================

// ── POST-Aktionen verarbeiten ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'create':
            $params = [
                'channel_name'             => trim($_POST['channel_name'] ?? ''),
                'channel_flag_permanent'   => 1,
                'channel_flag_semi_permanent' => 0,
            ];
            if (!empty($_POST['channel_password'])) {
                $params['channel_password'] = $_POST['channel_password'];
            }
            if (!empty($_POST['channel_codec'])) {
                $params['channel_codec'] = (int)$_POST['channel_codec'];
            }
            if (isset($_POST['channel_codec_quality']) && $_POST['channel_codec_quality'] !== '') {
                $params['channel_codec_quality'] = (int)$_POST['channel_codec_quality'];
            }
            if (!empty($_POST['channel_description'])) {
                $params['channel_description'] = $_POST['channel_description'];
            }
            if (isset($_POST['channel_maxclients']) && $_POST['channel_maxclients'] !== '') {
                $params['channel_maxclients'] = (int)$_POST['channel_maxclients'];
            }
            if (!empty($_POST['is_spacer'])) {
                // Spacer: Name mit [cspacer]-Prefix
                $params['channel_name'] = '[cspacer0]' . $params['channel_name'];
                $params['channel_flag_permanent'] = 1;
            }
            $result = api()->channelCreate($params);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? t('gen.success') . ': Channel angelegt.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=channels');
            exit;

        case 'edit':
            $cid    = (int)($_POST['cid'] ?? 0);
            $newName  = trim($_POST['channel_name'] ?? '');
            $origName = trim($_POST['original_name'] ?? '');
            $params   = [];
            // Namen nur senden wenn er sich geändert hat
            if ($newName !== $origName && $newName !== '') {
                $params['channel_name'] = $newName;
            }
            if (isset($_POST['channel_password'])) {
                $params['channel_password'] = $_POST['channel_password'];
            }
            if (isset($_POST['channel_codec'])) {
                $params['channel_codec'] = (int)$_POST['channel_codec'];
            }
            if (isset($_POST['channel_codec_quality'])) {
                $params['channel_codec_quality'] = (int)$_POST['channel_codec_quality'];
            }
            if (isset($_POST['channel_description'])) {
                $params['channel_description'] = $_POST['channel_description'];
            }
            if (isset($_POST['channel_maxclients'])) {
                $params['channel_maxclients'] = (int)$_POST['channel_maxclients'];
            }
            $result = api()->channelEdit($cid, $params);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? t('gen.success') . ': Channel gespeichert.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=channels&cid=' . $cid);
            exit;

        case 'delete':
            $cid    = (int)($_POST['cid'] ?? 0);
            $result = api()->channelDelete($cid, true);
            flash_set($result['success'] ? 'success' : 'danger',
                $result['success'] ? 'Channel gelöscht.' : t('gen.error') . ': ' . ($result['error'] ?? ''));
            header('Location: /?page=channels');
            exit;
    }
}

// ── Daten laden ───────────────────────────────────────────────
$channels    = $apiOnline ? (api()->channelList()['data'] ?? []) : [];
$selectedCid = isset($_GET['cid']) ? (int)$_GET['cid'] : null;
$channelInfo = null;
$channelPerms = [];
$showNew     = isset($_GET['new']);

if ($selectedCid && $apiOnline) {
    $infoResult   = api()->channelInfo($selectedCid);
    $channelInfo  = $infoResult['data'][0] ?? ($infoResult['data'] ?? null);
    $permsResult  = api()->channelPermList($selectedCid);
    $channelPerms = $permsResult['data'] ?? [];
}

$flash = flash_get();

// ── Hilfsfunktion: Channel-Name kürzen für Anzeige ───────────
function chan_display_name(string $name): string {
    // Spacer-Prefix entfernen für Anzeige
    return preg_replace('/^\[[\*c]+spacer\d*\]/', '', $name);
}
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('chan.title') ?></div>
    <div class="page-sub"><?= t('chan.sub') ?></div>
  </div>
  <div class="topbar-right">
    <a href="/?page=channels&new=1" class="btn btn-primary">+ <?= t('chan.new') ?></a>
  </div>
</div>

<?php if ($flash): ?>
<div style="padding: 0 24px 0">
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
</div>
<?php endif; ?>

<!-- Channel-Layout -->
<div class="chan-layout">

  <!-- Sidebar: Channel-Liste -->
  <div class="chan-sidebar">
    <div class="chan-sb-head">
      <span class="chan-sb-title">Channels (<?= count($channels) ?>)</span>
      <a href="/?page=channels&new=1" class="chan-sb-add" title="Channel anlegen">+</a>
    </div>
    <div class="chan-sb-list" id="chan-sortable">
      <?php if (empty($channels)): ?>
        <div style="padding:16px;font-size:13px;color:var(--text-muted)">Keine Channels gefunden.</div>
      <?php else:
        foreach ($channels as $ch):
          $name      = $ch['channel_name'] ?? '';
          $cid       = (int)($ch['cid'] ?? 0);
          $cpid      = (int)($ch['pid'] ?? 0);
          $clients   = (int)($ch['total_clients'] ?? 0);
          $isSpacer  = is_spacer($name);
          $hasPw     = !empty($ch['channel_flag_password']);
          $isActive  = ($selectedCid === $cid);

          if ($isSpacer):
            $displayName = chan_display_name($name);
      ?>
        <div class="chan-spacer" data-cid="<?= $cid ?>" data-cpid="<?= $cpid ?>">
          <?= e($displayName) ?>
        </div>
      <?php else: ?>
        <a href="/?page=channels&cid=<?= $cid ?>"
           class="chan-item <?= $isActive ? 'active' : '' ?>"
           data-cid="<?= $cid ?>"
           data-cpid="<?= $cpid ?>">
          <span class="chan-item-icon"><?= $hasPw ? '🔒' : '▶' ?></span>
          <span class="chan-item-name"><?= e($name) ?></span>
          <?php if ($clients > 0): ?>
          <span class="chan-item-cnt"><?= $clients ?></span>
          <?php endif; ?>
        </a>
      <?php endif;
        endforeach;
      endif; ?>
    </div>
    <div style="padding:8px 12px;border-top:1px solid var(--border);font-size:11px;color:var(--text-subtle);display:flex;align-items:center;gap:6px">
      <span style="color:var(--yellow)">⚠</span>
      Reihenfolge ändern: noch nicht verfügbar (TS6 Beta)
    </div>
  </div>

  <!-- Detail-Bereich -->
  <div class="chan-detail">

    <?php if ($showNew): ?>
    <!-- ── Neuen Channel anlegen ── -->
    <div class="chan-detail-head">
      <div class="chan-detail-title">Neuen Channel anlegen</div>
    </div>
    <div class="chan-detail-body">
      <form method="post" action="/?page=channels">
        <input type="hidden" name="action" value="create">

        <div class="form-section">
          <div class="form-section-head">Eigenschaften</div>
          <div class="form-section-body">
            <div class="form-group">
              <label class="form-label"><?= t('chan.name') ?> *</label>
              <input type="text" name="channel_name" class="form-input"
                     placeholder="Channel-Name" required autofocus>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label"><?= t('chan.password') ?></label>
                <input type="text" name="channel_password" class="form-input"
                       placeholder="Leer = kein Passwort" autocomplete="off">
              </div>
              <div class="form-group">
                <label class="form-label"><?= t('chan.max_clients') ?></label>
                <input type="number" name="channel_maxclients" class="form-input"
                       value="0" min="0">
                <div class="form-hint"><?= t('chan.max_hint') ?></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label"><?= t('chan.codec') ?></label>
                <select name="channel_codec" class="form-select">
                  <option value="4">Opus Voice (empfohlen)</option>
                  <option value="5">Opus Music</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Codec-Qualität (0–10)</label>
                <input type="number" name="channel_codec_quality" class="form-input"
                       value="10" min="0" max="10">
                <div class="form-hint">10 = beste Qualität</div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label"><?= t('chan.description') ?></label>
              <textarea name="channel_description" class="form-textarea"
                        placeholder="Optionale Beschreibung"></textarea>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-head">Optionen</div>
          <div class="form-section-body">
            <div class="toggle-row">
              <div>
                <div class="toggle-label"><?= t('chan.spacer') ?></div>
                <div class="toggle-sub">Fügt [cspacer0]-Prefix hinzu — Channel dient als Trennlinie</div>
              </div>
              <input type="hidden" name="is_spacer" value="0" id="is_spacer_val">
              <div class="toggle" id="is_spacer_toggle"
                   onclick="this.classList.toggle('on'); document.getElementById('is_spacer_val').value = this.classList.contains('on') ? '1' : '0'"></div>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:8px;padding-top:4px">
          <button type="submit" class="btn btn-primary"><?= t('chan.new') ?></button>
          <a href="/?page=channels" class="btn btn-ghost"><?= t('gen.cancel') ?></a>
        </div>
      </form>
    </div>

    <?php elseif ($channelInfo): ?>
    <!-- ── Channel bearbeiten ── -->
    <?php
      $name    = $channelInfo['channel_name'] ?? '';
      $hasPw   = !empty($channelInfo['channel_flag_password']);
      $maxCli  = $channelInfo['channel_maxclients'] ?? 0;
      $desc    = $channelInfo['channel_description'] ?? '';
      $isPerm  = !empty($channelInfo['channel_flag_permanent']);
    ?>
    <div class="chan-detail-head">
      <div class="chan-detail-title">
        <?= e($name) ?>
        <?php if ($hasPw): ?>
        <span class="badge badge-yellow"><?= t('chan.protected') ?></span>
        <?php else: ?>
        <span class="badge badge-gray"><?= t('chan.open') ?></span>
        <?php endif; ?>
      </div>
      <div class="chan-detail-actions">
        <form method="post" action="/?page=channels" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="cid" value="<?= $selectedCid ?>">
          <button type="submit" class="btn btn-danger btn-sm"
                  data-confirm="<?= e(sprintf(t('chan.confirm_delete'), $name)) ?>">
            <?= t('chan.delete') ?>
          </button>
        </form>
      </div>
    </div>
    <div class="chan-detail-body">
      <form method="post" action="/?page=channels">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="cid" value="<?= $selectedCid ?>">
        <input type="hidden" name="original_name" value="<?= e($name) ?>">

        <div class="form-section">
          <div class="form-section-head"><?= t('chan.title') ?> — Eigenschaften</div>
          <div class="form-section-body">
            <div class="form-group">
              <label class="form-label"><?= t('chan.name') ?></label>
              <input type="text" name="channel_name" class="form-input"
                     value="<?= e($name) ?>" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label"><?= t('chan.password') ?></label>
                <input type="text" name="channel_password" class="form-input"
                       placeholder="<?= $hasPw ? '••••••• (gesetzt)' : 'Kein Passwort' ?>"
                       autocomplete="off">
                <?php if ($hasPw): ?>
                <div class="form-hint">Leer lassen = Passwort unverändert</div>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label class="form-label"><?= t('chan.max_clients') ?></label>
                <input type="number" name="channel_maxclients" class="form-input"
                       value="<?= e($maxCli) ?>" min="-1">
                <div class="form-hint"><?= t('chan.max_hint') ?></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label"><?= t('chan.codec') ?></label>
                <select name="channel_codec" class="form-select">
                  <option value="4" <?= ($channelInfo['channel_codec'] ?? 4) == 4 ? 'selected' : '' ?>>Opus Voice</option>
                  <option value="5" <?= ($channelInfo['channel_codec'] ?? 4) == 5 ? 'selected' : '' ?>>Opus Music</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Codec-Qualität (0–10)</label>
                <input type="number" name="channel_codec_quality" class="form-input"
                       value="<?= e($channelInfo['channel_codec_quality'] ?? 10) ?>" min="0" max="10">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label"><?= t('chan.description') ?></label>
              <textarea name="channel_description" class="form-textarea"><?= e($desc) ?></textarea>
            </div>
          </div>
          <div class="form-section-footer">
            <button type="submit" class="btn btn-primary"><?= t('chan.save') ?></button>
            <a href="/?page=channels" class="btn btn-ghost"><?= t('gen.cancel') ?></a>
          </div>
        </div>

      </form>

      <!-- Channel-Info (Read-only) -->
      <div class="form-section">
        <div class="form-section-head">Channel-Info</div>
        <div class="form-section-body">
          <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php
            $infoFields = [
              'Channel-ID'        => $selectedCid,
              'Clients online'    => $channelInfo['channel_clients'] ?? 0,
              'Codec'             => match((int)($channelInfo['channel_codec'] ?? 4)) {
                                      4 => 'Opus Voice',
                                      5 => 'Opus Music',
                                      default => 'Unbekannt'
                                    },
              'Codec-Qualität'    => $channelInfo['channel_codec_quality'] ?? '—',
              'Permanent'         => $isPerm ? 'Ja' : 'Nein',
            ];
            foreach ($infoFields as $label => $val): ?>
            <tr>
              <td style="padding:7px 0;color:var(--text-muted);width:160px"><?= e($label) ?></td>
              <td style="padding:7px 0;color:var(--text-primary);font-family:var(--font-mono);font-size:12px"><?= e((string)$val) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>

      <?php if (!empty($channelPerms)): ?>
      <!-- Berechtigungen -->
      <div class="form-section">
        <div class="form-section-head">Berechtigungen</div>
        <div class="form-section-body" style="padding:0">
          <table class="data">
            <thead>
              <tr>
                <th>Permission</th>
                <th style="text-align:right">Wert</th>
                <th style="text-align:right">Negiert</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($channelPerms as $perm): ?>
              <tr>
                <td class="mono"><?= e($perm['permsid'] ?? $perm['permid'] ?? '—') ?></td>
                <td style="text-align:right"><?= e($perm['permvalue'] ?? '—') ?></td>
                <td style="text-align:right"><?= ($perm['permnegated'] ?? 0) ? '<span class="badge badge-red">ja</span>' : '—' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <?php else: ?>
    <!-- ── Platzhalter ── -->
    <div class="chan-placeholder">
      <div style="font-size:32px;margin-bottom:12px;opacity:0.3">#</div>
      <div>Channel auswählen oder</div>
      <a href="/?page=channels&new=1" style="color:var(--accent)">neuen Channel anlegen</a>
    </div>
    <?php endif; ?>

  </div><!-- .chan-detail -->
</div><!-- .chan-layout -->
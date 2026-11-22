<?php
// =============================================================
//  pages/config_page.php — Konfiguration
// =============================================================

$conf    = ts6ctl_conf_read();
$flash   = flash_get();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    if ($section === 'webui') {
        // ── WebUI-Einstellungen ──────────────────────────────
        $newUser  = trim($_POST['webui_user'] ?? '');
        $newPass  = $_POST['webui_pass'] ?? '';
        $newPass2 = $_POST['webui_pass2'] ?? '';
        $newLang  = $_POST['webui_lang'] ?? 'de';

        if ($newPass !== '' && $newPass !== $newPass2) {
            $errors[] = t('cfg.pass_mismatch');
        } else {
            $cfgFile    = CONFIG_PATH . '/config.php';
            $cfgContent = file_get_contents($cfgFile);

            if ($newUser) {
                $cfgContent = preg_replace(
                    "/define\('ADMIN_USER',\s*'[^']*'\);/",
                    "define('ADMIN_USER', '" . addslashes($newUser) . "');",
                    $cfgContent
                );
            }
            if ($newPass !== '') {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                // Hash in separate Datei speichern — vermeidet PHP-String-Escaping-Probleme
                file_put_contents(CONFIG_PATH . '/.admin_pass', $hash);
                chmod(CONFIG_PATH . '/.admin_pass', 0600);
            }
            $cfgContent = preg_replace(
                "/define\('DEFAULT_LANG',\s*'[^']*'\);/",
                "define('DEFAULT_LANG', '" . addslashes($newLang) . "');",
                $cfgContent
            );

            file_put_contents($cfgFile, $cfgContent);
            flash_set('success', t('cfg.saved'));
            header('Location: /?page=config');
            exit;
        }
    }

    if ($section === 'ts6ctl') {
        // ── ts6ctl.conf patchen ──────────────────────────────
        $mailTo   = trim($_POST['mail_to']   ?? '');
        $mailFrom = trim($_POST['mail_from'] ?? '');

        $confFile    = TS6CTL_CONF;
        $confContent = file_get_contents($confFile);

        $confContent = preg_replace('/^TS6_MAIL_TO=.*/m',   'TS6_MAIL_TO="' . $mailTo . '"',   $confContent);
        $confContent = preg_replace('/^TS6_MAIL_FROM=.*/m', 'TS6_MAIL_FROM="' . $mailFrom . '"', $confContent);

        file_put_contents($confFile, $confContent);
        flash_set('success', t('cfg.saved'));
        header('Location: /?page=config');
        exit;
    }

    if ($section === 'ts6server') {
        // ── Virtual-Server-Einstellungen (serveredit) ────────
        $fields = [
            'virtualserver_name'                         => 's',
            'virtualserver_welcomemessage'               => 's',
            'virtualserver_maxclients'                   => 'i',
            'virtualserver_reserved_slots'               => 'i',
            'virtualserver_hostmessage'                  => 's',
            'virtualserver_hostmessage_mode'             => 'i',
            'virtualserver_hostbanner_url'               => 's',
            'virtualserver_hostbanner_gfx_url'           => 's',
            'virtualserver_hostbanner_gfx_interval'      => 'i',
            'virtualserver_hostbanner_mode'              => 'i',
            'virtualserver_hostbutton_tooltip'           => 's',
            'virtualserver_hostbutton_url'               => 's',
            'virtualserver_hostbutton_gfx_url'           => 's',
            'virtualserver_needed_identity_security_level' => 'i',
            'virtualserver_complain_autoban_count' => 'i',
            'virtualserver_complain_autoban_time' => 'i',
            'virtualserver_log_channel' => 'i',
            'virtualserver_log_client' => 'i',
            'virtualserver_log_filetransfer' => 'i',
            'virtualserver_log_query' => 'i',
            'virtualserver_log_server' => 'i',
            'virtualserver_weblist_enabled' => 'i',
            'virtualserver_download_quota' => 'i',
            'virtualserver_upload_quota' => 'i',
            'virtualserver_default_server_group' => 'i',
            'virtualserver_default_channel_group' => 'i',
        ];
        $cur    = api()->serverInfo()['data'][0] ?? [];
        $params = [];
        foreach ($fields as $key => $type) {
            if (!isset($_POST[$key])) continue;
            $val = $type === 'i' ? (string)(int)$_POST[$key] : str_replace("\r\n", "\n", (string)$_POST[$key]);
            if ($val !== (string)($cur[$key] ?? '')) {
                $params[$key] = $type === 'i' ? (int)$val : $val;
            }
        }
        // Passwort: leer = unverändert, Checkbox = entfernen
        if (!empty($_POST['virtualserver_password_clear'])) {
            $params['virtualserver_password'] = '';
        } elseif (($_POST['virtualserver_password'] ?? '') !== '') {
            $params['virtualserver_password'] = (string)$_POST['virtualserver_password'];
        }

        if (!$params) {
            flash_set('success', 'Keine Änderungen.');
        } else {
            $res = api()->serverEdit($params);
            if ($res['success']) {
                flash_set('success', 'Server-Einstellungen gespeichert (' . count($params) . ' geändert).');
            } else {
                flash_set('danger', 'Fehler: ' . ($res['error'] ?? 'unbekannt'));
            }
        }
        header('Location: /?page=config');
        exit;
    }

    if ($section === 'apikey') {
        // ── API-Key in config.php setzen ─────────────────────
        $newKey  = trim($_POST['api_key'] ?? '');
        $cfgFile = CONFIG_PATH . '/config.php';
        $cfgContent = file_get_contents($cfgFile);
        $cfgContent = preg_replace(
            "/define\('TS6_API_KEY',\s*'[^']*'\);/",
            "define('TS6_API_KEY', '" . addslashes($newKey) . "');",
            $cfgContent
        );
        file_put_contents($cfgFile, $cfgContent);
        flash_set('success', 'API-Key gespeichert.');
        header('Location: /?page=config');
        exit;
    }
}

// Aktuelle Werte
$mailTo   = $conf['TS6_MAIL_TO']   ?? '';
$mailFrom = $conf['TS6_MAIL_FROM'] ?? '';
$version  = $conf['TS6_INSTALLED_VERSION'] ?? '—';
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('cfg.title') ?></div>
    <div class="page-sub"><?= t('cfg.sub') ?></div>
  </div>
</div>

<div class="content">

  <?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <!-- ts6ctl / E-Mail -->
  <form method="post" action="/?page=config">
    <input type="hidden" name="section" value="ts6ctl">
    <div class="form-section">
      <div class="form-section-head"><?= t('cfg.ts6ctl_section') ?> — E-Mail-Benachrichtigungen</div>
      <div class="form-section-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><?= t('cfg.mail_to') ?></label>
            <input type="email" name="mail_to" class="form-input"
                   value="<?= e($mailTo) ?>" placeholder="admin@example.com">
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('cfg.mail_from') ?></label>
            <input type="email" name="mail_from" class="form-input"
                   value="<?= e($mailFrom) ?>" placeholder="ts6ctl@example.com">
          </div>
        </div>
      </div>
      <div class="form-section-footer">
        <button type="submit" class="btn btn-primary"><?= t('cfg.save') ?></button>
      </div>
    </div>
  </form>

  <!-- WebUI-Einstellungen -->
  <form method="post" action="/?page=config">
    <input type="hidden" name="section" value="webui">
    <div class="form-section">
      <div class="form-section-head"><?= t('cfg.webui_section') ?></div>
      <div class="form-section-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><?= t('cfg.webui_user') ?></label>
            <input type="text" name="webui_user" class="form-input"
                   value="<?= e(ADMIN_USER) ?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('cfg.lang') ?></label>
            <select name="webui_lang" class="form-select">
              <option value="de" <?= DEFAULT_LANG === 'de' ? 'selected' : '' ?>>Deutsch</option>
              <option value="en" <?= DEFAULT_LANG === 'en' ? 'selected' : '' ?>>English</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><?= t('cfg.webui_pass') ?></label>
            <input type="password" name="webui_pass" class="form-input"
                   placeholder="Leer lassen = unverändert" autocomplete="new-password">
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('cfg.webui_pass2') ?></label>
            <input type="password" name="webui_pass2" class="form-input"
                   placeholder="Passwort bestätigen" autocomplete="new-password">
          </div>
        </div>
      </div>
      <div class="form-section-footer">
        <button type="submit" class="btn btn-primary"><?= t('cfg.save') ?></button>
      </div>
    </div>
  </form>

  <!-- API-Key -->
  <form method="post" action="/?page=config">
    <input type="hidden" name="section" value="apikey">
    <div class="form-section">
      <div class="form-section-head">TS6 API-Key</div>
      <div class="form-section-body">
        <div class="form-group">
          <label class="form-label">API-Key</label>
          <input type="password" name="api_key" class="form-input"
                 placeholder="Neuen API-Key eingeben"
                 autocomplete="off">
          <div class="form-hint">
            Aktueller Key: <?= TS6_API_KEY ? '<span style="color:var(--green)">gesetzt</span>' : '<span style="color:var(--red)">nicht gesetzt</span>' ?>
            — Aus Journal: <code>journalctl -u teamspeak6 | grep apikey</code>
          </div>
        </div>
      </div>
      <div class="form-section-footer">
        <button type="submit" class="btn btn-primary"><?= t('cfg.save') ?></button>
      </div>
    </div>
  </form>

  <!-- TS6 Server-Einstellungen (serveredit) -->
  <?php $srv = $apiOnline ? (api()->serverInfo()['data'][0] ?? []) : []; ?>
  <?php $sgList = $apiOnline ? (api()->serverGroupList()['data'] ?? []) : []; $cgList = $apiOnline ? (api()->channelGroupList()['data'] ?? []) : []; ?>
  <form method="post" action="/?page=config">
    <input type="hidden" name="section" value="ts6server">
    <div class="form-section">
      <div class="form-section-head">TS6 Server-Einstellungen</div>
      <div class="form-section-body">
        <?php if (!$srv): ?><div class="alert alert-danger">TS6-API nicht erreichbar.</div><?php endif; ?>
        <div class="form-row">
        <div class="form-group">
          <label class="form-label">Servername</label>
          <input type="text" name="virtualserver_name" class="form-input" value="<?= e((string)($srv['virtualserver_name'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Max. Clients</label>
          <input type="number" name="virtualserver_maxclients" class="form-input" value="<?= e((string)($srv['virtualserver_maxclients'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Reservierte Slots</label>
          <input type="number" name="virtualserver_reserved_slots" min="0" class="form-input" value="<?= e((string)($srv['virtualserver_reserved_slots'] ?? '')) ?>" placeholder="">
          <div class="form-hint">Für Admins freigehaltene Plätze, muss kleiner als Max. Clients sein.</div>
        </div>
        </div>
        <div class="form-group">
          <label class="form-label">Willkommensnachricht</label>
          <textarea name="virtualserver_welcomemessage" class="form-input" rows="4"><?= e((string)($srv['virtualserver_welcomemessage'] ?? '')) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Server-Passwort</label>
            <input type="password" name="virtualserver_password" class="form-input" placeholder="Leer lassen = unverändert" autocomplete="new-password">
            <div class="form-hint">Aktuell: <?= ($srv['virtualserver_flag_password'] ?? '0') === '1' ? 'gesetzt' : 'kein Passwort' ?>
              — <label><input type="checkbox" name="virtualserver_password_clear" value="1"> Passwort entfernen</label></div>
          </div>
        <div class="form-group">
          <label class="form-label">Min. Identity-Security-Level</label>
          <input type="number" name="virtualserver_needed_identity_security_level" class="form-input" value="<?= e((string)($srv['virtualserver_needed_identity_security_level'] ?? '')) ?>" placeholder="">
        </div>
        </div>
        <div class="form-row">
        <div class="form-group">
          <label class="form-label">Host-Nachricht</label>
          <input type="text" name="virtualserver_hostmessage" class="form-input" value="<?= e((string)($srv['virtualserver_hostmessage'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Host-Nachricht-Modus</label>
          <select name="virtualserver_hostmessage_mode" class="form-select">
              <option value="0" <?= (string)($srv['virtualserver_hostmessage_mode'] ?? '') === '0' ? 'selected' : '' ?>>0 – keine</option>
              <option value="1" <?= (string)($srv['virtualserver_hostmessage_mode'] ?? '') === '1' ? 'selected' : '' ?>>1 – im Log anzeigen</option>
              <option value="2" <?= (string)($srv['virtualserver_hostmessage_mode'] ?? '') === '2' ? 'selected' : '' ?>>2 – als Dialog</option>
              <option value="3" <?= (string)($srv['virtualserver_hostmessage_mode'] ?? '') === '3' ? 'selected' : '' ?>>3 – Dialog + Disconnect</option>
          </select>
        </div>
        </div>
        <div class="form-row">
        <div class="form-group">
          <label class="form-label">Banner-URL (Link)</label>
          <input type="text" name="virtualserver_hostbanner_url" class="form-input" value="<?= e((string)($srv['virtualserver_hostbanner_url'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Banner-Grafik-URL</label>
          <input type="text" name="virtualserver_hostbanner_gfx_url" class="form-input" value="<?= e((string)($srv['virtualserver_hostbanner_gfx_url'] ?? '')) ?>" placeholder="">
        </div>
        </div>
        <div class="form-row">
        <div class="form-group">
          <label class="form-label">Banner-Intervall (s)</label>
          <input type="number" name="virtualserver_hostbanner_gfx_interval" class="form-input" value="<?= e((string)($srv['virtualserver_hostbanner_gfx_interval'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Banner-Modus</label>
          <select name="virtualserver_hostbanner_mode" class="form-select">
              <option value="0" <?= (string)($srv['virtualserver_hostbanner_mode'] ?? '') === '0' ? 'selected' : '' ?>>0 – nicht anpassen</option>
              <option value="1" <?= (string)($srv['virtualserver_hostbanner_mode'] ?? '') === '1' ? 'selected' : '' ?>>1 – anpassen, Seitenverhältnis ignorieren</option>
              <option value="2" <?= (string)($srv['virtualserver_hostbanner_mode'] ?? '') === '2' ? 'selected' : '' ?>>2 – anpassen, Seitenverhältnis beibehalten</option>
          </select>
        </div>
        </div>
        <div class="form-row">
        <div class="form-group">
          <label class="form-label">Button-Tooltip</label>
          <input type="text" name="virtualserver_hostbutton_tooltip" class="form-input" value="<?= e((string)($srv['virtualserver_hostbutton_tooltip'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Button-URL</label>
          <input type="text" name="virtualserver_hostbutton_url" class="form-input" value="<?= e((string)($srv['virtualserver_hostbutton_url'] ?? '')) ?>" placeholder="">
        </div>
        <div class="form-group">
          <label class="form-label">Button-Grafik-URL</label>
          <input type="text" name="virtualserver_hostbutton_gfx_url" class="form-input" value="<?= e((string)($srv['virtualserver_hostbutton_gfx_url'] ?? '')) ?>" placeholder="">
        </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Standard-Servergruppe</label>
            <select name="virtualserver_default_server_group" class="form-select">
              <?php foreach ($sgList as $g): if ((int)($g['type'] ?? 1) !== 1) continue; ?>
              <option value="<?= (int)$g['sgid'] ?>" <?= (string)($srv['virtualserver_default_server_group'] ?? '') === (string)$g['sgid'] ? 'selected' : '' ?>><?= e($g['name']) ?> (<?= (int)$g['sgid'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Standard-Kanalgruppe</label>
            <select name="virtualserver_default_channel_group" class="form-select">
              <?php foreach ($cgList as $g): if ((int)($g['type'] ?? 1) !== 1) continue; ?>
              <option value="<?= (int)$g['cgid'] ?>" <?= (string)($srv['virtualserver_default_channel_group'] ?? '') === (string)$g['cgid'] ? 'selected' : '' ?>><?= e($g['name']) ?> (<?= (int)$g['cgid'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Autoban nach Beschwerden</label>
            <input type="number" name="virtualserver_complain_autoban_count" min="0" class="form-input" value="<?= e((string)($srv['virtualserver_complain_autoban_count'] ?? '')) ?>">
          <div class="form-hint">0 = aus</div>
          </div>
          <div class="form-group">
            <label class="form-label">Autoban-Dauer (s)</label>
            <input type="number" name="virtualserver_complain_autoban_time" min="0" class="form-input" value="<?= e((string)($srv['virtualserver_complain_autoban_time'] ?? '')) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Download-Quota</label>
            <input type="number" name="virtualserver_download_quota" min="0" class="form-input" value="<?= e((string)($srv['virtualserver_download_quota'] ?? '')) ?>">
          <div class="form-hint">4294967295 = Maximalwert (unbegrenzt)</div>
          </div>
          <div class="form-group">
            <label class="form-label">Upload-Quota</label>
            <input type="number" name="virtualserver_upload_quota" min="0" class="form-input" value="<?= e((string)($srv['virtualserver_upload_quota'] ?? '')) ?>">
          <div class="form-hint">4294967295 = Maximalwert (unbegrenzt)</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Logging</label>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:18px">
            <input type="hidden" name="virtualserver_log_channel" value="0">
            <input type="checkbox" name="virtualserver_log_channel" value="1" <?= ($srv['virtualserver_log_channel'] ?? '0') === '1' ? 'checked' : '' ?>> Channel
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:18px">
            <input type="hidden" name="virtualserver_log_client" value="0">
            <input type="checkbox" name="virtualserver_log_client" value="1" <?= ($srv['virtualserver_log_client'] ?? '0') === '1' ? 'checked' : '' ?>> Client
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:18px">
            <input type="hidden" name="virtualserver_log_filetransfer" value="0">
            <input type="checkbox" name="virtualserver_log_filetransfer" value="1" <?= ($srv['virtualserver_log_filetransfer'] ?? '0') === '1' ? 'checked' : '' ?>> Dateitransfer
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:18px">
            <input type="hidden" name="virtualserver_log_query" value="0">
            <input type="checkbox" name="virtualserver_log_query" value="1" <?= ($srv['virtualserver_log_query'] ?? '0') === '1' ? 'checked' : '' ?>> Query
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:18px">
            <input type="hidden" name="virtualserver_log_server" value="0">
            <input type="checkbox" name="virtualserver_log_server" value="1" <?= ($srv['virtualserver_log_server'] ?? '0') === '1' ? 'checked' : '' ?>> Server
          </label>
        </div>
        <div class="form-group">
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:18px">
            <input type="hidden" name="virtualserver_weblist_enabled" value="0">
            <input type="checkbox" name="virtualserver_weblist_enabled" value="1" <?= ($srv['virtualserver_weblist_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> In Weltliste eintragen
          </label>
        </div>
      </div>
      <div class="form-section-footer">
        <button type="submit" class="btn btn-primary"><?= t('cfg.save') ?></button>
      </div>
    </div>
  </form>

  <!-- Server-Info (read-only) -->
  <div class="form-section">
    <div class="form-section-head">TS6 Server-Info</div>
    <div class="form-section-body">
      <table style="width:100%;font-size:13px;border-collapse:collapse">
        <?php
        $serverInfo = $apiOnline ? (api()->serverInfo()['data'][0] ?? []) : [];
        $infoFields = [
            'Installierte Version' => $version,
            'API-Host'             => TS6_API_HOST . ':' . TS6_API_PORT,
            'Virtual Server ID'    => TS6_VSERVER_ID,
            'Voice-Port'           => $conf['TS6_VOICE_PORT'] ?? '—',
            'File-Transfer-Port'   => $conf['TS6_FILETRANSFER_PORT'] ?? '—',
            'Installationsverz.'   => $conf['TS6_INSTALL_DIR'] ?? '—',
            'Service'              => $conf['TS6_SERVICE'] ?? 'teamspeak6',
        ];
        foreach ($infoFields as $label => $val): ?>
        <tr>
          <td style="padding:7px 0;color:var(--text-muted);width:200px"><?= e($label) ?></td>
          <td style="padding:7px 0;color:var(--text-primary);font-family:var(--font-mono);font-size:12px"><?= e((string)$val) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

</div>
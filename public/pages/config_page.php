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
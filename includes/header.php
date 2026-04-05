<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TS6 Admin — <?= e(t('nav.' . $page)) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<div class="layout">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="sidebar">

    <!-- Logo -->
    <div class="sb-logo">
      <div class="sb-logo-inner">
        <div class="sb-badge">TS6</div>
        <div class="sb-name">Admin Panel</div>
      </div>
      <!-- Server-Info-Box -->
      <div class="sb-server-info">
        <div class="sb-si-row">
          <span class="sb-si-label">Status</span>
          <span class="sb-si-val <?= $apiOnline ? 'online' : 'offline' ?>">
            <span class="sb-si-dot"></span>
            <?= $apiOnline ? t('status.online') : t('status.offline') ?>
          </span>
        </div>
        <div class="sb-si-row">
          <span class="sb-si-label">Host</span>
          <span class="sb-si-val mono"><?= e(gethostname()) ?></span>
        </div>
        <?php
        $conf = ts6ctl_conf_read();
        if (!empty($conf['TS6_INSTALLED_VERSION'])):
        ?>
        <div class="sb-si-row">
          <span class="sb-si-label">Version</span>
          <span class="sb-si-val mono"><?= e($conf['TS6_INSTALLED_VERSION']) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sb-nav">

      <div class="sb-nav-group">
        <div class="sb-nav-title">Übersicht</div>
        <a href="/?page=dashboard" class="sb-nav-item <?= is_page('dashboard') ? 'active' : '' ?>">
          <span class="sb-nav-icon">◈</span>
          <span><?= t('nav.dashboard') ?></span>
        </a>
      </div>

      <div class="sb-nav-group">
        <div class="sb-nav-title">Server</div>
        <a href="/?page=channels" class="sb-nav-item <?= is_page('channels') ? 'active' : '' ?>">
          <span class="sb-nav-icon">#</span>
          <span><?= t('nav.channels') ?></span>
        </a>
        <a href="/?page=clients" class="sb-nav-item <?= is_page('clients') ? 'active' : '' ?>">
          <span class="sb-nav-icon">@</span>
          <span><?= t('nav.clients') ?></span>
        </a>
        <a href="/?page=groups" class="sb-nav-item <?= is_page('groups') ? 'active' : '' ?>">
          <span class="sb-nav-icon">◉</span>
          <span><?= t('nav.groups') ?></span>
        </a>
        <a href="/?page=permissions" class="sb-nav-item <?= is_page('permissions') ? 'active' : '' ?>">
          <span class="sb-nav-icon">⊞</span>
          <span><?= t('nav.permissions') ?></span>
        </a>
      </div>

      <div class="sb-nav-group">
        <div class="sb-nav-title">Moderation</div>
        <a href="/?page=bans" class="sb-nav-item <?= is_page('bans') ? 'active' : '' ?>">
          <span class="sb-nav-icon">⊘</span>
          <span><?= t('nav.bans') ?></span>
        </a>
        <a href="/?page=tokens" class="sb-nav-item <?= is_page('tokens') ? 'active' : '' ?>">
          <span class="sb-nav-icon">🔑</span>
          <span>Privilege Keys</span>
        </a>
        <a href="/?page=clients_db" class="sb-nav-item <?= is_page('clients_db') ? 'active' : '' ?>">
          <span class="sb-nav-icon">◎</span>
          <span>Client-Datenbank</span>
        </a>
      </div>

      <div class="sb-nav-group">
        <div class="sb-nav-title">Migration</div>
        <a href="/?page=migration" class="sb-nav-item <?= is_page('migration') ? 'active' : '' ?>">
          <span class="sb-nav-icon">⇄</span>
          <span>TS3 → TS6</span>
        </a>
      </div>

      <div class="sb-nav-group">
        <div class="sb-nav-title">System</div>
        <a href="/?page=config" class="sb-nav-item <?= is_page('config') ? 'active' : '' ?>">
          <span class="sb-nav-icon">⚙</span>
          <span><?= t('nav.config') ?></span>
        </a>
        <a href="/?page=ts6ctl" class="sb-nav-item <?= is_page('ts6ctl') ? 'active' : '' ?>">
          <span class="sb-nav-icon">↑</span>
          <span><?= t('nav.ts6ctl') ?></span>
        </a>
        <a href="/?page=logs" class="sb-nav-item <?= is_page('logs') ? 'active' : '' ?>">
          <span class="sb-nav-icon">≡</span>
          <span><?= t('nav.logs') ?></span>
        </a>
      </div>

    </nav>

    <!-- Virtual Server Auswahl -->
    <div class="sb-vserver">
      <div class="sb-vserver-label"><?= t('gen.virtual_server') ?></div>
      <div class="sb-vserver-select">
        <span>Virtual Server <?= TS6_VSERVER_ID ?></span>
        <span class="sb-vserver-arrow">▾</span>
      </div>
    </div>

    <!-- Abmelden -->
    <div class="sb-footer">
      <a href="/logout.php" class="sb-logout">
        <span class="sb-nav-icon">⏻</span>
        <span><?= t('nav.logout') ?> (<?= e(auth_user()) ?>)</span>
      </a>
    </div>

  </aside>

  <!-- ── Hauptbereich ─────────────────────────────────────── -->
  <div class="main">

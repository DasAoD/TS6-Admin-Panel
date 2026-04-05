<?php
// ── Daten holen ───────────────────────────────────────────────
$serverInfo = $apiOnline ? api()->serverInfo() : null;
$clientList = $apiOnline ? api()->clientList() : null;
$groupList  = $apiOnline ? api()->serverGroupList() : null;
$chanList   = $apiOnline ? api()->channelList() : null;

$clients     = $clientList['data'] ?? [];
$groups      = array_filter($groupList['data'] ?? [], fn($g) => $g['type'] == 1);
$channels    = $chanList['data'] ?? [];
$pwChannels  = array_filter($channels, fn($c) => !empty($c['channel_flag_password']));
$realClients = array_filter($clients, fn($c) => ($c['client_type'] ?? 0) == 0);

// Channel-Map für Anzeige
$channelMap = [];
foreach ($channels as $ch) {
    $name = $ch['channel_name'] ?? '—';
    // Spacer-Prefix entfernen
    $name = preg_replace('/^\[[\*c]+spacer\d*\]/', '', $name);
    $channelMap[(int)$ch['cid']] = $name;
}

// ts6ctl.conf für Lizenz-Info
$conf        = ts6ctl_conf_read();
$version     = $conf['TS6_INSTALLED_VERSION'] ?? '—';

$flash = flash_get();
?>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= t('dash.title') ?></div>
    <div class="page-sub"><?= t('dash.sub') ?></div>
  </div>
  <div class="topbar-right">
    <a href="/?page=ts6ctl&action=restart" class="btn btn-ghost"
       data-confirm="Service wirklich neu starten?">
      <?= t('dash.restart') ?>
    </a>
    <a href="/?page=ts6ctl&action=check" class="btn btn-primary">
      <?= t('dash.check_update') ?>
    </a>
  </div>
</div>

<!-- Content -->
<div class="content">

  <?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!$apiOnline): ?>
  <div class="alert alert-danger">
    Keine Verbindung zur TS6 HTTP-Query-API (Port <?= TS6_API_PORT ?>).
    Ist der Server gestartet und TSSERVER_QUERY_HTTP_ENABLED=true gesetzt?
  </div>
  <?php else: ?>

  <!-- Metriken -->
  <div class="metrics">
    <div class="metric">
      <div class="metric-label"><?= t('dash.online_clients') ?></div>
      <div class="metric-val accent"><?= count($realClients) ?></div>
      <div class="metric-sub"><?= t('status.slots') ?>: 32</div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= t('dash.channels') ?></div>
      <div class="metric-val"><?= count($channels) ?></div>
      <div class="metric-sub"><?= count($pwChannels) ?> <?= t('dash.pw_protected') ?></div>
    </div>
    <div class="metric">
      <div class="metric-label"><?= t('dash.groups') ?></div>
      <div class="metric-val"><?= count($groups) ?></div>
      <div class="metric-sub"><?= t('grp.type_regular') ?></div>
    </div>
  </div>

  <!-- 3-Spalten-Panel -->
  <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:14px;align-items:start">

    <!-- Channel-Übersicht (Live) -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Channel-Übersicht</span>
        <span id="tree-status" style="font-size:11px;color:var(--text-subtle)">wird geladen…</span>
      </div>
      <div id="channel-tree-panel" style="min-height:60px">
        <div style="padding:16px;color:var(--text-subtle);font-size:13px;text-align:center">
          Lade…
        </div>
      </div>
    </div>

    <!-- Verbundene Clients (wird live aktualisiert) -->
    <div class="card">
      <div class="card-head">
        <span class="card-title"><?= t('dash.active_clients') ?></span>
        <a href="/?page=clients" class="card-link"><?= t('dash.show_all') ?></a>
      </div>
      <div id="clients-panel">
        <div style="padding:16px;color:var(--text-subtle);font-size:13px;text-align:center">Lade…</div>
      </div>
    </div>

    <!-- Servergruppen -->
    <div class="card">
      <div class="card-head">
        <span class="card-title"><?= t('dash.server_groups') ?></span>
        <a href="/?page=groups" class="card-link"><?= t('dash.manage') ?></a>
      </div>
      <?php
      $colors = ['#f85149','#00b4d8','#e3b341','#3fb950','#a371f7','#ffa657','#79c0ff'];
      $i = 0;
      foreach (array_slice($groups, 0, 7) as $g): ?>
      <div class="card-item">
        <div class="ci-dot" style="background:<?= $colors[$i++ % count($colors)] ?>"></div>
        <span class="ci-name"><?= e($g['name']) ?></span>
        <span class="ci-meta"><?= e($g['count'] ?? '—') ?> <?= t('dash.members') ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>

  <script>
  (function() {
    var panel    = document.getElementById('channel-tree-panel');
    var statusEl = document.getElementById('tree-status');
    var interval = 5000;
    var prevState = '';

    function render(data) {
      if (!data.success) {
        panel.innerHTML = '<div style="padding:16px;color:var(--red);font-size:13px">' +
          'Fehler: ' + (data.error || 'Unbekannt') + '</div>';
        return;
      }

      var html = '';
      data.tree.forEach(function(ch) {
        if (ch.spacer) {
          html += '<div style="padding:4px 10px;background:var(--bg-elevated);' +
                  'font-size:10px;color:var(--text-subtle);border-bottom:1px solid var(--border);' +
                  'font-family:var(--font-mono)">' + escHtml(ch.name) + '</div>';
          if (ch.clients.length > 0) {
            ch.clients.forEach(function(c) {
              html += clientRow(c);
            });
          }
          return;
        }

        var icon = ch.password ? '🔒' : '▶';
        var cntBadge = ch.clients.length > 0
          ? '<span style="font-size:10px;background:var(--accent-dim);color:var(--accent);' +
            'padding:1px 7px;border-radius:10px;margin-left:auto">' + ch.clients.length + '</span>'
          : '';

        html += '<div style="display:flex;align-items:center;gap:6px;padding:7px 10px;' +
                'border-bottom:1px solid var(--border)">' +
                '<span style="font-size:10px;color:var(--text-subtle);width:10px;flex-shrink:0;' +
                'font-family:var(--font-mono)">' + icon + '</span>' +
                '<span style="font-size:13px;color:var(--text-primary);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escHtml(ch.name) + '</span>' +
                cntBadge + '</div>';

        ch.clients.forEach(function(c) {
          html += clientRow(c);
        });
      });

      var newState = JSON.stringify(data.tree);
      if (newState !== prevState) {
        panel.innerHTML = html || '<div style="padding:16px;color:var(--text-subtle);font-size:13px">Keine Channels.</div>';
        prevState = newState;
      }

      statusEl.textContent = data.timestamp + ' · ' + data.total +
        ' Client' + (data.total !== 1 ? 's' : '');

      // ── Clients-Panel aktualisieren ──────────────────────
      var clientsPanel = document.getElementById('clients-panel');
      if (clientsPanel) {
        // Alle Clients aus dem Tree sammeln
        var allClients = [];
        data.tree.forEach(function(ch) {
          ch.clients.forEach(function(c) {
            allClients.push({
              nickname: c.nickname,
              away:     c.away,
              channel:  ch.name,
            });
          });
        });

        var clientsHtml = '';
        if (allClients.length === 0) {
          clientsHtml = '<div style="padding:16px;font-size:13px;color:var(--text-muted)">Keine Clients verbunden.</div>';
        } else {
          allClients.forEach(function(c) {
            var color    = c.away ? 'var(--yellow)' : 'var(--green)';
            // = - Zeichen und Leerzeichen trimmen für kurze Anzeige
            var chanClean = c.channel.replace(/^[=\-\s]+|[=\-\s]+$/g, '').trim() || c.channel;
            clientsHtml += '<div style="display:flex;align-items:center;gap:8px;padding:9px 10px;' +
              'border-bottom:1px solid var(--border)">' +
              '<div style="width:8px;height:8px;border-radius:2px;background:' + color + ';flex-shrink:0"></div>' +
              '<span style="flex:1;font-size:13px;color:var(--color-text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escHtml(c.nickname) + '</span>' +
              '<span style="font-size:11px;color:var(--text-muted);flex-shrink:0;max-width:90px;overflow:hidden;' +
              'text-overflow:ellipsis;white-space:nowrap;text-align:right" title="' + escHtml(c.channel) + '">' + escHtml(chanClean) + '</span>' +
              '</div>';
          });
        }

        var prevClientsState = clientsPanel.dataset.prevState || '';
        if (clientsHtml !== prevClientsState) {
          clientsPanel.innerHTML = clientsHtml;
          clientsPanel.dataset.prevState = clientsHtml;
        }
      }
    }

    function clientRow(c) {
      var color = c.away ? 'var(--yellow)' : 'var(--green)';
      return '<div class="dash-client-row" style="display:flex;align-items:center;gap:8px;' +
        'padding:5px 10px 5px 24px;border-bottom:1px solid var(--border);' +
        'background:var(--bg-base);cursor:context-menu;user-select:none"' +
        ' data-clid="' + c.clid + '" data-nick="' + escHtml(c.nickname) + '">' +
        '<span style="color:' + color + ';font-size:10px">●</span>' +
        '<span style="font-size:12px;color:var(--text-primary)">' + escHtml(c.nickname) + '</span>' +
        (c.away ? '<span class="badge badge-yellow" style="font-size:10px;margin-left:auto">Abwesend</span>' : '') +
        '</div>';
    }

    function escHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fetch_tree() {
      fetch('/api/channel_tree.php')
        .then(function(r) { return r.json(); })
        .then(render)
        .catch(function() { statusEl.textContent = 'Verbindungsfehler'; });
    }

    fetch_tree();
    setInterval(fetch_tree, interval);
  })();
  </script>

  <!-- Kontextmenü -->
  <div id="ctx-menu" style="display:none;position:fixed;z-index:200;
       background:var(--bg-surface);border:1px solid var(--border-hi);
       border-radius:var(--radius-md);min-width:180px;box-shadow:0 4px 20px rgba(0,0,0,0.4);
       overflow:hidden">
    <div id="ctx-nick" style="padding:8px 14px;font-size:11px;color:var(--text-subtle);
         border-bottom:1px solid var(--border);font-weight:500"></div>
    <div class="ctx-item" data-action="poke">💬 Poke senden</div>
    <div class="ctx-item" data-action="move">↔ In Channel verschieben</div>
    <div style="border-top:1px solid var(--border);margin:3px 0"></div>
    <div class="ctx-item" data-action="kick_channel">⚡ Aus Channel kicken</div>
    <div class="ctx-item" data-action="kick" style="color:var(--red)">✖ Vom Server kicken</div>
    <div class="ctx-item" data-action="ban" style="color:var(--red)">⛔ Bannen</div>
  </div>

  <!-- Modals für Kontextmenü-Aktionen -->
  <div id="ctx-modal" class="modal-overlay" style="display:none">
    <div class="modal-box">
      <div class="modal-title" id="ctx-modal-title"></div>
      <div id="ctx-modal-body"></div>
      <div class="modal-actions">
        <button id="ctx-modal-ok" class="btn btn-primary">Ausführen</button>
        <button class="btn btn-ghost"
                onclick="document.getElementById('ctx-modal').style.display='none'">
          Abbrechen
        </button>
      </div>
    </div>
  </div>

  <script>
  (function() {
    var ctxMenu   = document.getElementById('ctx-menu');
    var ctxNick   = document.getElementById('ctx-nick');
    var ctxModal  = document.getElementById('ctx-modal');
    var ctxTitle  = document.getElementById('ctx-modal-title');
    var ctxBody   = document.getElementById('ctx-modal-body');
    var ctxOk     = document.getElementById('ctx-modal-ok');
    var activeClid = null;
    var activeNick = null;

    // CSS für Kontextmenü-Items
    var style = document.createElement('style');
    style.textContent = '.ctx-item{padding:8px 14px;font-size:13px;cursor:pointer;' +
      'color:var(--text-primary)}.ctx-item:hover{background:var(--bg-elevated)}';
    document.head.appendChild(style);

    // Kontextmenü öffnen — auf document lauschen für dynamisch gerenderte Elemente
    document.addEventListener('contextmenu', function(e) {
      var row = e.target.closest('.dash-client-row');
      if (!row) return;
      e.preventDefault();

      activeClid = row.dataset.clid;
      activeNick = row.dataset.nick;
      ctxNick.textContent = activeNick;

      ctxMenu.style.display = 'block';
      var x = e.clientX, y = e.clientY;
      if (x + 200 > window.innerWidth)  x = window.innerWidth - 210;
      if (y + 200 > window.innerHeight) y = window.innerHeight - 210;
      ctxMenu.style.left = x + 'px';
      ctxMenu.style.top  = y + 'px';
    });

    // Menü schließen bei Klick irgendwo
    document.addEventListener('click', function() { ctxMenu.style.display = 'none'; });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        ctxMenu.style.display = 'none';
        ctxModal.style.display = 'none';
      }
    });

    // Channel-Liste für Move-Dropdown (wird bei Bedarf befüllt)
    var channelCache = [];
    fetch('/api/channel_tree.php').then(r=>r.json()).then(function(d) {
      channelCache = (d.tree || []).filter(function(c) { return !c.spacer; });
    });

    // Menü-Item geklickt
    document.querySelectorAll('.ctx-item').forEach(function(item) {
      item.addEventListener('click', function() {
        ctxMenu.style.display = 'none';
        var action = item.dataset.action;
        showActionModal(action);
      });
    });

    function showActionModal(action) {
      var titles = {
        poke:         '💬 Poke an ' + activeNick,
        move:         '↔ ' + activeNick + ' verschieben',
        kick_channel: '⚡ ' + activeNick + ' aus Channel kicken',
        kick:         '✖ ' + activeNick + ' vom Server kicken',
        ban:          '⛔ ' + activeNick + ' bannen',
      };
      ctxTitle.textContent = titles[action] || action;
      ctxOk.className = (action === 'kick' || action === 'ban') ? 'btn btn-danger' : 'btn btn-primary';

      var body = '';
      if (action === 'poke') {
        body = '<div class="form-group">' +
          '<label class="form-label">Nachricht</label>' +
          '<input type="text" id="ctx-input-msg" class="form-input" placeholder="Deine Nachricht…" maxlength="100">' +
          '</div>';
      } else if (action === 'move') {
        var opts = channelCache.map(function(c) {
          return '<option value="' + c.cid + '">' + c.name + '</option>';
        }).join('');
        body = '<div class="form-group">' +
          '<label class="form-label">Ziel-Channel</label>' +
          '<select id="ctx-input-cid" class="form-select">' + opts + '</select>' +
          '</div>';
      } else if (action === 'kick' || action === 'kick_channel') {
        body = '<div class="form-group">' +
          '<label class="form-label">Grund (optional)</label>' +
          '<input type="text" id="ctx-input-reason" class="form-input" placeholder="Kick-Nachricht">' +
          '</div>';
      } else if (action === 'ban') {
        body = '<div class="form-group">' +
          '<label class="form-label">Grund</label>' +
          '<input type="text" id="ctx-input-reason" class="form-input" placeholder="Ban-Grund">' +
          '</div>' +
          '<div class="form-group">' +
          '<label class="form-label">Dauer in Sekunden (0 = permanent)</label>' +
          '<input type="number" id="ctx-input-time" class="form-input" value="0" min="0">' +
          '</div>';
      }

      ctxBody.innerHTML = body;
      ctxModal.style.display = 'flex';
      var firstInput = ctxBody.querySelector('input, select');
      if (firstInput) setTimeout(function() { firstInput.focus(); }, 50);

      // OK-Button
      ctxOk.onclick = function() {
        var payload = { action: action, clid: parseInt(activeClid) };
        if (action === 'poke')         payload.msg    = document.getElementById('ctx-input-msg').value;
        if (action === 'move')         payload.cid    = parseInt(document.getElementById('ctx-input-cid').value);
        if (action === 'kick' || action === 'kick_channel')
                                       payload.reason = document.getElementById('ctx-input-reason').value;
        if (action === 'ban') {
          payload.reason = document.getElementById('ctx-input-reason').value;
          payload.time   = parseInt(document.getElementById('ctx-input-time').value) || 0;
        }

        fetch('/api/client_action.php', {
          method:  'POST',
          headers: {'Content-Type': 'application/json'},
          body:    JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(function(data) {
          ctxModal.style.display = 'none';
          if (!data.success) {
            alert('Fehler: ' + (data.error || 'Unbekannt'));
          }
        })
        .catch(function() {
          ctxModal.style.display = 'none';
          alert('Verbindungsfehler.');
        });
      };
    }

    ctxModal.addEventListener('click', function(e) {
      if (e.target === ctxModal) ctxModal.style.display = 'none';
    });
  })();
  </script>
  <?php endif; ?>

</div>
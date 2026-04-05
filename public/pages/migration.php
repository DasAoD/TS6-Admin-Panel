<?php
// =============================================================
//  pages/migration.php — TS3 → TS6 Benutzer-Migration
// =============================================================

// ── Gruppen-Mapping TS3 → TS6 ─────────────────────────────────
$groupMapping = [
    'Server Admin'  => 6,
    'Mitglieder'    => 9,
    'Guest'         => 8,
    'Gildenleitung' => 10,
    'Rekrutierung'  => 11,
    'Normal'        => 7,
];

// ── POST: Gruppen-Zuweisung durchführen ───────────────────────
$assignResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matches = $_POST['ts6_match'] ?? [];
    $sgidFor = $_POST['sgid_for'] ?? [];
    foreach ($matches as $ts3Nick => $cldbidRaw) {
        // cldbid kann "4" oder "4:9" sein (mit vorgeschlagener sgid)
        $cldbid = (int)explode(':', $cldbidRaw)[0];
        $sgid   = (int)($sgidFor[$ts3Nick] ?? 0);
        if ($cldbid > 0 && $sgid > 0) {
            $result = api()->serverGroupAddClient($sgid, $cldbid);
            $assignResults[] = [
                'cldbid'  => $cldbid,
                'sgid'    => $sgid,
                'success' => $result['success'],
                'error'   => $result['error'] ?? '',
            ];
        }
    }
}

// ── TS3 User aus JSON laden ───────────────────────────────────
$ts3Users = [];
$ts3Error = '';
$jsonFile = CONFIG_PATH . '/ts3_users.json';
if (file_exists($jsonFile)) {
    $flat = json_decode(file_get_contents($jsonFile), true) ?? [];
    $byUid = [];
    foreach ($flat as $row) {
        $uid = $row['uid'];
        if (!isset($byUid[$uid])) {
            $byUid[$uid] = [
                'nickname' => $row['nickname'],
                'uid'      => $uid,
                'gruppen'  => [],
            ];
        }
        $byUid[$uid]['gruppen'][] = $row['gruppe'];
    }
    foreach ($byUid as &$u) {
        $u['gruppen'] = array_unique($u['gruppen']);
    }
    $ts3Users = array_values($byUid);
} else {
    $ts3Error = 'Exportdatei ts3_users.json nicht gefunden.';
}

// ── TS6 DB-Clients laden ──────────────────────────────────────
$ts6Clients = [];
if ($apiOnline) {
    $result = api()->clientDbList(0, 100);
    foreach ($result['data'] ?? [] as $c) {
        $cldbid = $c['cldbid'] ?? null;
        if ($cldbid) {
            $ts6Clients[$cldbid] = $c;
        }
    }
}

// Gruppen-Namen-Map für Anzeige
$groupNameMap = [];
if ($apiOnline) {
    foreach (api()->serverGroupList()['data'] ?? [] as $g) {
        $groupNameMap[(int)$g['sgid']] = $g['name'];
    }
}

$flash = flash_get();
?>

<div class="topbar">
  <div class="topbar-left">
    <div class="page-title">TS3 → TS6 Migration</div>
    <div class="page-sub">Benutzer manuell zuordnen und Gruppen übernehmen</div>
  </div>
</div>

<div class="content">

  <?php if (!empty($assignResults)): ?>
  <div class="form-section" style="margin-bottom:16px">
    <div class="form-section-head">Zuweisungs-Ergebnisse</div>
    <div class="form-section-body">
      <?php foreach ($assignResults as $r):
        $gName = $groupNameMap[$r['sgid']] ?? "Gruppe {$r['sgid']}";
        // Nick aus ts6Clients
        $nick = $ts6Clients[$r['cldbid']]['client_nickname'] ?? "DB-ID {$r['cldbid']}";
      ?>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px">
        <?php if ($r['success']): ?>
          <span style="color:var(--green)">✓</span>
          <span><?= e($nick) ?> → <strong><?= e($gName) ?></strong> zugewiesen</span>
        <?php else: ?>
          <span style="color:var(--red)">✗</span>
          <span><?= e($nick) ?> (DB-ID <?= $r['cldbid'] ?>): <?= e($r['error']) ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Metriken -->
  <div class="metrics" style="margin-bottom:16px">
    <div class="metric">
      <div class="metric-label">TS3 Benutzer</div>
      <div class="metric-val accent"><?= count($ts3Users) ?></div>
      <div class="metric-sub">aus ts3_users.json</div>
    </div>
    <div class="metric">
      <div class="metric-label">TS6 bekannte Clients</div>
      <div class="metric-val"><?= count($ts6Clients) ?></div>
      <div class="metric-sub">in TS6-Datenbank</div>
    </div>
    <div class="metric">
      <div class="metric-label">Hinweis</div>
      <div class="metric-val" style="font-size:13px;margin-top:8px;color:var(--yellow)">⚠ Neue UIDs</div>
      <div class="metric-sub">TS6 generiert neue UIDs — manuelles Matching nötig</div>
    </div>
  </div>

  <?php if ($ts3Error): ?>
  <div class="alert alert-warning"><?= e($ts3Error) ?></div>
  <div class="form-section">
    <div class="form-section-head">Export-Befehl (auf dns1 ausführen)</div>
    <div class="form-section-body">
      <pre style="background:var(--bg-base);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;font-size:12px;font-family:var(--font-mono);overflow-x:auto;color:var(--text-primary)">ssh vpn -p 3785 "mysql -u root teamspeak3 -Bse \"
SELECT c.client_nickname, c.client_unique_id, gs.name
FROM group_server_to_client gsc
JOIN clients c ON c.client_id = gsc.id1
JOIN groups_server gs ON gs.group_id = gsc.group_id
WHERE gs.type = 1 AND gs.name NOT IN ('Guest','Normal')
ORDER BY gs.name, c.client_nickname;\"" | \
python3 -c "
import sys,json
rows=[]
for line in sys.stdin:
    p=line.strip().split('\t')
    if len(p)==3:
        rows.append({'nickname':p[0],'uid':p[1],'gruppe':p[2]})
print(json.dumps(rows,indent=2,ensure_ascii=False))
" > /var/www/ts6admin/config/ts3_users.json</pre>
    </div>
  </div>

  <?php elseif (empty($ts3Users)): ?>
  <div class="alert alert-info">Keine Benutzer mit relevanten Gruppen gefunden.</div>

  <?php else: ?>

  <div class="alert alert-info">
    TS6 generiert für jeden Client eine neue UID — automatisches Matching ist nicht möglich.
    Ordne jeden TS3-Benutzer manuell dem entsprechenden TS6-Client zu.
    Clients die sich noch nie verbunden haben erscheinen nicht in der TS6-Liste.
  </div>

  <div class="form-section">
    <div class="form-section-head">Manuelle Zuordnung TS3 → TS6</div>
    <form method="post" action="/?page=migration">
    <table class="data">
      <thead>
        <tr>
          <th>TS3 Nickname</th>
          <th>TS3 Gruppe(n)</th>
          <th>TS6 Client zuordnen</th>
          <th>Gruppe zuweisen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ts3Users as $u):
          $nick   = $u['nickname'];
          $gruppen = $u['gruppen'];

          // Vorgeschlagene Gruppe
          $suggestedSgid = 0;
          foreach ($gruppen as $gName) {
              if (isset($groupMapping[$gName])) {
                  $suggestedSgid = $groupMapping[$gName];
                  break;
              }
          }

          // TS6-Client per Nickname vorschlagen
          $suggestedCldbid = '';
          foreach ($ts6Clients as $cldbid => $c) {
              if (strtolower($c['client_nickname'] ?? '') === strtolower($nick)) {
                  $suggestedCldbid = $cldbid;
                  break;
              }
          }
        ?>
        <tr>
          <td>
            <div style="font-weight:500"><?= e($nick) ?></div>
          </td>
          <td>
            <?php foreach ($gruppen as $g): ?>
            <span class="badge badge-gray" style="margin-right:3px"><?= e($g) ?></span>
            <?php endforeach; ?>
          </td>
          <td>
            <?php if (empty($ts6Clients)): ?>
            <span style="color:var(--text-subtle);font-size:12px">Keine TS6-Clients bekannt</span>
            <?php else: ?>
            <select name="ts6_match[<?= e($nick) ?>]"
                    class="form-select" style="font-size:12px;padding:4px 8px"
                    onchange="updateAssignment(this)">
              <option value="">— Noch nicht verbunden / überspringen —</option>
              <?php foreach ($ts6Clients as $cldbid => $c):
                $cNick = $c['client_nickname'] ?? '—';
                if ($cNick === 'ServerQuery Guest') continue;
                $sel = ($cldbid == $suggestedCldbid) ? 'selected' : '';
              ?>
              <option value="<?= e($cldbid) ?>:<?= $suggestedSgid ?>" <?= $sel ?>>
                <?= e($cNick) ?> (DB-ID: <?= e($cldbid) ?>)
                <?= $sel ? ' ← Namens-Treffer' : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
          </td>
          <td>
            <select name="sgid_for[<?= e($nick) ?>]"
                    class="form-select" style="font-size:12px;padding:4px 8px">
              <option value="0">— Keine Zuweisung —</option>
              <?php foreach ($groupMapping as $gName => $sgid): ?>
              <option value="<?= $sgid ?>" <?= $suggestedSgid === $sgid ? 'selected' : '' ?>>
                <?= e($gName) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="padding:14px 16px;border-top:1px solid var(--border);background:var(--bg-elevated);display:flex;gap:8px;align-items:center">
      <button type="submit" class="btn btn-primary">Zuweisungen speichern</button>
      <span style="font-size:12px;color:var(--text-muted)">
        Nur Zeilen mit gewähltem TS6-Client und Gruppe werden verarbeitet.
      </span>
    </div>
    </form>
  </div>

  <?php endif; ?>

</div>
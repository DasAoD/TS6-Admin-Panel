<?php
// =============================================================
//  TS6Api — PHP-Wrapper für die TS6 HTTP-Query-API
// =============================================================

class TS6Api {

    private string $host;
    private int    $port;
    private string $apiKey;
    private int    $vsId;
    private string $baseUrl;

    public function __construct(
        string $host   = TS6_API_HOST,
        int    $port   = TS6_API_PORT,
        string $apiKey = TS6_API_KEY,
        int    $vsId   = TS6_VSERVER_ID
    ) {
        $this->host    = $host;
        $this->port    = $port;
        $this->apiKey  = $apiKey;
        $this->vsId    = $vsId;
        $this->baseUrl = "http://{$host}:{$port}/{$vsId}";
    }

    // ── Kernmethode: HTTP-Request ─────────────────────────────
    private function request(string $command, array $params = [], string $method = 'GET'): array {
        $url = $this->baseUrl . '/' . $command;

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => $method,
                'header'  => [
                    'x-api-key: ' . $this->apiKey,
                    'Content-Type: application/json',
                ],
                'content' => ($method === 'POST') ? json_encode($params) : null,
                'timeout' => 5,
                'ignore_errors' => true,
            ]
        ]);

        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) {
            return ['success' => false, 'error' => 'Verbindung zum TS6-Server fehlgeschlagen.'];
        }

        $data = json_decode($raw, true);

        if (!isset($data['status'])) {
            return ['success' => false, 'error' => 'Ungültige Antwort vom Server.'];
        }

        if ($data['status']['code'] !== 0) {
            return ['success' => false, 'error' => $data['status']['message'] ?? 'Unbekannter Fehler.'];
        }

        return ['success' => true, 'data' => $data['body'] ?? []];
    }

    // ── Verbindungstest ───────────────────────────────────────
    public function ping(): bool {
        $result = $this->request('serverinfo');
        return $result['success'];
    }

    // ── Server ────────────────────────────────────────────────
    public function serverInfo(): array {
        return $this->request('serverinfo');
    }

    // ── Channels ──────────────────────────────────────────────
    public function channelList(): array {
        return $this->request('channellist', ['-topic', '-flags', '-voice', '-limits', '-icon', '-secondsempty']);
    }

    public function channelInfo(int $cid): array {
        return $this->request('channelinfo', ['cid' => $cid]);
    }

    public function channelCreate(array $params): array {
        return $this->request('channelcreate', $params, 'POST');
    }

    public function channelEdit(int $cid, array $params): array {
        $params['cid'] = $cid;
        return $this->request('channeledit', $params, 'POST');
    }

    public function channelDelete(int $cid, bool $force = false): array {
        return $this->request('channeldelete', ['cid' => $cid, 'force' => $force ? 1 : 0], 'POST');
    }

    public function channelPermList(int $cid): array {
        return $this->request('channelpermlist', ['cid' => $cid, '-permsid' => '']);
    }

    // ── Clients ───────────────────────────────────────────────
    public function clientList(): array {
        return $this->request('clientlist', ['-uid', '-away', '-groups', '-info']);
    }

    public function clientInfo(int $clid): array {
        return $this->request('clientinfo', ['clid' => $clid]);
    }

    public function clientKick(int $clid, int $reasonId = 5, string $msg = ''): array {
        return $this->request('clientkick', [
            'clid'      => $clid,
            'reasonid'  => $reasonId,
            'reasonmsg' => $msg,
        ], 'POST');
    }

    public function clientMove(int $clid, int $cid): array {
        return $this->request('clientmove', ['clid' => $clid, 'cid' => $cid], 'POST');
    }

    public function clientPoke(int $clid, string $msg): array {
        return $this->request('clientpoke', ['clid' => $clid, 'msg' => $msg], 'POST');
    }

    public function clientDbList(int $start = 0, int $duration = 25): array {
        return $this->request('clientdblist', ['start' => $start, 'duration' => $duration, '-count' => '']);
    }

    public function clientDbInfo(int $cldbid): array {
        return $this->request('clientdbinfo', ['cldbid' => $cldbid]);
    }

    // ── Server-Gruppen ────────────────────────────────────────
    public function serverGroupList(): array {
        return $this->request('servergrouplist');
    }

    public function serverGroupAdd(string $name, int $type = 1): array {
        return $this->request('servergroupadd', ['name' => $name, 'type' => $type], 'POST');
    }

    public function serverGroupDel(int $sgid, bool $force = false): array {
        return $this->request('servergroupdel', ['sgid' => $sgid, 'force' => $force ? 1 : 0], 'POST');
    }

    public function serverGroupRename(int $sgid, string $name): array {
        return $this->request('servergrouprename', ['sgid' => $sgid, 'name' => $name], 'POST');
    }

    public function serverGroupAddClient(int $sgid, int $cldbid): array {
        return $this->request('servergroupaddclient', ['sgid' => $sgid, 'cldbid' => $cldbid], 'POST');
    }

    public function serverGroupDelClient(int $sgid, int $cldbid): array {
        return $this->request('servergroupdelclient', ['sgid' => $sgid, 'cldbid' => $cldbid], 'POST');
    }

    public function serverGroupClientList(int $sgid): array {
        return $this->request('servergroupclientlist', ['sgid' => $sgid, '-names' => '']);
    }

    public function serverGroupPermList(int $sgid): array {
        return $this->request('servergrouppermlist', ['sgid' => $sgid, '-permsid' => '']);
    }

    public function serverGroupAddPerm(int $sgid, string $permSid, int $permValue, int $permNegated = 0, int $permSkip = 0): array {
        return $this->request('servergroupaddperm', [
            'sgid'        => $sgid,
            'permsid'     => $permSid,
            'permvalue'   => $permValue,
            'permnegated' => $permNegated,
            'permskip'    => $permSkip,
        ], 'POST');
    }

    // ── Bans ─────────────────────────────────────────────────
    public function banList(): array {
        return $this->request('banlist');
    }

    public function banAdd(array $params): array {
        // params: ip, name, uid, time, banreason
        return $this->request('banadd', $params, 'POST');
    }

    public function banDel(int $banid): array {
        return $this->request('bandel', ['banid' => $banid], 'POST');
    }

    public function banDelAll(): array {
        return $this->request('bandelall', [], 'POST');
    }

    public function banClient(int $clid, int $time = 0, string $reason = ''): array {
        return $this->request('banclient', [
            'clid'      => $clid,
            'time'      => $time,
            'banreason' => $reason,
        ], 'POST');
    }

    // ── Berechtigungen (Übersicht) ────────────────────────────
    public function permissionList(): array {
        return $this->request('permissionlist');
    }

    // ── Virtual Server Verwaltung ─────────────────────────────
    public function serverEdit(array $params): array {
        return $this->request('serveredit', $params, 'POST');
    }

    public function serverList(): array {
        // Rootlevel-Aufruf ohne vServer-ID
        $url = "http://{$this->host}:{$this->port}/serverlist";
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => 'x-api-key: ' . $this->apiKey,
                'timeout' => 5,
                'ignore_errors' => true,
            ]
        ]);
        $raw  = @file_get_contents($url, false, $ctx);
        $data = json_decode($raw, true);
        if (!$data || $data['status']['code'] !== 0) {
            return ['success' => false, 'error' => 'Serverliste nicht verfügbar.'];
        }
        return ['success' => true, 'data' => $data['body'] ?? []];
    }

    // ── Logs ─────────────────────────────────────────────────
    public function logView(int $lines = 50): array {
        return $this->request('logview', ['lines' => $lines, 'reverse' => 1]);
    }
}
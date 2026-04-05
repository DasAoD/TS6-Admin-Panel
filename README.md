# TS6 Admin Panel

**[English](#english) | [Deutsch](#deutsch)**

---

## English

### Overview
TS6 Admin Panel is a web-based administration interface for TeamSpeak 6 servers.
It provides a live dashboard, channel and client management, group permissions,
ban management, and a TS3→TS6 migration assistant.

Requires [ts6ctl](https://github.com/DasAoD/ts6-installer) — a CLI management
script for service control and automatic updates.

### Features
- 📊 Live dashboard with server stats and context menu (right-click on clients)
- 🗂️ Channel tree management
- 👥 Client and database client management
- 🔐 Server group & permission management
- 🚫 Ban management
- 🔑 Privilege token management
- 🔄 TS3 → TS6 migration assistant
- ⚙️ Web-based configuration (API key, password, mail settings)
- 🖥️ ts6ctl integration (service control, update management, log viewer)
- 🌐 Multilingual (de/en)

### Requirements
- VPS or dedicated/root server (Debian/Ubuntu recommended)
- PHP 8.4 (never 8.3!)
- nginx or Apache with document root pointing to `public/`
- TeamSpeak 6 Server (tested with v6.0.0-beta8)
- [ts6ctl](https://github.com/DasAoD/ts6-installer) — CLI management script
- `sudo` access for `www-data` (see [Sudoers Configuration](#sudoers-configuration))

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/DasAoD/TS6-Admin-Panel.git
   cd TS6-Admin-Panel
   ```

2. Copy and edit configuration files:
   ```bash
   cp config/config.example.php config/config.php
   cp config/ts6ctl.conf.example config/ts6ctl.conf
   nano config/config.php
   nano config/ts6ctl.conf
   ```

3. Set permissions:
   ```bash
   chown -R www-data:www-data .
   chmod 640 config/config.php config/ts6ctl.conf
   chmod 600 config/.admin_pass
   ```

4. Configure your web server to point the document root to `public/`.

5. Set an admin password via the configuration page, or manually:
   ```bash
   php -r "echo password_hash('YOURPASSWORD', PASSWORD_DEFAULT);" > config/.admin_pass
   chmod 600 config/.admin_pass
   chown www-data:www-data config/.admin_pass
   ```

6. Configure sudoers (see below).

### Sudoers Configuration

`www-data` needs limited sudo access for service control and ts6ctl.
Create `/etc/sudoers.d/ts6admin`:

```bash
visudo -f /etc/sudoers.d/ts6admin
```

Add the following content:

```
# ts6admin WebUI — allows www-data service control and ts6ctl
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start teamspeak6
www-data ALL=(ALL) NOPASSWD: /bin/systemctl stop teamspeak6
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart teamspeak6
www-data ALL=(ALL) NOPASSWD: /bin/systemctl status teamspeak6
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ts6ctl check-update
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ts6ctl update
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ts6ctl status
```

### Directory Structure
```
TS6-Admin-Panel/
├── config/                  # Sensitive config (not in Git)
│   ├── config.example.php   # Template for config.php
│   ├── ts6ctl.conf.example  # Template for ts6ctl.conf
│   ├── config.php           # ← create from example
│   ├── ts6ctl.conf          # ← create from example
│   └── .admin_pass          # ← set via config page or manually
├── includes/                # PHP helpers, auth, header, footer
├── lang/                    # Language files (de, en)
└── public/                  # Web root (nginx/Apache document root)
    ├── api/                 # AJAX endpoints
    ├── assets/              # CSS, JS
    ├── pages/               # Page templates
    ├── index.php
    ├── login.php
    └── logout.php
```

### Known Limitations (TS6 Beta)
- Icons not yet displayed in TS6 client (beta restriction)
- Drag & Drop channel ordering (channelmove API buggy in beta8)
- Poke reply (TS6 beta bug)
- TS6 generates new UIDs — no automatic TS3→TS6 avatar matching

### License
MIT — see [LICENSE](LICENSE)

---

## Deutsch

### Übersicht
TS6 Admin Panel ist eine webbasierte Administrationsoberfläche für TeamSpeak 6 Server.
Es bietet ein Live-Dashboard, Channel- und Client-Verwaltung, Gruppenberechtigungen,
Ban-Verwaltung und einen TS3→TS6 Migrationsassistenten.

Benötigt [ts6ctl](https://github.com/DasAoD/ts6-installer) — ein CLI-Management-Skript
für Service-Steuerung und automatische Updates.

### Funktionen
- 📊 Live-Dashboard mit Serverstatistiken und Kontextmenü (Rechtsklick auf Clients)
- 🗂️ Channel-Baum-Verwaltung
- 👥 Client- und Datenbankclients-Verwaltung
- 🔐 Servergruppen- & Berechtigungsverwaltung
- 🚫 Ban-Verwaltung
- 🔑 Privilege-Token-Verwaltung
- 🔄 TS3 → TS6 Migrationsassistent
- ⚙️ Webbasierte Konfiguration (API-Key, Passwort, Mail-Einstellungen)
- 🖥️ ts6ctl-Integration (Service-Steuerung, Update-Verwaltung, Log-Viewer)
- 🌐 Mehrsprachig (de/en)

### Voraussetzungen
- VPS oder Dedicated-/Root-Server (Debian/Ubuntu empfohlen)
- PHP 8.4 (niemals 8.3!)
- nginx oder Apache mit Document Root auf `public/`
- TeamSpeak 6 Server (getestet mit v6.0.0-beta8)
- [ts6ctl](https://github.com/DasAoD/ts6-installer) — CLI-Management-Skript
- `sudo`-Berechtigung für `www-data` (siehe [sudoers-Konfiguration](#sudoers-konfiguration))

### Installation

1. Repository klonen:
   ```bash
   git clone https://github.com/DasAoD/TS6-Admin-Panel.git
   cd TS6-Admin-Panel
   ```

2. Konfigurationsdateien kopieren und anpassen:
   ```bash
   cp config/config.example.php config/config.php
   cp config/ts6ctl.conf.example config/ts6ctl.conf
   nano config/config.php
   nano config/ts6ctl.conf
   ```

3. Berechtigungen setzen:
   ```bash
   chown -R www-data:www-data .
   chmod 640 config/config.php config/ts6ctl.conf
   chmod 600 config/.admin_pass
   ```

4. Webserver so konfigurieren, dass das Document Root auf `public/` zeigt.

5. Admin-Passwort über die Konfigurationsseite setzen oder manuell:
   ```bash
   php -r "echo password_hash('DEINPASSWORT', PASSWORD_DEFAULT);" > config/.admin_pass
   chmod 600 config/.admin_pass
   chown www-data:www-data config/.admin_pass
   ```

6. sudoers konfigurieren (siehe unten).

### sudoers-Konfiguration

`www-data` benötigt eingeschränkte sudo-Rechte für Service-Steuerung und ts6ctl.
Erstelle `/etc/sudoers.d/ts6admin`:

```bash
visudo -f /etc/sudoers.d/ts6admin
```

Mit folgendem Inhalt:

```
# ts6admin WebUI — erlaubt www-data Service-Steuerung und ts6ctl
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start teamspeak6
www-data ALL=(ALL) NOPASSWD: /bin/systemctl stop teamspeak6
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart teamspeak6
www-data ALL=(ALL) NOPASSWD: /bin/systemctl status teamspeak6
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ts6ctl check-update
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ts6ctl update
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ts6ctl status
```

### Verzeichnisstruktur
```
TS6-Admin-Panel/
├── config/                  # Sensitive Konfiguration (nicht in Git)
│   ├── config.example.php   # Vorlage für config.php
│   ├── ts6ctl.conf.example  # Vorlage für ts6ctl.conf
│   ├── config.php           # ← aus Vorlage erstellen
│   ├── ts6ctl.conf          # ← aus Vorlage erstellen
│   └── .admin_pass          # ← über Config-Seite oder manuell setzen
├── includes/                # PHP-Hilfsfunktionen, Auth, Header, Footer
├── lang/                    # Sprachdateien (de, en)
└── public/                  # Web-Root (nginx/Apache Document Root)
    ├── api/                 # AJAX-Endpunkte
    ├── assets/              # CSS, JS
    ├── pages/               # Seitenvorlagen
    ├── index.php
    ├── login.php
    └── logout.php
```

### Bekannte Einschränkungen (TS6 Beta)
- Icons werden im TS6-Client noch nicht angezeigt (Beta-Einschränkung)
- Drag & Drop Channel-Reihenfolge (channelmove API fehlerhaft in beta8)
- Poke-Reply (TS6-Beta-Bug)
- TS6 generiert neue UIDs — keine automatische TS3→TS6 Avatar-Zuordnung
```

### Lizenz
MIT — siehe [LICENSE](LICENSE)
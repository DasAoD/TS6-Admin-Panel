# TS6 Admin Panel

**[English](#english) | [Deutsch](#deutsch)**

---

## English

### Overview
TS6 Admin Panel is a web-based administration interface for TeamSpeak 6 servers, including a CLI management script (`ts6ctl`). It provides a live dashboard, channel and client management, group permissions, ban management, and TS3→TS6 migration tools.

### Features
- 📊 Live dashboard with server stats and context menu (right-click on clients)
- 🗂️ Channel tree management
- 👥 Client and database client management
- 🔐 Server group & permission management
- 🚫 Ban management
- 🔑 Privilege token management
- 🔄 TS3 → TS6 migration assistant
- ⚙️ Web-based configuration (API key, password, mail settings)
- 🖥️ ts6ctl integration (update, install, service control)
- 📝 Log viewer
- 🌐 Multilingual (de/en)

### Requirements
- PHP 8.4 (never 8.3!)
- nginx or Apache
- TeamSpeak 6 Server (tested with v6.0.0-beta8)
- `ts6ctl` management script
- `sudo` access for `www-data` (see `docs/sudoers.md`)

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

5. Set an admin password via the configuration page or:
   ```bash
   php -r "echo password_hash('YOURPASSWORD', PASSWORD_DEFAULT);" > config/.admin_pass
   chmod 600 config/.admin_pass
   chown www-data:www-data config/.admin_pass
   ```

### Directory Structure
```
ts6admin/
├── config/                  # Sensitive config (not in Git)
│   ├── config.example.php   # Template for config.php
│   ├── ts6ctl.conf.example  # Template for ts6ctl.conf
│   ├── config.php           # ← create from example
│   ├── ts6ctl.conf          # ← create from example
│   └── .admin_pass          # ← generated on first login
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
TS6 Admin Panel ist eine webbasierte Administrationsoberfläche für TeamSpeak 6 Server, inklusive eines CLI-Management-Skripts (`ts6ctl`). Es bietet ein Live-Dashboard, Channel- und Client-Verwaltung, Gruppenberechtigungen, Ban-Verwaltung und ein TS3→TS6 Migrations-Tool.

### Funktionen
- 📊 Live-Dashboard mit Serverstatistiken und Kontextmenü (Rechtsklick auf Clients)
- 🗂️ Channel-Baum-Verwaltung
- 👥 Client- und Datenbankclients-Verwaltung
- 🔐 Servergruppen- & Berechtigungsverwaltung
- 🚫 Ban-Verwaltung
- 🔑 Privilege-Token-Verwaltung
- 🔄 TS3 → TS6 Migrationsassistent
- ⚙️ Webbasierte Konfiguration (API-Key, Passwort, Mail-Einstellungen)
- 🖥️ ts6ctl-Integration (Update, Installation, Dienstverwaltung)
- 📝 Log-Viewer
- 🌐 Mehrsprachig (de/en)

### Voraussetzungen
- PHP 8.4 (niemals 8.3!)
- nginx oder Apache
- TeamSpeak 6 Server (getestet mit v6.0.0-beta8)
- `ts6ctl` Management-Skript
- `sudo`-Berechtigung für `www-data` (siehe `docs/sudoers.md`)

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

### Verzeichnisstruktur
```
ts6admin/
├── config/                  # Sensitive Konfiguration (nicht in Git)
│   ├── config.example.php   # Vorlage für config.php
│   ├── ts6ctl.conf.example  # Vorlage für ts6ctl.conf
│   ├── config.php           # ← aus Vorlage erstellen
│   ├── ts6ctl.conf          # ← aus Vorlage erstellen
│   └── .admin_pass          # ← wird beim ersten Login generiert
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

### Lizenz
MIT — siehe [LICENSE](LICENSE)
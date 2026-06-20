# TS6 Admin Panel

> **📌 Mirror-Hinweis:** Dieses Repository ist ein automatischer Spiegel.
> Die primäre Entwicklung findet auf **[git.uliana.de/DasAoD/REPONAME](https://git.uliana.de/DasAoD/REPONAME)** statt.
> Issues und Pull Requests bitte dort öffnen.

**[English](#english) | [Deutsch](#deutsch)**

---

## English

### Overview
TS6 Admin Panel is a web-based administration interface for TeamSpeak 6 servers.
It provides a live dashboard, channel and client management, group permissions, ban management, and a TS3→TS6 migration assistant.

Requires [ts6ctl](https://github.com/DasAoD/ts6-installer) - a CLI management script.

### Features
- 📊 Live dashboard with server stats
- 🗂️ Channel tree management
- 👥 Client management
- 🔄 TS3 → TS6 migration assistant
- 🖥️ ts6ctl integration
- 🌐 Multilingual (de/en)

### Requirements
- VPS or dedicated server (Debian/Ubuntu recommended)
- PHP 8.4 (never 8.3!)
- nginx or Apache with document root pointing to `public/`
- TeamSpeak 6 Server
- [ts6ctl](https://github.com/DasAoD/ts6-installer)

---

## Mitwirkende / Contributors

Dieses Projekt wurde in Zusammenarbeit mit [Claude](https://claude.ai) (Sonnet 4.6) von [Anthropic](https://anthropic.com) entwickelt.  
Der überwiegende Teil des Codes, der Architektur und der Dokumentation wurde durch KI generiert und iterativ verfeinert.

| Rolle / Role | Person / Tool |
|---|---|
| Projektidee & Anforderungen / Project idea & requirements | [DasAoD](https://git.uliana.de/DasAoD) |
| Code, Architektur, Dokumentation / Code, architecture, documentation | [Claude](https://git.uliana.de/Claude) (Anthropic) |

---

## License

[MIT](LICENSE)
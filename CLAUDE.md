# TS6-Admin-Panel – Projektkontext für Claude Code

Web-basiertes Administrations-Interface für TeamSpeak-6-Server: Dashboard, Channel-/Client-Verwaltung, Gruppenrechte, Ban-Management und TS3→TS6-Migrationsassistent.

## Tech-Stack
- PHP **8.4** (ausdrücklich **nicht 8.3** – laut README explizit inkompatibel)
- nginx oder Apache, Document Root zeigt auf `public/`
- Mehrsprachig (de/en)
- Benötigt zwingend [ts6ctl](https://git.uliana.de/DasAoD/ts6-installer) als CLI-Management-Script im Hintergrund

## Struktur
- `config/` – Konfiguration
- `includes/` – Shared PHP-Funktionen
- `lang/` – Übersetzungen (de/en)
- `public/` – Document Root
  - `api/` – API-Endpunkte
  - `assets/` – CSS/JS
  - `pages/` – Seiten/Views

## Features
- Live-Dashboard mit Serverstatistiken
- Channel-Tree-Verwaltung
- Client-Verwaltung
- Gruppenrechte-Verwaltung
- Ban-Management
- TS3 → TS6 Migrationsassistent
- ts6ctl-Integration

## Voraussetzungen (Deployment-Zielsystem)
- VPS/dedizierter Server (Debian/Ubuntu empfohlen)
- PHP 8.4 (nicht 8.3!)
- nginx.oder Apache
- TeamSpeak 6 Server
- ts6ctl installiert (siehe ts6-installer-Repo)

## Zusammenspiel mit ts6-installer
Dieses Panel ist die Web-UI, `ts6-installer` liefert das CLI-Tool `ts6ctl`, auf das das Panel für Server-Aktionen zurückgreift. Änderungen an einer Schnittstelle (z.B. ts6ctl-Befehlsausgabe) können das jeweils andere Repo betreffen – bei größeren Änderungen an einem der beiden kurz gegenchecken, ob das andere Repo davon betroffen ist.

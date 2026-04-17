# StudySpot

StudySpot ist eine PHP- und MySQL-Webanwendung fuer Studierende und Lernort-Betreiber. Nutzer koennen Lernorte suchen, filtern und bewerten. Owner koennen neue Orte einreichen, und Admins pruefen diese Anfragen im Backend.

## Features

- Startseite mit Suchbereich und dynamischen Statistiken
- Spot-Liste mit Filtern fuer Typ, WLAN, Steckdosen, Gruppenfreundlichkeit und Lautstaerke
- Detailseite mit Bewertungen
- Registrierung, Login und Account-Bereich
- Owner-Bereich fuer Lernort-Einreichungen
- Admin-Bereich fuer Kontakt-Nachrichten und Spot-Anfragen

## Tech Stack

- PHP
- MySQL / MariaDB
- Bootstrap 5
- Plain CSS

## Projektstruktur

- `index.php`: Startseite
- `spots.php`: Spot-Liste
- `spot.php`: Detailansicht eines Lernorts
- `ort_anmelden.php`: Formular fuer Owner
- `owner_home.php`: Bereich fuer Owner und Admin
- `admin_requests.php`: Admin-Freigaben fuer neue Orte
- `admin_contacts.php`: Admin-Inbox fuer Kontakt-Nachrichten
- `database/studyspot.sql`: Datenbank-Schema

## Lokal starten mit XAMPP

1. XAMPP starten.
2. `Apache` und `MySQL` aktivieren.
3. Dieses Projekt nach `C:\xampp\htdocs\Web` legen.
4. In `phpMyAdmin` eine Datenbank anlegen und `database/studyspot.sql` importieren.
5. Optional: `db.local.example.php` zu `db.local.php` kopieren und Zugangsdaten anpassen.
6. Im Browser aufrufen: `http://localhost/Web/index.php`

## Datenbank-Konfiguration

Die App liest Datenbankdaten in dieser Reihenfolge:

1. `db.local.php`
2. Umgebungsvariablen wie `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`
3. Fallback auf lokale XAMPP-Defaults

Beispiel fuer `db.local.php`:

```php
<?php

return [
    "DB_HOST" => "localhost",
    "DB_PORT" => "3306",
    "DB_USER" => "root",
    "DB_PASS" => "",
    "DB_NAME" => "studyspot",
];
```

## Auf GitHub hochladen

GitHub ist fuer das Repository perfekt geeignet, aber nicht fuer das Ausfuehren dieser App als Live-Seite. GitHub Pages unterstuetzt kein PHP und keine MySQL-Datenbank.

Du kannst das Projekt trotzdem sauber auf GitHub als Portfolio- oder Uni-Projekt hochladen:

1. Neues Repository auf GitHub erstellen, z. B. `studyspot`
2. Lokal im Projektordner Git initialisieren
3. Dateien committen
4. Remote hinzufuegen
5. Auf `main` pushen

Beispiel:

```bash
git init -b main
git add .
git commit -m "Initial StudySpot project"
git remote add origin https://github.com/DEIN-NAME/studyspot.git
git push -u origin main
```

## Live Deployment

Wenn du das Projekt online laufen lassen willst, brauchst du einen PHP-Host mit Datenbank, zum Beispiel:

- Render
- Railway
- InfinityFree
- 000webhost
- klassisches Webhosting mit PHP/MySQL

## Hinweis

Die Verzeichnisse `uploads/requests` und `uploads/spots` bleiben im Repository erhalten, aber hochgeladene Dateien selbst werden per `.gitignore` ausgeschlossen.

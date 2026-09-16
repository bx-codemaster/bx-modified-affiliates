# Bewertung: `bx-modified-affiliates` Modul

**Modul:** BX Modified Affiliates  
**Version:** 1.0.0  
**Entwickler:** Axel Benkert / benax  
**Kompatibilität:** modified eCommerce 3.1.0 – 3.3.0 · PHP ^7.4 || ^8.0  
**Preis:** 199,95 €  
**Bewertungsdatum:** 2026-08-24 (aktualisiert nach Review-Zyklus 2)

---

## Gesamtbewertung

| Kategorie                   | Bewertung              |
|-----------------------------|------------------------|
| Modulstruktur / Integration | ⭐⭐⭐⭐⭐           |
| Datenbankdesign             | ⭐⭐⭐⭐⭐           |
| Code-Qualität (PHP)         | ⭐⭐⭐⭐☆            |
| Sicherheit                  | ⭐⭐⭐⭐☆            |
| JavaScript / UX             | ⭐⭐⭐⭐☆            |
| Mehrsprachigkeit            | ⭐⭐⭐⭐☆            |
| Dokumentation               | ⭐⭐⭐⭐⭐           |
| **Gesamt**                  | **⭐⭐⭐⭐½ (4,4/5)** |

---

## 1. Modulstruktur & Integration (⭐⭐⭐⭐⭐ Sehr gut)

Das Modul folgt vorbildlich den Konventionen des modified-ModuleLoaderClient:

```
src/
├── admin/
│   ├── bx_modified_affiliates.php          # Haupt-Controller (Admin-Seite)
│   └── includes/
│       ├── extra/css/                       # Modulspezifisches CSS (page-guard)
│       ├── extra/filenames/                 # Dateinamen-Konstante
│       ├── extra/functions/                 # Hilfsfunktionen
│       ├── extra/javascript/               # AJAX-Handler
│       └── extra/menu/                     # Menü-Eintrag
│       └── modules/system/                 # Installations-/Deinstallationsklasse
├── includes/extra/database_tables/         # DB-Tabellen-Konstanten
└── lang/
    ├── german/  (extra/admin + modules/system)
    └── english/ (vorhanden)
```

✅ Alle Extra-Hooks korrekt platziert  
✅ Keine Core-Dateien überschrieben  
✅ Klares Namespacing (`bx_affiliate_*`, `BX_AFFILIATE_*`, `BX_FILENAME_*`)  
✅ `_VALID_XTC`-Guards in allen Include-Dateien  

---

## 2. Datenbankdesign (⭐⭐⭐⭐⭐ Sehr gut)

8 klar strukturierte Tabellen mit durchdachtem Schema:

| Tabelle                                | Zweck                                                 |
|----------------------------------------|-------------------------------------------------------|
| `bx_affiliate_partner`                 | Stammdaten (inkl. **Nested-Set** für Tier-Hierarchie) |
| `bx_affiliate_banners`                 | Werbemittel-Verwaltung                                |
| `bx_affiliate_banners_history`         | Tagesgenaue Impression/Klick-Statistik                |
| `bx_affiliate_clickthroughs`           | Klick-Tracking                                        |
| `bx_affiliate_payment`                 | Auszahlungen (mit Adress-**Snapshot**)                |
| `bx_affiliate_payment_status`          | Mehrsprachige Status-Bezeichnungen                    |
| `bx_affiliate_payment_status_history`  | Audit-Trail der Status-Änderungen                     |
| `bx_affiliate_sales`                   | Provisionen je Bestellung (inkl. Tier-Level)          |

**Besonders positiv:**
- Nested-Set für Affiliate-Hierarchie (Tiers) ist ein professioneller Ansatz
- Adress-Snapshot in `bx_affiliate_payment` sichert historische Korrektheit
- Vollständige Kommentare auf jeder Spalte
- Komposite Indizes für Performance-kritische Queries
- `utf8mb4` + `unicode_ci` als Standard
- Dynamische Mehrsprachigkeit der Status-Labels bei Installation (24 Sprachen!)

---

## 3. Code-Qualität PHP (⭐⭐⭐⭐☆ Gut)

### Positives

✅ **PHP 7.4+ Typen** konsequent verwendet (`string`, `bool`, `int`, `array`, `: void`, `: string`)  
✅ **Null-Coalescing** (`??`) korrekt eingesetzt  
✅ **`(int)` Casts** vor DB-IDs in den meisten Stellen  
✅ `splitPageResults` Pagination korrekt integriert  
✅ Sinnvolle Vorab-Aggregation: Alle Seiten-Zeilen werden erst geladen, dann `acID_found` ermittelt – verhindert Darstellungsfehler bei Suche/Sortierung  

### Verbesserungswürdig

⚠️ **N+1 Query-Problem** in der Haupt-Schleife (Zeile 319–325):  
Für jede Zeile wird eine separate `SELECT SUM(...)` Query auf `bx_affiliate_sales` ausgeführt, um die offene Provision zu berechnen. Bei z.B. 10 Affiliates pro Seite = 11+ Queries. Besser wäre ein `LEFT JOIN`/Subquery in der Hauptabfrage.

```php
// Aktuell: Pro Zeile eine Extra-Query (N+1)
$acnt_value = xtc_db_query("SELECT sum(a.bx_affiliate_payment) AS amount 
                             FROM " . TABLE_BX_AFFILIATE_SALES . " a, 
                                  " . TABLE_ORDERS . " o 
                             WHERE ... AND a.bx_affiliate_id = '" . $affiliate['bx_affiliate_id'] . "'");
```

⚠️ **Doppelte Aggregation** (Zeile 332–357): Für die ausgewählte Zeile werden nochmals 3 Queries ausgeführt (`info_query`, `country_query`, `sales_summary_query`), die bereits in der Hauptliste teilweise vorhanden sind. Beim AJAX-Handler (Zeile 35–69) passiert dies ebenfalls.

⚠️ **Redundante `$affiliate_sort` Zeile 161**: 
```php
$affiliate_sort .= ', ap.bx_affiliate_id DESC';
```
Wenn z. B. `$affiliate_sort` bereits `ap.bx_affiliate_id ASC` enthält, entsteht ein `ORDER BY ap.bx_affiliate_id ASC, ap.bx_affiliate_id DESC` – was widersprüchlich, aber nicht fehlerhaft ist.

⚠️ **`bx_affiliate_build_orders_status_query()`** (functions-Datei) hat einen logischen Fehler bei nicht-numerischen Status-Einträgen: Die Rückwärts-Kürzung via `substr($where, 0, -4)` entfernt das zuvor hinzugefügte `' OR '` wieder, was bei ungültigen Werten im letzten Element die Klammer `)` verschwinden lassen kann. Die `$sizeof`-Variable wird zudem doppelt dekrementiert (einmal vor der Schleife implizit, einmal explizit am Ende).

---

## 4. Sicherheit (⭐⭐⭐⭐☆ Gut)

### Positives

✅ **CSRF-Schutz vollständig** durch `application_top.php` (am Seitenanfang inkludiert): modified übernimmt die Token-Validierung global für alle Admin-Seiten – kein separater Check im Modul erforderlich  
✅ Der AJAX-Handler gibt aktualisierte CSRF-Tokens in der JSON-Response zurück, und der Client aktualisiert sie korrekt  
✅ `_VALID_XTC` Direct-Access-Guards in allen Include-Dateien  
✅ `xtc_db_input()` für Suchbegriffe verwendet  

### Verbesserungswürdig

🟡 **Lesbarkeit bei `$acID`-Queries:**  
`$acID` wird auf Zeile 27 einmalig mit `(int)` gecastet und ist damit sicher. Im Query-Code wird die Variable jedoch ohne erneuten Cast verwendet:

```php
WHERE ap.bx_affiliate_id = '" . $acID . "'
```
Funktionell korrekt, aber bei Refactoring riskant. **Empfehlung:** `(int)$acID` direkt im Query schreiben, um die Typsicherheit am Verwendungsort zu dokumentieren.

~~🟡 **Ausgabe-Escaping bei URLs:**~~  
~~Die Homepage-URL wird ohne HTML-Escaping ausgegeben.~~  
✅ **Korrektur (Review-Zyklus 2):** Der ursprüngliche Hinweis war falsch. Die URL läuft über `bx_affiliate_normalize_url()` und wird anschließend korrekt durch `xtc_output_string()` ausgegeben (Zeile 384). Kein Handlungsbedarf.

---

## 5. JavaScript / UX (⭐⭐⭐⭐☆ Gut)

✅ **Cleveres Click-to-AJAX-Pattern**: Erste Klick auf Zeile = Detailansicht via AJAX, zweiter Klick = Navigation zur Bearbeitung  
✅ **`history.replaceState()`** für Deep-Linking ohne Seitenreload  
✅ CSRF-Token-Aktualisierung nach AJAX-Response  
✅ Page-Guard: JavaScript wird nur auf der korrekten Admin-Seite ausgegeben  
✅ Fehlerbehandlung im `error`-Callback  

⚠️ **jQuery-Abhängigkeit** implizit (über `$(document)`, `$.ajax`). Keine Prüfung ob jQuery verfügbar – aber für modified-Admin üblich.  
✅ Auskommentierter Code (Zeile 205–218) jetzt mit `// TODO: Was ich in Version 2.0 mal brauchen könnte` dokumentiert.  

---

## 6. CSS (⭐⭐⭐⭐☆ Gut)

✅ Page-Guard verhindert globale Konflikte  
✅ Konsistentes Branding (Akzentfarbe `#af417e`)  
✅ Responsive Flexbox/Grid-Einsatz  

⚠️ Redundante `border-left`-Deklarationen (Zeilen 28–31 in der CSS-Datei):
```css
border-left: 3px solid #af417e;   /* ← überschreibt die drei Zeilen darüber */
```
Die expliziten `border-left-width/style/color`-Zeilen 28–30 sind überflüssig.

---

## 7. Mehrsprachigkeit (⭐⭐⭐⭐☆ Gut)

✅ Deutsch + Englisch vorhanden  
✅ Alle UI-Strings als Konstanten ausgelagert  
✅ Systemmodul-Beschreibungen vollständig übersetzt  

⚠️ Im Menü (menu-Datei) hat der `switch`-Block für DE und Default **identischen Inhalt** – die DE-Unterscheidung hat keinen Effekt:
```php
case 'de':
  define('MODULE_MODIFIED_AFFILIATES_MENU_TITLE','BX Affiliates');
  break;
default:
  define('MODULE_MODIFIED_AFFILIATES_MENU_TITLE','BX Affiliates');  // gleich!
```

⚠️ **Hardkodierte E-Mail-Adresse** `info@online-power.de` als Standardwert in `install()` (Zeile 113) – sollte ein Platzhalter wie `your@email.de` sein.

---

## 8. Dokumentation (⭐⭐⭐⭐⭐ Sehr gut)

✅ PHPDoc-Header in allen Dateien  
✅ Inline-Kommentare an komplexen Stellen  
✅ DB-Spalten vollständig kommentiert  
✅ Übersichtliche ASCII-Box im Menu-Header  
✅ `moduleinfo.json` vollständig ausgefüllt  

---

## Zusammenfassung der Verbesserungsvorschläge

### ✅ Erledigt (Review-Zyklus 2)

| Priorität | Problem | Status |
|---|---|---|
| 🟢 Niedrig | `INTEGER(1)` in ALTER TABLE | ✅ Geändert zu `TINYINT(1)` |
| 🟢 Niedrig | Auskommentierter UI-Code ohne Kontext | ✅ Mit `TODO v2.0`-Kommentar versehen |
| ~~🟡 Mittel~~ | ~~URL-Ausgabe nicht HTML-escaped~~ | ✅ War falsch bewertet – `xtc_output_string()` wird bereits korrekt angewendet |

### 🕐 Noch offen

| Priorität | Problem | Empfehlung |
|---|---|---|
| 🔴 Hoch | N+1 Query-Problem (offene Provision per Zeile) | Subquery/LEFT JOIN in Hauptabfrage integrieren |
| 🟡 Mittel | Hardkodierte E-Mail `info@online-power.de` in `install()` | Neutralen Platzhalter (`info@example.com`) verwenden |
| 🟡 Mittel | `bx_affiliate_build_orders_status_query()` Logikfehler | Funktion mit `array_filter` + `implode` neu implementieren |
| 🟢 Niedrig | Redundante `border-left`-CSS-Deklarationen (Zeilen 28–30) | Drei überflüssige Zeilen entfernen |
| 🟢 Niedrig | Doppelter Menü-Switch (DE = Default, kein Effekt) | `switch` entfernen oder unterschiedliche Bezeichnungen vergeben |

---

> **Fazit (nach Review-Zyklus 2):** Die kleineren Kritikpunkte wurden konsequent umgesetzt – `TINYINT`, TODO-Dokumentation, keine falschen Flags mehr. Das Modul bleibt auf sehr hohem Niveau. Der einzig wirklich relevante offene Punkt ist das N+1 Query-Problem, das bei größeren Affiliate-Listen spürbare Performance-Auswirkungen hat. Alle anderen offenen Punkte sind Detailverbesserungen ohne funktionalen Impact.

# Affiliate-Modul für Modified eCommerce — Anforderungs- & Datenmodell-Übersicht

**Basis:** Analyse des Alt-Moduls „affiliate-Modified_1.06_SP4" (Stand ~2010/2013, xtc5-Template-Ära, Vorläufer von Gambio-Affiliate)
**Zweck:** Fachliche Blaupause für eine Neuentwicklung nach aktuellem Modified-Standard (MMLC-Modul, Hook-Points statt Core-Patches, PHP 8.x-kompatibel)

---

## 1. Architektur-Entscheidung (Kurzfassung)

| | Altes Modul (2010) | Neues Modul (Ziel) |
|---|---|---|
| Einbindung | Manuelles Patchen von 12 Core-Dateien | Hook-Points / Auto-Include-System, MMLC-Paket |
| Code-Stil | Prozedural, eigene Doppelgänger-Klassen | Modernes PHP 8.x, Vendor-Prefix-Konvention |
| Templates | Smarty `templates/xtc5/module/*.html` | Aktuelles Standard-Template-Set |
| Updatesicherheit | Nein (Core wird verändert) | Ja (Kernprinzip des Hook-Systems) |

→ Empfehlung bleibt: **Neuentwicklung**, altes Modul dient nur als fachliche Referenz für Datenmodell und Funktionsumfang.

---

## 2. Datenmodell (aus dem Alt-Modul abgeleitet)

### 2.1 `affiliate` — Partnerstammdaten
Kern-Entität für jeden Affiliate/Partner.

**Wichtige Felder:**
- Persönliche Daten: Anrede, Vor-/Nachname, Geburtsdatum, E-Mail, Telefon, Fax, Passwort, Homepage
- Adresse: Straße, Ort, PLZ, Bundesland/Region, Land, Zone
- Firma: Firmenname, USt-ID
- Provision: `commission_percent` (individueller Prozentsatz), `tiers_allowed` (Flag für Mehrstufigkeit)
- Zahlungsdaten: Scheck, PayPal, Bankverbindung (Name, BLZ/Filiale, SWIFT/BIC, Kontoinhaber, IBAN/Kontonummer)
- Meta: letzter Login, Anzahl Logins, Erstellungs-/Änderungsdatum
- AGB-Zustimmung (Flag)

**Besonderheit — Nested-Set-Baum (`lft`, `rgt`, `root`):**
Das Alt-Modul bildet die **mehrstufige Partnerhierarchie** (Tier-System: Sub-Affiliates werben Sub-Sub-Affiliates) als Nested-Set-Baum ab, nicht als einfache `parent_id`. Das ermöglicht effiziente "alle Vorfahren eines Knotens"-Abfragen (siehe Tier-Provisionsberechnung unten), ist aber komplex zu pflegen (jede Einfügung/Löschung erfordert Neuberechnung von lft/rgt im gesamten Teilbaum).

**Modernisierungs-Empfehlung:** Für ein neues Modul eher ein einfaches `parent_affiliate_id` + rekursive Query (CTE, ab MySQL 8 / MariaDB 10.2 verfügbar) statt Nested Set — deutlich wartungsfreundlicher, moderne DB-Versionen unterstützen `WITH RECURSIVE`.

### 2.2 `affiliate_clickthroughs` — Klick-Tracking
Jeder Klick auf einen Affiliate-Link wird protokolliert:
- Referenz-Affiliate-ID, Zeitstempel, Browser (User-Agent), IP-Adresse, Referrer-URL
- Optional: verlinktes Produkt, verlinktes Banner

**Trigger:** `?ref=<affiliate_id>` als URL-Parameter → Session + Cookie-Setzung (Cookie-Lebensdauer konfigurierbar, `BX_AFFILIATE_COOKIE_LIFETIME`).

**Modernisierungs-Hinweis:** IP-Adressen und User-Agent-Speicherung sind heute DSGVO-relevant — braucht eine klare Rechtsgrundlage/Löschfrist, ggf. IP-Kürzung/Hashing einplanen.

### 2.3 `affiliate_banners` + `affiliate_banners_history` — Werbemittel
- `banners`: Titel, verknüpftes Produkt, Bild, Banner-Gruppe (Größe/Format), HTML-Text (für Text-Links), Ablaufdatum/Impressions-Limit, Status, Link-Typ (Produkt/Homepage/Kategorie)
- `banners_history`: Tagesgenaue Statistik pro Banner × Affiliate (Impressions, Klicks) — Basis für Performance-Auswertung im Admin

### 2.4 `affiliate_sales` — Verkäufe & Provisionen
Verknüpft eine Bestellung mit dem/den zugehörigen Affiliate(s):
- Bestellwert (netto), errechnete Provision, angewandter Prozentsatz
- Zahlungsstatus (offen/ausgezahlt), Referenz auf Zahlungsdatensatz
- `affiliate_level`: 0 = direkter Affiliate, 1+ = Tier-Ebene (Sub-Affiliate-Provisionen)
- `affiliate_salesman`: der ursprünglich vermittelnde Affiliate (bei Tier-Ketten)

**Kernlogik (aus `checkout_process.php`):**
1. Nettosumme der Bestellung berechnen
2. Individuellen Provisionssatz prüfen (falls aktiviert), sonst Standardsatz
3. Sales-Datensatz für den direkten Affiliate anlegen
4. Falls Tier-System aktiv: alle Vorfahren im Baum ermitteln (Nested-Set-Query) und je Ebene einen eigenen Sales-Datensatz mit gestaffeltem Prozentsatz (`BX_AFFILIATE_TIER_PERCENTAGE`, z.B. `8.00;5.00;1.00`) anlegen

### 2.5 `affiliate_payment` + Status-Tabellen — Auszahlungen
- `payment`: Sammelt offene Provisionen zu einer Auszahlung, inkl. Steueranteil, Gesamtbetrag, Zahlungsadresse (Snapshot zum Auszahlungszeitpunkt!)
- `payment_status` / `payment_status_history`: mehrsprachiger Status (Pending/Ausgezahlt/…) mit Änderungshistorie und Benachrichtigungs-Flag

**Wichtiges Detail:** Die Zahlungsadresse wird beim Auszahlungsvorgang **kopiert** (nicht referenziert), damit spätere Adressänderungen des Affiliates alte Auszahlungsbelege nicht verfälschen. Dieses Prinzip sollte im neuen Modul übernommen werden.

---

## 3. Kern-Workflows

### 3.1 Tracking-Flow
```
Besucher klickt Affiliate-Link (?ref=123)
  → Clickthrough-Datensatz anlegen
  → Session + Cookie setzen (Lifetime konfigurierbar)
  → Bei Banner-Klick: Banner-Statistik hochzählen
  → Cookie überlebt bis zum nächsten Besuch → Session wird aus Cookie wiederhergestellt
```

### 3.2 Provisions-Flow
```
Bestellung abgeschlossen (checkout_process)
  → Nettosumme berechnen
  → Provisionssatz ermitteln (individuell > Standard)
  → Sales-Datensatz für direkten Affiliate
  → [wenn Tiers aktiv] für jede übergeordnete Ebene im Partnerbaum
      einen weiteren Sales-Datensatz mit gestaffeltem Satz anlegen
```

### 3.3 Auszahlungs-Flow
```
Admin wählt offene Sales-Datensätze eines Affiliates
  → Payment-Datensatz mit Adress-Snapshot anlegen
  → Sales-Datensätze auf "ausgezahlt" + Payment-ID verknüpfen
  → Status-Historie protokollieren, Benachrichtigung an Affiliate
```

---

## 4. Konfigurierbare Parameter (aus `configuration`-Tabelle)

| Key | Bedeutung | Beispielwert |
|---|---|---|
| `BX_AFFILIATE_EMAIL_ADDRESS` | Kontakt-E-Mail für Partnerprogramm | — |
| `BX_AFFILIATE_PERCENT` | Standard-Provisionssatz | 10.0 % |
| `BX_AFFILIATE_THRESHOLD` | Mindestbetrag für Auszahlung | 50.00 |
| `BX_AFFILIATE_COOKIE_LIFETIME` | Tracking-Cookie-Lebensdauer (Sek.) | 7200 |
| `BX_AFFILIATE_BILLING_TIME` | Wartezeit bis Provision abrechenbar (Tage, z.B. wegen Widerrufsfrist) | 30 |
| `BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS` | Mindest-Bestellstatus für Provisionsanspruch | 3 |
| `BX_AFFILIATE_USE_CHECK` / `_USE_PAYPAL` / `_USE_BANK` | Aktivierte Auszahlungsarten | true/false |
| `BX_AFFILIATE_INDIVIDUAL_PERCENTAGE` | Individuelle Sätze je Affiliate erlaubt | true/false |
| `BX_AFFILIATE_USE_TIER` / `_TIER_ALLOWED` | Mehrstufiges Partnerprogramm aktiv | true/false |
| `BX_AFFILIATE_TIER_LEVELS` | Anzahl Tier-Ebenen | 3 |
| `BX_AFFILIATE_TIER_PERCENTAGE` | Provisionssätze je Ebene (Liste) | 8.00;5.00;1.00 |

---

## 5. Funktionsumfang — Checkliste für die Neuentwicklung

### Frontend (Affiliate-Bereich)
- [ ] Registrierung (Signup) inkl. AGB-Zustimmung
- [ ] Login / Logout / Passwort-vergessen
- [ ] Account-Verwaltung (Stammdaten, Zahlungsdaten ändern)
- [ ] Dashboard/Summary (Übersicht Klicks, Sales, offene Provision)
- [ ] Klick-Statistik-Ansicht
- [ ] Sales-/Provisions-Übersicht (mit Status)
- [ ] Auszahlungs-/Zahlungshistorie
- [ ] Banner-/Werbemittel-Center (HTML-Code zum Einbinden, ggf. Text-Links)
- [ ] Kontaktformular an Shop-Betreiber
- [ ] Hilfe-/FAQ-Seite (Anbindung Content-Manager)
- [ ] Box/Widget "Partnerprogramm" für die Shop-Startseite

### Backend (Admin)
- [ ] Partnerverwaltung (Liste, Details, Sperren/Freischalten, individuelle Provisionssätze)
- [ ] Sales-Übersicht mit Filter- und Exportmöglichkeit
- [ ] Klick-Statistiken
- [ ] Banner-Verwaltung (Anlegen, Bild-Upload, Ablaufregeln)
- [ ] Auszahlungsverwaltung (offene Provisionen sammeln, Payment anlegen, Status pflegen)
- [ ] Gesamtstatistik/Reporting
- [ ] Rechte-/Zugriffsverwaltung je Admin-Unterbereich (analog `admin_access`-Erweiterung im Alt-Modul)
- [ ] Konfigurationsseite für alle Parameter aus Abschnitt 4

### Technisch / Integration
- [ ] Hook in Checkout-Prozess (Provisionsberechnung nach Bestellabschluss)
- [ ] Hook in Session-Start (Referrer-Tracking, Cookie-Handling)
- [ ] E-Mail-Templates (Signup-Bestätigung, Passwort-Reset, Auszahlungsbenachrichtigung, Kontaktanfrage) — DE/EN
- [ ] Anbindung an Content-Manager für AGB/Info/FAQ-Texte
- [ ] Mehrsprachigkeit (mind. DE/EN, wie im Alt-Modul)

---

## 6. Empfohlene Anpassungen gegenüber dem Alt-Modul

1. **Rekursive Query statt Nested Set** für die Partnerhierarchie (siehe 2.1) — einfacher zu warten, moderne DB-Engines unterstützen das nativ.
2. **DSGVO-konformes Tracking**: IP-Speicherung minimieren/anonymisieren, Cookie-Consent-Anbindung, klare Lösch-/Aufbewahrungsfristen für Clickthrough-Daten definieren.
3. **Prepared Statements / DB-Abstraktion** statt String-Concat-SQL (im Alt-Modul durchgängig unsicher, klassisches SQL-Injection-Risiko).
4. **Passwort-Hashing** nach aktuellem Standard (`password_hash()`/bcrypt/argon2) statt alter MD5/eigener Hash-Funktion.
5. **Hook-Point-Integration** statt Core-Patches, siehe Abschnitt 1 — nutzt den MMLC-Standardweg.
6. **API/Webhook-fähig** denken, falls Provisions-Reporting an externe Tools (z.B. Buchhaltung) angebunden werden soll — im Alt-Modul nicht vorgesehen.

---

## 7. Offene fachliche Entscheidungen (bitte vorab klären)

- Soll das mehrstufige Tier-System (Sub-Affiliates) überhaupt benötigt werden, oder reicht ein einstufiges Partnerprogramm? (Reduziert Komplexität erheblich, falls nicht benötigt.)
- Welche Auszahlungswege sollen unterstützt werden (Scheck ist heute unüblich — vermutlich nur noch Bank/PayPal/ggf. Gutschrift)?
- Soll die Provisionsberechnung auf Netto- oder Bruttobasis erfolgen, und wie werden Stornos/Retouren behandelt (im Alt-Modul nicht abgebildet)?
- Reicht Cookie-basiertes Tracking oder wird ein persistenteres/serverseitiges Tracking gewünscht (Cookie-Lifetime-Problematik mit modernen Browsern/ITP)?

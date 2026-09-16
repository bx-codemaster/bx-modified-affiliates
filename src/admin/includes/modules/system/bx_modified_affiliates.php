<?php
/**
* BX Modified Affiliates System Module - Installation & Configuration
* 
* System module for installation and management of the BX Modified Affiliates module in modified eCommerce.
* Provides automated installation, configuration management, and database schema creation for the complete affiliate management system.
*
* @package    BX Modified Affiliates
* @subpackage System Module
* @version    1.0.0
* @author     benax
* @copyright  2006-2026 Various Contributors
* @license    GNU General Public License v2.0
* @link       https://www.modified-shop.org/forum
*/


defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

class bx_modified_affiliates {
  public string $code;
  public string $version;
  public string $development_status;
  public string $title;
  public string $description;
  public int $sort_order;
  public bool $enabled;
  public bool $_check;

  function __construct() {
    $this->code        = 'bx_modified_affiliates';
    $this->version     = '1.0.0';
    $this->title       = defined('MODULE_MODIFIED_AFFILIATES_TEXT_TITLE') ? MODULE_MODIFIED_AFFILIATES_TEXT_TITLE : '';
    $this->description = defined('MODULE_MODIFIED_AFFILIATES_TEXT_DESC') ? MODULE_MODIFIED_AFFILIATES_TEXT_DESC : '';
    $this->sort_order  = defined('MODULE_MODIFIED_AFFILIATES_SORT_ORDER') ? MODULE_MODIFIED_AFFILIATES_SORT_ORDER : 0;
    $this->enabled     = ((defined('MODULE_MODIFIED_AFFILIATES_STATUS') && MODULE_MODIFIED_AFFILIATES_STATUS == 'True') ? true : false);
    $this->development_status = '';
  }

  function process($file): bool {
    return true;
  }

  function display(): array {
    return array('text' => '<br /><div align="center">' . xtc_button(BUTTON_SAVE) .
    xtc_button_link(BUTTON_CANCEL, xtc_href_link(FILENAME_MODULE_EXPORT, 'set=' . $_GET['set'] . '&module=bx_modified_affiliates')) . "</div>");
  }

	function check(): bool {
    if (!isset($this->_check)) {
      if (defined('MODULE_MODIFIED_AFFILIATES_STATUS')) {
        $this->_check = true;
      } else {
        $check_query = xtc_db_query("SELECT configuration_value 
                                        FROM " . TABLE_CONFIGURATION . " 
                                      WHERE configuration_key = 'MODULE_MODIFIED_AFFILIATES_STATUS'");
        $this->_check = xtc_db_num_rows($check_query);
      }
    }
    return $this->_check;
  }

  function install(): void {
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD " . $this->code ." TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_banners TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_clicks TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_contact TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_invoice TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_payment TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_popup_image TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_sales TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_statistics TINYINT(1)");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD bx_modified_summary TINYINT(1)");

    xtc_db_query("UPDATE ".TABLE_ADMIN_ACCESS." SET
      " . $this->code ." = 1, bx_modified_banners = 1, bx_modified_clicks = 1, bx_modified_contact = 1, bx_modified_invoice = 1, bx_modified_payment = 1, bx_modified_popup_image = 1, bx_modified_sales = 1, bx_modified_statistics = 1, bx_modified_summary = 1");

    $freeId_query = xtc_db_query("SELECT MIN(configuration_group_id+1) AS id 
                                         FROM ".TABLE_CONFIGURATION_GROUP." 
                                        WHERE (configuration_group_id+1) NOT IN 
                                          (SELECT configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_id IS NOT NULL);");
    $freeId = xtc_db_fetch_array($freeId_query);

    $freeSort_query = xtc_db_query("SELECT MIN(sort_order+1) AS sort_order 
                                            FROM ".TABLE_CONFIGURATION_GROUP." 
                                           WHERE (sort_order+1) NOT IN (SELECT sort_order FROM ".TABLE_CONFIGURATION_GROUP." WHERE sort_order IS NOT NULL)");
    $freeSort = xtc_db_fetch_array($freeSort_query);

    xtc_db_query("INSERT INTO ".TABLE_CONFIGURATION_GROUP." ( configuration_group_id, 
                                                              configuration_group_title, 
                                                              configuration_group_description, 
                                                              sort_order, 
                                                              visible ) 
                                                      VALUES ( '" . (int)$freeId['id'] . "', 
                                                              '" . xtc_db_input('BX Modified Affiliates') . "', 
                                                              '" . xtc_db_input('Settings for the BX Modified Affiliates module') . "', 
                                                              '" . (int)$freeSort['sort_order'] . "',
                                                              1 );");

    xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_id, 
                                                            configuration_key, 
                                                            configuration_value, 
                                                            configuration_group_id, 
                                                            sort_order, 
                                                            last_modified, 
                                                            date_added, 
                                                            use_function, 
                                                            set_function) 
              VALUES 
              ('', 'MODULE_MODIFIED_AFFILIATES_STATUS',     'True',                       '6',                           '1', '', now(), '', 'xtc_cfg_select_option(array(\'True\', \'False\'), '),
              ('', 'MODULE_MODIFIED_AFFILIATES_VERSION',    '1.0.0',                      '6',                           '2', '', now(), '', ''),
              ('', 'MODULE_MODIFIED_AFFILIATES_CONFIG_ID',  '" . (int)$freeId['id'] . "', '6',                           '3', '', now(), '', 'bx_configuration_field_version('),
              ('', 'BX_AFFILIATE_EMAIL_ADDRESS',            '',                           '" . (int)$freeId['id'] . "',  '1', '', now(), '', ''),
              ('', 'BX_AFFILIATE_PERCENT',                  '10.0',                       '" . (int)$freeId['id'] . "',  '2', '', now(), '', ''),
              ('', 'BX_AFFILIATE_THRESHOLD',                '50.00',                      '" . (int)$freeId['id'] . "',  '3', '', now(), '', ''),
              ('', 'BX_AFFILIATE_COOKIE_LIFETIME',          '7200',                       '" . (int)$freeId['id'] . "',  '4', '', now(), '', ''),
              ('', 'BX_AFFILIATE_BILLING_TIME',             '30',                         '" . (int)$freeId['id'] . "',  '5', '', now(), '', ''),
              ('', 'BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS', '3',                          '" . (int)$freeId['id'] . "',  '6', '', now(), '', ''),
              ('', 'BX_AFFILIATE_USE_PAYPAL',               'True',                       '" . (int)$freeId['id'] . "',  '7', '', now(), '', 'xtc_cfg_select_option(array(\'True\', \'False\'), '),
              ('', 'BX_AFFILIATE_USE_BANK',                 'True',                       '" . (int)$freeId['id'] . "',  '8', '', now(), '', 'xtc_cfg_select_option(array(\'True\', \'False\'), '),
              ('', 'BX_AFFILIATE_INDIVIDUAL_PERCENTAGE',    'False',                      '" . (int)$freeId['id'] . "',  '9', '', now(), '', 'xtc_cfg_select_option(array(\'True\', \'False\'), '),
              ('', 'BX_AFFILIATE_USE_TIER',                 'False',                      '" . (int)$freeId['id'] . "', '10', '', now(), '', 'xtc_cfg_select_option(array(\'True\', \'False\'), '),
              ('', 'BX_AFFILIATE_TIER_LEVELS',              '3',                          '" . (int)$freeId['id'] . "', '11', '', now(), '', ''),
              ('', 'BX_AFFILIATE_TIER_PERCENTAGE',          '8.00;5.00;1.00',             '" . (int)$freeId['id'] . "', '12', '', now(), '', '')");

    // -----------------------------------------------------------------------------
    // 1. Partnerstammdaten
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_partner (
      bx_affiliate_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
      bx_affiliate_parent_id int(11) UNSIGNED DEFAULT NULL COMMENT 'Übergeordneter Partner (Tier-Baum), NULL = Root',
      bx_affiliate_gender char(1) NOT NULL DEFAULT '' COMMENT 'Anrede m/f',
      bx_affiliate_firstname varchar(32) NOT NULL DEFAULT '' COMMENT 'Vorname',
      bx_affiliate_lastname varchar(32) NOT NULL DEFAULT '' COMMENT 'Nachname',
      bx_affiliate_dob datetime DEFAULT NULL COMMENT 'Geburtsdatum',
      bx_affiliate_email_address varchar(96) NOT NULL DEFAULT '' COMMENT 'E-Mail-Adresse / Login',
      bx_affiliate_telephone varchar(32) NOT NULL DEFAULT '' COMMENT 'Telefonnummer',
      bx_affiliate_fax varchar(32) NOT NULL DEFAULT '' COMMENT 'Faxnummer',
      bx_affiliate_password varchar(255) NOT NULL DEFAULT '' COMMENT 'Passwort-Hash (bcrypt/argon2 via password_hash())',
      bx_affiliate_homepage varchar(96) NOT NULL DEFAULT '' COMMENT 'Homepage-URL des Partners',
      bx_affiliate_street_address varchar(64) NOT NULL DEFAULT '' COMMENT 'Straße und Hausnummer',
      bx_affiliate_suburb varchar(64) NOT NULL DEFAULT '' COMMENT 'Adresszusatz',
      bx_affiliate_city varchar(32) NOT NULL DEFAULT '' COMMENT 'Ort',
      bx_affiliate_postcode varchar(10) NOT NULL DEFAULT '' COMMENT 'Postleitzahl',
      bx_affiliate_state varchar(32) NOT NULL DEFAULT '' COMMENT 'Bundesland/Region (Freitext)',
      bx_affiliate_country_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu countries',
      bx_affiliate_zone_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu zones',
      bx_affiliate_agb tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'AGB-Zustimmung (Flag)',
      bx_affiliate_company varchar(60) NOT NULL DEFAULT '' COMMENT 'Firmenname',
      bx_affiliate_company_taxid varchar(64) NOT NULL DEFAULT '' COMMENT 'USt-IdNr.',
      bx_affiliate_commission_percent decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Individueller Provisionssatz in %',
      bx_affiliate_tiers_allowed tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Mehrstufiges Partnerprogramm erlaubt (Flag)',
      bx_affiliate_payment_check varchar(100) NOT NULL DEFAULT '' COMMENT 'Auszahlung per Scheck: Empfängerdaten',
      bx_affiliate_payment_paypal varchar(64) NOT NULL DEFAULT '' COMMENT 'Auszahlung per PayPal: E-Mail-Adresse',
      bx_affiliate_payment_bank_name varchar(64) NOT NULL DEFAULT '' COMMENT 'Bankname',
      bx_affiliate_payment_bank_branch_number varchar(64) NOT NULL DEFAULT '' COMMENT 'BLZ/Bankleitzahl',
      bx_affiliate_payment_bank_swift_code varchar(64) NOT NULL DEFAULT '' COMMENT 'SWIFT/BIC',
      bx_affiliate_payment_bank_account_name varchar(64) NOT NULL DEFAULT '' COMMENT 'Kontoinhaber',
      bx_affiliate_payment_bank_account_number varchar(64) NOT NULL DEFAULT '' COMMENT 'IBAN/Kontonummer',
      bx_affiliate_date_of_last_logon datetime DEFAULT NULL COMMENT 'Letzter Login-Zeitpunkt',
      bx_affiliate_number_of_logons int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Anzahl bisheriger Logins',
      bx_affiliate_date_account_created datetime NOT NULL DEFAULT '1000-01-01 00:00:00' COMMENT 'Account-Erstellungsdatum',
      bx_affiliate_date_account_last_modified datetime NOT NULL DEFAULT '1000-01-01 00:00:00' COMMENT 'Letzte Änderung des Accounts',
      PRIMARY KEY (bx_affiliate_id),
      KEY idx_bx_affiliate_parent_id (bx_affiliate_parent_id),
      KEY idx_bx_affiliate_email (bx_affiliate_email_address),
      CONSTRAINT fk_bx_affiliate_parent FOREIGN KEY (bx_affiliate_parent_id)
          REFERENCES bx_affiliate_partner (bx_affiliate_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Partner-/Affiliate-Stammdaten';");

    // -----------------------------------------------------------------------------
    // 2. Werbemittel (Banner)
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_banners (
      bx_affiliate_banners_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
      bx_affiliate_banners_title varchar(64) NOT NULL DEFAULT '' COMMENT 'Bezeichnung des Werbemittels',
      bx_affiliate_products_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu products (verlinktes Produkt, optional)',
      bx_affiliate_banners_image varchar(64) NOT NULL DEFAULT '' COMMENT 'Dateiname des Banner-Bilds',
      bx_affiliate_banners_group varchar(10) NOT NULL DEFAULT '' COMMENT 'Banner-Format/-Gruppe (z.B. Größe)',
      bx_affiliate_banners_html_text text COMMENT 'HTML-Code für Text-/Code-Werbemittel',
      bx_affiliate_expires_impressions int(7) UNSIGNED DEFAULT '0' COMMENT 'Ablauf nach X Impressions (0 = unbegrenzt)',
      bx_affiliate_expires_date datetime DEFAULT NULL COMMENT 'Ablaufdatum des Banners',
      bx_affiliate_date_scheduled datetime DEFAULT NULL COMMENT 'Geplanter Start-Zeitpunkt',
      bx_affiliate_date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Erstellungsdatum',
      bx_affiliate_date_status_change datetime DEFAULT NULL COMMENT 'Datum der letzten Statusänderung',
      bx_affiliate_status tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Aktiv/Inaktiv',
      bx_affiliate_link_type char(1) NOT NULL DEFAULT 'p' COMMENT 'Linkziel-Typ: p=Produkt, h=Homepage, c=Kategorie',
      bx_affiliate_banners_text text COMMENT 'Alternativtext/Beschreibung des Banners',
      PRIMARY KEY (bx_affiliate_banners_id),
      KEY idx_bx_affiliate_banners_status_dates (bx_affiliate_status, bx_affiliate_date_scheduled, bx_affiliate_expires_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Werbemittel (Banner/Text-Links) für Partner';");

    // -----------------------------------------------------------------------------
    // 3. Banner-Statistik (tagesgenau)
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_banners_history (
      bx_affiliate_banners_history_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
      bx_affiliate_banners_products_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu products (verlinktes Produkt zum Zeitpunkt der Anzeige)',
      bx_affiliate_banners_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_banners',
      bx_affiliate_banners_bx_affiliate_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_partner',
      bx_affiliate_banners_shown int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Anzahl Impressions an diesem Tag',
      bx_affiliate_banners_clicks int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Anzahl Klicks an diesem Tag',
      bx_affiliate_banners_history_date date NOT NULL COMMENT 'Tag der Statistik',
      PRIMARY KEY (bx_affiliate_banners_history_id),
      KEY idx_bx_affiliate_banners_history_lookup (bx_affiliate_banners_id, bx_affiliate_banners_bx_affiliate_id, bx_affiliate_banners_history_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tagesgenaue Impressions/Klick-Statistik je Banner und Partner';");

    // -----------------------------------------------------------------------------
    // 4. Klick-Tracking
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_clickthroughs (
      bx_affiliate_clickthrough_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
      bx_affiliate_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_partner',
      bx_affiliate_clientdate datetime NOT NULL DEFAULT '1000-01-01 00:00:00' COMMENT 'Zeitpunkt des Klicks',
      bx_affiliate_clientbrowser varchar(200) DEFAULT 'Could Not Find This Data' COMMENT 'User-Agent des Besuchers',
      bx_affiliate_clientip varchar(50) DEFAULT 'Could Not Find This Data' COMMENT 'IP-Adresse des Besuchers (DSGVO: Löschfrist beachten)',
      bx_affiliate_clientreferer varchar(200) DEFAULT 'none detected (maybe a direct link)' COMMENT 'Referrer-URL',
      bx_affiliate_products_id int(11) UNSIGNED DEFAULT '0' COMMENT 'FK zu products (verlinktes Produkt, optional)',
      bx_affiliate_banner_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_banners (0 = kein Banner-Klick)',
      PRIMARY KEY (bx_affiliate_clickthrough_id),
      KEY idx_bx_affiliate_clickthrough_refid (bx_affiliate_id),
      KEY idx_bx_affiliate_clickthrough_banner (bx_affiliate_banner_id),
      KEY idx_bx_affiliate_clickthrough_date (bx_affiliate_id, bx_affiliate_clientdate)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Protokoll aller Klicks auf Partnerlinks/Banner';");

    // -----------------------------------------------------------------------------
    // 5. Auszahlungen
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_payment (
      bx_affiliate_payment_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
      bx_affiliate_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_partner',
      bx_affiliate_payment decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Auszahlungsbetrag netto',
      bx_affiliate_payment_tax decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Steueranteil',
      bx_affiliate_payment_total decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Gesamtbetrag (netto + Steuer)',
      bx_affiliate_payment_date datetime DEFAULT NULL COMMENT 'Datum der Auszahlung',
      bx_affiliate_payment_last_modified datetime DEFAULT NULL COMMENT 'Letzte Änderung des Zahlungsdatensatzes',
      bx_affiliate_payment_status tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_payment_status',
      bx_affiliate_firstname varchar(32) NOT NULL DEFAULT '' COMMENT 'Vorname (Snapshot zum Auszahlungszeitpunkt)',
      bx_affiliate_lastname varchar(32) NOT NULL DEFAULT '' COMMENT 'Nachname (Snapshot zum Auszahlungszeitpunkt)',
      bx_affiliate_street_address varchar(64) NOT NULL DEFAULT '' COMMENT 'Straße (Snapshot)',
      bx_affiliate_suburb varchar(64) NOT NULL DEFAULT '' COMMENT 'Adresszusatz (Snapshot)',
      bx_affiliate_city varchar(32) NOT NULL DEFAULT '' COMMENT 'Ort (Snapshot)',
      bx_affiliate_postcode varchar(10) NOT NULL DEFAULT '' COMMENT 'PLZ (Snapshot)',
      bx_affiliate_country varchar(32) NOT NULL DEFAULT '0' COMMENT 'Land (Snapshot)',
      bx_affiliate_company varchar(60) NOT NULL DEFAULT '' COMMENT 'Firma (Snapshot)',
      bx_affiliate_state varchar(32) NOT NULL DEFAULT '0' COMMENT 'Bundesland/Region (Snapshot)',
      bx_affiliate_address_format_id int(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu address_format',
      bx_affiliate_last_modified datetime DEFAULT NULL COMMENT 'Letzte Änderung',
      PRIMARY KEY (bx_affiliate_payment_id),
      KEY idx_bx_affiliate_payment_status (bx_affiliate_id, bx_affiliate_payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auszahlungen an Partner inkl. Adress-Snapshot zum Auszahlungszeitpunkt';");

    // -----------------------------------------------------------------------------
    // 6. Zahlungsstatus (mehrsprachig)
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_payment_status (
      bx_affiliate_payment_status_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Status-ID (0=Pending, 1=Paid, ...)',
      bx_affiliate_language_id int(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'FK zu languages',
      bx_affiliate_payment_status_name varchar(32) NOT NULL DEFAULT '' COMMENT 'Sprachabhängige Statusbezeichnung',
      PRIMARY KEY (bx_affiliate_payment_status_id, bx_affiliate_language_id),
      KEY idx_bx_affiliate_payment_status_name (bx_affiliate_payment_status_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mehrsprachige Bezeichnungen der Zahlungsstatus';");

    // -----------------------------------------------------------------------------
    // 7. Zahlungsstatus-Historie
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_payment_status_history (
      bx_affiliate_status_history_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
      bx_affiliate_payment_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_payment',
      bx_affiliate_new_value int(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Neuer Status',
      bx_affiliate_old_value int(5) UNSIGNED DEFAULT NULL COMMENT 'Vorheriger Status',
      bx_affiliate_date_added datetime NOT NULL DEFAULT '1000-01-01 00:00:00' COMMENT 'Zeitpunkt der Statusänderung',
      bx_affiliate_notified tinyint(1) UNSIGNED DEFAULT '0' COMMENT 'Partner über Änderung benachrichtigt (Flag)',
      PRIMARY KEY (bx_affiliate_status_history_id),
      KEY idx_bx_affiliate_status_history_payment_date (bx_affiliate_payment_id, bx_affiliate_date_added)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historie aller Statusänderungen zu Auszahlungen';");

    // -----------------------------------------------------------------------------
    // 8. Verkäufe / Provisionen
    // -----------------------------------------------------------------------------
    xtc_db_query("CREATE TABLE IF NOT EXISTS bx_affiliate_sales (
      bx_affiliate_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_partner (Teil des zusammengesetzten PK)',
      bx_affiliate_date datetime NOT NULL DEFAULT '1000-01-01 00:00:00' COMMENT 'Zeitpunkt der Bestellung',
      bx_affiliate_browser varchar(100) NOT NULL DEFAULT '' COMMENT 'User-Agent des Käufers',
      bx_affiliate_ipaddress varchar(45) NOT NULL DEFAULT '' COMMENT 'IP-Adresse des Käufers (IPv4/IPv6)',
      bx_affiliate_orders_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu orders (Teil des zusammengesetzten PK)',
      bx_affiliate_value decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Bestellwert netto',
      bx_affiliate_payment decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Errechnete Provision',
      bx_affiliate_clickthroughs_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_clickthroughs (zuführender Klick)',
      bx_affiliate_billing_status tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Abrechnungsstatus (offen/ausgezahlt)',
      bx_affiliate_payment_date datetime DEFAULT NULL COMMENT 'Datum der Auszahlung dieser Provision',
      bx_affiliate_payment_id int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK zu bx_affiliate_payment',
      bx_affiliate_percent decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Angewandter Provisionssatz in %',
      bx_affiliate_salesman int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Ursprünglich vermittelnder Partner (bei Tier-Ketten)',
      bx_affiliate_level tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Tier-Ebene: 0=direkt, 1+=Sub-Affiliate-Provision',
      PRIMARY KEY (bx_affiliate_id, bx_affiliate_orders_id),
      KEY idx_bx_affiliate_sales_order (bx_affiliate_orders_id),
      KEY idx_bx_affiliate_sales_payment (bx_affiliate_payment_id),
      KEY idx_bx_affiliate_sales_date (bx_affiliate_id, bx_affiliate_date),
      KEY idx_bx_affiliate_sales_level (bx_affiliate_id, bx_affiliate_level)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Verkäufe/Provisionen je Partner und Bestellung, inkl. Tier-Ebenen';");

    // -----------------------------------------------------------------------------
    // 9. Basisdaten: Zahlungsstatus-Bezeichnungen - dynamisch für alle tatsächlich
    //    installierten Sprachen, da languages_id von Shop zu Shop unterschiedlich
    //    sein kann (Reihenfolge/Anzahl der installierten Sprachen ist nicht fix)
    // -----------------------------------------------------------------------------
    $bx_affiliate_payment_status_translations = array(
      'default' => array(0 => 'Pending',       1 => 'Paid'),
      'bg'      => array(0 => 'Чакащо',        1 => 'Платено'),
      'cs'      => array(0 => 'Čekající',      1 => 'Vyplaceno'),
      'da'      => array(0 => 'Afventer',      1 => 'Betalt'),
      'de'      => array(0 => 'Offen',         1 => 'Ausgezahlt'),
      'el'      => array(0 => 'Σε εκκρεμότητα', 1 => 'Πληρωμένο'),
      'en'      => array(0 => 'Pending',       1 => 'Paid'),
      'es'      => array(0 => 'Pendiente',     1 => 'Pagado'),
      'et'      => array(0 => 'Ootel',         1 => 'Välja makstud'),
      'fi'      => array(0 => 'Odottaa',       1 => 'Maksettu'),
      'fr'      => array(0 => 'En attente',    1 => 'Payé'),
      'ga'      => array(0 => 'Ar feitheamh',  1 => 'Íoctha'),
      'hr'      => array(0 => 'Na čekanju',    1 => 'Isplaćeno'),
      'hu'      => array(0 => 'Függőben',      1 => 'Kifizetve'),
      'it'      => array(0 => 'In sospeso',    1 => 'Pagato'),
      'lt'      => array(0 => 'Laukiama',      1 => 'Apmokėta'),
      'lv'      => array(0 => 'Gaida',         1 => 'Apmaksāts'),
      'mt'      => array(0 => 'Pendenti',      1 => 'Imħallas'),
      'nl'      => array(0 => 'In behandeling', 1 => 'Betaald'),
      'pl'      => array(0 => 'Oczekująca',    1 => 'Opłacono'),
      'pt'      => array(0 => 'Pendente',      1 => 'Pago'),
      'ro'      => array(0 => 'În așteptare',  1 => 'Plătit'),
      'sk'      => array(0 => 'Čakajúca',      1 => 'Vyplatené'),
      'sl'      => array(0 => 'Na čakanju',    1 => 'Izplačano'),
      'sv'      => array(0 => 'Väntande',      1 => 'Betald'),
    );

    $bx_affiliate_languages_query = xtc_db_query("SELECT languages_id, code FROM " . TABLE_LANGUAGES);
    while ($bx_affiliate_language = xtc_db_fetch_array($bx_affiliate_languages_query)) {
      $bx_affiliate_lang_code     = strtolower($bx_affiliate_language['code']);
      $bx_affiliate_labels        = $bx_affiliate_payment_status_translations[$bx_affiliate_lang_code]
                                    ?? $bx_affiliate_payment_status_translations['default'];

      foreach ($bx_affiliate_labels as $bx_affiliate_status_id => $bx_affiliate_status_label) {
        xtc_db_query("INSERT INTO bx_affiliate_payment_status ( bx_affiliate_payment_status_id, bx_affiliate_language_id, bx_affiliate_payment_status_name )
                            VALUES ( '" . (int)$bx_affiliate_status_id . "', '" . (int)$bx_affiliate_language['languages_id'] . "', '" . xtc_db_input($bx_affiliate_status_label) . "' )");
      }
    }

  }

  function remove(): void {
    xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id  = '" . (int)MODULE_MODIFIED_AFFILIATES_CONFIG_ID . "'");
    xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key in ('" . implode("', '", $this->keys()) . "')");
    
    if(defined('MAX_DISPLAY_LIST_AFFILIATES')) {
      xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MAX_DISPLAY_LIST_AFFILIATES'");
    }
    
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP " . $this->code);
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_banners");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_clicks");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_contact");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_invoice");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_payment");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_popup_image");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_sales");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_statistics");
    xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." DROP bx_modified_summary");

    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_partner");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_banners");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_banners_history");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_clickthroughs");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_payment");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_payment_status");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_payment_status_history");
    xtc_db_query("DROP TABLE IF EXISTS bx_affiliate_sales");

  }

  function keys(): array {
    $key = array(
      'MODULE_MODIFIED_AFFILIATES_STATUS',
      'MODULE_MODIFIED_AFFILIATES_VERSION',
      'MODULE_MODIFIED_AFFILIATES_CONFIG_ID',
      'BX_AFFILIATE_EMAIL_ADDRESS',
      'BX_AFFILIATE_PERCENT', 
      'BX_AFFILIATE_THRESHOLD',
      'BX_AFFILIATE_COOKIE_LIFETIME',
      'BX_AFFILIATE_BILLING_TIME',
      'BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS',
      'BX_AFFILIATE_USE_PAYPAL',
      'BX_AFFILIATE_USE_BANK',
      'BX_AFFILIATE_INDIVIDUAL_PERCENTAGE',
      'BX_AFFILIATE_USE_TIER',
      'BX_AFFILIATE_TIER_LEVELS',
      'BX_AFFILIATE_TIER_PERCENTAGE',
    );

    return $key;
  }

  function custom(): void { }
}

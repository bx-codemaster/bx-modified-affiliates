<?php
/** 
 * BX Modified Affiliates - German System Module Texts
 * 
 * System module configuration texts for BX Modified Affiliates.
 * Module description, title, description and status constants.
 * 
 * @package    BX Modified Affiliates
 * @subpackage Language
 * @category   System Module
 * @author     Axel Benkert
 * @version    1.0.0
 * @date       2026-08-22
 * @copyright  2020-2026 Axel Benkert
 * @license    GNU General Public License
 */

  define('MODULE_MODIFIED_AFFILIATES_TEXT_TITLE', 'BX Modified Affiliates');

  define('MODULE_MODIFIED_AFFILIATES_STATUS_TITLE', 'Modul aktiv?');
  define('MODULE_MODIFIED_AFFILIATES_STATUS_DESC', 'Soll das Modul angezeigt werden?');
  $description = '
<details class="bxac-card">
  <summary class="bxac-summary" style="list-style: none;">
  <span class="bxac-arrow">▸</span>
  ' . xtc_image(DIR_WS_ICONS.'heading/bx_modified_affiliates.png', 'BX Modified Affiliates', '', '', 'style="max-height: 32px; margin: 2px;"') . '
  <span class="bxac-title">BX Modified Affiliates</span>
  </summary>
  <div class="bxac-body">
    <h3 style="margin-top: 0;">Professionelles Verwaltungstool</h3>
    <p>Ein Tool für Modified eCommerce Shopsofware, das die Verwaltung von Partnerprogrammen vereinfacht. Mit einer modernen Oberfläche können Sie Partner, Provisionen und Zahlungen effizient verwalten.</p>';

  if (basename($_SERVER['PHP_SELF']) == 'module_export.php' && !defined('MODULE_MODIFIED_AFFILIATES_STATUS') ) {
    $description .= '<p><a class="button btnbox but_red" style="text-align:center;" onclick="return confirmLink(\'Alle Dateien löschen?\', \'\' ,this);" href="'.xtc_href_link(FILENAME_MODULE_EXPORT, 'set=system&module=bx_modified_affiliates&action=custom').'">Alle Moduldateien löschen</a></p>';
  }
  $description .= '</div></details>';

  define('MODULE_MODIFIED_AFFILIATES_TEXT_DESC', $description);

  define('MODULE_MODIFIED_AFFILIATES_SORT_ORDER_TITLE', 'Sortierreihenfolge');
  define('MODULE_MODIFIED_AFFILIATES_SORT_ORDER_DESC', 'Reihenfolge der Anzeige. Kleinste Ziffer wird zuerst angezeigt.');
  define('MODULE_MODIFIED_AFFILIATES_VERSION_TITLE', 'Modulversion');
  define('MODULE_MODIFIED_AFFILIATES_VERSION_DESC', 'Aktuelle Version des Moduls.');
  define('MODULE_MODIFIED_AFFILIATES_CONFIG_ID_TITLE', 'Konfigurations-ID');
  define('MODULE_MODIFIED_AFFILIATES_CONFIG_ID_DESC', 'ID der Konfiguration, die für die Verwaltung des Moduls verwendet wird.');
  define('BX_AFFILIATE_EMAIL_ADDRESS_TITLE', 'E-Mail-Adresse für Affiliate-Benachrichtigungen');
  define('BX_AFFILIATE_EMAIL_ADDRESS_DESC', 'E-Mail-Adresse, an die Benachrichtigungen über Affiliate-Aktivitäten gesendet werden sollen.');
  define('BX_AFFILIATE_PERCENT_TITLE', 'Standardprovisionssatz (%)');
  define('BX_AFFILIATE_PERCENT_DESC', 'Der Standardprozentsatz, der für die Berechnung von Affiliate-Provisionen verwendet wird.');
  define('BX_AFFILIATE_THRESHOLD_TITLE', 'Provisionsschwelle');
  define('BX_AFFILIATE_THRESHOLD_DESC', 'Der Mindestbetrag, der erreicht werden muss, bevor eine Auszahlung an den Affiliate erfolgt.');
  define('BX_AFFILIATE_COOKIE_LIFETIME_TITLE', 'Cookie-Lebensdauer (in Tagen)');
  define('BX_AFFILIATE_COOKIE_LIFETIME_DESC', 'Die Anzahl der Tage, die ein Affiliate-Cookie auf dem Computer des Benutzers verbleibt, bevor es abläuft.');
  define('BX_AFFILIATE_BILLING_TIME_TITLE', 'Abrechnungszeit');
  define('BX_AFFILIATE_BILLING_TIME_DESC', 'Die Zeit, zu der Affiliate-Zahlungen erfolgen.');
  define('BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS_TITLE', 'Mindestbestellstatus für Zahlungen');
  define('BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS_DESC', 'Der Mindestbestellstatus, der erreicht werden muss, bevor eine Affiliate-Zahlung ausgelöst wird.');
  define('BX_AFFILIATE_USE_BANK_TITLE', 'Banküberweisung für Zahlungen verwenden?');
  define('BX_AFFILIATE_USE_BANK_DESC', 'Geben Sie an, ob Banküberweisung als Zahlungsmethode für Affiliate-Zahlungen verwendet werden soll.');
  define('BX_AFFILIATE_USE_PAYPAL_TITLE', 'PayPal für Zahlungen verwenden?');
  define('BX_AFFILIATE_USE_PAYPAL_DESC', 'Geben Sie an, ob PayPal als Zahlungsmethode für Affiliate-Zahlungen verwendet werden soll.');
  define('BX_AFFILIATE_PAYPAL_EMAIL_ADDRESS_TITLE', 'PayPal-E-Mail-Adresse');
  define('BX_AFFILIATE_PAYPAL_EMAIL_ADDRESS_DESC', 'Die E-Mail-Adresse, die mit dem PayPal-Konto des Affiliates verknüpft ist.');
  define('BX_AFFILIATE_INDIVIDUAL_PERCENTAGE_TITLE', 'Individueller Provisionssatz für Affiliates');
  define('BX_AFFILIATE_INDIVIDUAL_PERCENTAGE_DESC', 'Geben Sie an, ob Affiliates individuelle Provisionssätze haben können.');
  define('BX_AFFILIATE_USE_TIER_TITLE', 'Stufenprovisionen verwenden?');
  define('BX_AFFILIATE_USE_TIER_DESC', 'Geben Sie an, ob Stufenprovisionen für Affiliates verwendet werden sollen.');
  define('BX_AFFILIATE_TIER_LEVELS_TITLE', 'Anzahl der Stufen');
  define('BX_AFFILIATE_TIER_LEVELS_DESC', 'Die Anzahl der Stufen, die für Stufenprovisionen verwendet werden sollen.');
  define('BX_AFFILIATE_TIER_PERCENTAGE_TITLE', 'Stufenprozentwerte');
  define('BX_AFFILIATE_TIER_PERCENTAGE_DESC', 'Die Prozentsätze, die für jede Stufe der Stufenprovisionen verwendet werden sollen, getrennt durch Kommas (z. B. 5,3,2 für drei Stufen).');
  
  // Custom Deinstallation Messages
  define('MODULE_MODIFIED_AFFILIATES_TEXT_FILES_DELETED', 'Erfolgreich gelöscht:');
  define('MODULE_MODIFIED_AFFILIATES_TEXT_FILES_FAILED', 'Fehler beim Löschen (bitte manuell per FTP entfernen):');
  define('MODULE_MODIFIED_AFFILIATES_TEXT_SUCCESSFULLY_REMOVED', 'BX Modified Affiliates wurde vollständig entfernt!');
  define('MODULE_MODIFIED_AFFILIATES_TEXT_REMOVAL_INCOMPLETE', 'BX Modified Affiliates wurde teilweise entfernt. Bitte prüfen Sie die Fehlermeldungen und löschen Sie die verbliebenen Dateien manuell per FTP.');

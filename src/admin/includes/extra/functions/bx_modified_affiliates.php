<?php
/**
 * BX Modified Affiliates Module - Helper Functions
 * 
 * Central helper function library for Affiliates module operations.
 * Provides reusable utilities for database operations, email notifications,
 * form field generation, and alert banner display.
 * 
 * @package    BX Modified Affiliates
 * @subpackage Core Functions
 * @version    1.0.0
 * @author     benax
 * @copyright  2006-2026 benax
 * @license    GNU GPL v2.0
 * 
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

function bx_affiliate_normalize_url(string $url): string {
  global $request_type;
  $url = trim($url);
  if ($url === '' || parse_url($url, PHP_URL_SCHEME)) {
    return $url;
  }
  switch ($request_type) {
    case 'SSL':
        return 'https://' . $url;
    case 'NONSSL':
        return 'http://' . $url;
  }
  return 'http://' . $url; // Fallback if $request_type is not set
}

/**
 * Erstellt den SQL-Filter für die zulässigen Bestellstatus der Affiliate-Auszahlungen.
 *
 * Die Status-IDs werden aus BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS gelesen
 * und als mit OR verknüpfte Bedingung für die Tabelle o zurückgegeben.
 *
 * @return string SQL-Fragment einschließlich AND und der umschließenden Klammern
 */
function bx_affiliate_build_orders_status_query(): string {
    $orders_status = explode(';', BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS);
    $conditions = array();

    foreach ($orders_status as $status) {
      $status = trim($status);
      if ($status !== '' && ctype_digit($status)) {
        $conditions[] = 'o.orders_status = ' . (int)$status;
      }
    }

    return !empty($conditions) ? 'AND (' . implode(' OR ', $conditions) . ')' : '';
}

/**
 * Konfigurationseingabefeld für die Modulversion (read-only)
 */
if (!function_exists('bx_configuration_field_version')) {
  function bx_configuration_field_version(string $value, string $constant): string {
    return xtc_draw_input_field( 'configuration['.$constant.']', $value, 'readonly="true" style="opacity: 0.4;"');
  }
}
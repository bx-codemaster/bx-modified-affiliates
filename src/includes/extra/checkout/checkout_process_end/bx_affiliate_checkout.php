<?php
/**
 * BX Modified Affiliates Module - Checkout Hook
 *
 * Wird nach Abschluss einer Bestellung (checkout_process_end) ausgeführt.
 * Ermittelt den zuführenden Affiliate aus Cookie/Session, läuft anhand von
 * bx_affiliate_parent_id die Upline hoch (WITH RECURSIVE, wie bereits im
 * Admin-Controller bx_modified_affiliates.php verwendet) und legt für den
 * direkten Affiliate sowie ggf. jede berechtigte Tier-Ebene einen
 * eigenen Datensatz in bx_affiliate_sales an.
 *
 * Hinweis: Dieser Hook legt Provisionen ausschließlich im Status "offen"
 * (bx_affiliate_billing_status = 0) an. Die spätere Prüfung von
 * BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS / BX_AFFILIATE_BILLING_TIME sowie
 * die Stornobehandlung erfolgen bewusst NICHT hier, sondern in einem
 * separaten, statusgetriebenen Prozess (siehe offene Punkte unten) --
 * zum Zeitpunkt von checkout_process_end steht der endgültige
 * Bestellstatus in der Regel noch nicht fest.
 *
 * @package    BX Modified Affiliates
 * @subpackage Checkout Integration
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

// modified stellt in diesem Hook üblicherweise $orders_id bereit
if (!isset($orders_id) || (int)$orders_id <= 0) {
  return;
}

$bx_affiliate_direct_id = isset($_COOKIE['bx_affiliate_id'])
  ? (int)$_COOKIE['bx_affiliate_id']
  : (isset($_SESSION['bx_affiliate_id']) ? (int)$_SESSION['bx_affiliate_id'] : 0);

if ($bx_affiliate_direct_id > 0) {

  // 1. Bestellwert (netto) ermitteln
  $bx_order_total_query = xtc_db_query(
    "SELECT value
     FROM " . TABLE_ORDERS_TOTAL . "
     WHERE orders_id = " . (int)$orders_id . "
       AND class = 'ot_subtotal'"
  );
  $bx_order_total_row = xtc_db_fetch_array($bx_order_total_query);
  $bx_net_amount = isset($bx_order_total_row['value']) ? (float)$bx_order_total_row['value'] : 0.0;

  if ($bx_net_amount > 0) {

    // 2. Tier-Konfiguration einlesen
    $bx_use_tier   = defined('BX_AFFILIATE_USE_TIER') && BX_AFFILIATE_USE_TIER === 'True';
    $bx_tier_levels = defined('BX_AFFILIATE_TIER_LEVELS') ? (int)BX_AFFILIATE_TIER_LEVELS : 0;
    $bx_tier_rates  = defined('BX_AFFILIATE_TIER_PERCENTAGE')
      ? array_map('floatval', array_filter(explode(';', BX_AFFILIATE_TIER_PERCENTAGE), 'strlen'))
      : array();
    $bx_max_levels  = $bx_use_tier ? min($bx_tier_levels, count($bx_tier_rates)) : 0;

    // 3. Direkten Affiliate + (falls Tiers aktiv) Upline per Rekursion laden.
    //    Gleiches WITH-RECURSIVE-Muster wie im Admin-Controller, nur in
    //    umgekehrter Richtung: von einem Knoten aus AUFWÄRTS zur Root.
    //    bx_affiliate_tiers_allowed wird hier bewusst NICHT gefiltert:
    //    das Flag bedeutet "Partner darf Sub-Partner haben" und gehört
    //    an die Stelle, an der bx_affiliate_parent_id gesetzt wird
    //    (Signup/Admin) -- nicht in die Provisionsberechnung. Ob das Modul
    //    überhaupt als Tier-System arbeitet, entscheidet ausschließlich
    //    BX_AFFILIATE_USE_TIER (siehe $bx_max_levels oben).
    $bx_upline_query_raw = "
      WITH RECURSIVE affiliate_upline AS (
        SELECT
            ap.bx_affiliate_id,
            ap.bx_affiliate_parent_id,
            ap.bx_affiliate_commission_percent,
            0 AS depth
        FROM " . TABLE_BX_AFFILIATE_PARTNER . " ap
        WHERE ap.bx_affiliate_id = " . (int)$bx_affiliate_direct_id . "

        UNION ALL

        SELECT
            parent.bx_affiliate_id,
            parent.bx_affiliate_parent_id,
            parent.bx_affiliate_commission_percent,
            child.depth + 1 AS depth
        FROM " . TABLE_BX_AFFILIATE_PARTNER . " parent
        INNER JOIN affiliate_upline child
            ON parent.bx_affiliate_id = child.bx_affiliate_parent_id
           AND child.depth < " . (int)$bx_max_levels . "
      )
      SELECT bx_affiliate_id, bx_affiliate_commission_percent, depth
      FROM affiliate_upline
      ORDER BY depth ASC";

    $bx_upline_query = xtc_db_query($bx_upline_query_raw);

    $bx_browser = xtc_db_input(substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 100));
    $bx_ip      = xtc_db_input(substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45));

    while ($bx_partner = xtc_db_fetch_array($bx_upline_query)) {
      $bx_level = (int)$bx_partner['depth'];

      if ($bx_level === 0) {
        // Direkter Affiliate: individueller Satz (falls aktiviert) sonst Standardsatz
        $bx_use_individual = defined('BX_AFFILIATE_INDIVIDUAL_PERCENTAGE')
          && BX_AFFILIATE_INDIVIDUAL_PERCENTAGE === 'True';
        $bx_individual_rate = (float)$bx_partner['bx_affiliate_commission_percent'];

        $bx_rate = ($bx_use_individual && $bx_individual_rate > 0)
          ? $bx_individual_rate
          : (defined('BX_AFFILIATE_PERCENT') ? (float)BX_AFFILIATE_PERCENT : 0.0);
      } else {
        // Tier-Ebene 1..n: gestaffelter Satz aus BX_AFFILIATE_TIER_PERCENTAGE
        if (!$bx_use_tier || !isset($bx_tier_rates[$bx_level - 1])) {
          continue;
        }
        $bx_rate = (float)$bx_tier_rates[$bx_level - 1];
      }

      if ($bx_rate <= 0) {
        continue;
      }

      $bx_commission = round(($bx_net_amount * $bx_rate) / 100, 2);

      xtc_db_query(
        "INSERT IGNORE INTO " . TABLE_BX_AFFILIATE_SALES . "
         (bx_affiliate_id, bx_affiliate_date, bx_affiliate_browser, bx_affiliate_ipaddress,
          bx_affiliate_orders_id, bx_affiliate_value, bx_affiliate_payment,
          bx_affiliate_clickthroughs_id, bx_affiliate_billing_status, bx_affiliate_payment_date,
          bx_affiliate_payment_id, bx_affiliate_percent, bx_affiliate_salesman, bx_affiliate_level)
         VALUES (
          " . (int)$bx_partner['bx_affiliate_id'] . ",
          NOW(),
          '" . $bx_browser . "',
          '" . $bx_ip . "',
          " . (int)$orders_id . ",
          " . number_format($bx_net_amount, 2, '.', '') . ",
          " . number_format($bx_commission, 2, '.', '') . ",
          0,
          0,
          NULL,
          0,
          " . number_format($bx_rate, 2, '.', '') . ",
          " . (int)$bx_affiliate_direct_id . ",
          " . $bx_level . "
         )"
      );
    }

    // Tracking-Cookie/-Session nach erfolgreichem Kauf zurücksetzen
    setcookie('bx_affiliate_id', '', time() - 3600, '/');
    unset($_SESSION['bx_affiliate_id']);
  }
}

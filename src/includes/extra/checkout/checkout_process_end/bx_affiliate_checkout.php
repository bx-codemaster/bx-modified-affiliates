<?php
// /includes/extra/checkout/checkout_process_end/bx_affiliate_checkout.php
// modified stellt in dieser Umgebung oft die $orders_id und das $order-Objekt bereit

$affiliate_id = isset($_COOKIE['bx_affiliate_id']) ? (int)$_COOKIE['bx_affiliate_id'] : (isset($_SESSION['bx_affiliate_id']) ? (int)$_SESSION['bx_affiliate_id'] : 0);

if ($affiliate_id > 0 && isset($orders_id)) {
    // 1. Instanz deines Tree-Managers laden
    if (!class_exists('bx_nested_set')) {
        // Pfad zu deiner Klassendatei anpassen
        require_once(DIR_FS_DOCUMENT_ROOT . 'includes/classes/bx_nested_set.php'); 
    }
    $treeManager = new bx_nested_set();
    
    // 2. Definiere die Provisionsstufen (Diese Werte kommen später aus der Konfiguration)
    // Ebene 1 (Direkt): 10%, Ebene 2: 5%, Ebene 3: 2%
    $rates = [1 => 10.00, 2 => 5.00, 3 => 2.00]; 
    $max_levels = count($rates);
    
    // 3. Upline bestimmen
    // Wir fügen den ausführenden Affiliate temporär vorne an, da getUpline() nur die Elternelemente holt
    $direct_partner = xtc_db_query("SELECT bx_affiliate_id FROM bx_affiliate_partner WHERE bx_affiliate_id = " . $affiliate_id);
    if (xtc_db_num_rows($direct_partner) > 0) {
        
        $upline = $treeManager->getUpline($affiliate_id, $max_levels - 1);
        
        // Den direkten Partner als Ebene 1 an den Anfang setzen
        array_unshift($upline, ['bx_affiliate_id' => $affiliate_id, 'mlm_level' => 1]);
        
        // 4. Bestellwert ermitteln (Netto-Warenwert ohne Versand/Steuer ist Best Practice)
        $order_total_query = xtc_db_query("SELECT value FROM orders_total WHERE orders_id = " . (int)$orders_id . " AND class = 'ot_subtotal'");
        $order_total = xtc_db_fetch_array($order_total_query);
        $net_amount = (float)$order_total['value'];
        
        // 5. Schleife durch die berechtigten Ebenen
        foreach ($upline as $partner) {
            $level = $partner['mlm_level'];
            if (isset($rates[$level])) {
                $commission_percentage = $rates[$level];
                $commission_amount = ($net_amount * $commission_percentage) / 100;
                
                // In die von dir geplante Provisions-Tabelle schreiben (Beispiel-INSERT)
                xtc_db_query("
                    INSERT INTO bx_affiliate_sales 
                    (orders_id, bx_affiliate_id, mlm_level, commission_amount, date_created, status) 
                    VALUES ({$orders_id}, " . (int)$partner['bx_affiliate_id'] . ", {$level}, {$commission_amount}, NOW(), 'pending')
                ");
            }
        }
        
        // Nach erfolgreichem Kauf Tracking-Cookie optional löschen
        setcookie('bx_affiliate_id', '', time() - 3600, '/');
        unset($_SESSION['bx_affiliate_id']);
    }
}

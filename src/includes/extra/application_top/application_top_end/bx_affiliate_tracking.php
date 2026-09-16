<?php
// /includes/extra/application_top/application_top_end/bx_affiliate_tracking.php

if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $ref_id = (int)$_GET['ref'];
    
    // Validieren, ob der Partner existiert
    $check_query = xtc_db_query("SELECT bx_affiliate_id FROM bx_affiliate_partner WHERE bx_affiliate_id = " . $ref_id);
    if (xtc_db_num_rows($check_query) > 0) {
        // Cookie für 30 Tage setzen (Hier ggf. Consent-Manager-Prüfung vorschalten)
        setcookie('bx_affiliate_id', $ref_id, time() + (3600 * 24 * 30), '/');
        $_SESSION['bx_affiliate_id'] = $ref_id; // Backup in der Session
    }
}

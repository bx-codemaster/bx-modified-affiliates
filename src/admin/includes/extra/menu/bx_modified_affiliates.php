<?php
/**
 * BX Modified Affiliates - Admin Menu Configuration
 *
 * Registers the Affiliates module menu entry in the admin backend under the "Customers" section.
 * Provides multi-language menu labels (German/English) and integrates with the modified
 * shop's admin navigation system.
 *
 * @package    BX_Modified_Affiliates
 * @subpackage Admin_Menu
 * @version    1.0.0
 * @author     BENAX (www.benax.de)
 * @copyright  Copyright (c) 2025-2026 BENAX
 * @license    GPL-2.0-or-later
 * @since      1.0.0
 *
 * ┌─────────────────────────────────────────────────────────────────────────────┐
 * │ MENU INTEGRATION                                                            │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ • Location: BOX_HEADING_CUSTOMERS (Customers section)                       │
 * │ • Access Control: admin_access_name = 'bx_modified_affiliates'              │
 * │ • Target File: BX_FILENAME_AFFILIATES (bx_modified_affiliates.php)          │
 * │ • Multi-Language: DE = "Partnerprogramm" / EN = "Affiliates"                │
 * └─────────────────────────────────────────────────────────────────────────────┘
 */

defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

switch ($_SESSION['language_code']) {
  case 'de':
    define('MODULE_MODIFIED_AFFILIATES_MENU_TITLE','BX Affiliates');
    break;
  default:
    define('MODULE_MODIFIED_AFFILIATES_MENU_TITLE','BX Affiliates');
    break;
}

$add_contents[BOX_HEADING_TOOLS][] = array( 
    'admin_access_name' 	=> 'bx_modified_affiliates', 
    'filename' 				    => 'bx_modified_affiliates.php',
    'boxname' 				    => MODULE_MODIFIED_AFFILIATES_MENU_TITLE,
    'parameters' 			    => '', 
    'ssl' 					      => ''
  );
  
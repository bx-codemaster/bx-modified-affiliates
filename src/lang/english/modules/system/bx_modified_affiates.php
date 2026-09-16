<?php
/** 
 * BX Modified Affiliates - English System Module Texts
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

  $description = '
<details class="bxac-card">
  <summary class="bxac-summary" style="list-style: none;">
  <span class="bxac-arrow">▸</span>
  ' . xtc_image(DIR_WS_ICONS.'heading/bx_modified_affiliates.png', 'BX Modified Affiliates', '', '', 'style="max-height: 32px; margin: 2px;"') . '
  <span class="bxac-title">BX Modified Affiliates</span>
  </summary>
  <div class="bxac-body">
    <h3 style="margin-top: 0;">Professional Management Tool</h3>
    <p>A tool for Modified eCommerce shop software that simplifies the management of affiliate programs. With a modern interface, you can efficiently manage affiliates, commissions, and payments.</p>';

  if (basename($_SERVER['PHP_SELF']) == 'module_export.php' && !defined('MODULE_MODIFIED_AFFILIATES_STATUS') ) {
    $description .= '<p><a class="button btnbox but_red" style="text-align:center;" onclick="return confirmLink(\'Delete all files?\', \'\' ,this);" href="'.xtc_href_link(FILENAME_MODULE_EXPORT, 'set=system&module=bx_modified_affiliates&action=custom').'">Delete all module files</a></p>';
  }
  $description .= '</div></details>';

  define('MODULE_MODIFIED_AFFILIATES_TEXT_DESC', $description);

  define('MODULE_MODIFIED_AFFILIATES_STATUS_TITLE', 'Module active?');
  define('MODULE_MODIFIED_AFFILIATES_STATUS_DESC', 'Should the module be displayed?');

  define('MODULE_MODIFIED_AFFILIATES_SORT_ORDER_TITLE', 'Sort order');
  define('MODULE_MODIFIED_AFFILIATES_SORT_ORDER_DESC', 'Display order. Smallest number is displayed first.');
  define('MODULE_MODIFIED_AFFILIATES_VERSION_TITLE', 'Module version');
  define('MODULE_MODIFIED_AFFILIATES_VERSION_DESC', 'Current version of the module.');
  define('MODULE_MODIFIED_AFFILIATES_CONFIG_ID_TITLE', 'Configuration ID');
  define('MODULE_MODIFIED_AFFILIATES_CONFIG_ID_DESC', 'ID of the configuration used to manage the module.');
  define('BX_AFFILIATE_EMAIL_ADDRESS_TITLE', 'Email address for affiliate notifications');
  define('BX_AFFILIATE_EMAIL_ADDRESS_DESC', 'Email address to which notifications about affiliate activities should be sent.');
  define('BX_AFFILIATE_PERCENT_TITLE', 'Default commission rate (%)');
  define('BX_AFFILIATE_PERCENT_DESC', 'The default percentage used to calculate affiliate commissions.');
  define('BX_AFFILIATE_THRESHOLD_TITLE', 'Commission threshold');
  define('BX_AFFILIATE_THRESHOLD_DESC', 'The minimum amount that must be reached before a payout is made to the affiliate.');
  define('BX_AFFILIATE_COOKIE_LIFETIME_TITLE', 'Cookie lifetime (in days)');
  define('BX_AFFILIATE_COOKIE_LIFETIME_DESC', 'The number of days an affiliate cookie remains on the user\'s computer before it expires.');
  define('BX_AFFILIATE_BILLING_TIME_TITLE', 'Billing time');
  define('BX_AFFILIATE_BILLING_TIME_DESC', 'The time at which affiliate payments are made.');
  define('BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS_TITLE', 'Minimum order status for payments');
  define('BX_AFFILIATE_PAYMENT_ORDER_MIN_STATUS_DESC', 'The minimum order status that must be reached before an affiliate payment is triggered.');
define('BX_AFFILIATE_USE_BANK_TITLE', 'Use bank transfer for payments?');
  define('BX_AFFILIATE_USE_BANK_DESC', 'Indicate whether bank transfer should be used as the payment method for affiliate payments.');
  define('BX_AFFILIATE_USE_PAYPAL_TITLE', 'Use PayPal for payments?');
  define('BX_AFFILIATE_USE_PAYPAL_DESC', 'Indicate whether PayPal should be used as the payment method for affiliate payments.');
  define('BX_AFFILIATE_PAYPAL_EMAIL_ADDRESS_TITLE', 'PayPal email address');
  define('BX_AFFILIATE_PAYPAL_EMAIL_ADDRESS_DESC', 'The email address associated with the PayPal account to be used for affiliate payments.');
  define('BX_AFFILIATE_INDIVIDUAL_PERCENTAGE_TITLE', 'Individual commission rate for affiliates');
  define('BX_AFFILIATE_INDIVIDUAL_PERCENTAGE_DESC', 'Indicate whether affiliates can have individual commission rates.');
  define('BX_AFFILIATE_USE_TIER_TITLE', 'Use tiered commissions?');
  define('BX_AFFILIATE_USE_TIER_DESC', 'Indicate whether tiered commissions should be used for affiliates.');
  define('BX_AFFILIATE_TIER_LEVELS_TITLE', 'Number of tiers');
  define('BX_AFFILIATE_TIER_LEVELS_DESC', 'The number of tiers to be used for tiered commissions.');
  define('BX_AFFILIATE_TIER_PERCENTAGE_TITLE', 'Tier percentage values');
  define('BX_AFFILIATE_TIER_PERCENTAGE_DESC', 'The percentages to be used for each tier of the tiered commissions, separated by commas (e.g., 5,3,2 for three tiers).');
  
  // Custom Deinstallation Messages
  define('MODULE_MODIFIED_AFFILIATES_TEXT_FILES_DELETED', 'Successfully deleted:');
  define('MODULE_MODIFIED_AFFILIATES_TEXT_FILES_FAILED', 'Error deleting (please remove manually via FTP):');
  define('MODULE_MODIFIED_AFFILIATES_TEXT_SUCCESSFULLY_REMOVED', 'BX Modified Affiliates was successfully removed!');
  define('MODULE_MODIFIED_AFFILIATES_TEXT_REMOVAL_INCOMPLETE', 'BX Modified Affiliates was partially removed. Please check the error messages and delete the remaining files manually via FTP.');

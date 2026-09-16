<?php
/**
 * BX Modified Affiliates Module - Affiliate Management System
 * 
 * Professional affiliate management system for modified eCommerce with comprehensive
 * features for handling affiliate programs and commissions.
 *
 * @package    BX Modified Affiliates
 * @version    1.0.0
 * @author     See credits below
 * @copyright  2006-2026
 * @license    GNU General Public License v2.0
 * @link       https://www.modified-shop.org
 *
 */

  include('includes/application_top.php');
  require_once (DIR_FS_INC.'get_customers_gender.inc.php');

  //display per page
  defined('MAX_DISPLAY_LIST_AFFILIATES') or define('MAX_DISPLAY_LIST_AFFILIATES', 10);
  $cfg_max_display_results_key = 'MAX_DISPLAY_LIST_AFFILIATES';
  $page_max_display_results    = (int) xtc_cfg_save_max_display_results($cfg_max_display_results_key);

  $actionWithList = array('edit', 'delete', 'details', 'save', 'update');

  $action  = (isset($_GET['action']) && in_array($_GET['action'], $actionWithList) ? $_GET['action'] : null);
  $acID    = (isset($_GET['acID'])    ? (int)$_GET['acID']   : null);
  $page    = (isset($_GET['page'])    ? (int)$_GET['page']   : 1);
  $ajax    = (isset($_GET['ajax'])    ? trim($_GET['ajax']) : null);
  $search  = (isset($_GET['search'])  ? trim($_GET['search']) : '');
  $sorting = (isset($_GET['sorting']) ? $_GET['sorting']   : '');

  if (isset($_GET['ajax']) && ($_GET['ajax'] === 'affiliate_details' || $_GET['ajax'] === 'bx_affiliate_details')) {
    if (!isset($currencies) || !is_object($currencies)) {
      require_once(DIR_WS_CLASSES . 'currencies.php');
      $currencies = new currencies();
    }

    $oldday = (new DateTime())->modify('-' . BX_AFFILIATE_BILLING_TIME . ' days')->format('Y-m-d');
    $affiliate_query_raw = "
    WITH RECURSIVE affiliate_tree AS (
      -- 1. Anker: Startet beim angefragten Partner (oder dessen Root-Knoten)
      SELECT 
          ap.bx_affiliate_id,
          ap.bx_affiliate_parent_id,
          ap.bx_affiliate_lastname,
          ap.bx_affiliate_firstname,
          ap.bx_affiliate_commission_percent,
          ap.bx_affiliate_date_account_created,
          ap.bx_affiliate_date_account_last_modified,
          ap.bx_affiliate_date_of_last_logon,
          ap.bx_affiliate_number_of_logons,
          ap.bx_affiliate_tiers_allowed,
          ap.bx_affiliate_country_id,
          0 AS depth,
          CAST(LPAD(ap.bx_affiliate_id, 10, '0') AS CHAR(500)) AS path
      FROM " . TABLE_BX_AFFILIATE_PARTNER . " ap
      WHERE ap.bx_affiliate_id = '" . (int)$acID . "'

      UNION ALL

      -- 2. Rekursion: Lädt alle untergeordneten Partner (Downline)
      SELECT 
          child.bx_affiliate_id,
          child.bx_affiliate_parent_id,
          child.bx_affiliate_lastname,
          child.bx_affiliate_firstname,
          child.bx_affiliate_commission_percent,
          child.bx_affiliate_date_account_created,
          child.bx_affiliate_date_account_last_modified,
          child.bx_affiliate_date_of_last_logon,
          child.bx_affiliate_number_of_logons,
          child.bx_affiliate_tiers_allowed,
          child.bx_affiliate_country_id,
          parent.depth + 1 AS depth,
          CONCAT(parent.path, '/', LPAD(child.bx_affiliate_id, 10, '0')) AS path
      FROM " . TABLE_BX_AFFILIATE_PARTNER . " child
      INNER JOIN affiliate_tree parent ON child.bx_affiliate_parent_id = parent.bx_affiliate_id
    )
    SELECT 
        tree.bx_affiliate_id,
        tree.bx_affiliate_lastname,
        tree.bx_affiliate_firstname,
        tree.bx_affiliate_commission_percent,
        tree.bx_affiliate_date_account_created AS date_account_created,
        tree.bx_affiliate_date_account_last_modified AS date_account_last_modified,
        tree.bx_affiliate_date_of_last_logon AS date_last_logon,
        tree.bx_affiliate_number_of_logons AS number_of_logons,
        tree.bx_affiliate_tiers_allowed,
        tree.depth,
        tree.path,
        c.countries_name,
        COALESCE(sales_summary.sales_count, 0) AS sales_count,
        sales_summary.sales_total,
        sales_summary.commission_total,
        COALESCE(open_commissions.open_commission, 0) AS open_commission
    FROM affiliate_tree tree
    LEFT JOIN " . TABLE_COUNTRIES . " c 
        ON c.countries_id = tree.bx_affiliate_country_id
    LEFT JOIN (
        SELECT 
            a.bx_affiliate_id,
            COUNT(*) AS sales_count,
            SUM(a.bx_affiliate_value) AS sales_total,
            SUM(a.bx_affiliate_payment) AS commission_total
        FROM " . TABLE_BX_AFFILIATE_SALES . " a
        JOIN " . TABLE_ORDERS . " o ON a.bx_affiliate_orders_id = o.orders_id
        WHERE 1=1 " . bx_affiliate_build_orders_status_query() . "
        GROUP BY a.bx_affiliate_id
    ) sales_summary ON sales_summary.bx_affiliate_id = tree.bx_affiliate_id
    LEFT JOIN (
        SELECT 
            a.bx_affiliate_id,
            SUM(a.bx_affiliate_payment) AS open_commission
        FROM " . TABLE_BX_AFFILIATE_SALES . " a
        JOIN " . TABLE_ORDERS . " o ON a.bx_affiliate_orders_id = o.orders_id
        WHERE a.bx_affiliate_billing_status != 1
          AND a.bx_affiliate_date <= '" . $oldday . "'
          " . bx_affiliate_build_orders_status_query() . "
        GROUP BY a.bx_affiliate_id
    ) open_commissions ON open_commissions.bx_affiliate_id = tree.bx_affiliate_id
    ORDER BY tree.path;";


    $affiliate_query = xtc_db_query($affiliate_query_raw);
    $affiliate = xtc_db_fetch_array($affiliate_query);

    header('Content-Type: application/json; charset=utf-8');
    if (!$affiliate) {
      echo json_encode(array(
        'success' => false,
        'csrf_name'  => $_SESSION['CSRFName'] ?? '',
        'csrf_token' => $_SESSION['CSRFToken'] ?? ''
      ));
      exit;
    }

    $open_commission = (float)$affiliate['open_commission'];

    $html= '<div class="bx-headboard bx-headboard--secondary"><strong>' . xtc_output_string($affiliate['bx_affiliate_firstname']) . ' ' . xtc_output_string($affiliate['bx_affiliate_lastname']) . '</strong></div>' . PHP_EOL
        . '  <dl class="bx-dl-horizontal"><div class="bx-dl-item"><dt>' . TEXT_INFO_DATE_ACCOUNT_CREATED . '</dt><dd>' . xtc_date_short($affiliate['date_account_created']) . '</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_DATE_ACCOUNT_LAST_MODIFIED . '</dt><dd>' . xtc_date_short($affiliate['date_account_last_modified']) . '</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_DATE_LAST_LOGON . '</dt><dd>' . xtc_date_short($affiliate['date_last_logon']) . '</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_NUMBER_OF_LOGONS . '</dt><dd>' . (int)$affiliate['number_of_logons'] . '</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_COUNTRY . '</dt><dd>' . xtc_output_string($affiliate['countries_name']) . '</dd></div>' . PHP_EOL
        . '  </dl>' . PHP_EOL
        . '  <dl class="bx-dl-horizontal">' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_COMMISSION_PERCENT . '</dt><dd>' . xtc_output_string($affiliate['bx_affiliate_commission_percent']) . '%</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_TIERS_ALLOWED . '</dt><dd>' . ((int)$affiliate['bx_affiliate_tiers_allowed'] === 1 ? TEXT_INFO_YES : TEXT_INFO_NO) . '</dd></div>' . PHP_EOL
        . '  </dl>' . PHP_EOL
        . '  <dl class="bx-dl-horizontal">' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_SALES_COUNT . '</dt><dd>' . (int)$affiliate['sales_count'] . '</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_SALES_TOTAL . '</dt><dd>' . $currencies->format((float)$affiliate['sales_total']) . '</dd></div>' . PHP_EOL
        . '  </dl>' . PHP_EOL
        . '  <dl class="bx-dl-horizontal">' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_COMMISSION_TOTAL . '</dt><dd>' . $currencies->format((float)$affiliate['commission_total']) . '</dd></div>' . PHP_EOL
        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_OPEN_COMMISSION . '</dt><dd>' . $currencies->format((float)$affiliate['open_commission']) . '</dd></div>' . PHP_EOL
        . '  </dl>' . PHP_EOL;

    echo json_encode(array(
      'success'    => true, 
      'html'       => $html,
      'csrf_name'  => $_SESSION['CSRFName'] ?? '',
      'csrf_token' => $_SESSION['CSRFToken'] ?? ''
    ));
    exit;
  }

  if (!isset($currencies) || !is_object($currencies)) {
    require_once(DIR_WS_CLASSES . 'currencies.php');
    $currencies = new currencies();
  }
  
  switch ($action) {
    case 'edit':

      break; // case 'edit'

    default:
      if (xtc_not_null($sorting)) {
        switch ($sorting) {
          case 'affiliate-id':
            $affiliate_sort = 'ap.bx_affiliate_id ASC';
            break;
          case 'affiliate-id-desc':
            $affiliate_sort = 'ap.bx_affiliate_id DESC';
            break;
          case 'lastname':
            $affiliate_sort = 'ap.bx_affiliate_lastname ASC';
            break;
          case 'lastname-desc':
            $affiliate_sort = 'ap.bx_affiliate_lastname DESC';
            break;
          case 'firstname':
            $affiliate_sort = 'ap.bx_affiliate_firstname ASC';
            break;
          case 'firstname-desc':
            $affiliate_sort = 'ap.bx_affiliate_firstname DESC';
            break;
          case 'commission':
            $affiliate_sort = 'ap.bx_affiliate_commission_percent ASC';
            break;
          case 'commission-desc':
            $affiliate_sort = 'ap.bx_affiliate_commission_percent DESC';
            break;
          case 'open-commission':
            $affiliate_sort = 'ap.bx_affiliate_payment ASC';
            break;
          case 'open-commission-desc':
            $affiliate_sort = 'ap.bx_affiliate_payment DESC';
            break;
          case 'account':
            $affiliate_sort = 'ap.bx_affiliate_email_address ASC';
            break;
          case 'account-desc':
            $affiliate_sort = 'ap.bx_affiliate_email_address DESC';
            break;
          case 'userhomepage':
            $affiliate_sort = 'ap.bx_affiliate_homepage ASC';
            break;
          case 'userhomepage-desc':
            $affiliate_sort = 'ap.bx_affiliate_homepage DESC';
            break;
          default:
            $affiliate_sort = 'ap.bx_affiliate_id DESC';
            break;
        }
      } else {
        $affiliate_sort = 'ap.bx_affiliate_id ASC';
      }
      break; // default
  }

  require (DIR_WS_INCLUDES.'head.php');
?>
</head>
  <body>
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->
    <!-- body //-->
    <table class="tableBody">
      <tr>
        <?php //left_navigation
        if (USE_ADMIN_TOP_MENU == 'false') {
          echo '<td class="columnLeft2">'.PHP_EOL;
          echo '<!-- left_navigation //-->'.PHP_EOL;       
          require_once(DIR_WS_INCLUDES . 'column_left.php');
          echo '<!-- left_navigation eof //-->'.PHP_EOL; 
          echo '</td>'.PHP_EOL;      
        }
        ?>
        <!-- body_text //-->
        <td class="boxCenter">
      
          <div class="pageHeadingImage">
            <?php echo xtc_image(DIR_WS_ICONS.'heading/bx_modified_affiliates.png', BX_AFFILIATES_HEADING_TITLE, '', '', 'style="height:100%;"'); ?>
          </div>
          <div class="pageHeading pdg2 flt-l">
            <?php echo BX_AFFILIATES_HEADING_TITLE; ?>
            <div class="main pdg2"><?php echo BX_AFFILIATES_HEADING_PARTNER_TITLE; ?></div> 
          </div>
          <div class="clear"></div> 
<?php 
switch ($action) {
  case 'edit': 
    $affiliate_query = xtc_db_query("SELECT * FROM " . TABLE_BX_AFFILIATE_PARTNER . " WHERE bx_affiliate_id = '" . $acID . "'");
    $affiliate       = xtc_db_fetch_array($affiliate_query);
    $aInfo           = new objectInfo($affiliate);

echo '<pre>';
print_r($aInfo);
echo '</pre>';
?>
          <div class="bx-grid">
            <section class="bx-main-content">
              <div class="bx-headboard">
                <div class="bx-headboard-title">
                  <?php echo '<strong>' . BX_AFFILIATES_HEADING_TITLE . '</strong> - ' . BX_AFFILIATES_TEXT_EDIT_PARTNER; ?>
                </div>
              </div>

              <article class="bx-panel">

                  <table>
                    <tr>
                      <?php echo xtc_draw_form('affiliate', BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('action')) . 'action=update', 'post', 'onsubmit="return check_form();"'); ?>
                      <td class="formAreaTitle"><?php echo CATEGORY_PERSONAL; ?></td>
                    </tr>
                    <tr>
                      <td class="formArea">
                        <table border="0" cellspacing="2" cellpadding="2">
              <?php
                  if (ACCOUNT_GENDER == 'true') {
              ?>
                        <tr>
                          <td class="main"><?php echo ENTRY_GENDER; ?></td>
                          <td class="main">
                            <?php
                            echo xtc_draw_radio_field('bx_affiliate_gender', 'm', false, $aInfo->bx_affiliate_gender) . '&nbsp;&nbsp;'
                             . BX_MALE . '&nbsp;&nbsp;' 
                             . xtc_draw_radio_field('bx_affiliate_gender', 'f', false, $aInfo->bx_affiliate_gender) . '&nbsp;&nbsp;' . BX_FEMALE
                             . xtc_draw_radio_field('bx_affiliate_gender', 'd', false, $aInfo->bx_affiliate_gender) . '&nbsp;&nbsp;' . BX_DIVERSE
                             ; ?></td>
                        </tr>
              <?php
                  }
              ?>
                        <tr>
                          <td class="main"><?php echo ENTRY_FIRST_NAME; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_firstname', $aInfo->bx_affiliate_firstname, 'maxlength="32"', true); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo ENTRY_LAST_NAME; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_lastname', $aInfo->bx_affiliate_lastname, 'maxlength="32"', true); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_email_address', $aInfo->bx_affiliate_email_address, 'maxlength="96"', true); ?></td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td><?php echo xtc_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
              <?php
                if (BX_AFFILIATE_INDIVIDUAL_PERCENTAGE === 'True') {
              ?>
                    <tr>
                      <td class="formAreaTitle"><?php echo TABLE_HEADING_COMMISSION; ?></td>
                    </tr>
                    <tr>
                      <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_COMMISSION; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_commission_percent', $aInfo->bx_affiliate_commission_percent, 'maxlength="5"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_TIERS_ALLOWED; ?></td>
                          <td class="main"><?php echo xtc_draw_checkbox_field('bx_affiliate_tiers_allowed', '', $aInfo->bx_affiliate_tiers_allowed); ?></td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td><?php echo xtc_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
              <?php
                  }
              ?>
                    <tr>
                      <td class="formAreaTitle"><?php echo CATEGORY_COMPANY; ?></td>
                    </tr>
                    <tr>
                      <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
                        <tr>
                          <td class="main"><?php echo ENTRY_COMPANY; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_company', $aInfo->bx_affiliate_company, 'maxlength="32"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_COMPANY_TAXID; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_company_taxid', $aInfo->bx_affiliate_company_taxid, 'maxlength="64"'); ?></td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td><?php echo xtc_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <tr>
                      <td class="formAreaTitle"><?php echo BX_CATEGORY_PAYMENT_DETAILS; ?></td>
                    </tr>
                    <tr>
                      <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
              <?php
                if (BX_AFFILIATE_USE_PAYPAL == 'true') {
              ?>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_PAYMENT_PAYPAL; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_payment_paypal', $aInfo->bx_affiliate_payment_paypal, 'maxlength="64"'); ?></td>
                        </tr>
              <?php
                }
                if (BX_AFFILIATE_USE_BANK == 'true') {
              ?>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_PAYMENT_BANK_NAME; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_payment_bank_name', $aInfo->bx_affiliate_payment_bank_name, 'maxlength="64"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_PAYMENT_BANK_BRANCH_NUMBER; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_payment_bank_branch_number', $aInfo->bx_affiliate_payment_bank_branch_number, 'maxlength="64"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_PAYMENT_BANK_SWIFT_CODE; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_payment_bank_swift_code', $aInfo->bx_affiliate_payment_bank_swift_code, 'maxlength="64"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_PAYMENT_BANK_ACCOUNT_NAME; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_payment_bank_account_name', $aInfo->bx_affiliate_payment_bank_account_name, 'maxlength="64"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo BX_ENTRY_AFFILIATE_PAYMENT_BANK_ACCOUNT_NUMBER; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_payment_bank_account_number', $aInfo->bx_affiliate_payment_bank_account_number, 'maxlength="64"'); ?></td>
                        </tr>
              <?php
                }
              ?>
                      </table></td>
                    </tr>
                    <tr>
                      <td><?php echo xtc_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <tr>
                      <td class="formAreaTitle"><?php echo CATEGORY_ADDRESS; ?></td>
                    </tr>
                    <tr>
                      <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
                        <tr>
                          <td class="main"><?php echo ENTRY_STREET_ADDRESS; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_street_address', $aInfo->bx_affiliate_street_address, 'maxlength="64"', true); ?></td>
                        </tr>
              <?php
                if (ACCOUNT_SUBURB == 'true') {
              ?>
                        <tr>
                          <td class="main"><?php echo ENTRY_SUBURB; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_suburb', $aInfo->bx_affiliate_suburb, 'maxlength="64"', false); ?></td>
                        </tr>
              <?php
                }
              ?>
                        <tr>
                          <td class="main"><?php echo ENTRY_CITY; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_city', $aInfo->bx_affiliate_city, 'maxlength="32"', true); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo ENTRY_POST_CODE; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_postcode', $aInfo->bx_affiliate_postcode, 'maxlength="8"', true); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo ENTRY_COUNTRY; ?></td>
                          <td class="main"><?php echo xtc_draw_pull_down_menu('bx_affiliate_country_id', xtc_get_countries(), $aInfo->bx_affiliate_country_id, 'onChange="update_zone(this.form);"'); ?></td>
                        </tr>
              <?php
                  if (ACCOUNT_STATE == 'true') {
              ?>
                        <tr>
                          <td class="main"><?php echo ENTRY_STATE; ?></td>
                          <td class="main"><?php echo xtc_draw_pull_down_menu('bx_affiliate_zone_id', xtc_prepare_country_zones_pull_down($aInfo->bx_affiliate_country_id), $aInfo->bx_affiliate_zone_id, 'onChange="resetStateText(this.form);"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main">&nbsp;</td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_state', $aInfo->bx_affiliate_state, 'maxlength="32" onChange="resetZoneSelected(this.form);"'); ?></td>
                        </tr>
              <?php
                  }
              ?>
                      </table></td>
                    </tr>
                    <tr>
                      <td><?php echo xtc_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <tr>
                      <td class="formAreaTitle"><?php echo CATEGORY_CONTACT; ?></td>
                    </tr>
                    <tr>
                      <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
                        <tr>
                          <td class="main"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_telephone', $aInfo->bx_affiliate_telephone, 'maxlength="32"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo ENTRY_FAX_NUMBER; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_fax', $aInfo->bx_affiliate_fax, 'maxlength="32"'); ?></td>
                        </tr>
                        <tr>
                          <td class="main"><?php echo TABLE_HEADING_USERHOMEPAGE; ?></td>
                          <td class="main"><?php echo xtc_draw_input_field('bx_affiliate_homepage', $aInfo->bx_affiliate_homepage, 'maxlength="64"', true); ?></td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td><?php echo xtc_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <tr>
                      <td align="right" class="main">
                        <input type="submit" class="button float_right" onClick="this.blur();" value="<?php echo BUTTON_UPDATE; ?>">
                        <?php echo ' <a class="button float_right" onClick="this.blur();" href="' . xtc_href_link(BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('action'))) .'">' . BUTTON_CANCEL . '</a>'; ?>
                      </td>
                    </tr></form>
                  </table>

              </article> <!-- .bx-panel //-->
            </section>

            <!-- Sidebar (Rechts) -->
             <aside class="bx-sidebar">
              <?php
              $gender_values = array(
                'm' => BX_MALE,
                'f' => BX_FEMALE,
                'd' => BX_DIVERSE,
              );
              $gender_option = '<option value="" disabled selected hidden>Bitte wählen...</option>'.PHP_EOL;

              foreach ($gender_values as $key => $value) {
                if($aInfo->bx_affiliate_gender == $key) {
                  $gender_option .= '<option value="' . $key . '" selected>' . $value . '</option>' . PHP_EOL;
                  continue;
                }
                $gender_option .= '<option value="' . $key . '">' . $value . '</option>' . PHP_EOL;
              }

              if(is_object($aInfo)) {
                echo '<div class="bx-headboard bx-headboard--secondary">'. PHP_EOL
                   . '<strong>' . $gender_values[$aInfo->bx_affiliate_gender] . ' ' . $aInfo->bx_affiliate_firstname . ' ' . $aInfo->bx_affiliate_lastname . '</strong>'
                   . '</div>';
                echo '<form action="/submit" method="POST">
                      <div class="bx-panel">
                        <div class="bx-panel-title">'.CATEGORY_PERSONAL.'</div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_gender">Anrede</label>
                          <div class="bx-select-wrapper">
                            <select id="bx_affiliate_gender" name="bx_affiliate_gender" class="bx-select" required>
                              ' . $gender_option . '
                            </select>
                          </div>
                        </div>

                        <div class="bx-form-group">
                          <label for="bx_affiliate_firstname">Vorname</label>
                          <input type="text" id="bx_affiliate_firstname" name="bx_affiliate_firstname" value="' . htmlspecialchars($aInfo->bx_affiliate_firstname) . '" class="bx-input" required placeholder="z. B. Max">
                        </div>

                        <div class="bx-form-group">
                          <label for="bx_affiliate_lastname">Nachname</label>
                          <input type="text" id="bx_affiliate_lastname" name="bx_affiliate_lastname" value="' . htmlspecialchars($aInfo->bx_affiliate_lastname) . '" class="bx-input" required placeholder="z. B. Mustermann">
                        </div>

                        <div class="bx-form-group">
                          <label for="bx_affiliate_email">E-Mail</label>
                          <input type="email" id="bx_affiliate_email" name="bx_affiliate_email_address" value="' . htmlspecialchars($aInfo->bx_affiliate_email_address) . '" class="bx-input" required placeholder="z. B. max.mustermann@example.com">
                        </div>
                      </div>';
                      
                      echo '<div class="bx-panel">
                        <div class="bx-panel-title">'.CATEGORY_COMPANY.'</div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_company">'.ENTRY_COMPANY.'</label>
                          <input type="text" id="bx_affiliate_company" name="bx_affiliate_company" value="' . htmlspecialchars($aInfo->bx_affiliate_company) . '" class="bx-input" required placeholder="z. B. Musterfirma GmbH">
                        </div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_company_taxid">'.BX_ENTRY_AFFILIATE_COMPANY_TAXID.'</label>
                          <input type="text" id="bx_affiliate_company_taxid" name="bx_affiliate_company_taxid" value="' . htmlspecialchars($aInfo->bx_affiliate_company_taxid) . '" class="bx-input" required placeholder="z. B. 123/456/7890">
                        </div>
                      </div>';

                      echo '<div class="bx-panel">
                        <div class="bx-panel-title">'.CATEGORY_ADDRESS.'</div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_street_address">'.ENTRY_STREET_ADDRESS.'</label>
                          <input type="text" id="bx_affiliate_street_address" name="bx_affiliate_street_address" value="' . htmlspecialchars($aInfo->bx_affiliate_street_address) . '" class="bx-input" required placeholder="z. B. Musterstraße 1">
                        </div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_postcode">'.ENTRY_POST_CODE.'</label>
                          <input type="text" id="bx_affiliate_postcode" name="bx_affiliate_postcode" value="' . htmlspecialchars($aInfo->bx_affiliate_postcode) . '" class="bx-input" required placeholder="z. B. 12345">
                        </div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_city">'.ENTRY_CITY.'</label>
                          <input type="text" id="bx_affiliate_city" name="bx_affiliate_city" value="' . htmlspecialchars($aInfo->bx_affiliate_city) . '" class="bx-input" required placeholder="z. B. Musterstadt">
                        </div>
                        <div class="bx-form-group">
                          <label for="bx_affiliate_country">'.ENTRY_COUNTRY.'</label>
                          <input type="text" id="bx_affiliate_country" name="bx_affiliate_country" value="' . htmlspecialchars($aInfo->bx_affiliate_country) . '" class="bx-input" required placeholder="z. B. Deutschland">
                        </div>
                      </div>';

                      echo '<button type="submit" class="bx-btn">Einstellungen speichern</button>
                      </div>
                      </form>';
              } else {
                echo '<div class="bx-headboard bx-headboard--secondary">'. PHP_EOL
                   . '<strong>' . BX_AFFILIATES_HEADING_TITLE . '</strong>'
                   . '</div>';
              }
              ?>
                <?php

                  $heading  = array();
                  $contents = array();

                  if(is_object($aInfo)) {
                    $heading[]  = array('text' => $gender_values[$aInfo->bx_affiliate_gender] . ' '
                                                . $aInfo->bx_affiliate_firstname . ' ' 
                                                . $aInfo->bx_affiliate_lastname);
                  
                    $contents[] = array('text' => xtc_draw_pull_down_menu('bx_affiliate_gender', get_customers_gender(), $aInfo->bx_affiliate_gender));
                  }

                  if ( (xtc_not_null($heading)) && (xtc_not_null($contents)) ) {
                    $box = new box;
                    echo $box->infoBox($heading, $contents);
                  }
                ?>
            </aside>
          </div>
<?php
    break; // case 'edit'
  default:
?>
          <div class="bx-grid">
            <section class="bx-main-content">
              <div class="bx-headboard bx-headboard--with-search">
                <div class="bx-headboard-title">
                  <?php echo '<strong>' . BX_AFFILIATES_HEADING_TITLE . '</strong> - ' . BX_AFFILIATES_TEXT_LIST_PARTNER; ?>
                </div>
                
                <search class="bx-headboard-search">
                  <?php echo xtc_draw_form('search', BX_FILENAME_AFFILIATES, '', 'get'); ?>
                    <label for="bx-search" class="visually-hidden"><?php echo BX_AFFILIATES_HEADING_TITLE_SEARCH; ?>:</label>
                    <input type="text" name="search" id="bx-search" class="bx-input-compact" placeholder="<?php echo BX_AFFILIATES_HEADING_TITLE_SEARCH; ?>..." value="<?php echo xtc_output_string($search); ?>">                    
                    <button type="submit" class="bx-icon-btn" title="<?php echo BX_AFFILIATES_HEADING_TITLE_SEARCH; ?>">
                      <div class="bx_svg_icon">✅</div>
                    </button>

                    <button type="button" class="bx-icon-btn" title="<?php echo BX_AFFILIATES_HEADING_TITLE_SEARCH; ?>">
                      <div class="bx_svg_icon">❎</div>
                    </button>
                  </form>
                </search>
              </div>

              <!--// TODO: Was ich in Version 2.0 mal brauchen könnte 
              <div class="main">
                <?php
                echo xtc_draw_pull_down_menu('search_field', array(
                  array('id' => 'bx_affiliate_id', 'text' => TABLE_HEADING_AFFILIATE_ID),
                  array('id' => 'lastname',     'text' => TABLE_HEADING_LASTNAME),
                  array('id' => 'firstname',    'text' => TABLE_HEADING_FIRSTNAME),
                  array('id' => 'commission',   'text' => TABLE_HEADING_COMMISSION),
                  array('id' => 'account',      'text' => TABLE_HEADING_ACCOUNT),
                  array('id' => 'userhomepage', 'text' => TABLE_HEADING_USERHOMEPAGE)
                ), '', 'class="bx-affiliates-select"');
                ?>
              </div>
              //-->

              <table class="bx-affiliates-table">
                <tr>
                  <th>
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_AFFILIATE_ID . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'affiliate-id')) . '</span>'; ?></div>
                  </th>
                  <th>
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_LASTNAME . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'lastname')) . '</span>'; ?></div>
                  </th>
                  <th>
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_FIRSTNAME . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'firstname')) . '</span>'; ?></div>
                  </th>
                  <th>
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_COMMISSION . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'commission')) . '</span>'; ?></div>
                  </th>
                  <th>                       
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_OPEN_COMMISSION . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'open-commission')) . '</span>'; ?></div>
                  </th>
                  <th>
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_ACCOUNT . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'account')) . '</span>'; ?></div>
                  </th>
                  <th>
                    <div class="container"><?php echo '<span>' . TABLE_HEADING_USERHOMEPAGE . '</span><span>' . str_replace('<br />', '', xtc_sorting(BX_FILENAME_AFFILIATES, 'userhomepage')) . '</span>'; ?></div>
                  </th>
                  <th>
                    <?php echo TABLE_HEADING_ACTION; ?>
                  </th>
                </tr>
                <?php
/* ANFANG */
                  $rows = ($page > 1) ? $page * $page_max_display_results - $page_max_display_results : 0;
                  $oldday = (new DateTime())->modify('-' . BX_AFFILIATE_BILLING_TIME . ' days')->format('Y-m-d');

                  // 1. Suche & Paginierungs-Query ermitteln
                  if (xtc_not_null($search)) {
                    $search_db = xtc_db_input($search);

                    // Findet Suchtreffer (auch Unterpartner) und ermittelt den obersten Root-Partner (Ebene 0)
                    $search_root_query_raw = "
                    WITH RECURSIVE search_tree AS (
                        SELECT 
                            bx_affiliate_id, 
                            bx_affiliate_parent_id, 
                            bx_affiliate_id AS root_id
                        FROM " . TABLE_BX_AFFILIATE_PARTNER . "
                        WHERE (CAST(bx_affiliate_id AS CHAR) LIKE '%" . $search_db . "%'
                           OR bx_affiliate_lastname LIKE '%" . $search_db . "%'
                           OR bx_affiliate_firstname LIKE '%" . $search_db . "%'
                           OR bx_affiliate_email_address LIKE '%" . $search_db . "%'
                           OR bx_affiliate_homepage LIKE '%" . $search_db . "%')

                        UNION ALL

                        SELECT 
                            parent.bx_affiliate_id, 
                            parent.bx_affiliate_parent_id, 
                            parent.bx_affiliate_id AS root_id
                        FROM " . TABLE_BX_AFFILIATE_PARTNER . " parent
                        INNER JOIN search_tree child ON child.bx_affiliate_parent_id = parent.bx_affiliate_id
                    )
                    SELECT DISTINCT root_id AS bx_affiliate_id
                    FROM search_tree
                    WHERE bx_affiliate_parent_id IS NULL OR bx_affiliate_parent_id = 0";

                    $matched_root_ids = array();
                    $search_root_query = xtc_db_query($search_root_query_raw);
                    while ($row = xtc_db_fetch_array($search_root_query)) {
                      $matched_root_ids[] = (int)$row['bx_affiliate_id'];
                    }

                    if (!empty($matched_root_ids)) {
                      $pagination_query_raw = "SELECT ap.bx_affiliate_id 
                                               FROM " . TABLE_BX_AFFILIATE_PARTNER . " ap 
                                               WHERE ap.bx_affiliate_id IN (" . implode(',', $matched_root_ids) . ") 
                                               ORDER BY ap.bx_affiliate_id ASC";
                    } else {
                      $pagination_query_raw = "SELECT ap.bx_affiliate_id 
                                               FROM " . TABLE_BX_AFFILIATE_PARTNER . " ap 
                                               WHERE 1=0 
                                               ORDER BY ap.bx_affiliate_id ASC";
                    }
                  } else {
                    // Standard ohne Suche: Alle Root-Partner
                    $pagination_query_raw = "SELECT ap.bx_affiliate_id 
                                             FROM " . TABLE_BX_AFFILIATE_PARTNER . " ap 
                                             WHERE (ap.bx_affiliate_parent_id IS NULL OR ap.bx_affiliate_parent_id = 0) 
                                             ORDER BY ap.bx_affiliate_id ASC";
                  }

                  // 2. PAGINIERUNG
                  $affiliates_query_numrows = 0;
                  $affiliates_split = new splitPageResults(
                    $page, 
                    $page_max_display_results, 
                    $pagination_query_raw, 
                    $affiliates_query_numrows, 
                    'ap.bx_affiliate_id'
                  );

                  // Root-IDs für die aktuelle Seite laden
                  $root_ids_page = array();
                  $page_roots_query = xtc_db_query($pagination_query_raw);
                  while ($root = xtc_db_fetch_array($page_roots_query)) {
                    $root_ids_page[] = (int)$root['bx_affiliate_id'];
                  }

                  // 3. HAUPTABFRAGE: Rekursiver Baumaufbau für die geladenen Root-Partner
                  if (!empty($root_ids_page)) {
                    $affiliates_query_raw = "
                    WITH RECURSIVE affiliate_tree AS (
                        SELECT 
                            ap.*,
                            0 AS depth,
                            CAST(LPAD(ap.bx_affiliate_id, 10, '0') AS CHAR(500)) AS path
                        FROM " . TABLE_BX_AFFILIATE_PARTNER . " ap
                        WHERE ap.bx_affiliate_id IN (" . implode(',', $root_ids_page) . ")

                        UNION ALL

                        SELECT 
                            child.*,
                            parent.depth + 1 AS depth,
                            CONCAT(parent.path, '/', LPAD(child.bx_affiliate_id, 10, '0')) AS path
                        FROM " . TABLE_BX_AFFILIATE_PARTNER . " child
                        INNER JOIN affiliate_tree parent ON child.bx_affiliate_parent_id = parent.bx_affiliate_id
                    )
                    SELECT 
                        tree.bx_affiliate_id,
                        tree.bx_affiliate_lastname,
                        tree.bx_affiliate_firstname,
                        tree.bx_affiliate_commission_percent,
                        tree.bx_affiliate_email_address,
                        tree.bx_affiliate_homepage,
                        tree.bx_affiliate_country_id,
                        tree.bx_affiliate_date_account_created AS date_account_created,
                        tree.bx_affiliate_date_account_last_modified AS date_account_last_modified,
                        tree.bx_affiliate_date_of_last_logon AS date_last_logon,
                        tree.bx_affiliate_number_of_logons AS number_of_logons,
                        tree.bx_affiliate_tiers_allowed,
                        tree.depth,
                        tree.path,
                        c.countries_name,
                        COALESCE(sales_summary.sales_count, 0) AS sales_count,
                        sales_summary.sales_total,
                        sales_summary.commission_total,
                        COALESCE(open_commissions.open_commission, 0) AS open_commission
                    FROM affiliate_tree tree
                    LEFT JOIN " . TABLE_COUNTRIES . " c ON c.countries_id = tree.bx_affiliate_country_id
                    LEFT JOIN (
                        SELECT 
                            a.bx_affiliate_id,
                            COUNT(*) AS sales_count,
                            SUM(a.bx_affiliate_value) AS sales_total,
                            SUM(a.bx_affiliate_payment) AS commission_total
                        FROM " . TABLE_BX_AFFILIATE_SALES . " a
                        JOIN " . TABLE_ORDERS . " o ON a.bx_affiliate_orders_id = o.orders_id
                        WHERE 1=1 " . bx_affiliate_build_orders_status_query() . "
                        GROUP BY a.bx_affiliate_id
                    ) sales_summary ON sales_summary.bx_affiliate_id = tree.bx_affiliate_id
                    LEFT JOIN (
                        SELECT 
                            a.bx_affiliate_id,
                            SUM(a.bx_affiliate_payment) AS open_commission
                        FROM " . TABLE_BX_AFFILIATE_SALES . " a
                        JOIN " . TABLE_ORDERS . " o ON a.bx_affiliate_orders_id = o.orders_id
                        WHERE a.bx_affiliate_billing_status != 1
                          AND a.bx_affiliate_date <= '" . $oldday . "'
                          " . bx_affiliate_build_orders_status_query() . "
                        GROUP BY a.bx_affiliate_id
                    ) open_commissions ON open_commissions.bx_affiliate_id = tree.bx_affiliate_id
                    ORDER BY tree.path";

                    $affiliates_query = xtc_db_query($affiliates_query_raw);
                  } else {
                    $affiliates_query = xtc_db_query("SELECT * FROM " . TABLE_BX_AFFILIATE_PARTNER . " WHERE 1=0");
                  }
/* ENDE */
                  // Alle Zeilen der aktuellen Seite vorab einlesen (klein dank Pagination) - so lässt sich
                  // vorab feststellen, ob acID überhaupt in der (ggf. gefilterten) Ergebnismenge vorkommt,
                  // bevor die erste <tr> ausgegeben wird.
                  $affiliates = array();
                  while ($row = xtc_db_fetch_array($affiliates_query)) {
                    $affiliates[] = $row;
                  }

                  // Effektive Auswahl bestimmen: kommt acID nicht in der Liste vor (z. B. Default-Wert,
                  // gelöschter Partner, oder Suche/Sortierung ohne Treffer), wird die erste Zeile markiert.
                  $acID_effective = $acID;
                  $acID_found     = false;

                  foreach ($affiliates as $row) {
                    if ((int)$row['bx_affiliate_id'] === $acID) {
                      $acID_found = true;
                      break;
                    }
                  }

                  if (!$acID_found && !empty($affiliates)) {
                    $acID_effective = (int)$affiliates[0]['bx_affiliate_id'];
                  }

                  $aInfo = null; // außerhalb der Schleife -> bleibt nach dem Durchlauf für die rechte Box erhalten
                  $affiliate_detail_templates = array();

                  foreach ($affiliates as $index => $affiliate) {
                    $is_selected = ((int)$affiliate['bx_affiliate_id'] === $acID_effective);

                    $open_commission = (float)$affiliate['open_commission'];

                    if ($is_selected && !$aInfo) {
                      $aInfo_array    = array_merge($affiliate, array('open_commission' => $open_commission));
                      $aInfo          = new objectInfo($aInfo_array);
                    }

                    // Vorgerendertes HTML der Detail-Box für clientseitiges Umschalten ohne AJAX-Request
                    $affiliate_detail_html = '<div class="bx-headboard bx-headboard--secondary"><strong>' . xtc_output_string($affiliate['bx_affiliate_firstname']) . ' ' . xtc_output_string($affiliate['bx_affiliate_lastname']) . '</strong></div>' . PHP_EOL
                        . '  <dl class="bx-dl-horizontal"><div class="bx-dl-item"><dt>' . TEXT_INFO_DATE_ACCOUNT_CREATED . '</dt><dd>' . xtc_date_short($affiliate['date_account_created']) . '</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_DATE_ACCOUNT_LAST_MODIFIED . '</dt><dd>' . xtc_date_short($affiliate['date_account_last_modified']) . '</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_DATE_LAST_LOGON . '</dt><dd>' . ($affiliate['date_last_logon'] ? xtc_date_short($affiliate['date_last_logon']) : TEXT_INFO_NEVER) . '</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_NUMBER_OF_LOGONS . '</dt><dd>' . (int)$affiliate['number_of_logons'] . '</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_COUNTRY . '</dt><dd>' . xtc_output_string($affiliate['countries_name']) . '</dd></div>' . PHP_EOL
                        . '  </dl>' . PHP_EOL
                        . '  <dl class="bx-dl-horizontal">' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_COMMISSION_PERCENT . '</dt><dd>' . xtc_output_string($affiliate['bx_affiliate_commission_percent']) . '%</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_TIERS_ALLOWED . '</dt><dd>' . ((int)$affiliate['bx_affiliate_tiers_allowed'] === 1 ? TEXT_INFO_YES : TEXT_INFO_NO) . '</dd></div>' . PHP_EOL
                        . '  </dl>' . PHP_EOL
                        . '  <dl class="bx-dl-horizontal">' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_SALES_COUNT . '</dt><dd>' . (int)$affiliate['sales_count'] . '</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_SALES_TOTAL . '</dt><dd>' . $currencies->format((float)$affiliate['sales_total']) . '</dd></div>' . PHP_EOL
                        . '  </dl>' . PHP_EOL
                        . '  <dl class="bx-dl-horizontal">' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_COMMISSION_TOTAL . '</dt><dd>' . $currencies->format((float)$affiliate['commission_total']) . '</dd></div>' . PHP_EOL
                        . '    <div class="bx-dl-item"><dt>' . TEXT_INFO_OPEN_COMMISSION . '</dt><dd>' . $currencies->format($open_commission) . '</dd></div>' . PHP_EOL
                        . '  </dl>' . PHP_EOL;

                    $affiliate_detail_templates[(int)$affiliate['bx_affiliate_id']] = $affiliate_detail_html;

                    if ($is_selected) {
                      echo '<tr class="bx-affiliates-selected" data-affiliate-id="' . (int)$affiliate['bx_affiliate_id'] . '" data-edit-url="' . xtc_href_link(BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('acID', 'action')) . 'acID=' . $affiliate['bx_affiliate_id'] . '&action=edit') . '">' . PHP_EOL;
                    } else {
                      echo '<tr data-affiliate-id="' . (int)$affiliate['bx_affiliate_id'] . '" data-edit-url="' . xtc_href_link(BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('acID', 'action')) . 'acID=' . $affiliate['bx_affiliate_id'] . '&action=edit') . '">' . PHP_EOL;
                    }

                    $affiliate['bx_affiliate_homepage'] = bx_affiliate_normalize_url($affiliate['bx_affiliate_homepage']);

                    $rows++;

                    echo '<td style="text-align: center;">' . (int)$affiliate['bx_affiliate_id'] . '</td>' . PHP_EOL;
                    
                    $depth         = (int)$affiliate['depth'];
                    $numAffiliates = count($affiliates);

                    // Hilfsfunktion: Prüft, ob auf einer bestimmten Ebene ab dem aktuellen Index noch Geschwister folgen
                    $hasMoreOnLevel = function($fromIndex, $targetDepth) use (&$affiliates, $numAffiliates) {
                        for ($i = $fromIndex + 1; $i < $numAffiliates; $i++) {
                            $checkDepth = (int)$affiliates[$i]['depth'];
                            if ($checkDepth === $targetDepth) {
                                return true; // Geschwisterkind auf gleicher Ebene gefunden
                            }
                            if ($checkDepth < $targetDepth) {
                                return false; // Hierarchie ist nach oben gesprungen -> Ebene beendet
                            }
                        }
                        return false;
                    };

                    echo '<td>';

                    if ($depth > 0) {
                        echo '<span class="tree-branch">';
                        
                        // 1. Linien für alle ÜBERGEORDNETEN Ebenen zeichnen (von Ebene 1 bis depth-1)
                        for ($l = 1; $l < $depth; $l++) {
                            if ($hasMoreOnLevel($index, $l)) {
                                echo '│   '; // Durchgehende Linie, da auf dieser höheren Ebene noch Geschwister folgen
                            } else {
                                echo '    '; // Leerzeichen, da die höhere Ebene hier bereits beendet ist
                            }
                        }

                        // 2. Abzweigungssymbol für die AKTUELLE Ebene zeichnen
                        if ($hasMoreOnLevel($index, $depth)) {
                            echo '├─ '; // Es kommen noch weitere Partner auf dieser Ebene
                        } else {
                            echo '└─ '; // Letzter Partner auf dieser Ebene
                        }
                        
                        echo '</span>';
                    }

                    echo xtc_output_string($affiliate['bx_affiliate_lastname']);
                    echo '</td>';


                    echo '<td>' . xtc_output_string($affiliate['bx_affiliate_firstname']) . '</td>' . PHP_EOL;
                    echo '<td style="text-align: center;">' . xtc_output_string($affiliate['bx_affiliate_commission_percent']) . '%</td>' . PHP_EOL;
                    echo '<td style="text-align: center;">' . $currencies->format($open_commission) . '</td>' . PHP_EOL;
                    echo '<td>' . xtc_output_string($affiliate['bx_affiliate_email_address']) . '</td>' . PHP_EOL;
                    echo '<td>' . xtc_output_string($affiliate['bx_affiliate_homepage']) . '</td>' . PHP_EOL;
                    echo '<td style="text-align: right;">';
                    if ( (is_object($aInfo)) && ($affiliate['bx_affiliate_id'] == $aInfo->bx_affiliate_id) ) {
                      echo xtc_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', IMAGE_ICON_INFO);
                    } else {
                      echo xtc_image(DIR_WS_IMAGES . 'icon_arrow_grey.gif', IMAGE_ICON_INFO);
                    }
                    echo '</td>' . PHP_EOL;
                    echo '</tr>' . PHP_EOL;
                  }

                  if ($rows == 0) {
                    echo '<tr><td colspan="8"><div class="error_message txta-c">' . BX_AFFILIATES_TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS . '</div></td></tr>' . PHP_EOL;
                  }
                ?>
              </table>
              <div class="bx-affiliates-panel" style="display: grid; grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); gap: 4px; margin: 6px 0;">
                <div class="smallText">
                  <?php echo $affiliates_split->display_count($affiliates_query_numrows, $page_max_display_results, $page, TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?>
                </div>
                <div class="smallText txta-r">
                  <?php echo $affiliates_split->display_links($affiliates_query_numrows, $page_max_display_results, MAX_DISPLAY_PAGE_LINKS, $page, xtc_get_all_get_params(array('page'))); ?>
                </div>
                <div class="smallText">
                  <?php echo draw_input_per_page(BX_FILENAME_AFFILIATES.'?page='.$page, $cfg_max_display_results_key, $page_max_display_results); ?>
                </div>
              </div>

              <?php
              foreach ($affiliate_detail_templates as $aff_id => $tpl_html) {
                echo '<template id="bx-affiliate-detail-' . (int)$aff_id . '">' . $tpl_html . '</template>' . PHP_EOL;
              }
              ?>
            </section>

            <aside class="bx-sidebar" id="bx-affiliate-details">
                <?php
                if (is_object($aInfo) && isset($affiliate_detail_templates[(int)$aInfo->bx_affiliate_id])) {
                  echo $affiliate_detail_templates[(int)$aInfo->bx_affiliate_id];
                } else {
                  echo '<div class="bx-headboard bx-headboard--secondary"><strong>' . TEXT_INFO_NO_SELECTION_HEADING . '</strong></div><p>' . TEXT_INFO_NO_SELECTION . '</p>';
                }
                ?>
            </aside>
          </div>
<?php
    break; // case default
}
?>
        </td>
      </tr>
    </table>
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>

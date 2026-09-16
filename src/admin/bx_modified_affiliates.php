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
    case 'update':
      if ($acID > 0) {
        $bx_gender        = in_array($_POST['bx_affiliate_gender'] ?? '', array('m', 'f', 'd'), true) ? $_POST['bx_affiliate_gender'] : '';
        $bx_firstname     = trim((string)($_POST['bx_affiliate_firstname'] ?? ''));
        $bx_lastname      = trim((string)($_POST['bx_affiliate_lastname'] ?? ''));
        $bx_email         = trim((string)($_POST['bx_affiliate_email_address'] ?? ''));
        $bx_company       = trim((string)($_POST['bx_affiliate_company'] ?? ''));
        $bx_company_taxid = trim((string)($_POST['bx_affiliate_company_taxid'] ?? ''));
        $bx_street        = trim((string)($_POST['bx_affiliate_street_address'] ?? ''));
        $bx_postcode      = trim((string)($_POST['bx_affiliate_postcode'] ?? ''));
        $bx_city          = trim((string)($_POST['bx_affiliate_city'] ?? ''));
        $bx_country_id    = (int)($_POST['bx_affiliate_country_id'] ?? 0);

        // Minimal-Validierung der Pflichtfelder. TODO: Fehler dem Nutzer im Formular
        // anzeigen statt bei Ungültigkeit stillschweigend nicht zu speichern.
        if ($bx_gender !== '' && $bx_firstname !== '' && $bx_lastname !== ''
            && $bx_street !== '' && $bx_postcode !== '' && $bx_city !== '' && $bx_country_id > 0
            && filter_var($bx_email, FILTER_VALIDATE_EMAIL)) {

          xtc_db_query(
            "UPDATE " . TABLE_BX_AFFILIATE_PARTNER . " SET
               bx_affiliate_gender = '" . xtc_db_input($bx_gender) . "',
               bx_affiliate_firstname = '" . xtc_db_input($bx_firstname) . "',
               bx_affiliate_lastname = '" . xtc_db_input($bx_lastname) . "',
               bx_affiliate_email_address = '" . xtc_db_input($bx_email) . "',
               bx_affiliate_company = '" . xtc_db_input($bx_company) . "',
               bx_affiliate_company_taxid = '" . xtc_db_input($bx_company_taxid) . "',
               bx_affiliate_street_address = '" . xtc_db_input($bx_street) . "',
               bx_affiliate_postcode = '" . xtc_db_input($bx_postcode) . "',
               bx_affiliate_city = '" . xtc_db_input($bx_city) . "',
               bx_affiliate_country_id = " . (int)$bx_country_id . ",
               bx_affiliate_date_account_last_modified = NOW()
             WHERE bx_affiliate_id = " . (int)$acID
          );
        }
      }

      // POST-Redirect-GET: verhindert erneutes Absenden bei Seiten-Reload
      xtc_redirect(xtc_href_link(BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('action')) . 'action=edit'));
      break; // case 'update'

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
    $affiliate_query = xtc_db_query("SELECT * FROM " . TABLE_BX_AFFILIATE_PARTNER . " WHERE bx_affiliate_id = '" . (int)$acID . "'");
    $affiliate       = xtc_db_fetch_array($affiliate_query);
    $aInfo           = $affiliate ? new objectInfo($affiliate) : null;

    $gender_values = array(
      'm' => BX_MALE,
      'f' => BX_FEMALE,
      'd' => BX_DIVERSE,
    );
?>
          <div class="bx-grid">
            <section class="bx-main-content">
              <div class="bx-headboard">
                <div class="bx-headboard-title">
                  <?php echo '<strong>' . BX_AFFILIATES_HEADING_TITLE . '</strong> - ' . BX_AFFILIATES_TEXT_EDIT_PARTNER; ?>
                </div>
              </div>

              <article class="bx-panel">
                <div class="bx-panel-title"><?php echo BX_AFFILIATES_TEXT_EVALUATION_TITLE; ?></div>
                <!-- TODO: Affiliate-Auswertung -- Klicks, Sales, Provisionsverlauf, Auszahlungen für diesen Partner -->
                <p class="main"><?php echo BX_AFFILIATES_TEXT_EVALUATION_PLACEHOLDER; ?></p>
              </article>
            </section>

            <!-- Sidebar (Rechts): persönliche Daten bearbeiten -->
            <aside class="bx-sidebar">
              <?php if (is_object($aInfo)) : ?>
                <div class="bx-headboard bx-headboard--secondary">
                  <strong><?php echo $gender_values[$aInfo->bx_affiliate_gender] . ' ' . xtc_output_string($aInfo->bx_affiliate_firstname) . ' ' . xtc_output_string($aInfo->bx_affiliate_lastname); ?></strong>
                </div>

                <?php echo xtc_draw_form('affiliate', BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('action')) . 'action=update', 'post'); ?>

                  <div class="bx-panel">
                    <div class="bx-panel-title"><?php echo CATEGORY_PERSONAL; ?></div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_gender"><?php echo ENTRY_GENDER; ?></label>
                      <div class="bx-select-wrapper">
                        <?php echo xtc_draw_pull_down_menu('bx_affiliate_gender', get_customers_gender(), $aInfo->bx_affiliate_gender, 'id="bx_affiliate_gender" class="bx-select" required'); ?>
                      </div>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_firstname"><?php echo ENTRY_FIRST_NAME; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_firstname', $aInfo->bx_affiliate_firstname, 'id="bx_affiliate_firstname" class="bx-input" maxlength="32" required'); ?>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_lastname"><?php echo ENTRY_LAST_NAME; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_lastname', $aInfo->bx_affiliate_lastname, 'id="bx_affiliate_lastname" class="bx-input" maxlength="32" required'); ?>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_email_address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_email_address', $aInfo->bx_affiliate_email_address, 'id="bx_affiliate_email_address" class="bx-input" type="email" maxlength="96" required'); ?>
                    </div>
                  </div>

                  <div class="bx-panel">
                    <div class="bx-panel-title"><?php echo CATEGORY_COMPANY; ?></div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_company"><?php echo ENTRY_COMPANY; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_company', $aInfo->bx_affiliate_company, 'id="bx_affiliate_company" class="bx-input" maxlength="32"'); ?>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_company_taxid"><?php echo BX_ENTRY_AFFILIATE_COMPANY_TAXID; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_company_taxid', $aInfo->bx_affiliate_company_taxid, 'id="bx_affiliate_company_taxid" class="bx-input" maxlength="64"'); ?>
                    </div>
                  </div>

                  <div class="bx-panel">
                    <div class="bx-panel-title"><?php echo CATEGORY_ADDRESS; ?></div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_street_address"><?php echo ENTRY_STREET_ADDRESS; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_street_address', $aInfo->bx_affiliate_street_address, 'id="bx_affiliate_street_address" class="bx-input" maxlength="64" required'); ?>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_postcode"><?php echo ENTRY_POST_CODE; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_postcode', $aInfo->bx_affiliate_postcode, 'id="bx_affiliate_postcode" class="bx-input" maxlength="8" required'); ?>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_city"><?php echo ENTRY_CITY; ?></label>
                      <?php echo xtc_draw_input_field('bx_affiliate_city', $aInfo->bx_affiliate_city, 'id="bx_affiliate_city" class="bx-input" maxlength="32" required'); ?>
                    </div>
                    <div class="bx-form-group">
                      <label for="bx_affiliate_country_id"><?php echo ENTRY_COUNTRY; ?></label>
                      <div class="bx-select-wrapper">
                        <?php echo xtc_draw_pull_down_menu('bx_affiliate_country_id', xtc_get_countries(), $aInfo->bx_affiliate_country_id, 'id="bx_affiliate_country_id" class="bx-select"'); ?>
                      </div>
                    </div>
                  </div>

                  <div class="bx-form-actions">
                    <button type="submit" class="bx-btn"><?php echo BUTTON_UPDATE; ?></button>
                    <a class="bx-btn--secondary" href="<?php echo xtc_href_link(BX_FILENAME_AFFILIATES, xtc_get_all_get_params(array('action'))); ?>"><?php echo BUTTON_CANCEL; ?></a>
                  </div>
                </form>
              <?php else : ?>
                <div class="bx-headboard bx-headboard--secondary"><strong><?php echo TEXT_INFO_NO_SELECTION_HEADING; ?></strong></div>
                <p class="error_message"><?php echo TEXT_INFO_NO_SELECTION; ?></p>
              <?php endif; ?>
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
                  echo '<div class="bx-headboard bx-headboard--secondary"><strong>' . TEXT_INFO_NO_SELECTION_HEADING . '</strong></div><p class="error_message">' . TEXT_INFO_NO_SELECTION . '</p>';
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

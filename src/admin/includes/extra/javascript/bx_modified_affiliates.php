<?php
/**
 * BX Modified Affiliates Module - JavaScript & AJAX Handler
 * 
 * Implements dynamic cascading selection system and AJAX operations
 * for Affiliates management. Provides seamless UX with Select2 autocomplete,
 * automatic data loading, and real-time form updates.
 *
 * @package    BX Modified Affiliates
 * @subpackage JavaScript
 * @version    1.0.0
 * @author     benax
 * @copyright  2006-2026
 * @license    GNU GPL v2.0
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

if (defined('MODULE_MODIFIED_AFFILIATES_STATUS') && ((string)MODULE_MODIFIED_AFFILIATES_STATUS === 'True') && basename($_SERVER['PHP_SELF']) == 'bx_modified_affiliates.php') {
?>
<script>
  "use strict";
	$(document).on('click', '.bx-affiliates-table tr[data-affiliate-id]', function () {
		var $row = $(this);
		var affiliateId = $row.data('affiliate-id');
		var $details = $('#bx-affiliate-details');

		if (!affiliateId || !$details.length) {
			return;
		}

		if ($row.hasClass('bx-affiliates-selected')) {
			window.location.href = $row.data('edit-url');
			return;
		}

		let selectedIcon   = '<?php echo DIR_WS_IMAGES . 'icon_arrow_right.gif'; ?>';
		let unselectedIcon = '<?php echo DIR_WS_IMAGES . 'icon_arrow_grey.gif'; ?>';
		$('.bx-affiliates-table tr.bx-affiliates-selected')
			.removeClass('bx-affiliates-selected')
			.find('td:last-child img').attr('src', unselectedIcon);
		$row.addClass('bx-affiliates-selected');
		$row.find('td:last-child img').attr('src', selectedIcon);

		var template = document.getElementById('bx-affiliate-detail-' + affiliateId);
		if (template) {
			$details.html(template.innerHTML);
			if (window.history && window.history.replaceState) {
				var url = window.location.href.replace(/([?&])action=[^&]*/, '$1');
				if (/[?&]acID=/.test(url)) {
					url = url.replace(/([?&])acID=[^&]*/, '$1acID=' + encodeURIComponent(affiliateId));
				} else {
					url += (url.indexOf('?') === -1 ? '?' : '&') + 'acID=' + encodeURIComponent(affiliateId);
				}
				window.history.replaceState(null, '', url);
			}
			return;
		}

		$.ajax({
			url: '<?php echo BX_FILENAME_AFFILIATES; ?>',
			data: {
        ajax: 'affiliate_details', 
        acID: affiliateId<?php if (defined('CSRF_TOKEN_SYSTEM') && CSRF_TOKEN_SYSTEM == 'true') {
          echo ','.PHP_EOL.'        '.$_SESSION["CSRFName"].": '".$_SESSION["CSRFToken"]."'";
          }
        ?>},
			dataType: 'json',
			headers: {'X-Requested-With': 'XMLHttpRequest'},
			success: function (data) {
				if (data.success) {
					$details.html(data.html);
					if (window.history && window.history.replaceState) {
						var url = window.location.href.replace(/([?&])action=[^&]*/, '$1');
						if (/[?&]acID=/.test(url)) {
							url = url.replace(/([?&])acID=[^&]*/, '$1acID=' + encodeURIComponent(affiliateId));
						} else {
							url += (url.indexOf('?') === -1 ? '?' : '&') + 'acID=' + encodeURIComponent(affiliateId);
						}
						window.history.replaceState(null, '', url);
					}

          // Dynamische Aktualisierung aller Token-Felder
          if (data.csrf_name && data.csrf_token) {
            // Betroffenes Input-Feld aktualisieren
            $('form[name="cfg_max"] input[type="hidden"]').first().attr("name", data.csrf_name).val(data.csrf_token);

            // WICHTIG: Falls noch andere Formulare auf der Seite den Token nutzen, solltest du diese hier ebenfalls mit aktualisieren:
            //$('input[name="' + data.csrf_name + '"]').val(data.csrf_token);
          }
				}
			},
			error: function (xhr, status, error) {
				console.error('Affiliate details could not be loaded:', status, error);
			}
		});
});

  $(document).ready(function() {
    setTimeout(function(){ $(".fixed_messageStack").hide("slow"); }, 3000);
  });
</script>
<?php
}

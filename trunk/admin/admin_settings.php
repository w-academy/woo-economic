<?php
/**
 * Woo Economic settings page
 *
 * @author kristianrasmussen.com
 */
function woo_economic_settings_page() {
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  $woo_economic_sync_hidden_field_name = WOO_ECONOMIC_FORM_SYNC;
  if (isset($_POST[$woo_economic_sync_hidden_field_name])) {
    if ($_POST[$woo_economic_sync_hidden_field_name] == 'woo2eco') {
      /* give an alert - do you really want to sync ... */
      /* make an overlay with ticker wheel to show sync in progress */
      _log("woo_economic_settings_page sync woo2eco");

      woo_economic_synchronize_to_eco();
      do_action('woo_economic_synchronize_to_eco');
    }
    if ($_POST[$woo_economic_sync_hidden_field_name] == 'eco2woo') {
      /* give an alert - do you really want to sync ... */
      /* make an overlay with ticker wheel to show sync in progress */
      _log("woo_economic_settings_page sync eco2woo");
      woo_economic_synchronize_to_woo();
      do_action('woo_economic_synchronize_to_woo');
    }
  }

  /* TODO Clean this bit up */

  echo '<div class="wrap">';
  echo '<div class="icon32" id="icon-options-general"></div>';
  echo '<h2>' . WOO_ECONOMIC_INTEGRATION_TITLE . '</h2>';
  echo '<form action="options.php" method="post">';
  settings_fields(WOO_ECONOMIC_OPTIONS);
  do_settings_sections($_GET['page']);
  echo '<p class="submit">';
  echo '<input type="submit" class="button-primary" value="Save Changes" />';
  echo '</p>';
  echo '</form>';
  echo '<hr/>';
  echo '<h3>' . WOO_ECONOMIC_SYNC_TITLE . '</h3>';
  echo '<form name="woo_economic_sync" action="" method="post">';
  echo '<p>Synchronize all products to e-conomic&nbsp;';
  echo '<input type="hidden" name="' . WOO_ECONOMIC_FORM_SYNC . '" value="woo2eco"/>';
  echo '<input type="submit" class="button-primary" value="WooCommerce Products -> E-conomic" />';
  echo '</p>';
  echo '</form>';
  echo '<form name="woo_economic_sync" action="" method="post">';
  echo '<p>Synchronize all products from e-conomic&nbsp;';
  echo '<input type="hidden" name="' . WOO_ECONOMIC_FORM_SYNC . '" value="eco2woo"/>';
  echo '<input type="submit" class="button-primary" value="E-conomic Products -> WooCommerce" />';
  echo '</p>';
  echo '</form>';
  echo '</div>';
}
?>

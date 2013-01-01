<?php
/**
 * Woo Economic initialisation and callback hook registration
 *
 * @author kristianrasmussen.com
 */

function activate_woo_economic() {
  woo_economic_install();
}

function woo_economic_install() {
  add_option(WOO_ECONOMIC_VERSOIN_OPT, WOO_ECONOMIC_VERSION);
}

function woo_economic_default_options() {
  add_settings_section(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_GENERAL_SETTINGS, 'woo_economic_options_section_callback', 'woo_economic_settings');
  add_settings_field(WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME, WOO_ECONOMIC_OPTION_PRODUCT_OFFSET, 'woo_economic_options_product_offset_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME, WOO_ECONOMIC_OPTION_PRODUCT_GROUP, 'woo_economic_options_product_group_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME, WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET, 'woo_economic_options_customer_offset_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_CUSTOMER_GROUP_NAME, WOO_ECONOMIC_OPTION_CUSTOMER_GROUP, 'woo_economic_options_customer_group_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME, WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER, 'woo_economic_options_shipping_product_number_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT_NAME, WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT, 'woo_economic_options_auto_debtor_payment_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);

  $show_cashbooks = get_option(WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT_NAME);
  if ($show_cashbooks) {
    add_settings_field(WOO_ECONOMIC_OPTION_CASHBOOK_NAME, WOO_ECONOMIC_OPTION_CASHBOOK, 'woo_economic_options_cashbook_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  }

  /****************** economic settings ******************/
  add_settings_field(WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT_NAME, WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT, 'woo_economic_options_economic_agreement_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_ECONOMIC_USER_NAME, WOO_ECONOMIC_OPTION_ECONOMIC_USER, 'woo_economic_options_economic_user_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);
  add_settings_field(WOO_ECONOMIC_OPTION_ECONOMIC_PASSWD_NAME, WOO_ECONOMIC_OPTION_ECONOMIC_PASSWD, 'woo_economic_options_economic_password_callback', 'woo_economic_settings', WOO_ECONOMIC_OPTIONS);


  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME, "woo_economic_validate_product_offset");
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME, "woo_economic_validate_product_group");
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME, "woo_economic_validate_customer_offset");
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_CUSTOMER_GROUP_NAME, "woo_economic_validate_customer_group");
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME, "woo_economic_validate_shipping_product_number");
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT_NAME, "woo_economic_validate_checkbox");


  if ($show_cashbooks) {
    register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_CASHBOOK_NAME, "woo_economic_validate_not_empty");
  }
  /****************** economic settings ******************/
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT_NAME, "woo_economic_validate_agreement_number");
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_ECONOMIC_USER_NAME);
  register_setting(WOO_ECONOMIC_OPTIONS, WOO_ECONOMIC_OPTION_ECONOMIC_PASSWD_NAME, "woo_economic_validate_password");

}

add_action('admin_init', 'woo_economic_default_options');

/************************ Validation functions ****************************/

function woo_economic_validate_product_offset($input)
{
  return woo_economic_validate_option_is_integer(WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME, WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME . ' must be a number', $input);
}

function woo_economic_validate_product_group($input)
{
  return woo_economic_validate_option_is_integer(WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME, WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME . ' must be a number', $input);
}

function woo_economic_validate_customer_offset($input)
{
  return woo_economic_validate_option_is_integer(WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME, WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET. ' must be a number', $input);
}

function woo_economic_validate_customer_group($input)
{
  return woo_economic_validate_option_is_integer(WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME, WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET .' must be a number', $input);
}

function woo_economic_validate_shipping_product_number($input)
{
  return woo_economic_validate_option_is_integer(WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME, WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER .' must be a number', $input);
}

function woo_economic_validate_agreement_number($input)
{
  return woo_economic_validate_option_is_integer(WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT_NAME, WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT . ' must be a number', $input);
}

function woo_economic_validate_password($input) {
  _log("woo_economic_validate_password validating password");
  $stored_password = get_option(WOO_ECONOMIC_OPTION_ECONOMIC_PASSWD_NAME);
  if (isset($input) && !empty($input)) {
    return $input;
  }

  return $stored_password;
}

function woo_economic_validate_option_is_integer($field, $message, &$input)
{
  _log("woo_economic_validate_option_is_integer field: " . $field . " input: " . $input);
  if (!is_numeric($input))
    add_settings_error($field, $field, $message, 'error');
  return $input;
}

function woo_economic_validate_checkbox($input) {
  _log("woo_economic_validate_checkbox field: " . $input);
  if (isset($input) && !empty($input)) {
    return true;
  }
  return false;
}

function woo_economic_validate_not_empty($input) {
  _log("woo_economic_validate_not_empty field: " . $input);
  /**
   * check cashbook exists before saving!
   */
  if (!isset($input) || empty($input)) {
    add_settings_error(WOO_ECONOMIC_OPTION_CASHBOOK_NAME, WOO_ECONOMIC_OPTION_CASHBOOK_NAME, "The cashbook can not be empty.", 'error');
  }
  return trim($input);
}


/************************ Callback functions ******************************/

function woo_economic_options_section_callback()
{
  echo '<p>Setup the options to match how the e-conomic integration should behave.</p>';
}

function woo_economic_economic_options_section_callback()
{
  echo '<p>Setup the options to match your e-conomic settings</p>';
}

function woo_economic_options_product_offset_callback()
{
  echo '<input id="' . WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME . '"name="' . WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME) . '"/><br/>This offset will be added to products saved to e-conomic from woo-commerce';
}

function woo_economic_options_product_group_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME) . '"/><br/>When adding products to woocommerce, products are synced to e-conomic and placed in this product group';
}

function woo_economic_options_customer_offset_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME) . '"/><br/>This offset will be added to customers saved to e-conomic from woo-commerce';
}

function woo_economic_options_customer_group_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_CUSTOMER_GROUP_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_CUSTOMER_GROUP_NAME) . '"/><br/>Customers from woocommerce will be added this customer group';
}

function woo_economic_options_shipping_product_number_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME) . '"/><br/>This product number is added to all invoices as the product number for shipping';
}

function woo_economic_options_auto_debtor_payment_callback() {
  echo '<input name="' . WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT_NAME. '" type="checkbox" value="1"' . checked( 1, get_option(WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT_NAME), false )  . '/><br/>Check this if you want the integration to create a debtor payment after creating the invoice, matching the invoice amount. Used when your gateway has autocapture.';
}

function woo_economic_options_cashbook_callback() {
  echo '<input name="' . WOO_ECONOMIC_OPTION_CASHBOOK_NAME. '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_CASHBOOK_NAME) . '"/><br/>Select the cashbook to add debtor payments to.';
}

function woo_economic_options_economic_agreement_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT_NAME) . '"/>';
}

function woo_economic_options_economic_user_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_ECONOMIC_USER_NAME . '" type="text" value="' . get_option(WOO_ECONOMIC_OPTION_ECONOMIC_USER_NAME) . '"/>';
}

function woo_economic_options_economic_password_callback()
{
  echo '<input name="' . WOO_ECONOMIC_OPTION_ECONOMIC_PASSWD_NAME . '" type="password" value=""/>';
}

function woo_economic_register_menu()
{
  // Add a new submenu under Settings:
  add_options_page('E-connomic integration', WOO_ECONOMIC_PLUGIN_NAME, 'manage_options', 'woo_economic_settings', 'woo_economic_settings_page');
}

add_action('admin_menu', 'woo_economic_register_menu');
?>

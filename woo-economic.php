<?php
/*
 * Plugin Name: Woo-economic
 * Plugin URI: http://kristianrasmussen.com/wooeconomic
 * Description: An integration between woocommerce and E-conomic
 * Version: 1.0.6
 * Author: kristianrasmussen.com
 * Author URI: http://kristianrasmussen.com
 * Requires at least: 3.3
 * License: GPL
 */
if (!session_id()) session_start();

require_once( 'admin/admin_debug.php' );
require_once( 'admin/admin_constants.php' );
require_once( 'admin/admin_economic.php' );

if ( is_admin() ) :

  require_once( 'admin/admin_init.php' );
  register_activation_hook( __FILE__, 'activate_woo_economic' );
  if (get_option(WOO_ECONOMIC_VERSOIN_OPT) != WOO_ECONOMIC_VERSION) : add_action('init', 'woo_economic_install', 0); endif;
endif;

include_once('admin/admin_settings.php');

add_action('save_post', 'woo_economic_save_object', 2, 2);
function woo_economic_save_object( $post_id, $post) {
  _log("woo_economic_save_object called with post_id: " . $post_id . " posttype: " . $post->post_type);
  if ( !$_POST ) return $post_id;
  if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
  if( is_int( wp_is_post_autosave( $post_id ) ) ) return;
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
  if ($post->post_type != 'product' && $post->post_status != 'publish') return $post_id;
  do_action('woo_economic_save_'.$post->post_type, $post_id, $post);
}

add_action('woo_economic_save_product', 'woo_economic_save_product', 1,2);
function woo_economic_save_product($post_id, $post) {
  global $woo_economic_product_lock;

  if ($woo_economic_product_lock) {
    _log("woo_economic_save_product ommit save product, lock is raised");
    return;
  }
  _log("woo_economic_save_product() called id: " . $post_id);
  $product = new WC_Product($post->ID);
  _log("saving product: " . $product->get_title() . " id: " . $product->id . " sku: " . $product->sku);
  woo_economic_save_product_to_economic($product);
}


/*
 * This creates the user in economic - with bare minimum details
 * This is called when user first registers - it calls on to save user keys
 */
add_action('woocommerce_checkout_order_processed', 'woo_economic_update_customer');
function woo_economic_update_customer($order_id) {
  $order = new WC_Order($order_id);
  $user = new WP_User($order->user_id);
  _log("woo_economic_update_customer() called user_id: " . $user->ID);

  if (woo_economic_is_customer($user)) {
    woo_economic_save_customer_to_economic($user);
  }
}

function woo_economic_is_customer(WP_User $user) {
  $is_customer = false;
  foreach ($user->roles as $role) {
    _log("user role: " . $role);
    if ($role == 'customer') {
      $is_customer = true;
      break;
    }
  }
  return $is_customer;
}

/*
 * This adds additional data to the user
 * Calls on to save user keys
 */
add_action('update_user_meta', 'woo_economic_update_user_meta', 10, 4);
function woo_economic_update_user_meta($meta_id, $object_id, $meta_key, $_meta_value) {
  _log("woo_economic_update_user_meta: meta_id: ".$meta_id." object_id: ".$object_id." meta_key: ".$meta_key." meta_value: ".$_meta_value);
  $user = new WP_User($object_id);
  if (woo_economic_is_customer($user)) {
    woo_economic_save_customer_meta_data($user, $meta_key, $_meta_value);
  }
}

/*
 * This creates an invoice in e-conomic
 */
add_action('woocommerce_order_status_completed', 'woo_economic_create_invoice', 10, 4);
function woo_economic_create_invoice($order_id) {
  _log("woo_economic_create_invoice: order_id: ".$order_id);
  $order = new WC_Order($order_id);
  $user = new WP_User($order->user_id);
  woo_economic_save_invoice_to_economic($user, $order);

  /**
   * if create auto debtor payment - create it
   */
  $auto_create_debtor = get_option(WOO_ECONOMIC_OPTION_AUTO_DEB_PAYMENT_NAME);
  if (isset($auto_create_debtor) && $auto_create_debtor) {
    woo_economic_create_debtor_payment($user, $order);
  }
}

/*
 * This refunds an invoice in e-conomic
 */
add_action('woocommerce_order_status_refunded', 'woo_economic_refund_invoice', 10, 4);
function woo_economic_refund_invoice($order_id) {
  _log("woo_economic_create_invoice: order_id: ".$order_id);
  $order = new WC_Order($order_id);
  $user = new WP_User($order->user_id);
  woo_economic_save_refund_to_economic($user, $order);
}

?>

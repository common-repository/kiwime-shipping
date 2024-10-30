<?php
/**
 * @package KiwiMe Shipping
 * @version 0.1
 */
/*
Plugin Name: KiwiMe Shipping
Plugin URI: https://www.kiwime.io/
Description: Add KiwiMe local shipping options to your WooCommerce store.
Author: KiwiMe Team
Version: 0.1
Author URI: https://www.kiwime.io/
*/
include_once(__DIR__ . '/admin.php');
include_once(__DIR__ . '/shipping.php');
require_once(__DIR__ . '/vendor/autoload.php');

use GuzzleHttp\Client;


function kiwime_init()
{
  global $kiwime_logger;
  if (!kiwime_has_woocommerce()) {
    kiwime_log('Abort Kiwime init. Store has no woocommerce.');
    return;
  }
  $kiwime_logger = new WC_Logger();
  $kiwime_logger->log('info', 'KiwiMe initiated');
  add_action('woocommerce_payment_complete', 'kiwime_order_hook');
//  add_action('woocommerce_order_status_completed', 'kiwime_order_hook');
  kiwime_shipping_init();
}


add_action('admin_menu', 'kiwime_register_admin_page');
add_action('admin_init', 'kiwime_settings_init');
add_action('init', 'kiwime_init');

function kiwime_activation_redirect($plugin)
{
  if ($plugin == plugin_basename(__FILE__)) {
    exit(wp_redirect(admin_url('admin.php?page=kiwime-settings')));
  }
}

add_action('activated_plugin', 'kiwime_activation_redirect');

function kiwime_order_hook($order_id)
{
  kiwime_log('order received');
  $order = wc_get_order($order_id);
  if ($order === false or is_a($order, 'WC_Order_Refund')) {
    kiwime_log("Skipping $order_id - either not found or is a refund");
    return;
  }
  global $kiwime_api_root;
  $client = new Client();
  $url = $kiwime_api_root . '/wc/order';
  $shipping_methods = $order->get_shipping_methods();
  $shipping = [];
  foreach ($shipping_methods as $method) {
    array_push($shipping, [
      'id' => $method->get_id(),
      'methodId' => $method->get_method_id(),
      'instance' => $method->get_instance_id(),
      'fees' => $method->get_total(),
      'title' => $method->get_method_title(),
      'meta' => $method->get_meta_data(),
    ]);
  }
  $items = [];
  foreach ($order->get_items() as $item) {
    array_push($items, [
      'name' => $item->get_name(),
      'quantity' => $item->get_quantity(),
      'price' => $item->get_total()
    ]);
  }
  $data = [
    'order' => $order->get_data(),
    'items' => $items,
    'shipping' => $shipping
  ];
  try {
    $headers = ['X-KiwiMe-API-Key' => get_option('kiwime_api_key'), 'X-KiwiMe-Store-ID' => get_option('kiwime_store_id')];
    $response = $client->post($url, ['json' => $data, 'headers' => $headers]);
    $body = $response->getBody()->getContents();
    kiwime_log(print_r($body, true));
  } catch (Exception $ex) {
    kiwime_log($ex->getMessage());
    kiwime_log($ex->getTraceAsString());
  }

}



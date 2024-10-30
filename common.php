<?php
$kiwime_api_root = 'https://s-app.kiwime.io';
//$kiwime_api_root = 'http://localhost:3000';
$kiwime_logger = NULL;

function kiwime_has_woocommerce()
{
  return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}


function kiwime_is_active()
{
  if (!kiwime_has_woocommerce()) {
    kiwime_log('Store does not have WooCommerce');
    return false;
  }
  $api_key = get_option('kiwime_api_key');
  $store_id = get_option('kiwime_store_id');
  if (empty($api_key) or empty($store_id)) {
    kiwime_log('API Key or Store ID is empty');
    return false;
  }
  global $wp_filter;
  $hooks = $wp_filter['woocommerce_payment_complete'];
  if (!$hooks->has_filter('', 'kiwime_order_hook')) {
    kiwime_log('woocommerce_payment_complete has not registered kiwime_order_hook');
    return false;
  }
  return true;
}


function kiwime_log($arg)
{
  global $kiwime_logger;
  if (empty($kiwime_logger)) {
    error_log($arg);
  } else {
    error_log($arg);
    $kiwime_logger->log('info', $arg);
  }

}

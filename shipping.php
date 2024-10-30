<?php
include_once(__DIR__ . '/common.php');
require_once(__DIR__ . '/vendor/autoload.php');

use GuzzleHttp\Client;

if (!kiwime_has_woocommerce()) {
  return;
}


function kiwime_shipping_method_init()
{
  if (class_exists('KiwiMe_Shipping_Method')) {
    return;
  }

  class KiwiMe_Shipping_Method extends WC_Shipping_Method
  {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($instanceId = null)
    {
      $this->id = 'kiwime';
      $this->plugin_id = 'kiwime';
      $this->method_title = 'KiwiMe Shipping';
      $this->method_description = 'Get dynamic shipping rates and availability from kiwime.io (requires sign-up)';
      $this->enabled = "yes";
      $this->title = "KiwiMe Shipping";
      $this->instance_id = absint($instanceId);
      $this->supports = array(
        'settings',
        'shipping-zones',
        'instance-settings',
        'global-instance',
      );

      $this->init();
    }

    function init_form_fields()
    {
      $this->form_fields = array(
        'title' => array(
          'title' => 'Enabled',
          'type' => 'checkbox',
          'description' => '',
          'default' => 'yes'
        )
      );
    }

    function init()
    {
      $this->init_form_fields();
      $this->init_settings();

      add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function calculate_shipping($package = array())
    {
      global $kiwime_api_root;
      $client = new Client();
      $url = $kiwime_api_root . '/wc/rates';
      $data = [
        'package' => $package,
        'timezone' => get_option('timezone_string')
      ];
//      kiwime_log($data);
      try {
        $headers = ['X-KiwiMe-API-Key' => get_option('kiwime_api_key'), 'X-KiwiMe-Store-ID' => get_option('kiwime_store_id')];
        $response = $client->post($url, ['json' => $data, 'headers' => $headers]);
        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);
        $rates = $decoded['rates'];
        foreach ($rates as $rate) {
//          kiwime_log(print_r($rate, true));
          $this->add_rate($rate);
        }
      } catch (Exception $ex) {
        kiwime_log($ex->getMessage());
        kiwime_log($ex->getTraceAsString());
      }
    }
  }
}

function kiwime_add_shipping_method($methods)
{
  $methods['kiwime'] = 'KiwiMe_Shipping_Method';
  return $methods;
}

function kiwime_shipping_init()
{
  add_action('woocommerce_shipping_init', 'kiwime_shipping_method_init');
  add_filter('woocommerce_shipping_methods', 'kiwime_add_shipping_method');
//  apply_filters('woocommerce_shipping_methods', ['kiwime' => 'KiwiMe_Shipping_Method']);
}

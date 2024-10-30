<?php
include_once(__DIR__ . '/common.php');

function kiwime_add_settings_section_callback()
{
  echo '';
}

function kiwime_api_key_field_callback()
{
  $key = get_option('kiwime_api_key');
  echo <<<STR
<input id='kiwime_api_key' name='kiwime_api_key' value='$key' type='text'/>
<p>The unique API Key you got from KiwiMe team.</p>
STR;
}

function kiwime_store_id_field_callback()
{
  $store_id = get_option('kiwime_store_id');
  echo <<<STR
<input id='kiwime_store_id' name='kiwime_store_id' value='$store_id' type='text'/>
<p>(Optional) The store ID to distinguish different stores owned by you.</p>
STR;
}

function kiwime_render_welcome()
{
  if (kiwime_is_active()) {
    echo <<<STR
<div class="kiwime-welcome">
    <h2>✅ KiwiMe is currently active.</h2>
</div>
STR;
    return true;
  }
  return false;
}

function kiwime_settings_page()
{
  $is_active = kiwime_render_welcome();
  ?>
  <form action="options.php" method="post">
    <?php
    $save_text = 'Activate KiwiMe';
    if ($is_active) {
      $save_text = 'Update';
    }
    settings_fields('kiwime-settings');
    do_settings_sections('kiwime-settings');
    submit_button($save_text, 'primary', 'submit', true, array('id' => 'kiwime-save-settings'));
    echo "";
    ?>
    <div id='kiwime-api-key-check-response'></div>
    <script>
      jQuery(document).ready(function () {
        var saveSettingsButton = jQuery('#kiwime-save-settings')
        saveSettingsButton.on('click', function () {
          jQuery('kiwime-api-key-check-response').text('Saved and activated!')
        })
        // TODO: check validity of API keye on the fly;
      })
    </script>
  </form>
  <?php
}

function kiwime_register_admin_page()
{
  wp_enqueue_script('jquery');
  wp_enqueue_style('kiwime_admin_styles', plugins_url('/admin.css', __FILE__), array());
  add_menu_page('KiwiMe Settings', 'KiwiMe', 'manage_options', 'kiwime-settings', 'kiwime_settings_page', plugins_url('/kiwime.png', __FILE__));
}

function kiwime_settings_init()
{
  $args = array('type' => 'string', 'description' => 'API Key you received from KiwiMe', 'default' => NULL);
  register_setting('kiwime-settings', 'kiwime_api_key', $args);
  register_setting('kiwime-settings', 'kiwime_store_id', $args);
  add_settings_section('kiwime_general', 'KiwiMe Settings', 'kiwime_add_settings_section_callback', 'kiwime-settings');
  add_settings_field('kiwime_api_key', 'API Key', 'kiwime_api_key_field_callback', 'kiwime-settings', 'kiwime_general');
  add_settings_field('kiwime_store_id', 'Store ID', 'kiwime_store_id_field_callback', 'kiwime-settings', 'kiwime_general');
}

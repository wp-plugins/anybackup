<?php
/*
 * Plugin Name: AnyBackup
 * Plugin URI: http://www.anybackup.io
 * Description: Automatic backups for your wordpress sites.
 * Version: 1.0.8
 * Author: 255 BITS LLC
 * Author URI: https://anybackup.io
 * License: MIT
 */

if (is_multisite()) {
  exit("AnyBackup does not support multisite wordpress configurations.  Contact us at support@255bits.com to get access to our multisite beta.");
}

require(dirname(__FILE__).'/includes/BitsAnyBackupAPI.php');
require(dirname(__FILE__).'/includes/BitsBackupStateMachine.php');
require(dirname(__FILE__).'/includes/BitsRestoreStateMachine.php');
require(dirname(__FILE__).'/includes/BitsUtil.php');
require(dirname(__FILE__).'/includes/BitsBackupWorker.php');
require(dirname(__FILE__).'/includes/BitsRestoreWorker.php');

register_activation_hook( __FILE__, 'bits_anybackup_activation');
register_deactivation_hook( __FILE__, 'bits_anybackup_deactivation');
add_action('admin_menu', 'bits_anybackup_menu');
add_action("wp_ajax_bits_backup_for_date", "bits_backup_for_date");
add_action("wp_ajax_bits_backup_start_job", "bits_backup_start_job");
add_action("wp_ajax_bits_backup_save_settings", "bits_backup_save_settings");
add_action("wp_ajax_bits_backup_force_backup_now", "bits_backup_force_backup_now");
add_action("wp_ajax_bits_backup_get_status", "bits_backup_get_status");
add_action("wp_ajax_bits_backup_get_backup", "bits_backup_get_backup");
add_action("wp_ajax_bits_backup_get_sites", "bits_backup_get_sites");
add_action("wp_ajax_bits_backup_get_plans", "bits_backup_get_plans");
add_action("wp_ajax_bits_backup_send_support", "bits_backup_send_support");
add_action("wp_ajax_bits_backup_update_account", "bits_backup_update_account");

add_action("wp_ajax_bits_register_account", "bits_register_account");
add_action("wp_ajax_bits_login_account", "bits_login_account");

add_action("wp_ajax_bits_restore_from_backup", "bits_restore_from_backup");
add_action("wp_ajax_bits_force_cancel", "bits_force_cancel");

add_action( 'admin_enqueue_scripts', 'bits_load_scripts');
add_action( 'admin_init', 'bits_init');
function bits_anybackup_activation() {
  //TODO: Record activation stas
  bits_get_api();
}
function bits_anybackup_deactivation() {
}

function bits_init() {
  wp_register_script('anybackup-angular',
    plugins_url( '/plugin-assets/anybackup-angular-1.2.20.min.js', __FILE__ ));
  wp_register_script('anybackup-angular-ui',
    plugins_url( '/plugin-assets/anybackup-angular-ui-bootstrap-0.12.0.min.js', __FILE__ ));
  wp_register_script('anybackup-wordpress',
    plugins_url( '/plugin-assets/anybackup-wordpress-1.0.js', __FILE__ ));
  wp_register_script('anybackup-moment',
    plugins_url( '/plugin-assets/moment-2.8.2.min.js', __FILE__ ));
  wp_register_style('anybackup-bootstrap',
    plugins_url( '/plugin-assets/anybackup-bootstrap-namespaced-3.2.0.css', __FILE__ ));
  wp_register_style('anybackup-wordpress',
    plugins_url( '/plugin-assets/anybackup-wordpress-1.0.css', __FILE__ ));
  wp_register_style('anybackup-font-awesome',
    plugins_url( '/plugin-assets/font-awesome-4.3.0/css/font-awesome.min.css', __FILE__ ));
}
function bits_load_scripts() {
  wp_enqueue_script('anybackup-angular');
  wp_enqueue_script('anybackup-angular-ui');
  wp_enqueue_script('anybackup-moment');
  wp_enqueue_script('anybackup-wordpress');
  wp_enqueue_style('anybackup-bootstrap');
  wp_enqueue_style('anybackup-wordpress');
  wp_enqueue_style('anybackup-font-awesome');
}

function bits_anybackup_menu() {
  $icon = plugins_url("anybackup/plugin-assets/logo-20x20.png");
  add_menu_page(__('AnyBackup'), __('AnyBackup'), 'manage_options', 'backup_bits_anybackup', 'bits_anybackup_menu_render', $icon); 
}

function bits_get_api() {
  $api = new BitsAnyBackupAPI();
  $api_key = get_option("bits_api_key");
  if($api_key == false) {
    $api_key = $api->create_api_key();
    add_option("bits_api_key", $api_key);
  }
  $api->set_api_key($api_key);
  return $api;
}

function bits_get_new_api() {
  $api = new BitsAnyBackupAPI();
  $api_key = $api->create_api_key();
  update_option("bits_api_key", $api_key);
  $api->set_api_key($api_key);
  return $api;
}


function bits_anybackup_menu_render() {
  if(isset($_GET['runSpecs'])) {
    require 'spec/bits-backup-spec.php';
  } elseif(isset($_POST['upgradeAccountToPaid'])) {
    bits_debug_upgrade_account();
    require 'includes/admin-menu.php';
  } elseif(isset($_POST['createNewApiKey'])) {
    $api_key = bits_get_api()->create_api_key();
    update_option("bits_api_key", $api_key);
    // This removes any stale cron jobs from previous docker runs
    $timestamp = wp_next_scheduled( 'bits_iterate_backup' );
    if($timestamp !== false) {
      wp_unschedule_event($timestamp, 'bits_iterate_backup');
    }
    require 'includes/admin-menu.php';
  } elseif(isset($_POST['debugBackupNow'])) {
    bits_debug_backup();
  } elseif(isset($_POST['forceCancel'])) {
    bits_force_cancel();
  } else {
    require 'includes/admin-menu.php';
  }
}

function bits_start_backup_wp_cron() {
  $timestamp = wp_next_scheduled( 'bits_iterate_backup' );

  if( $timestamp == false ){
    wp_schedule_event( time(), 'hourly', 'bits_iterate_backup' );
  }

}

add_action( 'bits_iterate_backup', 'bits_create_backup' );
function bits_create_backup(){
  BitsUtil::renice(20);
  $api = bits_get_api();
  $api->create_backup(array("user-initiated" => true));
  $sm = new BitsBackupStateMachine($api);

  $sm->run();
  BitsUtil::reset_nice();
}

add_action( 'bits_iterate_restore', 'bits_create_restore' );
function bits_create_restore($id){
  BitsUtil::renice(20);
  $api = bits_get_api();
  $api->create_restore($id);
  $sm = new BitsRestoreStateMachine($api);

  $sm->run();
  BitsUtil::reset_nice();
}
function bits_debug_backup(){
  BitsUtil::renice(20);
  $api = bits_get_api();
  $summary = $api->step_summary();
  if($summary["backup_id"]) {
    $api->cancel_backup($summary["backup_id"]);
  }
  $api->create_backup(array("user-initiated" => true));
  $sm = new BitsBackupStateMachine($api);

  $sm->run();
  BitsUtil::reset_nice();
}

function bits_backup_for_date() {
  $GLOBALS["BITS_DEBUG"]=false;
  $api = bits_get_api();
  die($api->json($api->get_backups()));
}

function bits_register_account() {
  $api = bits_get_api();
  $response = $api->create_account($_REQUEST["email"], $_REQUEST["password"]);
  die($api->json($response));
}

function bits_login_account() {
  $api = bits_get_api();
  $response = $api->login_account($_REQUEST["email"], $_REQUEST["password"]);
  die($api->json($response));
}

function bits_backup_force_backup_now() {
  wp_schedule_single_event(time(), 'bits_iterate_backup');
  die('ok');
}
function bits_backup_start_job() {
  bits_start_backup_wp_cron();
  die('ok');
}

function bits_force_cancel_safe() {
  $api = bits_get_api();

  $sm = new BitsBackupStateMachine($api);
  $summary = $api->step_summary();
  if(is_wp_error($summary)) {
    return $summary;
  }
  if(isset($summary["backup_id"]) && $summary["backup_id"]) {
    $api->cancel_backup($summary["backup_id"]);
  }
  if(isset($summary["restore_id"]) && $summary["restore_id"]) {
    $api->cancel_restore($summary["restore_id"]);
  }
  return $summary;
}

function bits_force_cancel() {
  bits_force_cancel_safe();
  die("{}");
}

function bits_backup_save_settings() {
  $api = bits_get_api();
  $response = $api->update_site_server($_REQUEST['selected_site']);
  die($api->json($response));
}

function bits_backup_get_status() {
  $api = bits_get_api();

  $summary = $api->step_summary();
  if(is_wp_error($summary)) {
    return die($api->json(array("error" => $summary->get_error_message())));
  }
  $next_scheduled_backup = wp_next_scheduled( 'bits_iterate_backup' );
  if($next_scheduled_backup) {
    $next_scheduled_backup -= time();
  }
  $summary['next_scheduled_backup'] = $next_scheduled_backup;

  die($api->json($summary));
}

function bits_backup_get_backup() {
  $api = bits_get_api();
  $backup = $api->get_backup($_REQUEST["id"]);

  die($api->json($backup));
}

function bits_backup_get_sites() {
  $api = bits_get_api();
  die($api->json($api->get_sites()));
}

function bits_backup_get_plans() {
  $api = bits_get_api();
  die($api->json($api->get_plans()));
}

function bits_restore_from_backup() {
  bits_create_restore($_REQUEST["id"]);
  die("OK");
}

function bits_backup_send_support() {
  $api = bits_get_api();
  die($api->json($api->create_support_ticket($_REQUEST["urgent"], $_REQUEST["content"])));
}

function bits_backup_update_account() {
  $api = bits_get_api();
  $update = $api->update_account($_REQUEST["plan_id"], $_REQUEST["token"]);
  die($api->json($update));
}

?>

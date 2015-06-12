<?php
/*
 * Plugin Name: AnyBackup
 * Plugin URI: http://www.anybackup.io
 * Description: Automatic backups for your wordpress sites.
 * Version: 1.3.3
 * Author: 255 BITS LLC
 * Author URI: https://anybackup.io
 * License: MIT
 */

$GLOBALS["BITS_ANYBACKUP_PLUGIN_VERSION"] = "1.3.3";

if (is_multisite()) {
  exit("AnyBackup does not support multisite wordpress configurations.  Contact us at support@255bits.com to get access to our multisite beta.");
}


require(dirname(__FILE__).'/includes/BitsAnyBackupAPI.php');
require(dirname(__FILE__).'/includes/BitsBackupStateMachine.php');
require(dirname(__FILE__).'/includes/BitsRestoreStateMachine.php');
require(dirname(__FILE__).'/includes/BitsUtil.php');
require(dirname(__FILE__).'/includes/BitsBackupWorker.php');
require(dirname(__FILE__).'/includes/BitsRestoreWorker.php');
require(dirname(__FILE__).'/includes/class-persistent-http.php');

register_activation_hook( __FILE__, 'bits_anybackup_activation');
register_deactivation_hook( __FILE__, 'bits_anybackup_deactivation');
add_action('admin_menu', 'bits_anybackup_menu');
add_action("wp_ajax_bits_backup_list", "bits_backup_list");
add_action("wp_ajax_bits_backup_backup_now", "bits_backup_backup_now");
add_action("wp_ajax_bits_backup_save_settings", "bits_backup_save_settings");
add_action("wp_ajax_bits_backup_get_status", "bits_backup_get_status");
add_action("wp_ajax_bits_backup_get_backup", "bits_backup_get_backup");
add_action("wp_ajax_bits_backup_update_backup", "bits_backup_update_backup");
add_action("wp_ajax_bits_backup_get_sites", "bits_backup_get_sites");
add_action("wp_ajax_bits_backup_get_plans", "bits_backup_get_plans");
add_action("wp_ajax_bits_backup_update_account", "bits_backup_update_account");

add_action("wp_ajax_bits_register_account", "bits_register_account");
add_action("wp_ajax_bits_login_account", "bits_login_account");

add_action("wp_ajax_bits_restore_from_backup", "bits_restore_from_backup");
add_action("wp_ajax_bits_force_cancel", "bits_force_cancel");

add_action( 'admin_enqueue_scripts', 'bits_load_scripts');
add_action( 'admin_init', 'bits_init');

add_action('admin_notices', 'bits_anybackup_admin_notice');

function bits_anybackup_activation() {
  $api = bits_get_api();
  $api->activate();
  bits_start_backup_wp_cron();
}
function bits_anybackup_deactivation() {
  $api = bits_get_api();
  $api->deactivate();
}

function bits_init() {
  //vendor
  wp_register_script('anybackup-angular', plugins_url( '/plugin-assets/anybackup-angular-1.2.20.min.js', __FILE__ ));
  wp_register_script('anybackup-moment', plugins_url( '/plugin-assets/moment-2.8.2.min.js', __FILE__ ));
  wp_register_script('anybackup-angular-ui', plugins_url( '/plugin-assets/anybackup-angular-ui-bootstrap-0.12.0.min.js', __FILE__ ));

  //custom
  wp_register_script('anybackup-base', plugins_url( '/plugin-assets/custom/base.js', __FILE__ ));
  wp_register_script('anybackup-factories', plugins_url( '/plugin-assets/custom/factories.js', __FILE__ ));

  wp_register_script('anybackup-backup', plugins_url( '/plugin-assets/custom/backup.js', __FILE__ ));
  wp_register_script('anybackup-pricing', plugins_url( '/plugin-assets/custom/pricing.js', __FILE__ ));
  wp_register_script('anybackup-settings', plugins_url( '/plugin-assets/custom/settings.js', __FILE__ ));
  wp_register_script('anybackup-restore', plugins_url( '/plugin-assets/custom/restore.js', __FILE__ ));
  wp_register_script('anybackup-migrate', plugins_url( '/plugin-assets/custom/migrate.js', __FILE__ ));

  //vendor
  wp_register_style('anybackup-bootstrap', plugins_url( '/plugin-assets/anybackup-bootstrap-namespaced-3.2.0.css', __FILE__ ));
  wp_register_style('anybackup-font-awesome', plugins_url( '/plugin-assets/font-awesome-4.3.0/css/font-awesome.min.css', __FILE__ ));

  //custom
  wp_register_style('anybackup-base', plugins_url( '/plugin-assets/custom/base.css', __FILE__ ));
}
function bits_load_scripts() {
  //vendor
  wp_enqueue_script('anybackup-angular');
  wp_enqueue_script('anybackup-angular-ui');
  wp_enqueue_script('anybackup-moment');

  //custom
  wp_enqueue_script('anybackup-base');
  wp_enqueue_script('anybackup-factories');

  wp_enqueue_script('anybackup-backup');
  wp_enqueue_script('anybackup-pricing');
  wp_enqueue_script('anybackup-settings');
  wp_enqueue_script('anybackup-restore');
  wp_enqueue_script('anybackup-migrate');

  //vendor
  wp_enqueue_style('anybackup-bootstrap');
  wp_enqueue_style('anybackup-font-awesome');

  //custom
  wp_enqueue_style('anybackup-base');
}

function bits_anybackup_menu() {
  $icon = plugins_url("anybackup/plugin-assets/logo-20x20.png");
  add_menu_page("AnyBackup", "AnyBackup", 'manage_options', 'backup_bits_anybackup', 'anybackup_render_backup', $icon); 
  add_submenu_page('backup_bits_anybackup', __('Restore'), __('Restore'), 'manage_options', 'anybackup_render_restore', 'anybackup_render_restore'); 
  add_submenu_page('backup_bits_anybackup', __('Migrate'), __('Migrate'), 'manage_options', 'anybackup_render_migrate', 'anybackup_render_migrate'); 
  //add_submenu_page('backup_bits_anybackup', __('Changelog'), __('Changelog'), 'manage_options', 'anybackup_render_changelog', 'anybackup_render_changelog'); 
  add_submenu_page('backup_bits_anybackup', __('Settings'), __('Settings'), 'manage_options', 'anybackup_render_settings', 'anybackup_render_settings'); 
  add_submenu_page('backup_bits_anybackup', __('Support'), __('Support'), 'manage_options', 'anybackup_render_support', 'anybackup_render_support'); 
  add_submenu_page('backup_bits_anybackup', __('Plans & Pricing'), "<span style='color:#f18500'>".__('Plans & Pricing')."</span>", 'manage_options', 'anybackup_render_pricing', 'anybackup_render_pricing'); 
  global $submenu;
  if( isset( $submenu['backup_bits_anybackup'] ) ) {
    $submenu['backup_bits_anybackup'][0][0] = __("Backup");
  }

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

function anybackup_render($page) {
  require "includes/admin-menu-common.php";
  require "includes/$page";
}

function anybackup_render_backup() {
  if(isset($_GET['runSpecs'])) {
    anybackup_render('bits-backup-spec.php');
  } elseif(isset($_POST['upgradeAccountToPaid'])) {
    bits_debug_upgrade_account();
    anybackup_render('admin-menu-backup.php');
  } elseif(isset($_POST['createNewApiKey'])) {
    $api_key = bits_get_api()->create_api_key();
    update_option("bits_anybackup_dismiss_initial_notice", false);
    update_option("bits_api_key", $api_key);
    // This removes any stale cron jobs from previous docker runs
    $timestamp = wp_next_scheduled( 'bits_iterate_backup' );
    if($timestamp !== false) {
      wp_unschedule_event($timestamp, 'bits_iterate_backup');
    }
    anybackup_render('admin-menu-backup.php');
  } elseif(isset($_POST['debugBackupNow'])) {
    bits_debug_backup();
  } elseif(isset($_POST['forceCancel'])) {
    bits_force_cancel();
  } else {
    anybackup_render('admin-menu-backup.php');
  }
}

function anybackup_render_restore() {
  anybackup_render("admin-menu-restore.php");
}
function anybackup_render_migrate() {
  anybackup_render('admin-menu-migrate.php');
}
function anybackup_render_changelog() {
  anybackup_render('admin-menu-changelog.php');
}
function anybackup_render_settings() {
  if($_POST) {
    $api = bits_get_api();
    $result = $api->update_site(array("backup_frequency_in_hours" => intval($_POST["backup_frequency_in_hours"])));
  }
  anybackup_render('admin-menu-settings.php');
}

function anybackup_render_support() {
  if($_POST){
    $api = bits_get_api();
    $api->create_support_ticket($_REQUEST["urgent"], $_REQUEST["content"]);
  }

  anybackup_render('admin-menu-support.php');
}
function anybackup_render_pricing() {
  anybackup_render('admin-menu-pricing.php');
}
add_filter('cron_schedules', 'add_scheduled_interval');
 
// add once 30 minute interval to wp schedules
function add_scheduled_interval($schedules) {

  $schedules['minutes_30'] = array('interval'=>1800, 'display'=>'Once every 30 minutes');

  return $schedules;
}

function bits_start_backup_wp_cron() {
  $timestamp = wp_next_scheduled( 'bits_iterate_backup' );

  if( $timestamp == false ){
    wp_schedule_event( time()+1800, 'minutes_30', 'bits_iterate_backup' );
  }
  wp_schedule_single_event(time(), 'bits_user_initiated_backup');

}

add_action( 'bits_iterate_backup', 'bits_create_backup' );
function bits_create_backup(){
  BitsUtil::renice(20);
  $api = bits_get_api();
  $api->create_backup(array());
  $sm = new BitsBackupStateMachine($api);

  $sm->run();
  BitsUtil::reset_nice();
}

add_action( 'bits_user_initiated_backup', 'bits_user_initiated_create_backup' );
function bits_user_initiated_create_backup() {
  $api = bits_get_api();
  $sm = new BitsBackupStateMachine($api);

  $sm->run();
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

function bits_backup_list() {
  $GLOBALS["BITS_DEBUG"]=false;
  $api = bits_get_api();
  die($api->json($api->get_backups($_REQUEST['site_id'])));
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

function bits_backup_backup_now() {
  $api = bits_get_api();
  $api->create_backup(array("user-initiated" => true));
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
  $api = bits_get_api();
  $api->log("info", "User cancelled");
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
  if($summary['next_scheduled_backup'] < $next_scheduled_backup) {
    $summary['next_scheduled_backup'] = $next_scheduled_backup;
  }

  die($api->json($summary));
}

function bits_backup_get_backup() {
  $api = bits_get_api();
  $backup = $api->get_backup($_REQUEST["id"]);

  die($api->json($backup));
}

function bits_backup_update_backup() {
  $api = bits_get_api();
  
  $args = array("name" => $_REQUEST["name"]);
  die($api->update_backup($_REQUEST["id"], $args));
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
  $bits_required_permissions = array('manage_options', 'update_core', 'update_plugins', 'update_themes', 'upload_files');
  foreach($bits_required_permissions as $permission) {
    if(!current_user_can($permission)) {
      $api->log("info", "Restore called without permission");
      exit("You do not have permission to administrate this site.  Missing permission '$permission'.  Please use this plugin with an admin user.");
    }
  }


  bits_create_restore($_REQUEST["id"]);
  die("OK");
}

function bits_backup_update_account() {
  $api = bits_get_api();
  $update = $api->update_account($_REQUEST["plan_id"], $_REQUEST["token"]);
  die($api->json($update));
}

function bits_anybackup_admin_notice(){
  $current_page = "";
  if(isset($_GET) && isset($_GET['page'])) {
    $current_page = $_GET['page'];
  }
  if(isset($_GET) && isset($_GET['bits_anybackup_dismiss'])) {
    update_option("bits_anybackup_dismiss_initial_notice", true);
  }
  if(get_option("bits_anybackup_dismiss_initial_notice") == true) {
    return;
  }
  if($current_page != 'backup_bits_anybackup') {
?>
   <div class="updated">
     <a href='<?php echo admin_url('admin.php?page=backup_bits_anybackup');?>'>
        <h3> 
          <img src="<?php echo plugins_url("anybackup/plugin-assets/logo-512x512.png"); ?>" style='width:24px;height:24px;float:left;margin-right:6px;'/>
          AnyBackup
        </h3>
      </a> 
      <p>
        Congratulations!  You're now setup for daily backups.  Register to access your backups anywhere.
      </p>
      <p>
      <a target="_self" href='<?php echo admin_url('admin.php?page=backup_bits_anybackup');?>'>Backups</a>
      | 
      <a target="_self" href='<?php echo admin_url('admin.php?page=anybackup_render_settings');?>'>Settings</a>
      | 
      <a href='<?php echo add_query_arg(array('bits_anybackup_dismiss' => true),  $_SERVER["REQUEST_URI"] ); ?>'> Dismiss </a>
      </p>
    </div>
<?php
  }
}


?>

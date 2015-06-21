<?php
  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }
?>
<?php
  $api = bits_get_api();
  $site = $api->get_site();
  $backup_frequency_in_hours = $site["backup_frequency_in_hours"];
?>
<div class='wrap' ng-controller="SettingsController" ng-app="BitsAnyBackup">
  <?php if($_POST) { ?>
  <div id="setting-error-settings_updated" class="updated settings-error"> 
    <p><strong>Settings saved.</strong></p>
  </div>
  <?php } ?>

  <h2>AnyBackup Settings</h2>
  <?php require("_common-notifications.php"); ?>

  <form method='POST'>
    <table class="form-table">
      <tr class="form-field">
        <th><label>Automatic Backup Frequency</label></th>
        <td>
          <select name='backup_frequency_in_hours'>
            <?php
              $options = array( 168 => "Weekly", 24 => "Daily", 12 => "Twice a day", -1 => "Manual Only");
              foreach($options as $hours => $name) {?>
                <option value='<?php echo $hours ?>' <?php echo(($backup_frequency_in_hours == $hours) ? "selected" : "")?>><?php echo $name ?></option>
            <?php } ?>
          </select>
        </td>
      </tr>
    </table>
    <p class='submit'>
      <input class='button button-primary' type='submit' value="Save Changes"></input>
    </p>
  </form>
</div>

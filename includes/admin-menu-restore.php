<?php
  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }
?>
<?php $api = bits_get_api(); ?>
<div class='wrap'>
  <div ng-controller='RestoreController' ng-app="BitsAnyBackup">
    <h2>Restore</h2>
    <?php require("_common-notifications.php"); ?>

    <div class='backup-list' ng-show="backups.length > 0">
      <h3>Backup to restore from:</h3>
      <select ng-model="selectedBackupId" ng-change="selectBackup()">
        <option value="">Select a Backup</option>
        <option ng-repeat="backup in backups | filter:{state:'COMMITTED'}" ng-value="backup.id" ng-selected="backup.id == selectedBackupId">{{backup.name}} created {{readableDate(backup) | lowercase}}</option>
      </select>
      <div class='detail' ng-if="!selectedBackup && selectedBackupId">
        Loading...
      </div>
      <div class='detail backup-info' ng-if='selectedBackup'>
        <div class='backup-details'>
          Restoration will download '{{selectedBackup.name}}' and then restore.
        </div>
        <div class='backup-details' ng-if="selectedBackup">
          <a href="#" ng-click="restoreFromBackup()" class="button button-primary">
            Restore from {{selectedBackup.name}}
          </a>
          <a class="button" href="?page=backup_bits_anybackup&backup_id={{selectedBackup.id}}">View Details</a>
        </div>
      </div>


  </div>
  <div class='backup-list' ng-show="backups.length == 0 && !loading">
    <h3> No Backups Found </h3>
    <p><a href="?page=backup_bits_anybackup">Create a backup</a></p>
  </div>
</div>

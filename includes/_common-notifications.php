<?php
if (! defined( 'ABSPATH' )) {
  exit; // Exit if accessed directly
}
?>
<div class="updated settings-error" ng-if="status.most_recent_backup && status.most_recent_backup.committed_seconds_ago != null && status.most_recent_backup.committed_seconds_ago < 60"> 
  <p>
    Finished '<a href="?page=backup_bits_anybackup&backup_id={{status.most_recent_backup.id}}">{{status.most_recent_backup.name}}</a>'
  </p>
</div>

<div class="updated settings-error" ng-if="status.most_recent_restore && status.most_recent_restore.completed_seconds_ago != null && status.most_recent_restore.completed_seconds_ago < 60"> 
  <p>
    Finished restore from '<a href="?page=backup_bits_anybackup&backup_id={{status.most_recent_restore.backup_id}}">{{status.most_recent_restore.backup_name}}</a>'
  </p>
</div>

<div class="updated settings-error" id='status' ng-if="status.backup_running">
  <p>
  <h3 class='backing-up-header'>Creating a new backup
    <a href="#" ng-click='cancel()' class='cancel-container'><i class='fa fa-times-circle'></i><span class='cancel-button'>Cancel</span></a>
  </h3>
  <hr>
  <div>
    <i class='fa fa-spinner fa-pulse'></i> 
    {{status.step_description}}
  </div>
  </p>
</div>

<div class="updated settings-error" id='status' ng-if="status.restore_running">
  <p>
  <h3 class='restore-up-header'>Restoring
    <a href="#" ng-click='cancel()' class='cancel-container'><i class='fa fa-times-circle'></i><span class='cancel-button'>Cancel</span></a>
  </h3>
  <hr>
  <div>
    <i class='fa fa-spinner fa-pulse'></i> 
    {{status.step_description}}
  </div>
  </p>
</div>

<div class="error settings-error" ng-if="backup_cancelled"> 
 <p>
   Backup cancelled.
 </p>
</div>

<div class="error settings-error" ng-if="restore_cancelled"> 
 <p>
   Restore cancelled.
 </p>
</div>
<div ng-if='loading'>
  <div class="logo-section">
    <h1><i class='fa fa-spinner fa-pulse'></i></h1>
  </div>
</div>


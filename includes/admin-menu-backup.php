<?php 
  $api = bits_get_api();
  $backups = $api->get_backups();
?>

<div class='wrap' ng-app="BitsAnyBackup" ng-controller="BackupController">
  <?php require 'admin-menu-debug-bar.php';?>
    <h2>
      Backup
    </h2>

  <div class="updated settings-error" ng-if="!status.email && !loading"> 
    <p>
      <a ng-click='openLogin()'>Login</a> or <a href='#' ng-click='openRegister()'>register</a> to access your backups in an emergency.
    </p>
  </div>
  <div class="updated premium-nag settings-error" ng-if="!status.paid && status.email"> 
    <p>
      <span ng-if="thanks_for_registering">Thanks for registering. </span>
      <a target="_self" href="?page=anybackup_render_pricing">Upgrade</a> to unlock unlimited previews, multiple sites, and urgent support.  
    </p>
  </div>

  <?php require("_common-notifications.php"); ?>
  <div class="updated settings-error" ng-if="backups.length == 0 && status.backup_running"> 
    <p>
      Your first backup can take a while.  Feel free to leave this page and check in later.
    </p>
  </div>
  <div ng-if='loading'>
    <div class="logo-section">
      <h1><i class='fa fa-spinner fa-pulse'></i></h1>
    </div>
  </div>
 
  <div id='dashboard' ng-hide='loading'>
    <div ng-if="!status.backup_running">
      <div ng-if='status.backup_allowed'>
        <div class='backup-status'>
          <button id="enableBackups" ng-if='!backup_loading && !status.backup_running && !status.restore_running' class='button button-primary' ng-click="backupNow()">Backup Now</button>
          <div ng-if="backup_loading">Loading...</div>
          <div ng-if="!backup_loading" id="status-number">{{status.step_description}}</div>
        </div>
      </div>
    </div>

    <div class='backup-list' ng-show="backups.length > 0">

      <h3>My backups</h3>

      <select ng-model="selectedBackupId" ng-change="selectBackup()">
        <option value="">Select a Backup</option>
        <option ng-repeat="backup in backups" ng-value="backup.id" ng-selected="backup.id == selectedBackupId">{{backup.name}} created {{readableDate(backup) | lowercase}}</option>
      </select>
    </div>
    <div class='detail' ng-if="!selectedBackup && selectedBackupId">
      Loading...
    </div>
    <div class='detail backup-info' ng-show='selectedBackup'>
      <div ng-if="editingName" class="title">
        <form ng-submit='saveName()'>
          <input id='edit-name' ng-model='selectedBackup.name'></input>
          <input type='submit'></input>
        </form>
      </div>
      <div class="title" ng-hide='editingName'>{{selectedBackup.name}} <a ng-click="editName()"><i class="fa fa-pencil"></i></a> </div>
      <form method="POST">
        <div class='backup-details'>
          <span class="field-label"><label>Created</label></span>
          <span class='backup-content'>{{readableDate(selectedBackup)}}</span>
        </div>
        <div class='backup-details'>
          <label>Tables</label>
          <span class='backup-content'>{{selectedBackup.schema_count}}</span>
        </div>
        <div class='backup-details'>
          <label>Rows</label>
          <span class='backup-content'>{{selectedBackup.row_count}}</span>
        </div>
        <div class='backup-details'>
          <label>Files</label>
          <span class='backup-content'>{{selectedBackup.file_count}}</span>
        </div>
        <div ng-repeat="(key, attributes) in selectedBackup.site_info">
          <div class='backup-details'>
            <label>{{attributes.name}}</label>
            <span class='backup-content'>{{attributes.value}}</span>
          </div>
        </div>

        <div class='backup-details'>
          <a class="button" ng-disabled='!status.previews_allowed || !status.email' href="<?php echo $api->get_server(); ?>/backups/{{selectedBackup.id}}/preview" target='_blank'>
            Live Preview
          </a>
          <span ng-if="!status.paid" class='premium'>*</span>
          <a target="_self" href="?page=anybackup_render_restore&backup_id={{selectedBackup.id}}" class="button button-primary">Restore</a>
        </div>
        <div class='backup-details' ng-if='!status.paid && status.email'>
          <div class='premium spacer'>
            <i class='fa fa-asterisk'></i> {{status.previews_remaining}} previews remaining.  <a href="?page=anybackup_render_pricing">Upgrade</a> for unlimited.
          </div>
        </div>
         <div class='backup-details' ng-if='!status.paid && !status.email'>
          <div class='premium spacer'>
            <i class='fa fa-asterisk'></i> Register to access your live preview.
          </div>
        </div>
        <input type='hidden' name='restoreFromBackup' value="true"/>
        <input type="hidden" name='backupId' value='{{selectedBackup.id}}'/>
      </form>
    </div>
  </div>
  <footer class='footer' ng-if="status.email"> Logged in as {{status.email}} </footer>
</div>
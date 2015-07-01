<?php
  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }
?>
<div class="wrap" ng-controller='MigrateController' ng-app="BitsAnyBackup" >
<h2> Migrate </h2>
<?php require("_common-notifications.php"); ?>
<div id="setting-error-settings_updated" class="updated settings-error"> 
  <p><strong>Migration Instructions</strong>
      <ol>
        <li>On a fresh wordpress, install the AnyBackup plugin</li>
        <li><a ng-click='openLogin()'>Login</a> to your AnyBackup account</li>
        <li>Visit this 'Migrate' page</strong></p>
</div>
<div>
  <div ng-if="sites.length > 0">
    <h3>Which site?</h3>
    <select ng-options='site.id as (""+site.name+" - "+site.uri) for site in sites' ng-model='selectedSiteId' ng-change="selectSite(selectedSiteId)">
    </select>
    <div ng-show='selectedSiteId'>
      <h3>Which backup?</h3>
      <select ng-model="selectedBackupId" ng-change="selectBackup()" >
        <option value="">Select a Backup</option>
        <option ng-repeat="backup in backups | filter:{state:'COMMITTED'}" ng-value="backup.id" ng-selected="backup.id == selectedBackupId">{{backup.name}} created {{readableDate(backup) | lowercase}}</option>
      </select>
    </div>
    <div ng-if='selectedBackupId'>
      <p>
        Migration will overwrite any data on this site with data from the selected backup.
      </p>
      <button id="migrate" class='button button-primary' ng-click="migrate(selectedBackupId)">Migrate</button>
    </div>
  </div>
  <div ng-if="sites.length == 0">
    <div ng-if="status.email">
      0 sites found for {{status.email}}.  
    </div>
    <div ng-if="status.email == null">
      Please <a ng-click='openLogin()'>login</a> to migrate.
    </div>
  </div>

</div>
</div>

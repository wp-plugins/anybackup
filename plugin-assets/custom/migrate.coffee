app.controller "MigrateController", ($scope, $controller, $http) ->
  $controller("BaseController", {$scope:$scope}) #subclass
  $scope.loading = true
  $scope.updateStatus() 

  $scope.migrate = (backupId) ->
    $scope.status.restore_running = true
    $scope.status.step_description = "Setting up migration."
    console.log("Selected backup id: ", backupId)
    clearTimeout($scope.updateStatusTimeout) if $scope.updateStatusTimeout
    $scope.updateStatusTimeout = null
    data = {
      action: "bits_restore_from_backup",
      id: backupId
    }
    request = $http {
      url: ajaxurl, 
      method: "post",
      params: data
    }
    request.success ->
      $scope.updateStatus() 

    $scope.step_description = "Starting migration.  You can cancel at any step."



  $scope.getSites = ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_sites"
      }
    }

    request.success (data, status) ->
      $scope.sites = (site for site in data['sites'] when site.name != null)
      $scope.currentSiteId = data['current_site_id']
      $scope.list()
      $scope.loading=false

  $scope.selectSite = (siteId) ->
    $scope.list(siteId)
  $scope.getSites()

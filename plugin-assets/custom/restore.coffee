app.controller "RestoreController", ($scope, $controller, $http) ->
  $controller("BaseController", {$scope:$scope}) #subclass
  $scope.loading = true

  $scope.updateStatus() 

  $scope.restoreFromBackup = ->
    $scope.status.restore_running = true
    $scope.status.step_description = "Preparing."
    data = {
      action: "bits_restore_from_backup",
      id: $scope.selectedBackup.id
    }
    request = $http {
      url: ajaxurl, 
      method: "post",
      params: data
    }
    request.success ->
      $scope.updateStatus() 

    $scope.step_description = "Starting restore.  You can cancel at any step."


  $scope.list()

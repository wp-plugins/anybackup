app.controller "SettingsController", ($scope, $http, $controller) ->
  $controller("BaseController", {$scope:$scope}) #subclass
  $scope.updateStatus() 

  $scope.saveSettings = ->
    data = {
        action: "bits_backup_save_settings"
        selected_site: $scope.selected_site
      }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success (data, status, headers, config) =>
      setTimeout updateStatus, 1


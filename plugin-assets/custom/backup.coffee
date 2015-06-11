app.controller "BackupController", ($scope, $http, $controller, backupFactory, accountFactory) ->
  $controller("BaseController", {$scope:$scope}) #subclass
  $scope.list =  ->
    backupFactory.list null, (data) ->
      $scope.backups = data.backups
      $scope.loading = false
      #request.error (data, status, headers, config) =>

  $scope.editName = () ->
    $scope.editingName=true
    focus = ->
      angular.element("#edit-name")[0].focus()
    window.setTimeout focus, 1

  $scope.saveName = () ->
    $scope.editingName=false
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_update_backup",
        id: $scope.selectedBackup.id
        name: $scope.selectedBackup.name
      }
    }
    request.success (data, status, headers, config) =>
      $scope.list()
      console?.log("Updated name.")

  $scope.backupNow = ->
    $scope.status.backup_running = true
    $scope.backup_cancelled = false
    $scope.backup_loading = true
    data = {
        action: "bits_backup_backup_now"
      }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success ->
      $scope.updateStatus (data) ->
        $scope.backup_loading = false
      $scope.state = "enabled"
    $scope.step_description = "Starting your backup"

  $scope.supportMessageSent = ->
    $scope.showSupportMessage = true

  $scope.updateStatus() 

  $scope.status = "Loading"
  $scope.step_number = -1
  $scope.list()

  $scope.$on "user-login", ->
    $scope.updateStatus()
    $scope.list()
  $scope.$on "user-registered", ->
    $scope.thanks_for_registering = true
    $scope.updateStatus()
    $scope.list()

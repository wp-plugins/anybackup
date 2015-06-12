window.app = angular.module('BitsAnyBackup', ['ui.bootstrap'])

app.filter 'html', ($sce) ->
  (input) ->
    $sce.trustAsHtml(input)

app.config ($locationProvider) ->
  $locationProvider.html5Mode(true)

app.controller "LoginModalController", ($scope, $http, $modalInstance, $rootScope) ->
  $scope.backups = []
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')

  $scope.loginAccount = ->
    $scope.loginFormSubmitting=true
    data = {
        action: "bits_login_account",
        email: $scope.email_input,
        password: $scope.password_input
      }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success (data, status, headers, config) =>
      $scope.status = data.status
      $scope.loginFormSubmitting=false
      if(data.status == 200)
        $rootScope.$broadcast("user-login")
        $modalInstance.dismiss('complete')

    request.error (data, status, headers, config) =>
      $scope.loginFormSubmitting=false
      $scope.status = 500

    false


app.controller "RegistrationModalController", ($scope, $modalInstance, $http, $rootScope) ->
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')
  $scope.registerUser = ->
    $scope.registerFormSubmitting=true

    data = {
        action: "bits_register_account",
        email: $scope.email_input,
        password: $scope.password_input
      }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success (data, status, headers, config) =>
      if(data.status == 200)
        if(data.error)
          $scope.registerFormSubmitting=false
          $scope.error = data.error
        else
          $rootScope.$broadcast("user-registered")
          $modalInstance.dismiss('complete')

    request.error (data, status, headers, config) =>
      $scope.registerFormSubmitting=false
      $scope.error = "Error communicating with server"


app.controller "BaseController", ($scope, $http, $location, backupFactory, accountFactory) ->
  $scope.backups = []

  $scope.selectedBackupId = $location.search().backup_id

  $scope.list = (siteId) ->
    backupFactory.list siteId, (data) ->
      $scope.backups = data.backups
      $scope.loading = false

  $scope.selectBackup = () ->
    unless $scope.selectedBackupId
      $scope.selectedBackup = null
      return
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_backup",
        id: $scope.selectedBackupId
      }
    }
    request.success (data, status, headers, config) =>
      $scope.selectedBackup = data


  $scope.openLogin = ->
    accountFactory.loginModal()

  $scope.openRegister = ->
    accountFactory.registerModal()

  $scope.readableDate = (backup) ->
    if(backup && backup.committed_at)
      localTimeZone = new Date().getTimezoneOffset()
      moment.parseZone(backup.committed_at).zone(localTimeZone/60).calendar()
    else
      ""

  #Warning this sets up a timeout event
  $scope.updateStatus = (callback)->
    request = accountFactory.getStatus (data) ->
      $scope.status = data
      callback(data) if callback

    request.finally ->
      clearTimeout($scope.updateStatusTimeout) if $scope.updateStatusTimeout
      $scope.updateStatusTimeout = setTimeout($scope.updateStatus, 7000)


  $scope.cancel = () ->
    data = {
      action: "bits_force_cancel"
    }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success ->
      $scope.backup_cancelled = $scope.status.backup_running
      $scope.restore_cancelled = $scope.status.restore_running
      $scope.status.backup_running = false
      $scope.status.restore_running = false
      $scope.status.step_description = "Cancelled."
      $scope.updateStatus()

    $scope.status.step_description = "Cancelling..."

  if($scope.selectedBackupId)
    $scope.selectBackup()


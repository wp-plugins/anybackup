app = angular.module('BitsAnyBackup', ['ui.bootstrap'])

app.filter 'html', ($sce) ->
  (input) ->
    $sce.trustAsHtml(input)

updateEmail = (email) ->
  ->
    $parentScope = angular.element("#dashboard").scope()
    $parentScope.$apply ->
      $parentScope.email = email

updateStatus = () ->
  $parentScope = angular.element("#dashboard").scope()
  $parentScope.$apply ->
    $parentScope.updateStatus()
    $parentScope.updateBackupsForChosenDay()

supportMessage = () ->
  $parentScope = angular.element("#dashboard").scope()
  $parentScope.$apply ->
    $parentScope.supportMessageSent()

getEmail = () ->
  $parentScope = angular.element("#dashboard").scope()
  $parentScope.email

planChanged = () ->
  $parentScope = angular.element("#dashboard").scope()
  $parentScope.$apply ->
    $parentScope.paid=true

app.controller "BitsSettingsModal", ($scope, $http, $modalInstance) ->
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')
  $scope.getSites = ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_sites"
      }
    }

    request.success (data, status) ->
      $scope.sites = data['sites']
      $scope.selected_site = data['current_site_id']
      # Default the name
      for site in $scope.sites
        if $scope.selected_site == site.id && site.name == null
          site.name = "New site"


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
      if(data.status == 200)
        $modalInstance.dismiss('complete')
      else
        console.error("Did not save site")
        console.error(data)

        # TODO: error handling?

  $scope.getSites()

app.controller "BitsSupportModal", ($scope, $http, $modalInstance, $modal) ->
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')

  $scope.upgradeToPaid = ->
    $modalInstance.dismiss('cancel')
    content = angular.element('#upgradeModal').html()
    console.log $modal.open({
      template: content,
      controller: 'BitsUpgradeModal',
      size: 'lg'
    })



  $scope.sendSupport = ->
    data = {
      action: "bits_backup_send_support",
      content: $scope.content,
      urgent: $scope.urgent
    }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success (data, status, headers, config) =>
      console.log data
      if(data.status == 200)
        setTimeout supportMessage, 1
        $modalInstance.dismiss('complete')
      else
        console.error("Did not send support")
        console.error(data)
        $scope.error = data

        # TODO: error handling?

app.controller "BitsUpgradeModal", ($scope, $http, $modalInstance) ->
  $scope.loadPlans = ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_plans"
      }
    }
    request.success (data, status, headers, config) =>
      console.log "Success"
      console.log(data)
      $scope.plans = data.plans
      
    request.error (data, status, headers, config) =>
      console.log "error"
      console.error("Request to bits_load_plans_failed!") # TODO - error reporting?

  $scope.loadPlans()
  console.log("Loading plans")


  $scope.getStripeToken = () ->
    DEV_TOKEN="pk_096woK2npZnBc1cPETWmMsNmjod7e"
    PROD_TOKEN="pk_096wf6Q6pH1974ZLUF8lZXTUh3Ceg"
    if(window.location.href.match(/localhost/))
      DEV_TOKEN
    else
      PROD_TOKEN
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')


  $scope.getPlan = (name) ->
    (plan for plan in $scope.plans when plan.name is name)[0]
  $scope.cancelAccount = ()->
    plan_id = $scope.getPlan('free').id
    if(confirm("Are you sure you want to cancel your account?  This may stop backup of your sites."))
      $scope.updateAccount(plan_id)
  $scope.updateAccount = (plan_id, token_id=null)->
    params = {
      action: "bits_backup_update_account",
      plan_id: plan_id,
      token: token_id
    }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params:params
    }
    request.success (data, status, headers, config) =>
      $scope.status = data.status
      setTimeout planChanged, 1
      console.log("SUCCESSED",data)

    request.error (data, status, headers, config) =>
      $scope.status = 500
      console.log "error"
      console.error("Request to bits_update_account failed!") # TODO - error reporting?
      console.log(data)


  $scope.openCheckout = (plan) ->
    handler = StripeCheckout.configure
      key: $scope.getStripeToken(),
      image: 'https://anybackup.io/images/logo-512x512.png',
      email: getEmail(),
      token: (token) ->
        $scope.updateAccount(plan.id, token.id)

    handler.open
      name: 'AnyBackup ' + plan.name,
      description: plan.price+'/m',
      amount: plan.price_in_cents


app.controller "BitsLoginModal", ($scope, $http, $modalInstance) ->
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')

  $scope.loginAccount = ->
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
      if(data.status == 200)
        setTimeout updateEmail(data.email), 1
        $modalInstance.dismiss('complete')
        console.log("closeModal")
      console.log "Success"
      console.log(data)

    request.error (data, status, headers, config) =>
      $scope.status = 500
      console.log "error"
      console.error("Request to bits_login_account failed!") # TODO - error reporting?
      console.log(data)

    false


app.controller "BitsRegistrationModal", ($scope, $modalInstance, $http) ->
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')
  $scope.registerUser = ->

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
      console.log "Success"
      console.log(data)
      if(data.status == 200)
        if(data.error)
          $scope.error = data.error
        else
          setTimeout updateEmail(data.email), 1
          $modalInstance.dismiss('complete')
          console.log("closeModal")

    request.error (data, status, headers, config) =>
      console.log "error"
      $scope.error = "Error communicating with server"
      console.error("Request to bits_register_account failed!") # TODO - error reporting?



app.controller "BitsAnyBackupDashboard", ($scope, $http, $modal, $rootScope) ->
  $scope.selectedCalendarDate = new Date()
  $scope.format = 'dd-MMMM-yyyy'
  $scope.backups = []
  $scope.plans = ['free', 'professional']

  $scope.changePlanDebug = (element) ->
    console.log(element, "toggle")

  $scope.setInitialNextRunTimestamp = (timestamp) ->
    console.log 'set initial'
    console.log timestamp
    if(timestamp.length > 0) 
      $scope.state = 'enabled'
    else
      $scope.state = 'welcome'

  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  }

  $scope.updateBackupsForChosenDay = ->
    console.log("doing ajax")
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_for_date",
        date: $scope.selectedCalendarDate
      }
    }
    request.success (data, status, headers, config) =>
      console.log "Success"
      console.log(data)
      $scope.backups = data.backups
      
    request.error (data, status, headers, config) =>
      console.log "error"
      console.error("Request to bits_backup_for_date failed!") # TODO - error reporting?


  $scope.selectBackup = (backup) ->
    $scope.selectedBackup = backup
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_backup",
        id: backup.id
      }
    }
    request.success (data, status, headers, config) =>
      console.log "Success"
      console.log(data)
      $scope.selectedBackup = data
      
    request.error (data, status, headers, config) =>
      console.log "error"
      console.error("Request to bits_backup_get_backup failed!") # TODO - error reporting?


  $scope.open = ($event) ->
    $event.preventDefault()
    $event.stopPropagation()
    $scope.opened = true

  $scope.openSettings = ->
    content = angular.element('#settingsModal').html()
    console.log $modal.open({
      template: content,
      controller: 'BitsSettingsModal',
      size: 'sm'
    })


  $scope.openSupport = ->
    content = angular.element('#supportModal').html()
    console.log $modal.open({
      template: content,
      controller: 'BitsSupportModal',
      size: 'sm'
    })

  $scope.openUpgrade = ->
    content = angular.element('#upgradeModal').html()
    console.log $modal.open({
      template: content,
      controller: 'BitsUpgradeModal',
      size: 'lg'
    })
  $scope.openLogin = ->
    content = angular.element('#loginModal').html()
    console.log $modal.open({
      template: content,
      controller: 'BitsLoginModal',
      size: 'sm'
    })
  $scope.openRegister = ->
    content = angular.element('#registrationModal').html()
    console.log $modal.open({
      template: content,
      controller: 'BitsRegistrationModal',
      size: 'sm'
    })
  $scope.enableBackups = ->
    $scope.backup_running = true
    data = {
        action: "bits_backup_start_job"
      }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success ->
      $scope.state = "enabled"
    $scope.step_description = "Starting your backup"

  $scope.forceBackupNow = ->
    $scope.backup_running = true
    data = {
        action: "bits_backup_force_backup_now"
      }
    request = $http {
      url: ajaxurl, 
      method: "POST",
      params: data
    }
    request.success ->
      $scope.state = "enabled"
    $scope.step_description = "Starting your backup"

  $scope.readableDate = (backup) ->
    if(backup && backup.committed_at)
      localTimeZone = new Date().getTimezoneOffset()
      moment.parseZone(backup.committed_at).zone(localTimeZone/60).calendar()
    else
      ""

  $scope.supportMessageSent = ->
    $scope.showSupportMessage = true

  $scope.restoreFromBackup = ->
    $scope.restore_running = true
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
      console.log("SUCCESS")
    $scope.step_description = "Starting restore.  You can cancel at any step."

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
      $scope.backup_running = false
      $scope.restore_running = false
      $scope.step_description = "Cancelled."
      $scope.updateStatus()

    $scope.step_description = "Cancelling..."


  $scope.updateStatus = ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_status"
      }
    }
    $scope.calls = 0

    request.success (data, status, headers, config) =>
      $scope.email=data.email
      $scope.status_known = true
      $scope.backup_allowed = data.backup_allowed
      $scope.restore_allowed = data.restore_allowed
      $scope.disabled_reason = data.disabled_reason
      $scope.previews_allowed = data.previews_allowed
      $scope.previews_remaining = data.previews_remaining
      $rootScope.paid = data.paid
      $rootScope.plan = data.plan
      if(data.backup_id || data.restore_id)
        $scope.percent_complete = data.percent_complete
        if($scope.percent_complete < 15)
          $scope.percent_complete = 15

        $scope.step_description = data.step_description
        $scope.status = data.status
        $scope.backup_running = data.backup_id
        $scope.restore_running = data.restore_id
      else
        if($scope.backup_running)
          $scope.updateBackupsForChosenDay()
        $scope.backup_running = false
        $scope.restore_running = false
        $scope.percent_complete = 0
        if(data.next_scheduled_backup == false)
          $scope.step_description = ""
        else
          readable_time = moment().add(data.next_scheduled_backup, "seconds").fromNow()
          $scope.step_description = "Your next backup starts #{readable_time}.  "
        

    request.error (data, status, headers, config) =>
      console.log "error"
      console.error("Request to bits_backup_get_status failed!") # TODO - error reporting?

  setInterval($scope.updateStatus, 7000)
  $scope.updateStatus()

  $scope.status = "Loading"
  $scope.step_number = -1
  console.log("Updating backup")
  $scope.updateBackupsForChosenDay()

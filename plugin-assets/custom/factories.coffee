app.factory 'accountFactory', ($http, $modal) ->
  this.loginModal = ->
    content = angular.element('#loginModal').html()
    $modal.open({
      template: content,
      controller: 'LoginModalController',
      size: 'sm'
    })

  this.registerModal = ->
    content = angular.element('#registrationModal').html()
    $modal.open({
      template: content,
      controller: 'RegistrationModalController',
      size: 'sm'
    })

  this.getStatus = (callback) ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_status"
      }
    }

    request.success (data, status, headers, config) =>
      result = {}
      result.email=data.email
      result.status_known = true
      result.backup_allowed = data.backup_allowed
      result.restore_allowed = data.restore_allowed
      result.disabled_reason = data.disabled_reason
      result.previews_allowed = data.previews_allowed
      result.previews_remaining = data.previews_remaining
      result.onboarding_status = data.onboarding_status
      result.paid = data.paid
      result.plan = data.plan

      if(data.most_recent)
        result.most_recent_backup = data.most_recent.backup
        result.most_recent_restore = data.most_recent.restore
      # if backup/restore running
      if(data.backup_id || data.restore_id)
        result.percent_complete = data.percent_complete
        if(result.percent_complete < 15)
          result.percent_complete = 15

        result.step_description = data.step_description
        result.status = data.status
        result.backup_running = data.backup_id
        result.restore_running = data.restore_id
      else
        if(result.backup_running)
          result.updateBackupsForChosenDay()
        result.backup_running = false
        result.restore_running = false
        result.percent_complete = 0
        if(data.next_scheduled_backup == false)
          result.step_description = ""
        else
          if(data.next_scheduled_backup)
            readable_time = moment().add(data.next_scheduled_backup, "seconds").fromNow()
            result.step_description = "Your next backup starts #{readable_time}.  "
          else
            result.step_description = "Manual backups only."

      callback(result)
    return request
  return this

app.factory 'backupFactory', ($http) ->
  this.list = (siteId, callback) ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_list",
        site_id: siteId
      }
    }
    request.success (data, status, headers, config) =>
      callback(data, status, headers, config)
 
  return this

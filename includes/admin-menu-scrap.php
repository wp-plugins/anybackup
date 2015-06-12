
      <div class="col-md-2 col-sm-12 right-align pull-right top-section" ng-show="email">

        <span class="dropdown" dropdown>
          <button class="btn dropdown-toggle" dropdown-toggle>
            {{email}}
            <i class="fa fa-caret-down"></i>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li><a href="#" ng-click='openUpgrade()' >Change Plan</a></li>
            <li><a href="#" ng-click="openSettings()">Settings</a> </li>
            <li class="divider"></li>
            <li><a href="#" ng-click="openSupport()">Support</a></li>
          </ul>
        </span>

      </div>

<div ng-show='!paid && state == "enabled" && email'>-->
  <div ng-show='!paid && state == "enabled" && email'><button class='btn btn-premium' ng-click='openUpgrade()'>Upgrade</button></div>
</div>

    <div ng-show='showSupportMessage'>
      <div class='alert alert-success'>Thank you for your feedback.  We will be in touch as soon as possible.</div>
    </div>
    <div ng-show='disabled_reason'>
      <div class='alert alert-danger'>
        <span class='server-reason'>{{disabled_reason}}</span> 
        <span class='restore-note'>Access existing backups in the <a href='#settings' ng-click='openSettings()'>settings</a> menu.</span>
      </div>
      
    </div>


  <footer>
    <ul>
      <li><a target="_blank" href="https://www.anybackup.io"> About </a> </li>
      <li><a target="_blank" href="https://www.anybackup.io/#faq"> FAQ </a></li>
      <li><a target="_blank" href="https://www.anybackup.io/#pricing"> Pricing </a></li>
    </ul>
  </footer>

app.controller "BitsSupportModal", ($scope, $http, $modalInstance, $modal) ->
  $scope.dismiss = ->
    $modalInstance.dismiss('cancel')

  $scope.upgradeToPaid = ->
    $modalInstance.dismiss('cancel')
    content = angular.element('#upgradeModal').html()
    $modal.open({
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
      if(data.status == 200)
        setTimeout supportMessage, 1
        $modalInstance.dismiss('complete')
      else
        $scope.error = data

        # TODO: error handling?



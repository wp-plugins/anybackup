app.controller "PricingController", ($scope, $http, accountFactory) ->
  $scope.loadPlans = ->
    request = $http {
      url: ajaxurl, 
      method: "GET",
      params: {
        action: "bits_backup_get_plans"
      }
    }

    request.success (planData, status, headers, config) =>
      accountFactory.getStatus (data) ->
        $scope.plans = planData.plans
        $scope.status = data


  $scope.loadPlans()

  $scope.getStripeToken = () ->
    DEV_TOKEN="pk_096woK2npZnBc1cPETWmMsNmjod7e"
    PROD_TOKEN="pk_096wf6Q6pH1974ZLUF8lZXTUh3Ceg"
    if(window.location.href.match(/localhost/))
      DEV_TOKEN
    else
      PROD_TOKEN


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
      accountFactory.getStatus (data) ->
        $scope.status = data

    request.error (data, status, headers, config) =>
      $scope.status = 500


  $scope.openCheckout = (plan) ->
    handler = StripeCheckout.configure
      key: $scope.getStripeToken(),
      image: 'https://anybackup.io/images/logo-512x512.png',
      email: status.email,
      token: (token) ->
        $scope.updateAccount(plan.id, token.id)

    handler.open
      name: 'AnyBackup ' + plan.name,
      description: plan.price+'/m',
      amount: plan.price_in_cents



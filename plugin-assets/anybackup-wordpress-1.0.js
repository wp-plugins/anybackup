// Generated by CoffeeScript 1.8.0
(function() {
  var app, getEmail, planChanged, supportMessage, updateEmail, updateStatus;

  app = angular.module('BitsAnyBackup', ['ui.bootstrap']);

  app.filter('html', function($sce) {
    return function(input) {
      return $sce.trustAsHtml(input);
    };
  });

  updateEmail = function(email) {
    return function() {
      var $parentScope;
      $parentScope = angular.element("#dashboard").scope();
      return $parentScope.$apply(function() {
        return $parentScope.email = email;
      });
    };
  };

  updateStatus = function() {
    var $parentScope;
    $parentScope = angular.element("#dashboard").scope();
    return $parentScope.$apply(function() {
      $parentScope.updateStatus();
      return $parentScope.updateBackupsForChosenDay();
    });
  };

  supportMessage = function() {
    var $parentScope;
    $parentScope = angular.element("#dashboard").scope();
    return $parentScope.$apply(function() {
      return $parentScope.supportMessageSent();
    });
  };

  getEmail = function() {
    var $parentScope;
    $parentScope = angular.element("#dashboard").scope();
    return $parentScope.email;
  };

  planChanged = function() {
    var $parentScope;
    $parentScope = angular.element("#dashboard").scope();
    return $parentScope.$apply(function() {
      return $parentScope.paid = true;
    });
  };

  app.controller("BitsSettingsModal", function($scope, $http, $modalInstance) {
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    $scope.getSites = function() {
      var request;
      request = $http({
        url: ajaxurl,
        method: "GET",
        params: {
          action: "bits_backup_get_sites"
        }
      });
      return request.success(function(data, status) {
        var site, _i, _len, _ref, _results;
        $scope.sites = data['sites'];
        $scope.selected_site = data['current_site_id'];
        _ref = $scope.sites;
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          site = _ref[_i];
          if ($scope.selected_site === site.id && site.name === null) {
            _results.push(site.name = "New site");
          } else {
            _results.push(void 0);
          }
        }
        return _results;
      });
    };
    $scope.saveSettings = function() {
      var data, request;
      data = {
        action: "bits_backup_save_settings",
        selected_site: $scope.selected_site
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      return request.success((function(_this) {
        return function(data, status, headers, config) {
          setTimeout(updateStatus, 1);
          if (data.status === 200) {
            return $modalInstance.dismiss('complete');
          } else {
            console.error("Did not save site");
            return console.error(data);
          }
        };
      })(this));
    };
    return $scope.getSites();
  });

  app.controller("BitsSupportModal", function($scope, $http, $modalInstance, $modal) {
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    $scope.upgradeToPaid = function() {
      var content;
      $modalInstance.dismiss('cancel');
      content = angular.element('#upgradeModal').html();
      return console.log($modal.open({
        template: content,
        controller: 'BitsUpgradeModal',
        size: 'lg'
      }));
    };
    return $scope.sendSupport = function() {
      var data, request;
      data = {
        action: "bits_backup_send_support",
        content: $scope.content,
        urgent: $scope.urgent
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      return request.success((function(_this) {
        return function(data, status, headers, config) {
          console.log(data);
          if (data.status === 200) {
            setTimeout(supportMessage, 1);
            return $modalInstance.dismiss('complete');
          } else {
            console.error("Did not send support");
            console.error(data);
            return $scope.error = data;
          }
        };
      })(this));
    };
  });

  app.controller("BitsUpgradeModal", function($scope, $http, $modalInstance) {
    $scope.loadPlans = function() {
      var request;
      request = $http({
        url: ajaxurl,
        method: "GET",
        params: {
          action: "bits_backup_get_plans"
        }
      });
      request.success((function(_this) {
        return function(data, status, headers, config) {
          console.log("Success");
          console.log(data);
          return $scope.plans = data.plans;
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          console.log("error");
          return console.error("Request to bits_load_plans_failed!");
        };
      })(this));
    };
    $scope.loadPlans();
    console.log("Loading plans");
    $scope.getStripeToken = function() {
      var DEV_TOKEN, PROD_TOKEN;
      DEV_TOKEN = "pk_096woK2npZnBc1cPETWmMsNmjod7e";
      PROD_TOKEN = "pk_096wf6Q6pH1974ZLUF8lZXTUh3Ceg";
      if (window.location.href.match(/localhost/)) {
        return DEV_TOKEN;
      } else {
        return PROD_TOKEN;
      }
    };
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    $scope.getPlan = function(name) {
      var plan;
      return ((function() {
        var _i, _len, _ref, _results;
        _ref = $scope.plans;
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          plan = _ref[_i];
          if (plan.name === name) {
            _results.push(plan);
          }
        }
        return _results;
      })())[0];
    };
    $scope.cancelAccount = function() {
      var plan_id;
      plan_id = $scope.getPlan('free').id;
      if (confirm("Are you sure you want to cancel your account?  This may stop backup of your sites.")) {
        return $scope.updateAccount(plan_id);
      }
    };
    $scope.updateAccount = function(plan_id, token_id) {
      var params, request;
      if (token_id == null) {
        token_id = null;
      }
      params = {
        action: "bits_backup_update_account",
        plan_id: plan_id,
        token: token_id
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: params
      });
      request.success((function(_this) {
        return function(data, status, headers, config) {
          $scope.status = data.status;
          setTimeout(planChanged, 1);
          return console.log("SUCCESSED", data);
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          $scope.status = 500;
          console.log("error");
          console.error("Request to bits_update_account failed!");
          return console.log(data);
        };
      })(this));
    };
    return $scope.openCheckout = function(plan) {
      var handler;
      handler = StripeCheckout.configure({
        key: $scope.getStripeToken(),
        image: 'https://anybackup.io/images/logo-512x512.png',
        email: getEmail(),
        token: function(token) {
          return $scope.updateAccount(plan.id, token.id);
        }
      });
      return handler.open({
        name: 'AnyBackup ' + plan.name,
        description: plan.price + '/m',
        amount: plan.price_in_cents
      });
    };
  });

  app.controller("BitsLoginModal", function($scope, $http, $modalInstance) {
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    return $scope.loginAccount = function() {
      var data, request;
      data = {
        action: "bits_login_account",
        email: $scope.email_input,
        password: $scope.password_input
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      request.success((function(_this) {
        return function(data, status, headers, config) {
          $scope.status = data.status;
          if (data.status === 200) {
            setTimeout(updateEmail(data.email), 1);
            $modalInstance.dismiss('complete');
            console.log("closeModal");
          }
          console.log("Success");
          return console.log(data);
        };
      })(this));
      request.error((function(_this) {
        return function(data, status, headers, config) {
          $scope.status = 500;
          console.log("error");
          console.error("Request to bits_login_account failed!");
          return console.log(data);
        };
      })(this));
      return false;
    };
  });

  app.controller("BitsRegistrationModal", function($scope, $modalInstance, $http) {
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    return $scope.registerUser = function() {
      var data, request;
      data = {
        action: "bits_register_account",
        email: $scope.email_input,
        password: $scope.password_input
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      request.success((function(_this) {
        return function(data, status, headers, config) {
          console.log("Success");
          console.log(data);
          if (data.status === 200) {
            if (data.error) {
              return $scope.error = data.error;
            } else {
              setTimeout(updateEmail(data.email), 1);
              $modalInstance.dismiss('complete');
              return console.log("closeModal");
            }
          }
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          console.log("error");
          $scope.error = "Error communicating with server";
          return console.error("Request to bits_register_account failed!");
        };
      })(this));
    };
  });

  app.controller("BitsAnyBackupDashboard", function($scope, $http, $modal, $rootScope) {
    $scope.selectedCalendarDate = new Date();
    $scope.format = 'dd-MMMM-yyyy';
    $scope.backups = [];
    $scope.plans = ['free', 'professional'];
    $scope.changePlanDebug = function(element) {
      return console.log(element, "toggle");
    };
    $scope.setInitialNextRunTimestamp = function(timestamp) {
      console.log('set initial');
      console.log(timestamp);
      if (timestamp.length > 0) {
        return $scope.state = 'enabled';
      } else {
        return $scope.state = 'welcome';
      }
    };
    $scope.dateOptions = {
      formatYear: 'yy',
      startingDay: 1
    };
    $scope.updateBackupsForChosenDay = function() {
      var request;
      console.log("doing ajax");
      request = $http({
        url: ajaxurl,
        method: "GET",
        params: {
          action: "bits_backup_for_date",
          date: $scope.selectedCalendarDate
        }
      });
      request.success((function(_this) {
        return function(data, status, headers, config) {
          console.log("Success");
          console.log(data);
          return $scope.backups = data.backups;
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          console.log("error");
          return console.error("Request to bits_backup_for_date failed!");
        };
      })(this));
    };
    $scope.selectBackup = function(backup) {
      var request;
      request = $http({
        url: ajaxurl,
        method: "GET",
        params: {
          action: "bits_backup_get_backup",
          id: backup.id
        }
      });
      request.success((function(_this) {
        return function(data, status, headers, config) {
          console.log("Success");
          console.log(data);
          return $scope.selectedBackup = data;
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          console.log("error");
          return console.error("Request to bits_backup_get_backup failed!");
        };
      })(this));
    };
    $scope.open = function($event) {
      $event.preventDefault();
      $event.stopPropagation();
      return $scope.opened = true;
    };
    $scope.openSettings = function() {
      var content;
      content = angular.element('#settingsModal').html();
      return console.log($modal.open({
        template: content,
        controller: 'BitsSettingsModal',
        size: 'sm'
      }));
    };
    $scope.openSupport = function() {
      var content;
      content = angular.element('#supportModal').html();
      return console.log($modal.open({
        template: content,
        controller: 'BitsSupportModal',
        size: 'sm'
      }));
    };
    $scope.openUpgrade = function() {
      var content;
      content = angular.element('#upgradeModal').html();
      return console.log($modal.open({
        template: content,
        controller: 'BitsUpgradeModal',
        size: 'lg'
      }));
    };
    $scope.openLogin = function() {
      var content;
      content = angular.element('#loginModal').html();
      return console.log($modal.open({
        template: content,
        controller: 'BitsLoginModal',
        size: 'sm'
      }));
    };
    $scope.openRegister = function() {
      var content;
      content = angular.element('#registrationModal').html();
      return console.log($modal.open({
        template: content,
        controller: 'BitsRegistrationModal',
        size: 'sm'
      }));
    };
    $scope.enableBackups = function() {
      var data, request;
      $scope.backup_running = true;
      data = {
        action: "bits_backup_start_job"
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      request.success(function() {
        return $scope.state = "enabled";
      });
      return $scope.step_description = "Starting your backup";
    };
    $scope.forceBackupNow = function() {
      var data, request;
      $scope.backup_running = true;
      data = {
        action: "bits_backup_force_backup_now"
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      request.success(function() {
        return $scope.state = "enabled";
      });
      return $scope.step_description = "Starting your backup";
    };
    $scope.readableDate = function(backup) {
      if (backup && backup.committed_at) {
        return moment.parseZone(backup.committed_at).calendar();
      } else {
        return "";
      }
    };
    $scope.supportMessageSent = function() {
      return $scope.showSupportMessage = true;
    };
    $scope.restoreFromBackup = function() {
      var data, request;
      $scope.restore_running = true;
      data = {
        action: "bits_restore_from_backup",
        id: $scope.selectedBackup.id
      };
      request = $http({
        url: ajaxurl,
        method: "post",
        params: data
      });
      request.success(function() {
        return console.log("SUCCESS");
      });
      return $scope.step_description = "Starting restore.  You can cancel at any step.";
    };
    $scope.cancel = function() {
      var data, request;
      data = {
        action: "bits_force_cancel"
      };
      request = $http({
        url: ajaxurl,
        method: "POST",
        params: data
      });
      request.success(function() {
        $scope.backup_running = false;
        $scope.restore_running = false;
        $scope.step_description = "Cancelled.";
        return $scope.updateStatus();
      });
      return $scope.step_description = "Cancelling...";
    };
    $scope.updateStatus = function() {
      var request;
      request = $http({
        url: ajaxurl,
        method: "GET",
        params: {
          action: "bits_backup_get_status"
        }
      });
      $scope.calls = 0;
      request.success((function(_this) {
        return function(data, status, headers, config) {
          var readable_time;
          $scope.email = data.email;
          $scope.status_known = true;
          $scope.backup_allowed = data.backup_allowed;
          $scope.restore_allowed = data.restore_allowed;
          $scope.disabled_reason = data.disabled_reason;
          $scope.previews_allowed = data.previews_allowed;
          $scope.previews_remaining = data.previews_remaining;
          $rootScope.paid = data.paid;
          $rootScope.plan = data.plan;
          if (data.backup_id || data.restore_id) {
            $scope.percent_complete = data.percent_complete;
            if ($scope.percent_complete < 15) {
              $scope.percent_complete = 15;
            }
            $scope.step_description = data.step_description;
            $scope.status = data.status;
            $scope.backup_running = data.backup_id;
            return $scope.restore_running = data.restore_id;
          } else {
            if ($scope.backup_running) {
              $scope.updateBackupsForChosenDay();
            }
            $scope.backup_running = false;
            $scope.restore_running = false;
            $scope.percent_complete = 0;
            if (data.next_scheduled_backup === false) {
              return $scope.step_description = "";
            } else {
              readable_time = moment().add(data.next_scheduled_backup, "seconds").fromNow();
              return $scope.step_description = "Your next backup starts " + readable_time + ".  ";
            }
          }
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          console.log("error");
          return console.error("Request to bits_backup_get_status failed!");
        };
      })(this));
    };
    setInterval($scope.updateStatus, 7000);
    $scope.updateStatus();
    $scope.status = "Loading";
    $scope.step_number = -1;
    console.log("Updating backup");
    return $scope.updateBackupsForChosenDay();
  });

}).call(this);

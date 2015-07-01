// Generated by CoffeeScript 1.8.0
(function() {
  window.app = angular.module('BitsAnyBackup', ['ui.bootstrap']);

  app.filter('html', function($sce) {
    return function(input) {
      return $sce.trustAsHtml(input);
    };
  });

  app.controller("LoginModalController", function($scope, $http, $modalInstance, $rootScope) {
    $scope.backups = [];
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    return $scope.loginAccount = function() {
      var data, request;
      $scope.loginFormSubmitting = true;
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
          $scope.loginFormSubmitting = false;
          if (data.status === 200) {
            $rootScope.$broadcast("user-login");
            return $modalInstance.dismiss('complete');
          }
        };
      })(this));
      request.error((function(_this) {
        return function(data, status, headers, config) {
          $scope.loginFormSubmitting = false;
          return $scope.status = 500;
        };
      })(this));
      return false;
    };
  });

  app.controller("RegistrationModalController", function($scope, $modalInstance, $http, $rootScope) {
    $scope.dismiss = function() {
      return $modalInstance.dismiss('cancel');
    };
    return $scope.registerUser = function() {
      var data, request;
      $scope.registerFormSubmitting = true;
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
          if (data.status === 200) {
            if (data.error) {
              $scope.registerFormSubmitting = false;
              return $scope.error = data.error;
            } else {
              $rootScope.$broadcast("user-registered");
              return $modalInstance.dismiss('complete');
            }
          }
        };
      })(this));
      return request.error((function(_this) {
        return function(data, status, headers, config) {
          $scope.registerFormSubmitting = false;
          return $scope.error = "Error communicating with server";
        };
      })(this));
    };
  });

  app.controller("BaseController", function($scope, $http, backupFactory, accountFactory) {
    $scope.backups = [];
    $scope.parseUrl = function(url) {
      var params, part, parts;
      if (url == null) {
        url = location.href;
      }
      params = {};
      return ((function() {
        var _i, _len, _ref, _results;
        if (url.indexOf("?") !== -1) {
          _ref = (url.split("?")).pop().split("&");
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            part = _ref[_i];
            _results.push((parts = part.split("=")) && (params[parts[0]] = parts[1]));
          }
          return _results;
        }
      })()) && params || {};
    };
    $scope.urlParams = $scope.parseUrl();
    $scope.selectedBackupId = $scope.urlParams.backup_id;
    $scope.list = function(siteId) {
      return backupFactory.list(siteId, function(data) {
        $scope.backups = data.backups;
        return $scope.loading = false;
      });
    };
    $scope.selectBackup = function() {
      var request;
      if (!$scope.selectedBackupId) {
        $scope.selectedBackup = null;
        return;
      }
      request = $http({
        url: ajaxurl,
        method: "GET",
        params: {
          action: "bits_backup_get_backup",
          id: $scope.selectedBackupId
        }
      });
      return request.success((function(_this) {
        return function(data, status, headers, config) {
          return $scope.selectedBackup = data;
        };
      })(this));
    };
    $scope.openLogin = function() {
      return accountFactory.loginModal();
    };
    $scope.openRegister = function() {
      return accountFactory.registerModal();
    };
    $scope.readableDate = function(backup) {
      var localTimeZone;
      localTimeZone = new Date().getTimezoneOffset();
      if (backup && backup.committed_at) {
        return moment.parseZone(backup.committed_at).zone(localTimeZone / 60).calendar().toLowerCase();
      } else if (backup && backup.created_at) {
        return moment.parseZone(backup.created_at).zone(localTimeZone / 60).calendar().toLowerCase();
      } else {
        return "";
      }
    };
    $scope.updateStatus = function(callback) {
      var request;
      request = accountFactory.getStatus(function(data) {
        $scope.status = data;
        if (callback) {
          return callback(data);
        }
      });
      return request["finally"](function() {
        if ($scope.updateStatusTimeout) {
          clearTimeout($scope.updateStatusTimeout);
        }
        $scope.updateStatusTimeout = setTimeout($scope.updateStatus, 7000);
        if ($scope.statusUpdated) {
          return $scope.statusUpdated();
        }
      });
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
        $scope.backup_cancelled = $scope.status.backup_running;
        $scope.restore_cancelled = $scope.status.restore_running;
        $scope.status.backup_running = false;
        $scope.status.restore_running = false;
        $scope.status.step_description = "Cancelled.";
        return $scope.updateStatus();
      });
      return $scope.status.step_description = "Cancelling...";
    };
    if ($scope.selectedBackupId) {
      return $scope.selectBackup();
    }
  });

}).call(this);

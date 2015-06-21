<?php
  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }
?>

<script type="text/ng-template" id="loginModal">
  <div class="bootstrap-styles">
    <div class="modal-header">
      <button type="button" class="close" ng-click="dismiss()" aria-hidden="true">×</button>
      <h4 class="modal-title" id="myModalLabel">Log in to AnyBackup</h4>
    </div>

    <form role="form" name="loginForm">
      <div class="modal-body">
        <div class="error" ng-show="status == 403">
          Invalid email or password
        </div>
        <div class="error" ng-show="status == 500">
          Error communicating to server.  Please contact support@255bits.com
        </div>
        <div class="form-group">
          <div class="input-group">
            <input type="email" ng-required="true" class="form-control" id="uLogin" placeholder="Email" ng-model="email_input" required>
            <label for="uLogin" class="input-group-addon fa fa-user"></label>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group">
            <input type="password" class="form-control" id="uPassword" placeholder="Password" ng-model="password_input" required>
            <label for="uPassword" class="input-group-addon fa fa-lock"></label>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <a target="_blank" href="https://anybackup.io/accounts/password/new">Forgot your password?</a>
        <input type='submit' ng-click="loginAccount()" class="form-control btn btn-primary" ng-disabled="!loginForm.$valid || loginFormSubmitting" value="Ok"></input>

      </div>
    </form>
  </div>
</script>

<script type="text/ng-template" id="registrationModal">
  <div class="bootstrap-styles">
    <div class="modal-header">
      <button type="button" class="close" ng-click="dismiss()" aria-hidden="true">×</button>
      <h4 class="modal-title" id="myModalLabel">Register for AnyBackup</h4>
    </div>

    <form role="form" name='registerForm' ng-submit="registerUser()">
      <div class="modal-body">
        <div class="error" ng-show="error">
          {{error}}
        </div>
        <div class="form-group">
          <div class="input-group">
            <input type="email" class="form-control" id="uLogin" placeholder="Email" ng-model="email_input" required>
            <label for="uLogin" class="input-group-addon fa fa-user"></label>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group">
            <input type="password" class="form-control" id="uPassword" placeholder="Password" ng-model="password_input" required>
            <label for="uPassword" class="input-group-addon fa fa-lock"></label>
          </div>
        </div>

         <div class="form-group">
          <div class="input-group">
            <input type="password" class="form-control" id="uPasswordConfirm" placeholder="Password confirmation" ng-model="password_confirmation" required>
            <label for="uPassword" class="input-group-addon fa fa-lock"></label>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <input type='submit' class="form-control btn btn-primary" ng-disabled="!registerForm.$valid || (password_input != password_confirmation) || registerFormSubmitting" value="Ok"></input>
      </div>
    </form>
  </div>
</script>



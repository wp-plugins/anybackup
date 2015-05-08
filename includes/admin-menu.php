<?php 
  $api = bits_get_api();
  $backups = $api->get_backups();
?>

<script src="https://checkout.stripe.com/checkout.js"></script>


<?php 
  # TODO: How do we detect this state with angular?
  $timestamp = wp_next_scheduled( 'bits_iterate_backup' );
?>

<script type="text/ng-template" id="settingsModal">
  <div class="bootstrap-styles">
    <div class="modal-header">
      <button type="button" class="close" ng-click="dismiss()" aria-hidden="true">×</button>
      <h4 class="modal-title" id="myModalLabel">AnyBackup Settings</h4>
    </div>

    <div class="modal-body">
      <form role="form" name="settingsForm">
        <div class="form-group">
          <label>Site</label>
          <div class="input-group">
            <div ng-repeat='site in sites'>
              <div class="radio" ng-show="site.name">
                <label>
                  <input type='radio' name='site' ng-model='$parent.selected_site' ng-value='site.id'></input>
                  <span ng-bind-html="site.name | html"></span>
                  <em><span ng-bind-html="site.uri | html"></span></em>
                </label>
              </div>
            </div>
          </div>
        </div>
      </form>

    </div>

    <div class="modal-footer">
      <input type='submit' ng-click="saveSettings()" class="form-control btn btn-primary" ng-disabled="!settingsForm.$valid" value="Save"></input>
    </div>
  </div>
</script>

<script type="text/ng-template" id="supportModal">
  <div class="bootstrap-styles">
    <div class="modal-header">
      <button type="button" class="close" ng-click="dismiss()" aria-hidden="true">×</button>
      <h4 class="modal-title" id="myModalLabel">AnyBackup Support</h4>
    </div>

    <div class="modal-body">
      <div class="error" ng-show="error">
        {{error}}
      </div>
      <form role="form" name="supportForm">
        <div class="form-group">
          <label>Tell us whats on your mind</label>
          <div class="input-group">
            <textarea ng-model='content'></textarea>
            <div class='checkbox'>
              <div ng-html='paid'/>
              <label><input type='checkbox' ng-model='urgent' ng-disabled="!paid"> This is urgent </input></label>
              <div class='premium' ng-show='!paid' ng-click='upgradeToPaid()'>Upgrade to a paid plan in order to get urgent support</div>
            </div>
          </div>
        </div>
      </form>

    </div>

    <div class="modal-footer">
      <input type='submit' ng-click="sendSupport()" class="form-control btn btn-primary" ng-disabled="!supportForm.$valid" value="Save"></input>
    </div>
  </div>
</script>

<script type="text/ng-template" id="upgradeModal">
  <div class="bootstrap-styles">
    <div class="modal-header">
      <button type="button" class="close" ng-click="dismiss()" aria-hidden="true">×</button>
      <h4 class="modal-title" id="myModalLabel">AnyBackup Premium</h4>
      <p> Pays for itself in just one prevented disaster </p>
    </div>
    <div class="modal-body">
      <div class="row">
        <div ng-repeat="p in plans" ng-class='{"current-plan": p.name == plan}'>
          <div class="col-xs-12 col-md-4" ng-show='p.price_in_cents > 0'>
            <div class="panel panel-primary panel-#{p.name}}">
              <div class="panel-heading">
                <h3 class="panel-title">
                  {{p.display_name}} 
                </h3>
              </div>
              <div class="panel-body">
                <div class="the-price">
                  <h1>
                    ${{p.price_in_cents/100}}<span class="subscript">/mo</span>
                  </h1>
                </div>
                <table class="table">
                  <tr>
                    <td>
                     {{p.max_number_of_sites}} Sites
                    </td>
                  </tr>
                  <tr>
                    <td >
                      Unlimited backups
                    </td>
                  </tr>
                  <tr>
                    <td>
                      Unlimited restores
                    </td>
                  </tr>
                  <tr>
                    <td>
                      Unlimited previews
                    </td>
                  </tr>
                  <tr class="active">
                    <td>
                      Emergency support
                    </td>
                  </tr>
                </table>
              </div>
              <div class="panel-footer">
                <button class="btn btn-premium" ng-show="paid && p.name != 'free' && p.name != plan" ng-click="updateAccount(p.id)"> Change</button>
                <button class="btn btn-premium" ng-show="!paid && p.name != plan" ng-click="openCheckout(p)"> Upgrade </button>
                <h4 ng-show="p.name == plan"> Current Plan </h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div style="clear:both"></div>
    <div class="modal-footer">
      <p style='text-align:left;'>
        <small>
          Downgrade or cancel at any time.
          <a href ng-show="paid" ng-click="cancelAccount()"> 
            Cancel my AnyBackup account 
          </a>
        </small>
      </p>

    </div>
  </div>
</script>


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

<div class="bootstrap-wrapper">
<div class='bootstrap-styles' ng-app="BitsAnyBackup" ng-controller="BitsAnyBackupDashboard" ng-init="setInitialNextRunTimestamp('<?php echo $timestamp?>')">
  <div id='debugBar' ng-hide='true'>
    <label class='pull-left'>Debug: </label>
    <form method="POST" action="#" class='pull-left'>
      <input type='hidden' name='createNewApiKey' value='true'/>
      <input type='submit' value='New Server'/>
    </form>

    <a href="?page=backup_bits_anybackup&runSpecs=true"> Run Specs </a>


  </div>
  <div style="clear:both"></div>

  <div ng-show='loading'>
    <div class="col-md-10 col-sm-12 pull-left logo-section">
      <div class='logo-container'>
        <span class='icon-96'><img src="<?php echo plugins_url("anybackup/plugin-assets/logo-512x512.png"); ?>"/></span>
      </div>
      <span class='branding'>
        <h1 id='brand-name'>AnyBackup</h1>
      </span>
      <h1><i class='fa fa-spinner fa-pulse'></i></h1>
    </div>
    <div style="clear:both"></div>
  </div>
 
  <div id='dashboard' ng-hide='loading'>
    <div class="row">

      <div class="col-md-2 col-sm-12 right-align pull-right top-section" ng-show="email == null && status_known">
        <a class="btn btn-info" ng-click="openLogin()"> Login </a>
        <a class="btn btn-success" ng-click="openRegister()"> Register </a>
      </div>
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
      <div class="col-md-10 col-sm-12 pull-left logo-section">
        <div class='logo-container'>
          <span class='icon-96'><img src="<?php echo plugins_url("anybackup/plugin-assets/logo-512x512.png"); ?>"/></span>
        </div>
        <span class='branding'>
          <h1 id='brand-name'>AnyBackup</h1>
          <div ng-show='!paid && state == "enabled" && email'>
            <label>Premium users enjoy unlimited previews, multiple sites, and urgent support.</label>
          </div>
          <div ng-show='!paid && state == "enabled" && email'><button class='btn btn-premium' ng-click='openUpgrade()'>Upgrade</button></div>

          <div id='welcome' ng-show="state == 'welcome'">
            <div>
              <button id="enableBackups" class='btn btn-primary btn-enable-backups' ng-click="enableBackups()" ng-disabled='!backup_allowed'>Enable Backups</button>
            </div>
            <p class='help-block' ng-show='backup_allowed && !restore_running'>
              Your site will be backed up to our disaster proof servers.
            </p>
          </div>
           <div ng-show='!email' class='col-md-6 col-sm-6 col-xs-6 no-left-padding'>
            <div class='alert alert-warning'><i class="fa fa-exclamation-triangle"></i> <a href='#' ng-click='openLogin()'>Login</a> or <a href='#' ng-click='openRegister()'>register</a> to access your backups in an emergency.</div>
          </div>
        </span>
      </div>
      
    </div>

  <div style="clear:both"></div>

  <div class="row" ng-show="onboarding_status == 'failed'">
    <div class="col-md-12">
      <h4>Oops!  Looks like there's a problem.</h4>
      <p>
        We test AnyBackup throughly, but sometimes unforseen issues can come up.  Our engineers have been notified.  If this is urgent, please contact us with our
        <a ng-click='openSupport()'>Support</a> link.
       </p>
    </div>
  </div>
<div style="clear:both"></div>
    <div ng-show='showSupportMessage'>
      <div class='alert alert-success'>Thank you for your feedback.  We will be in touch as soon as possible.</div>
    </div>
    <div ng-show='disabled_reason'>
      <div class='alert alert-danger'>
        <span class='server-reason'>{{disabled_reason}}</span> 
        <span class='restore-note'>Access existing backups in the <a href='#settings' ng-click='openSettings()'>settings</a> menu.</span>
      </div>
      
    </div>

    <div id='status' ng-show="state == 'enabled' || backup_running || restore_running">
      <div class="row">
        <div class='col-md-6 col-sm-6'>
          <progressbar max=100.0 value=percent_complete class='progress-striped active' ng-show="backup_running || restore_running"><span></span></progressbar>
        </div>
        <div class='col-md-6'>
          <button class='btn btn-danger' ng-show='backup_running || restore_running' ng-click='cancel()'>Cancel</button>
        </div>
      </div>
      <div class="row" ng-show="backups.length == 0 && backup_running">
        <div class='col-md-12'>
          <p>
              Your first backup can take a while.  We are carefully syncing your site to avoid interrupting your visitors.  <br/><br/>Feel free to leave this page and check in later.
           </p>
        </div>
      </div>
      <div class="row" style="clear: both;" ng-hide="backups.length == 0 && backup_running">
        <div class="col-md-6 col-sm-6" ng-show='backup_allowed'>
          <div class='backup-status'>
            <span id="status-number">{{step_description}}</span>
            <a ng-click="forceBackupNow()" href="#" ng-show='!backup_running && !restore_running'>Backup Now</a>
          </div>
        </div>
      </div>
    </div>

    <div style="clear:both"></div>
  
    <div class="row" class="backup-list">
      <div class='backup-list col-md-4 col-sm-4' ng-show="backups.length > 0">

        <!--
          TODO:  Pagination and search through backup history
        <p class="input-group">
          <input type="text" 
              class="form-control" 
              datepicker-popup="{{format}}" 
              ng-model="selectedCalendarDate" 
              is-open="opened" 
              min-date="minDate" 
              max-date="maxDate" 
              datepicker-options="dateOptions" 
              date-disabled=false 
              ng-required="true" 
              close-text="Close" />
          <span class="input-group-btn">
            <button type="button" 
                    class="btn btn-default" 
                    ng-click="open($event)">
              <i class="fa fa-calendar"></i>
            </button>
          </span>
        </p>
        -->
        <table class='table table-striped table-hover' ng-show='backups.length > 0'>
          <tbody>
            <tr>
              <th class='name'>Name</th>
              <th>Created</th>
            </tr>
            <tr ng-repeat="backup in backups" ng-click='selectBackup(backup)' class='selectable' ng-class='(selectedBackup.id == backup.id) ? "selectedRow" : ""'>
              <td ng-if="backup.id == selectedBackup.id">
                {{selectedBackup.name}}
              </td>
              <td ng-if="backup.id != selectedBackup.id">
                {{backup.name}}
              </td>
              <td>{{readableDate(backup)}} <i class="fa fa-angle-right"></i> </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class='detail col-md-4 col-sm-4 backup-info' ng-show='selectedBackup'>
        <div ng-show="editingName" class="title">
          <form ng-submit='saveName()'>
            <input id='edit-name' ng-model='selectedBackup.name'></input>
            <input type='submit'></input>
          </form>
        </div>
        <div class="title" ng-hide='editingName'>{{selectedBackup.name}} <a ng-click="editName()"><i class="fa fa-pencil"></i></a> </div>
        <form method="POST">
          <div class='row backup-details'>
            <div class='col-md-3 col-sm-3'>
              Date
            </div>
            <div class='col-md-9 col-sm-9'>
              {{readableDate(selectedBackup)}}
            </div>
          </div>
          <div style="clear:both"></div>
          <div class='row backup-details'>
            <div class='col-md-3 col-sm-3'>
              Tables
            </div>
            <div class='col-md-4 col-sm-4'>
              {{selectedBackup.schema_count}}
            </div>
          </div>
          <div style="clear:both"></div>
          <div class='row backup-details'>
            <div class='col-md-3 col-sm-3'>
              Rows
            </div>
            <div class='col-md-9 col-sm-9'>
              {{selectedBackup.row_count}}
            </div>
          </div>
          <div style="clear:both"></div>
          <div class='row backup-details'>
            <div class='col-md-3 col-sm-3'>
              Files
            </div>
            <div class='col-md-9 col-sm-9'>
              {{selectedBackup.file_count}}
            </div>
          </div>
          <div ng-repeat="(key, attributes) in selectedBackup.site_info">
            <div style="clear:both"></div>
            <div class='row backup-details'>
              <div class='col-md-3 col-sm-3'>
                {{attributes.name}}
              </div>
              <div class='col-md-9 col-sm-9'>
                {{attributes.value}}
              </div>
            </div>
          </div>

          <div style="clear:both"></div>
          <div class='row backup-details'>
            <div class='col-md-12 col-sm-12'>
              <a class="btn btn-success" ng-disabled='!previews_allowed || !email' href="<?php echo $api->get_server(); ?>/backups/{{selectedBackup.id}}/preview" target='_blank'>
                Live Preview
                <span ng-show="!paid">*</span>
              </a>
              <input type='submit' class="btn btn-danger"  value='Restore On This Site' ng-click="restoreFromBackup()"/>
            </div>
          </div>
          <div class='row backup-details' ng-show='!paid && email'>
            <div class='col-md-12 col-sm-12 premium spacer'>
              <i class='fa fa-asterisk'></i> {{previews_remaining}} previews remaining.  Upgrade for unlimited.
            </div>
          </div>
           <div class='row backup-details' ng-show='!paid && !email'>
            <div class='col-md-12 col-sm-12 premium spacer'>
              <i class='fa fa-asterisk'></i> Register to access your live preview.
            </div>
          </div>
          <input type='hidden' name='restoreFromBackup' value="true"/>
          <input type="hidden" name='backupId' value='{{selectedBackup.id}}'/>
          <div style="clear:both"></div>
        </form>
      </div>
    </div>
  </div>
  <div style="clear:both"></div>
  <footer>
    <div class="row">
      <div class="col-md-12">
        <ul>
          <li><a target="_blank" href="https://www.anybackup.io"> About </a> </li>
          <li><a target="_blank" href="https://www.anybackup.io/#faq"> FAQ </a></li>
          <li><a target="_blank" href="https://www.anybackup.io/#pricing"> Pricing </a></li>
        </ul>
      </div>
    </div>
  </footer>

</div>
</div>

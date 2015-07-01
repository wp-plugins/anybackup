<?php
  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }
?>
<?php 
  $api = bits_get_api();
  $backups = $api->get_backups();
?>

<script src="https://checkout.stripe.com/checkout.js"></script>



<div class="wrap">
  <div class="bootstrap-styles" ng-controller="PricingController" ng-app="BitsAnyBackup">
    <?php require 'admin-menu-debug-bar.php';?>
    <div class="modal-header">
      <h2 class="modal-title" id="myModalLabel">AnyBackup Pricing</h2>
      <p> Pays for itself in just one prevented disaster. </p>
    </div>
    <div class="modal-body">
      <div class="row">
        <div ng-show="status.plan == 'free'">You are currently on the free plan.  You are limited to 1 site and 10mb maximum individual file size.</div>
        <div ng-repeat="p in plans" ng-class='{"current-plan": p.name == status.plan}'>
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
                  <tr>
                    <td>
                      Unlimited file size
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
                <button class="btn btn-premium" ng-show="status.paid && p.name != 'free' && p.name != status.plan" ng-click="updateAccount(p.id)"> Change</button>
                <button class="btn btn-premium" ng-show="!status.paid && p.name != status.plan" ng-click="openCheckout(p)"> Upgrade </button>
                <h4 ng-show="p.name == status.plan"> Current Plan </h4>
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
          <a href ng-show="status.paid" ng-click="cancelAccount()"> 
            Cancel my AnyBackup account 
          </a>
        </small>
      </p>

    </div>
  </div>
</div>

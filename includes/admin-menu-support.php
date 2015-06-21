<?php
  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }
?>
<div class='wrap' ng-app="BitsAnyBackup">
  <form method='POST' action="?page=anybackup_render_support">
    <h2>AnyBackup Support</h2>
    <?php require("_common-notifications.php"); ?>

    <?php if($_POST) { ?>
      <div id="setting-error-settings_updated" class="updated settings-error"> 
        <p><strong>Message received.</strong>  Thank you, we will be in touch soon.</p>
      </div>
    <?php } ?>

    <div class="form-group">
      <label>Tell us whats on your mind.</label>
      <div class="input-group">
        <textarea cols=50 rows=12 name='content'></textarea>
        <div class='checkbox'>
          <div ng-html='paid'/>
          <label><input type='checkbox' name='urgent'>This is urgent</input></label>
          <div class='premium'>Premium members only</div>
        </div>
      </div>
    </div>

    <input type='submit' class="button button-primary" value="Send"></input>

  </form>
  <p> You can also email us at support@255bits.com </p>
</div>

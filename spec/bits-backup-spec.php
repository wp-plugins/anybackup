<?php
  $GLOBALS["BITS_ENVIRONMENT"]="TEST";
  $WP_PATH=BitsUtil::fs_get_wp_config_path();

  global $wpdb;
  $wpdb->show_errors(); 
?>
<style>
tr:nth-child(even) {
    background-color: #FFFFFF;
}
</style>

<?php
$api = new BitsAnyBackupAPI();
$api_key = $api->create_api_key();
//This clears out the database of any test tables
$worker = new BitsBackupWorker($api);
$api->set_api_key($api_key);

$backup_state_machine = new BitsBackupStateMachine($api);
$restore_state_machine = new BitsRestoreStateMachine($api);

function create_backup($api, $opts=array()) {
  $api_key = $api->create_api_key();
  $api->set_api_key($api_key);

  return $api->create_backup($opts);
}

function pass() {
  echo "<span style='color:#8b8;font-weight:bold;'>PASS</span>";
}

function fail($obj) {
  echo "<span style='color:#b88;font-weight:bold;'>FAIL</span><br>";
  echo "<pre>";
  var_dump($obj);
  echo "</pre>";
}

?>
<table>
  <tr>
    <th>Test name</th>
    <th>Status</th>
  </tr>
  <tr>
    <th colspan="2"> API  - Backup </th>
  </tr>
  <tr>
    <td>API Create backup</td>
    <td>
      <?php
          $result = create_backup($api, array( "test" => "true" ));
          if($result["id"] > 0) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API get backups(empty)</td>
    <td>
      <?php
          $result = create_backup($api, array( "test" => "true" ));
          if(is_array($result)) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API get a backup</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $result = $api->get_backup($backup["id"]);
          if($result["id"] == $backup["id"]) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
   <tr>
    <td>API error on null get_backup</td>
    <td>
      <?php
          $result = $api->get_backup(null);
          if(is_wp_error($result)) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
 <tr>
    <td>API commit backup (empty)</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $result = $api->commit_backup($backup["id"]);
          if($result["id"] == $backup["id"]) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API add to backup (schema doesn't exist)</td>
    <td>
      <?php
          $GLOBALS["BITS_DEBUG"]=true;
          $backup = create_backup($api, array( "test" => "true" ));
          $result = $api->add_schemas_to_backup($backup["id"], ['no-exist'] );
          if(
            !is_wp_error($result) &&
            count($result["schemas"]) == 1 &&
            $result['schemas'][0]['status'] == 200
          ) { pass(); } else { fail($result); };
          $GLOBALS["BITS_DEBUG"]=false;
      ?>
    </td>
  </tr>
  <tr>
    <td>API add file to backup</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $files = array(
            $WP_PATH."/wp-config.php" => "http://test.test/path/to/file"
          );

          $result = $api->add_files_to_backup($backup['id'], $files);
          if(
            count($result["files"]) == 1
          ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>

  <tr>
    <td>API handles sym links</td>
    <td>
<?php
          $meta = $api->get_file_metadata($api->root . "/wp-content/plugins/anybackup");
          if(
            $meta["type"] == "directory" &&
            $meta['content_fingerprint'] == null
          ) { pass(); } else { fail($meta); };
?>
    </td>
  </tr>

  <tr>
    <td>API fresh backup, next step is scan_schema</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));

          $result = $api->next_step();
          if(
            $result["step"] == "scan_schema"
          ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
 
  <tr>
    <td>API step scan_schema completes</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));

          $step = $api->next_step();
          $result = $api->complete_step($step["step_id"]);
          if(
            $result["step_status"] == "COMPLETE"
          ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr> <tr>
    <th colspan="2"> API - Restore </th>
  </tr>
  <tr>
    <td>API restore create</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $result = $api->create_restore($backup["id"]);
          if($result["status"] == 200 && $result['id'] > 0) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API restore get schemas</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $restore = $api->create_restore($backup["id"]);
          $result = $api->get_restore_schemas($restore["id"]);
          if($result["status"] == 200 && count($result['schemas']) == 0) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API restore get rows (404)</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $restore = $api->create_restore($backup["id"]);
          $result = $api->get_restore_rows($restore["id"], "schema-not-exist", 0);
          if(is_wp_error($result) ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API restore get missing files</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $restore = $api->create_restore($backup["id"]);
          $files = array();
          $result = $api->restore_missing_files($restore['id'], $files);
          if($result["missing_files"] == [] ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API restore get file operations needed</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $restore = $api->create_restore($backup["id"]);
          $files = array();
          $result = $api->restore_file_operations($restore['id'], $files);
          if(!is_wp_error($result) && $result == []) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr> <tr>
    <td>API restore swap schema operations</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $restore = $api->create_restore($backup["id"]);
          $result = $api->swap_schema_operations($restore["id"]);
          if(!is_wp_error($result) && count($result) == 11) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API restore get restore operations - delete file</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $commit = $api->commit_backup($backup["id"]);
          $restore = $api->create_restore($backup["id"]);
          $files = array(BitsUtil::fs_get_wp_config_path()."./wp-config.php");
          $result = $api->restore_file_operations($restore["id"], $files);

          if(!is_wp_error($result) && count($result) == 1) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>


  <tr>
    <th colspan="2"> API - Util </th>
  </tr>

  <tr>
    <td>API select_404_results - empty</td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $result = $api->select_404_results([], []);
          if(count($result) == 0) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API select_404_results - one 404</td>
    <td>
      <?php
          $missing = array( "status" => 404 );
          $success = array( "status" => 200 );
          $entry1 = array("a" => "b");
          $entry2 = array("c" => "d");
          $result = $api->select_404_results([$success, $missing], [$entry1, $entry2]);
          if($result[0] == $entry2 && sizeof($result) == 1) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API get schemas (schema doesn't exist)</td>
    <td>
      <?php
          $result = $api->get_schemas(["not-exist", "not-exist-2"]);
          if(count($result) == 2) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API creates schemas</td>
    <td>
      <?php
          $result = $api->create_schemas(["test", "test2"]);
          if(count($result) == 2) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API get rows </td>
    <td>
      <?php
          $result = $api->get_rows(["xx"]);
          if(count($result) == 1) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API create rows </td>
    <td>
      <?php
          $result = $api->create_schemas(["test"]);
          $row1 = array( "content" => array( "field", "".microtime() ), "schema" => "test");
          $row2 = array( "content" => array( "field", "".microtime() . 1 ), "schema" => "test");
          $result = $api->create_rows([ $row1, $row2 ]);
          if(count($result) == 2 && $result[0]["status"] == 200) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API get row groups </td>
    <td>
      <?php
          $result = $api->get_row_groups(["xx"]);
          if(count($result) == 1) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API create row group </td>
    <td>
      <?php
          $result = $api->create_schemas(["test"]);
          $row1 = array( "content" => array( "field", microtime() ), "schema" => "test");
          $row_result = $api->create_rows([ $row1 ]);
          $row_group1 = array( "rows" => [ "not-found" ], "schema" => "test" );
          $result = $api->create_row_groups(array(array("schema" => "test", "rows" => [$row1])));
          if(count($result) == 1 && $result[0]["status"] == 200) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>API Add missing row group </td>
    <td>
      <?php
          $backup = create_backup($api, array( "test" => "true" ));
          $result = $api->create_schemas(["test"]);
          $row1 = array("a" => "b");
          $row2 = array("a" => "c");
          $result = $api->add_rows_to_backup($backup["id"], [$row1, $row2], "test");
          if(count($result) == 2) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr> 
  <tr>
    <td>API row fingerprints with same data are equal</td>
    <td>
      <?php
          $content = array("a" => "b");
          $row1 = array( "content" => $content, "schema" => "1" );
          $row2 = array( "content" => $content, "schema" => "1" );
          $fingerprint1 = $api->fingerprint_for_row($row1);
          $fingerprint2 = $api->fingerprint_for_row($row2);
          if($fingerprint1 == $fingerprint2) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
   <tr>
    <td>API row fingerprint includes schema</td>
    <td>
      <?php
          $content = array("a" => "b");
          $row1 = array( "content" => $content, "schema" => "1" );
          $row2 = array( "content" => $content, "schema" => "2" );
          $fingerprint1 = $api->fingerprint_for_row($row1);
          $fingerprint2 = $api->fingerprint_for_row($row2);
          if($fingerprint1 != $fingerprint2) { pass(); } else { fail([$fingerprint1, $fingerprint2]); };
      ?>
    </td>
  </tr> 
   <tr>
    <td>API row fingerprint content order doesn't matter</td>
    <td>
      <?php
          $content1 = array("a" => "b", "c" => "d");
          $content2 = array("c" => "d", "a" => "b");
          $row1 = array( "content" => $content1, "schema" => "1" );
          $row2 = array( "content" => $content2, "schema" => "1" );
          $fingerprint1 = $api->fingerprint_for_row($row1);
          $fingerprint2 = $api->fingerprint_for_row($row2);
          if($fingerprint1 == $fingerprint2) { pass(); } else { fail([$fingerprint1, $fingerprint2]); };
      ?>
    </td>
  </tr> 
   <tr>
    <td>API row_group row content order doesn't matter</td>
    <td>
      <?php
          $schema = "CREATE TABLE test";
          $content1 = array("a" => "b", "c" => "d");
          $content2 = array("c" => "d", "a" => "b");
          $row1 = array( "content" => $content1, "schema" => $schema);
          $row2 = array( "content" => $content2, "schema" => $schema);
          $row_group1 = array("rows" => [$row1, $row2], "schema" => $schema);
          $row_group2 = array("rows" => [$row1, $row2], "schema" => $schema);
          $fingerprint1 = $api->fingerprint_for_row_group($row_group1);
          $fingerprint2 = $api->fingerprint_for_row_group($row_group2);
          if($fingerprint1 == $fingerprint2) { pass(); } else { fail([$fingerprint1, $fingerprint2]); };
      ?>
    </td>
  </tr> 
  <tr>
    <td>API row_group row order does matter</td>
    <td>
      <?php
          $schema = "CREATE TABLE test";
          $content1 = array("a" => "b", "c" => "d");
          $content2 = array("c" => "d", "a" => "b");
          $row1 = array( "content" => $content1, "schema" => $schema);
          $row2 = array( "content" => $content2, "schema" => $schema);
          $row_group1 = array("rows" => [$row1, $row2], "schema" => $schema);
          $row_group2 = array("rows" => [$row2, $row1], "schema" => $schema);
          $fingerprint1 = $api->fingerprint_for_row_group($row_group1);
          $fingerprint2 = $api->fingerprint_for_row_group($row_group2);
          if($fingerprint1 != $fingerprint2) { pass(); } else { fail([$fingerprint1, $fingerprint2]); };
      ?>
    </td>
  </tr> 
   <tr>
    <td>API row_group fingerprint includes schema</td>
    <td>
      <?php
          $schema = "CREATE TABLE test";
          $content1 = array("a" => "b", "c" => "d");
          $content2 = array("c" => "d", "a" => "b");
          $row1 = array( "content" => $content1, "schema" => $schema);
          $row2 = array( "content" => $content2, "schema" => $schema);
          $row_group1 = array("rows" => [$row1, $row2], "schema" => $schema);
          $row_group2 = array("rows" => [$row1, $row2], "schema" => ($schema."DIFFERENT"));
          $fingerprint1 = $api->fingerprint_for_row_group($row_group1);
          $fingerprint2 = $api->fingerprint_for_row_group($row_group2);
          if($fingerprint1 != $fingerprint2) { pass(); } else { fail([$fingerprint1, $fingerprint2]); };
      ?>
    </td>
  </tr> 
  <tr>
   <td>API upload files</td>
   <td>
     <?php
        $files = array($WP_PATH, $WP_PATH."/index.php");
        $results = $api->upload_files($files, 0, BitsUtil::fs_get_wp_config_path());
        if(
          count($results) == count($files) &&
          count($results[$WP_PATH."/index.php"]) > 0
        ) { pass(); } else { fail($results); };
     ?>
   </td>
  </tr> 
  <tr>
    <th colspan="2"> Backup State Machine </th>
  </tr>
  <tr>
   <td>Invalid State return WP_Error</td>
   <td>
     <?php
        $result = $backup_state_machine->process_step($worker, "NOT-REAL-STEP");
        if(
          is_wp_error($result)
        ) { pass(); } else { fail($result); };
     ?>
   </td>
  </tr> 
  <tr>
    <th colspan="2"> Restore State Machine </th>
  </tr>
  <tr>
    <th colspan="2"> Backup Worker </th>
  </tr>
  <tr>
    <td> Util - all_tables</td>
    <td>
    <?php
          $result = BitsUtil::all_tables();
          if(sizeof($result) > 0) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>Backup Worker - query throws error</td>
    <td>
    <?php
          global $wpdb;
          $restore = create_restore($api);
          $worker = new BitsBackupWorker($api);
          $result = $worker->query("NOT VALID SQL");
          $worker->query("SELECT 1"); // Clear out database errors

          if(is_wp_error($result)) { pass(); } else { fail($result); };
    ?>
    </td>
  </tr>

  <tr>
    <td>Backup API - creates backup</td>
    <td>
    <?php
          $worker = new BitsBackupWorker($api);
          $result = create_backup($api);
          if($result["id"] > 0) { pass(); } else { fail($result);};
      ?>
    </td>
  </tr>
  <tr>
    <td>Backup Worker - upload files</td>
    <td>
    <?php
          $worker = new BitsBackupWorker($api);
          $backup_id = create_backup($api)["id"];
          $worker->upload_files($backup_id, 0, BitsUtil::fs_get_wp_config_path());
          $result = $api->get_backup($backup_id);
          if($result["file_count"] > 5
          ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>Backup Worker - scan schema</td>
    <td>
    <?php
          $worker = new BitsBackupWorker($api);
          $backup_id = create_backup($api)["id"];

          $scan_response = $worker->scan_schema($backup_id);
          if(is_wp_error($scan_response)) return fail($scan_response);
          $result = $api->get_backup($backup_id);
          if($result["schema_count"] > 0 && $result["id"] == $backup_id) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>Backup Worker - scan table - handles page 0 of table 3</td>
    <td>
    <?php
          $worker = new BitsBackupWorker($api);
          $backup_id = create_backup($api)["id"];
          $worker->scan_schema($backup_id);
          $schema = 'wp_posts';
          $worker->scan_table($backup_id,$schema, 0);
          $result = $api->get_backup($backup_id);
          if(
            $result["row_group_count"] >= 1
          ) { pass(); } else { fail($result); };
      ?>
    </td>
  </tr>
  <tr>
    <td>[integration] Backup Worker - adds schema to active backup API</td>
    <td>
    <?php
          $worker = new BitsBackupWorker($api);
          $backup_id = create_backup($api)["id"];
          $worker->scan_schema($backup_id);
          $backup = $api->get_backup($backup_id);
          if($backup["schema_count"] > 0) { pass(); } else { fail($backup); };
      ?>
    </td>
  </tr>
  <tr>
    <td>[integration] Backup Worker - complete empty backup</td>
    <td>
    <?php
          $worker = new BitsBackupWorker($api);
          $backup_id = create_backup($api)["id"];
          $worker->complete($backup_id);
          $backup = $api->get_backup($backup_id);

          if($backup["state"] == "COMMITTED") { pass(); } else { fail($backup); };
      ?>
    </td>
  </tr>

  <tr>
    <th colspan="2"> Restore Worker </th>
  </tr>

  <?php
    function create_restore($api) {
      $backup = create_backup($api, array( "test" => "true" ));
      $backup_worker = new BitsBackupWorker($api);
      $backup_worker->scan_schema($backup['id']);
      $backup_worker->scan_table($backup['id'],"wp_posts", 0);
      $committed = $api->commit_backup($backup["id"]);
      return $api->create_restore($backup['id']);
    }
  ?>

  <tr>
    <td>Restore Worker - create_restore can create a restore</td>
    <td>
    <?php
          $result = create_restore($api);
          if($result["id"] > 0) { pass(); } else { fail($result); };
    ?>
    </td>
  </tr>
  <tr>
    <td>Restore Worker - restore_schema adds tables</td>
    <td>
    <?php
          global $wpdb;
          $restore = create_restore($api);
          $worker = new BitsRestoreWorker($api);
          $count = $wpdb->get_results("SELECT COUNT(*) FROM information_schema.tables", ARRAY_N)[0][0];
          
          $worker->restore_schema($restore['id']);
          $new_count = $wpdb->get_results("SELECT COUNT(*) FROM information_schema.tables", ARRAY_N)[0][0];

          if($new_count > $count) { pass(); } else { fail("$count is not < $new_count"); };
    ?>
    </td>
  </tr>
  <tr>
    <td>Restore Worker - query throws error</td>
    <td>
    <?php
          global $wpdb;
          $restore = create_restore($api);
          $worker = new BitsRestoreWorker($api);
          $result = $worker->query("NOT VALID SQL");
          $worker->query("SELECT 1");

          if(is_wp_error($result)) { pass(); } else { fail($result); };
    ?>
    </td>
  </tr>
  <tr>
    <td>Restore Worker - download_files downloads files</td>
    <td>
    <?php
          $missing_file = "https://www.google.com/images/srpr/logo11w.png";

          $restore = create_restore($api);
          $worker = new BitsRestoreWorker($api);
          $file_path = "/tmp/fp1";
          $to_download = array(array(
                "content_fingerprint" => "fp1",
                "url" => $missing_file,
                "filename" => "/tmp/fp1"
          ));
          $result = $worker->download_files($restore['id'], $to_download);

          $file_exists = file_exists($file_path);
          if($file_exists && !is_wp_error($result)) { pass(); } else { fail($result); };
    ?>
    </td>
  </tr> 
  <tr>
    <td>Restore Worker - swap_fs renames and deletes</td>
    <td>
    <?php
          $restore = create_restore($api);
          $worker = new BitsRestoreWorker($api);
          $operations = array(
            array(
              "type" => "delete",
              "path" => BitsUtil::fs_get_wp_config_path()."/wp-content/test_delete"
            ),
            array(
              "type" => "copy",
              "from" => BitsUtil::fs_get_wp_config_path()."/wp-content/test_copy",
              "to" => BitsUtil::fs_get_wp_config_path()."/wp-content/test_copy_complete"
            )
          );
          touch(BitsUtil::fs_get_wp_config_path()."/wp-content/test_delete");
          touch(BitsUtil::fs_get_wp_config_path()."/wp-content/test_copy");
          unlink(BitsUtil::fs_get_wp_config_path()."/wp-content/test_copy_complete");
          $result = $worker->process_file_operations($operations, BitsUtil::fs_get_wp_config_path());

          $copyd = file_exists(BitsUtil::fs_get_wp_config_path()."/wp-content/test_copy_complete");
          $deleted = !file_exists(BitsUtil::fs_get_wp_config_path()."/wp-content/test_delete");

          if($copyd && $deleted) { pass(); } 
          else { fail("copyd $copyd/ deleted $deleted"); };
    ?>
    </td>
  </tr> 




</table>
<h4> End of tests </h4>

<?php

  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }

 class BitsBackupWorker {
    private $api;
    const ROWS_PER_GROUP=1000;
    const FILES_PER_CHUNK=600;
    function __construct($api) {
      $this->api = $api;
    }

    function upload_files($backup_id, $page, $path) {
      $files = BitsUtil::glob_r($path);
      // chunk $state["files"] as to_upload
      $to_upload = array_slice($files, self::FILES_PER_CHUNK * $page, self::FILES_PER_CHUNK);
      if(count($to_upload) == 0) {
        return true;
      }

      $uploaded = $this->api->upload_files($to_upload);
      if(is_wp_error($uploaded)) {
        return $uploaded;
      }
      $added = $this->api->add_files_to_backup($backup_id, $uploaded);
      if(is_wp_error($added)) {
        return $added;
      }

      return true;
    }


    /***********************************
          SQL Backup
     ***********************************/
    function query($sql, $lookup=ARRAY_N) {
      return BitsUtil::query($sql, $lookup);
    }

    private function send_table_schemas($backup_id, $tables) {
      $sqls = array_map(array($this, "get_schema"), $tables);
      return $this->api->add_schemas_to_backup($backup_id, $sqls);
    }

    private function get_schema($table) {
      return BitsUtil::get_schema($table);
    }
    private function count_rows($table) {
      $result= $this->query("select count(*) from `$table`");
      if(is_wp_error($result)) { 
        return $result;
      }
      $count = $result[0][0];
      return intval($count);
    }
    private function rows_for_table($table, $offset, $limit) {
      $sql = "select * from `$table` limit $limit offset $offset";
      $results = $this->query($sql, ARRAY_A);
      return $results;
    }
    private function rows_for_table_page($table, $page) {
      return $this->rows_for_table($table, $page*self::ROWS_PER_GROUP, self::ROWS_PER_GROUP);
    }

    private function map_schema_to_table($table_name) {
      return array(
        "name" => $table_name
      );
    }

    function all_tables() {
      return BitsUtil::all_tables();
    }

    function scan_schema($backup_id) {
      $tables = $this->all_tables();
      if(is_wp_error($tables)) {
        return $tables;
      }
      $schemas = $this->send_table_schemas($backup_id, $tables);
      if(is_wp_error($schemas)) {
        return $schemas;
      }
      return true;
    }

    function scan_table($backup_id, $table_name, $table_page, $schema_fingerprint) {
      // grab all rows for table page
      $rows = $this->rows_for_table_page($table_name, $table_page);

      $rows = $this->api->add_rows_to_backup($backup_id, $rows, $schema_fingerprint);
      if(is_wp_error($rows)) {
        return $rows;
      }
      return true;
    }
    function complete($backup_id) {
      $result = $this->api->commit_backup($backup_id);
      if(is_wp_error($result)) {
        return $result;
      }
      return true;
    }
  }
?>

<?php

  // Resumes an existing backup
  // + Saves the state of the current backup
  class BitsBackupStateMachine {
    private $api;
    function __construct($api) {
      $this->api = $api;
    }
    function process_step($worker, $next_step) {
      if(!isset($next_step['step'])) {
        return new WP_Error("could-not-find-step", $this->api->json($next_step));
      }
      $next = $next_step['step'];
      $backup_id = $next_step['backup_id'];
      if($next == "scan_schema") {
        return $worker->scan_schema($backup_id);
      } 
      if($next == "complete") {
        return $worker->complete($backup_id);
      } 
      if($next == "scan_table") {
        $table = $next_step['table'];
        $page = $next_step['page'];
        $table_fingerprint = $next_step['fingerprint'];
        return $worker->scan_table($backup_id, $table, $page, $table_fingerprint);
      }
      if($next == "upload_files") {
        $page = $next_step['page'];
        $path = $next_step['path'];
        return $worker->upload_files($backup_id, $page, $path);
      }
      return new WP_Error('invalid state', "No backup state $next");
    }

    function run() {
      while($this->transition() != true);
    }
    function transition() {
      if(is_wp_error($this->api->root)) {
        return $this->handle_wp_error($this->api->root, "wp-config-path");
      }
      $start_time = time();
      $worker = new BitsBackupWorker($this->api);
      $next_step = $this->api->next_step();
      if(is_wp_error($next_step)) {
        return $this->handle_wp_error($next_step, "error-processing-step");
      }
      if($next_step == null || $next_step['step'] == null) {
        return true; // error or no backup
      }
      $this->api->log("info", "Processing step ".$next_step['step'] );
      $process_result = $this->process_step($worker, $next_step);
      if(is_wp_error($process_result)) {
        $this->api->log("info", "Error in processing ".$next_step['step']);
        return $this->handle_wp_error($process_result, $next_step['step']);
      } else {
        $this->api->log("info", "Processed ".$next_step['step']);
        $this->api->complete_step($next_step['step_id']);
      }
      if($next_step['step'] == 'complete') {
        return true; //Backup done
      }

      return false;
    }

    function handle_wp_error($wp_error, $stepname) {
      $messages = $wp_error->get_error_messages();
      $message = implode("\n", $messages);
      $this->api->log("error", "Error on step ".$stepname." : ".$message, array());
      bits_force_cancel_safe();
      return true;
    }

  }
?>

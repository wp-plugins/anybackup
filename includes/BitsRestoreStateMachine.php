<?php

  // Restores a backup
  // Note:  To restore a backup, you must call restore_from_backup_id($backup_id) .
  class BitsRestoreStateMachine {
    private $api;
    function __construct($api) {
      $this->api = $api;
    }
    function process_step($worker, $next_step) {
      if(!isset($next_step['step'])) {
        return new WP_Error("could-not-find-step", $this->api->json($next_step));
      }
      $next = $next_step['step'];
      $restore_id = $next_step['restore_id'];
      if($next == "restore_schema") {
        return $worker->restore_schema($restore_id);
      } 
      if($next == "restore_complete") {
        return $worker->restore_complete($restore_id);
      } 
      if($next == "restore_table") {
        $schema_fingerprint = $next_step['schema_fingerprint'];
        $page = $next_step['page'];
        return $worker->restore_table($restore_id, $schema_fingerprint, $page);
      }
      if($next == "restore_files") {
        $paths = $next_step['paths'];
        return $worker->restore_files($restore_id, $paths);
      }
      if($next == "swap") {
        $paths = $next_step['paths'];
        return $worker->swap($restore_id, $paths);
      }

      return new WP_Error('invalid state', "No restore state $next");
    }

    function run() {
      while($this->transition() != true);
    }
    function transition() {
      if(is_wp_error($this->api->root)) {
        return $this->handle_wp_error($this->api->root, "wp-config-path");
      }
      $start_time = time();
      $worker = new BitsRestoreWorker($this->api);

      $next_step = $this->api->next_step();


      if(is_wp_error($next_step)) {
        return $this->handle_wp_error($next_step, 'step-not-found');
      }

      if($next_step == null || $next_step['step'] == null) {
        return true; // error or no backup
      }
      $processed_step = $this->process_step($worker, $next_step);
      if(is_wp_error($processed_step)) {
        return $this->handle_wp_error($processed_step, $next_step['step']);
      } else {
        $this->api->complete_step($next_step['step_id']);
      }
      if($next_step['step'] == 'restore_complete') {
        return true; //Restore done
      }

      return false;
    }

    function handle_wp_error($wp_error, $stepname) {
      $messages = $wp_error->get_error_messages();
      $message = implode("\n", $messages);
      $this->api->log("error", "Error on step ".$stepname." : ".$message, array());
      bits_force_cancel_safe();
      return $wp_error;
    }


    function reset() {
      $this->api->set_save_state(array());
    }

  }
?>

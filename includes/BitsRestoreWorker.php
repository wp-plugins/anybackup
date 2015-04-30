<?php

  class BitsRestoreWorker {
    const DOWNLOAD_FILES_PER_CHUNK=1000;
    private $api;
    function __construct($api) {
      $this->api = $api;
    }
    function query($sql, $lookup=ARRAY_N) {
      return BitsUtil::query($sql, $lookup);
    }
    function restore_table($restore_id, $schema_fingerprint, $page) {
      $to_restore = $this->api->get_restore_rows($restore_id, $schema_fingerprint, $page);
      if(is_wp_error($to_restore)) {
        return $to_restore;
      }
      foreach($to_restore['rows'] as $row) {
        $result = $this->query($row);
        if(is_wp_error($result)) { 
          return $result; 
        }
      }
      return true;
    }
    function restore_schema($restore_id) {
      $to_restore = $this->api->get_restore_schemas($restore_id);
      if(is_wp_error($to_restore)) {
        return $to_restore;
      }
      foreach($to_restore["schemas"] as $schema) {
        $sql = $schema["sql"];
        $result = $this->query($sql);
        if(is_wp_error($result)) { 
          return $result; 
        }
      }
      return true;
    }
    function restore_files($restore_id, $paths) {
      $files = BitsUtil::glob_r($paths);
      $result = $this->api->restore_missing_files($restore_id, $files);
      if(is_wp_error($result)) {
        return $result;
      }
      return $this->download_files($restore_id, $result['missing_files']);
    }


    function download_files($restore_id, $to_download) {
      $download_i = 0;
      foreach($to_download as $download) {
        if($download_i % 100 == 0) {
          echo("Downloading file $download_i\n");
        }
        $download_i += 1;
        $fingerprint = $download["content_fingerprint"];
        $url = $download["url"];

        @mkdir(dirname($download["filename"]), 0777, true);
        $permfile = $download["filename"];
        $result = $this->api->download_url( $url, $permfile, $timeout = 3000 );
        if(is_wp_error($result)) {
          echo "Encountered an error downloading $permfile from $url\n";
          return $result;
        } else {
          if(!file_exists($permfile)) {
            return new WP_Error("downloaded-file-missing", "Download completed but file does not exist at $permfile");
          }
        }
      }
      return true;
    }

    function swap($restore_id, $paths) {
      $schema_operations = $this->api->swap_schema_operations($restore_id);
      if(is_wp_error($schema_operations)) {
        return $schema_operations;
      }
      $files = BitsUtil::glob_r($paths);
      $file_operations = $this->api->restore_file_operations($restore_id, $files);
      if(is_wp_error($file_operations)) {
        return $file_operations;
      }
      $files = $this->process_file_operations($file_operations);
      if(is_wp_error($files)) {
        //TODO: revert?
        return $files;
      }
      $tables = $this->process_schema_operations($schema_operations);
      if(is_wp_error($tables)) {
        //TODO: revert?
        return $tables;
      }
      return true;
    }

    function process_schema_operations($operations) {
      foreach($operations as $operation) {
        $result = $this->query($operation);
        if(is_wp_error($result)) { 
          return $result; 
        }
      }
    }

    function process_file_operations($operations) {
      for($i=0; $i<sizeof($operations); $i+=1) {
        $operation = $operations[$i];
        if($operation["type"] == 'copy') {
          $source = $operation["from"];
          $dest = $operation["to"];

          @mkdir(dirname($dest), 0777, true);
          if( !file_exists($source) ) {
            return new WP_Error("file-missing", "$source is missing - cannot be renamed");
          }
          if( !is_writable(dirname($dest)) ) {
            $this->api->log("warning", "$dest cannot be written to");
            continue;
          }
          @unlink( $dest );
          if(!copy( $source, $dest )) {
            return new WP_Error("unknown-copy-error", "Copy failed.  Cannot copy to $dest.");
          }
        } elseif($operation["type"] == 'delete') {
          $path = $operation['path'];
          if(file_exists($path) && !is_dir($path)) {
            unlink( $path );
          }
        } else {
          echo "Unknown operation:\n";
          var_dump($operation);
          // TODO: Handle unknown operation?
        }
      }

      return true;
    }


    function restore_complete($restore_id) {
      $result = $this->api->complete_restore($restore_id);
      if(is_wp_error($result)) {
        return $result;
      }
      return true;
    }
  }
?>

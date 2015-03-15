<?php
class BitsUtil {
    /** Returns a list of all files in $path, recursively */
    static function glob_r($path) {
      if(is_array($path)) {
        $files = array();
        foreach($path as $p) {
          $files = array_merge($files, BitsUtil::glob_r($p));
        }
        return $files;
      }
      $path_exploded = preg_split("/[\\/\\\\]/", $path);
      $path_name = $path_exploded[count($path_exploded)-1];
      
      if($path_name == "." || $path_name == "..") {
        return array();
      }

      if(!is_readable($path)) {
        return array();
      }
      if(!is_dir($path)) {
        return array($path);
      }
      $pattern = $path.'/{,.}*';
      $contents = glob($pattern, GLOB_BRACE);

      $result = array($path);
      $globs = array_map(array('BitsUtil', "glob_r"), $contents);
      foreach($globs as $glob) {
        $result = array_merge($result, $glob);
      }
      return $result;
    }

    static function fs_get_wp_config_path()
    {
      if(defined('ABSPATH')) {
        return ABSPATH;
      }
      return new WP_Error("wp-config path not found", "ABSPATH is not defined");
    }

    static function renice($priority) {
      exec("renice +$priority ".getmypid());
    }
    static function reset_nice(){
      if(function_exists('posix_kill')) {
        // Kill us so that the thread priority will return to the default.  
        // We cannot raise the priority due to linux standards.
        posix_kill( getmypid(), 28 );
      }
    }

    static function all_tables() {
      $all_tables = BitsUtil::query("SHOW TABLES");
      if(is_wp_error($all_tables)) {
        return $all_tables;
      }
      return array_map(array('BitsUtil', "map_all_tables"), $all_tables);
    }

    static function map_all_tables($table) {
      return $table[0];
    }

    static function query($sql, $lookup=ARRAY_N) {
      global $wpdb;
      //echo "<br/><div style='font-weight: 900'>Running sql '$sql'</div><br/>";
      //$wpdb->show_errors();
      
      $prior_error = $wpdb->last_error;
      $result = $wpdb->get_results($sql, $lookup);
      if($wpdb->last_error != $prior_error) {
        # Error while calling query
        #
        return new WP_Error("sql-error", "Query='$sql', error='".$wpdb->last_error."'");
      }
      return $result;
    }

    static function get_schema($table) {
      $result = BitsUtil::query("SHOW CREATE TABLE `$table`");
      if(is_wp_error($result)) { 
        return $result;
      }
      return $result[0][1];

    }

    static function get_all_table_sqls() {
      $tables = BitsUtil::all_tables();
      return array_map(array('BitsUtil', "get_schema"), $tables);
    }
}

?>

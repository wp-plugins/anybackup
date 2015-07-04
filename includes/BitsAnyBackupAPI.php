<?php

  if (! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
  }

  // Calls the 255 BITS time machine API
  class BitsAnyBackupAPI {
    const API_VERSION = 1;
    private $api_key = null;
    public $root = null;

    function __construct() {
      $this->root = BitsUtil::fs_get_wp_config_path();
    }

    function get_server() {
      if(isset($GLOBALS["BITS_ANYBACKUP_SERVER"])) {
        return $GLOBALS["BITS_ANYBACKUP_SERVER"];
      }
      return "https://anybackup.io";
    }
    
    function get_asset_server() {
      return $this->get_server();
    }

    function get_api_key() {
      return $this->api_key;
    }

    /** 
     * Sets the API key.  You must call this before using any api function.
     */
    function set_api_key($api_key) {
      $this->api_key = $api_key;
    }

    function create_api_key() {
      $site_server = $this->call_api("POST", "site_servers");
      if(is_wp_error($site_server)) {
        return $site_server;
      }
      //TODO: error handling
      return $site_server["api_key"];
    }

    /**
     * Called when the plugin is activated
     */
    function activate() {
      $email = get_option("admin_email");
      return $this->call_api("POST", "site_servers/activate", array("email" => $email));
    }


    /**
     * Called when the plugin is deactivated
     */
    function deactivate() {
      return $this->call_api("POST", "site_servers/deactivate", array());
    }

    /**
     * Register an account
     **/
    function create_account($email, $password) {
      return $this->call_api("POST", "accounts", array("email" => $email, "password" => $password));
    }

    /**
     * Login to an existing account
     */
    function login_account($email, $password) {
      return $this->call_api("POST", "accounts/login", array("email" => $email, "password" => $password));
    }

    /**
     * Update an account - used in upgrading / downgrading
     */
    function update_account($plan_id, $token) {
      return $this->call_api("POST", "accounts/update", array("plan_id" => $plan_id, "token" => $token));
    }


    /**
     * List all plans available to this site_server.
     */
    function get_plans() {
      return $this->call_api("GET", "plans");
    }

    /**
     * Create a support ticket.
     */
    function create_support_ticket($urgent, $content) {
      return $this->call_api("POST", "support_tickets", array("content" => $content, "urgent" => $urgent));
    }

    /**
     * Transfers a site_server to a new site.  This allows for migration onto new servers.
     */
    function update_site_server($site_id) {
      return $this->call_api("POST", "site_servers/update", array("site_id" => $site_id));
    }

    /** 
     * Returns a list of sites associated with the api key
     */
    function get_sites() {
      return $this->call_api("GET", "sites", array());
    }

    /**
     * Returns all information about the current site
     **/
    function get_site() {
      return $this->call_api("GET", "sites/current", array());
    }

    /**
     * Set the site settings. 
     * $options includes:
     *  
     * * backup_frequency_in_hours - automatic backup frequency.  -1 for never
     **/
    function update_site($options) {
      return $this->call_api("POST", "sites/update", $options);
    }

    /**
     *  Gets the next step for this site server.
     *  It could be a restore, a backup, or neither.
     **/
    function next_step() {
      return $this->call_api("GET", "steps/next", array());
    }

    /**
     * Step summary lets us update progress bar
     */
    function step_summary() {
      return $this->call_api("GET", "steps/summary");
    }

    function complete_step($step_id) {
      return $this->call_api("POST", "steps/$step_id/complete", array());
    }

    /**
     * $opts can take any arbitrary metadata.  This can include things like:
     * "wordpress_version" => "4.1"
     *
     * or any other information that isn't tied to a higher order object.
     */
    function create_backup($opts=array()) {
      $args = array_merge($this->get_platform_metadata(), $opts);
      return $this->call_api("POST", "backups", $args);
    }

    /**
     * Internal, trims out all but name, version and url from plugin.
     */
    function trim_plugin_info($plugin) {

      return array("name" => $plugin["Name"], "version" => $plugin["Version"], "uri" => $plugin["PluginURI"]);
    }


    /**
     * Filtering a array by its keys using a callback.
     * From: https://gist.github.com/h4cc/8e2e3d0f6a8cd9cacde8
     * 
     * @param $array array The array to filter
     * @param $callback Callback The filter callback, that will get the key as first argument.
     * 
     * @return array The remaining key => value combinations from $array.
     */
    function array_filter_key($callback, array $array)
    {
      $matchedKeys = array_filter(array_keys($array), $callback);

      return array_intersect_key($array, array_flip($matchedKeys));
    }

    function select_active_plugin($key) {
      return is_plugin_active($key);
    }


    /**
     * Internal, platform specific metadata
     */
    function get_platform_metadata() {
      global $wpdb;
      global $wp_theme_directories;
      $name = get_bloginfo("name");
      $version = get_bloginfo("version");
      $uri = home_url();
      $wp_config_php_path = BitsUtil::fs_get_wp_config_path();
      $content_path = WP_CONTENT_DIR; # TODO what happens if this is not explicitly set
      $plugins_path = WP_PLUGIN_DIR;
      $current_theme_path = get_template_directory();
      $stylesheet_path = get_stylesheet_directory();

      $extra_themes_paths = $wp_theme_directories;
      $wp_lang_dir = null;
      if(defined("WP_LANG_DIR")) {
        $wp_lang_dir = WP_LANG_DIR;
      }
      $curl_exists = function_exists('curl_version');
      if($curl_exists) {
        $curl_version = curl_version();
      } else {
        $curl_version = null;
      }


      $plugins_installed = null;
      if(!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      if(function_exists('get_plugins')) {
        $active_plugins = $this->array_filter_key( array($this, "select_active_plugin"), get_plugins());
        $plugins_installed = array_map( array($this, "trim_plugin_info"), $active_plugins);
      }

      //TODO any of these arrays?
      $upload_dir = wp_upload_dir();
      if(isset($upload_dir['basedir'])) {
        $uploads_path = $upload_dir['basedir'];
      } else {
        $uploads_path = WP_CONTENT_DIR."/uploads";
      }
      return array(
        "plugin_version" => $GLOBALS["BITS_ANYBACKUP_PLUGIN_VERSION"],
        "site_name" => $name,
        "site_type" => "wordpress",
        "site_uri" => $uri,
        "wordpress_version" => $version,
        "table_prefix" => $wpdb->prefix,
        "php_version" => phpversion(),
        "php_os" => PHP_OS,
        "php_uname_s" => php_uname('s'),
        "gzip" => function_exists("gzencode"),
        "bzip2" => function_exists("bzcompress"),
        "native_json_support" => function_exists("json_encode"),
        "curl_version" => $curl_version,
        "plugins" => $plugins_installed,
        "paths" => array(
          "wp-config.php" => $wp_config_php_path,
          "wp-content" => $content_path,
          "plugins" => $plugins_path,
          "extra_themes" => $extra_themes_paths,
          "uploads" => $uploads_path,
          "theme" => $current_theme_path,
          "stylesheets" => $stylesheet_path,
          "lang" => $wp_lang_dir
        )
      );
    }

    /**
     * Returns a list of all backups for the site.
     *
     * Each associative array is of the form:
     * {
     *   id: BACKUP_ID,
     *   state: [UNCOMMITTED|COMMITTED],
     *   links: ...,
     *   created_at: ...,
     *   committed_at: ...
     * }
     */
    function list_backups() {
      return $this->call_api("GET", "backups");
    }

    /** 
     * $id is a valid backup $id in an UNCOMMITTED state
     *
     * $row_contents is of the form:
     *
     * array(array( "field1" => "value1", "field2" => "value2" ), ...);
     *
     * $schema_fingerprint is passed in from the current Step on the server
     */
    function add_rows_to_backup($id, $row_contents, $schema_fingerprint) {
      if(sizeof($row_contents) == 0) {
        return array();
      }
      $rows = $this->build_rows_with_schema($row_contents, $schema_fingerprint);
      // calculate row_group
      $row_group = array("schema" => $schema_fingerprint, "rows" => $rows);
      $row_group_fingerprint_map = $this->map_row_group_fingerprint($row_group);
      $row_group_fingerprint = $row_group_fingerprint_map["fingerprint"];
      // add row group
      $add_row_group_args = array("row_groups" =>array($row_group_fingerprint));
      $result = $this->call_api("POST", "backups/$id/add", $add_row_group_args);
      if(is_wp_error($result)) {
        return $result;
      }
      $row_group_exists = $result["row_groups"][0]["status"] == 200;

      if(!$row_group_exists) {
        // if not exists:
        $row_fingerprints = array_map(array($this, "fingerprint_for_row"), $rows);
        $row_show_responses = $this->get_rows($row_fingerprints);
        if(is_wp_error($row_show_responses)) {
          return $row_show_responses;
        }
        //   find missing rows
        $missing_rows = $this->select_404_results($row_show_responses, $rows);
        if(count($missing_rows) > 0) {
          $result = $this->create_rows($missing_rows);
          if(is_wp_error($result)) {
            return $result;
          }
        }
        //   create row_group
        $new_row_groups = $this->create_row_groups(array($row_group));
        if(is_wp_error($new_row_groups)) {
          return $new_row_groups;
        }
        //   add row group
        $result = $this->call_api("POST", "backups/$id/add", $add_row_group_args);
        if(is_wp_error($result)) {
          return $result;
        }
      }
      return $rows;
    }

    function build_rows_with_schema($row_contents, $schema_fingerprint) {
      $rows_with_content_and_schema = array();
      foreach($row_contents as $row) {
        array_push( $rows_with_content_and_schema, array(
          "content" => $row,
          "schema" => $schema_fingerprint
        ));
      }
      return $rows_with_content_and_schema;
    }

    /**
     * $id is a valid backup $id in an UNCOMMITTED state
     *
     * $schemas is of the form:
     *
     * array("CREATE TABLE ...", "CREATE TABLE ...");
     */
    function add_schemas_to_backup($id, $schemas) {
      //$GLOBALS["BITS_DEBUG"]=true;
      $schema_fingerprints = array_map(array($this, "schema_fingerprint"), $schemas);
      $result = $this->call_api("POST", "backups/$id/add", array("schemas" => $schema_fingerprints));
      if(is_wp_error($result)) {
        return $result;
      }
      $schemas_to_create = $this->select_404_results($result["schemas"], $schemas);
      if(sizeof($schemas_to_create) == 0) {
        return $result;
      }
      $this->create_schemas($schemas_to_create);
      //$GLOBALS["BITS_DEBUG"]=false;
      return $this->call_api("POST", "backups/$id/add", array("schemas" => $schema_fingerprints));
    }

    /**
     * Used internally
     */
    function schema_fingerprint($schema) {
      return sha1($this->schema_fingerprint_replace_autoincrement($schema));
    }

    /**
     * Used internally
     */
    function schema_fingerprint_replace_autoincrement($schema) {
      return preg_replace('/ AUTO_INCREMENT=\d+/i', "", $schema);
    }

    /**
     * Used internally
     */
    function select_404_results($results, $original) {
      $selected = array();
      for($i = 0; $i < sizeof($results); $i += 1) {
        $item = $results[$i];
        if($item['status'] == 404) {
          array_push($selected, $original[$i]);
        }
      }
      return $selected;
    }

    /**
     *
     * Changes an UNCOMMITTED backup to a COMMITTED backup.
     *
     * COMMITTED backups cannot add additional data
     *
     */
    function commit_backup($id) {
      return $this->call_api("POST", "backups/$id/commit");
    }

    /**
     *
     * Cancels a running backup.
     *
     */
    function cancel_backup($id) {
      return $this->call_api("POST", "backups/$id/cancel");
    }

    /**
     * Returns a list of backups
     *
     */
    function get_backups($site_id=null) {
      return $this->call_api("GET", "backups", array("site_id"=>$site_id));
    }


    /**
     *
     * Returns a backup of the form:
     *
     * array(
     *  "id" => $backup_id,
     *  ...
     * )
     *
     * See API documentation for more details
     */
    function get_backup($id) {
      if($id == null) {
        return new WP_Error('invalid api usage', "Need an id when calling get_backup");
      }
      return $this->call_api("GET", "backups/$id");
    }

     /**
     *
     * Sets the name of a backup
     *
     * $id - the id of the backup
     * $args - the fields to update.  Any missing fields will not be updated.
     *
     * $args example:
     * array(
     *  "name => "new backup name"
     * )
      */
     function update_backup($id, $args) {
      if($id == null) {
        return new WP_Error('invalid api usage', "Need an id when calling get_backup");
      }
      return $this->call_api("POST", "backups/$id/update", $args);
    }

    /**
     * Used internally
     */
    function get_schemas($fingerprints) {
      $inline = implode(',', $fingerprints);
      return $this->call_api("GET", "schemas/$inline");
    }


    /**
     * $schemas is of the form
     * array("CREATE TABLE ...", ...);
     *
     * Calling add_schemas_to_backup will create any missing schemas.
     */
    function create_schemas($schemas) {
      $objs = array_map(array($this, "map_schema_fingerprint"), $schemas);
      return $this->call_api("POST", "schemas", array("schemas" => $objs));
    }

    /**
     * Used internally
     */
    function map_schema_fingerprint($sql) {
      $sha1 = $this->schema_fingerprint($sql);
      return array("fingerprint" => $sha1, "sql" => $sql);
    }

    /**
     * Used internally
     */
    function get_rows($fingerprints) {
      return $this->call_api("POST", "rows/lookup", array("fingerprints" => $fingerprints));
    }

    /**
     *
     * $rows is of the form:
     *
     * array(array( "field1" => "value1", "field2" => "value2" ), ...);
     *
     * Calling add_rows_to_backup will create any missing rows.
     */
    function create_rows($rows) {
      $objs = array_map(array($this, "map_row_fingerprint"), $rows);
      return $this->call_api("POST", "rows", array("rows" => $objs));
    }

    /**
     * Used internally
     */
    function map_row_fingerprint($row) {
      $sha1 = $this->fingerprint_for_row($row);
      $content = $this->json($row['content']);
      return array("fingerprint" => $sha1, "content" => $content, "schema" => $row["schema"]);
    }

    /**
     * Used internally
     */
    function map_row_group_fingerprint($row_group) {
      $sha1 = $this->fingerprint_for_row_group($row_group);
      $row_fingerprints = array_map(array($this, "fingerprint_for_row"), $row_group["rows"]);
      return array("fingerprint" => $sha1, "rows" => $row_fingerprints, "schema" => $row_group["schema"]);
    }



    /**
     * Used internally
     */
    function get_row_groups($fingerprints) {
      $inline = implode(',', $fingerprints);
      return $this->call_api("GET", "row_groups/$inline");
    }

    /**
     * Used internally
     *
     * $row_groups is a list of objects in the form:
     * "schema" => "CREATE TABLE ...",
     * "rows" => [full_row1, full_row2]
     */
    function create_row_groups($row_groups) {
      $objs = array_map(array($this, "map_row_group_fingerprint"), $row_groups);
      return $this->call_api("POST", "row_groups", array("row_groups" => $objs));
    }

    /**
     * Used internally
     */
    function fingerprint_for_row($row) {
      ksort($row['content']);
      return sha1($this->json($row));
    }

    function is_utf8($str) {
      return (bool) preg_match('//u', $str);
    }
    function force_utf_8($obj) {
      if(is_string($obj)) {
        if($this->is_utf8($obj)) {
          return $obj;
        }
        return "base64:".base64_encode($obj);
      }
      if(is_array($obj)) {
        foreach($obj as $key => $value) { 
          $obj[$key]=$this->force_utf_8($value);
        }
      }
      return $obj;
    }
    function json($obj) { 
      if($obj == null) {
        return "{}";
      }
      $force_utf8_obj = $this->force_utf_8($obj);
      if(function_exists("json_encode")) {
        return json_encode($force_utf8_obj);
      } else {
        return new WP_Error("json-encode-missing", "json_encode is missing on this version of PHP.  Please use PHP 5+");
      }

    }

    function json_decode($response) {
      if(function_exists("json_decode")) {
        return json_decode($response);
      } else {
        return new WP_Error("json-decode-missing", "json_decode is missing on this version of PHP.  Please use PHP 5+");
      }
    }

    /**
     * Used internally
     */
    function fingerprint_for_row_group($row_group) {
      ksort($row_group['rows']);
      return sha1($this->json($row_group));
    }


    /**
     *
     * Will lazily upload a set of files.  Files are only uploaded if
     * they haven't been uploaded in the past.
     *
     * Ex:
     *
     * upload_files(["/tmp/myfile", "/tmp"]);
     *
     * Result:
     *
     * array(
     *  array(
     *    "/tmp/myfile" => "https://..." # location of file
     *   ),
     *   array(
     *    "/tmp" => null # Will not upload directories or symlinks
     *   )
     * )
     */
    function upload_files($files) {
      // grab sha1s of to_upload and see if they exist
      // upload non-existant
      $file_sha1s = array_map("sha1_file", $files);
      $blobs = $this->get_blobs($file_sha1s);

      $result = array();
      for($i = 0; $i < count($files); $i+=1) {

        $file = $files[$i];
        $blob = $blobs[$i];
        $upload_path = $this->upload_file($file, $blob);
        if(is_wp_error($upload_path)) {
          return $upload_path;
        }
        if($upload_path != null) {
          $result[$file] = $upload_path;
        }
      }
      return $result;
    }

    function upload_file($file, $blob) {
      if(!is_file($file)) {
        return $this->get_file_type($file);
      }
      if($blob['status'] == 200) {
        return $blob['fingerprint'];
      }
      if($blob['status'] != 404) {
        $status = $blob['status'];
        return new WP_Error("unknown-blob-status", "file $file returned unknown status $status");
      }

      if(!is_readable($file)) {
        return new WP_Error("cannot-read-file", "file $file is not readable.  Cannot backup");
      }
      $filesize = filesize($file);

      if($filesize <= 1024*1024) {
        $uploaded = $this->upload_small_file($file, $blob);
      } else {
        $initialize = $this->multipart_upload_initialize($blob, $filesize);
        if(is_wp_error($initialize)) {
          return $initialize;
        }
        if($initialize['file-size-too-large']) {
          $this->log("warning", "$file cannot be backed up.  It is too large.  Please upgrade for unlimited storage.");
          return null;
        }
        $parts = $this->multipart_upload_parts($file, $blob);
        if(is_wp_error($parts)) {
          return $parts;
        }
        $uploaded = $this->multipart_upload_complete($blob);
      }
      if(is_wp_error($uploaded)) {
        return $uploaded;
      }
      return $uploaded['fingerprint'];
    }

    function upload_small_file($file, $blob) {
      $fingerprint = $blob["fingerprint"];
      $size = filesize($file);
      $file_handle = fopen($file, "rb");
      if($file_handle == false) {
        return new WP_Error("cannot-open-file", $file);
      }
      $bytes = fread($file_handle, $size);
      $sha1 = sha1($bytes);
      fclose($file_handle);
      $headers = array(
        'Content-Type' => 'application/octet-stream',
        'Content-Length' => $size
      );

      $result =  $this->call_api("POST", "blobs/".$fingerprint."/upload_small", $bytes, $headers);
      return $result;
    }

    function multipart_upload_initialize($blob, $filesize) {
      $fingerprint = $blob["fingerprint"];
      echo "==Initializing multipart upload for:\n";
      var_dump($blob);
      return $this->call_api("POST", "blobs/".$fingerprint."/start", array("filesize" => $filesize));
    }

    function multipart_upload_parts($file, $blob) {
      $size = filesize($file);
      $chunk_size = 1024*1024*5; # in bytes, 5 MB, least allowed
      $max = intval($size/$chunk_size) + (($size%$chunk_size) > 0 ? 1 : 0);
      $file_handle = fopen($file, "rb");
      echo "==Uploading parts\n";
      var_dump($blob);
      for($i = 0; $i < $max; $i += 1) {
        echo "== $i\n";
        $upload = $this->multipart_upload_part($file_handle, $blob, $i, $chunk_size);
        if(is_wp_error($upload)) {
          fclose($file_handle);
          echo "== WPERROR\n";
          return $upload;
        }
        echo "== $i complete\n";
      }
      fclose($file_handle);
    }
    function multipart_upload_part($file_handle, $blob, $part_number, $size) {
      $fingerprint = $blob["fingerprint"];
      $bytes = fread($file_handle, $size);
      $headers = array(
        'Content-Type' => 'application/octet-stream',
        'Content-Length' => strlen($bytes)
      );
      return $this->call_api("POST", "blobs/".$fingerprint."/parts/".($part_number+1), $bytes, $headers);
    }

    function multipart_upload_complete($blob) {
      $fingerprint = $blob["fingerprint"];
      echo "==Completing\n";
      return $this->call_api("POST", "blobs/".$fingerprint."/complete");
    }

    function get_blobs($fingerprints) {
      return $this->call_api("POST", "blobs/lookup", array("fingerprints" => $fingerprints));
    }

    /**
     * Add files to an UNCOMMITTED backup.
     *
     * $files takes the form:
     * array(
     *   array(
     *     "/path/to/file" => "content_fingerprint"
     *   )
     * )
     *
     * returns:
     *
     * array(
     *   array(
     *     "/path/to/file" => array(
     *       "status" => "200",
     *       "type" => "file",
     *       ... other information - see main api docs ..
     *     )
     *   )
     * )
     *
     **/
    function add_files_to_backup($id, $files) {
      //$GLOBALS['BITS_DEBUG']=true;
      // find meta info on each file (fingerprint, type, etc).
      $metadatas = $this->metadata_for_files($files);
      // call POST /backup/add to see if it exists
      $fingerprints = array_map(array($this, "fingerprint_for_file"), $metadatas);
      $add_files_args = array("files" => $fingerprints);
      $added_file_responses = $this->call_api("POST", "backups/$id/add", $add_files_args);
      if(is_wp_error($added_file_responses)) {
        return $added_file_responses;
      }
      // call POST /files on any missing elements
      $missing_files = $this->select_404_results($added_file_responses['files'], $metadatas);

      if(count($missing_files) == 0) {
        //$GLOBALS['BITS_DEBUG']=false;
        return $added_file_responses; 
      }
      $this->create_files($missing_files);
      // call POST /backup/add again for missing files
      $result = $this->call_api("POST", "backups/$id/add", $add_files_args);
      //$GLOBALS['BITS_DEBUG']=false;
      return $result;
    }

    private function metadata_for_files($files) {
      $result = array();
      foreach($files as $key => $value) {
        $meta = $this->metadata_for_file($key, $value);
        array_push($result, $meta);
      }
      return $result;
    }

    private function get_file_type($path) {
      if(is_dir($path)) {
        return 'directory';
      } elseif(is_file($path)) {
        return 'file';
      } else {
        // We will probably not get here, unless someone backups a block device or something really weird
        return "UNKNOWN";  
      }
    }


    function join_paths($original, $next) {
      if($next[0] == "/") {
        return $next;
      }
      $paths = array($original, $next);

      return preg_replace('#/+#','/',join('/', $paths));
    }

    function get_file_metadata($path) {
      $ctime = filectime($path);
      $mtime = filemtime($path);
      if(function_exists("posix_getgrgid")) {
        $group_gid = posix_getgrgid(filegroup($path));
        $group = $group_gid["name"];
        $user_gid = posix_getpwuid(fileowner($path));
        $user = $user_gid["name"];
      } else {
        $group = "windows";
        $user = "windows";
      }
      $mode = fileperms($path);
      $type = $this->get_file_type($path);
      return array(
        "ctime" => $ctime,
        "type" => $type,
        "group" => $group,
        "user" => $user,
        "mtime" => $mtime,
        "path" => $path,
        "mode" => $mode
      );
    }
    private function metadata_for_file($path, $content_fingerprint) {
      $result = $this->get_file_metadata($path);
      $type = $result['type'];
      if($type == "directory") {
        $result["fingerprint"] = sha1($this->json($result));
      } else if($type == "file") {
        //TODO: investigate why this has /anybackup/
        //WARNING:  The order here matters
        $result["size"]=filesize($path);
        $result["content_fingerprint"]=$content_fingerprint;
        $result["fingerprint"] = sha1($this->json($result));
      }
      
      return $result;
    }
    
    private function fingerprint_for_file($file_meta) {
      return $file_meta["fingerprint"];
    }

    function log($log_level, $message, $options=array()) {
      if($log_level == 'error') {
        $message = "[WP] ".$message;
      }
      $params = array_merge(array("message" => $message, "log_level" => $log_level), $options);
      return $this->call_api("POST", "logs", $params);
    }


    function create_files($files) {
      $files_args = array( "files" => $files);
      return $this->call_api("POST", "files", $files_args);
    }


    // Restore
    function create_restore($backup_id) {
      $opts = array("backup_id" => $backup_id);
      $args = array_merge($this->get_platform_metadata(), $opts);
      return $this->call_api("POST", "restores", $args);
    }

    function complete_restore($restore_id) {
      return $this->call_api("POST", "restores/$restore_id/complete");
    }

    function cancel_restore($restore_id) {
      return $this->call_api("POST", "restores/$restore_id/cancel");
    }

    function get_restore($id) {
      $path = "restores/$id";
      return $this->call_api("GET", $path);
    }

    function get_restore_schemas($id) {
      $path = "restores/$id/schemas";
      return $this->call_api("GET", $path);
    }

    function get_restore_rows($id, $schema_fingerprint, $page) {
      $path = "restores/$id/rows";
      $args = array(
        "schema" => $schema_fingerprint, 
        "page" => $page
      );
      return $this->call_api("GET", $path, $args);
    }

    function swap_schema_operations($id) {
      $sqls = BitsUtil::get_all_table_sqls();
      $path = "restores/$id/schema_operations";

      $args = array("schemas" => $sqls);
      $result = $this->call_api("POST", $path, $args);
      if(is_wp_error($result)) {
        return $result;
      }
      return $result['operations'];
    }

    function restore_file_operations($id, $files) {
      $path = "restores/$id/file_operations";

      $file_details = array_map(array($this, "get_file_details"), $files);

      $result = $this->call_api("POST", $path, array("files" => $file_details));
      if(is_wp_error($result)) {
        return $result;
      }
      return $result['operations'];
    }
    function restore_missing_files($id, $files) {
      $path = "restores/$id/missing_files";

      $file_details = array_map(array($this, "get_file_details"), $files);

      $result = $this->call_api("POST", $path, array("files" => $file_details));

      return $result;
    }


    private function get_file_details($file) {
      if(!file_exists($file)) { 
        return new WP_Error("file-doesnt-exist", "Could not get details for '$file'");
      }
      $sha1 = sha1_file($file);
      return $this->metadata_for_file($file, $sha1);
    }

    function raw_call_http_retry($times, $method, $url, $data, $headers) {
      $retries = 1;
      $response = $this->raw_call_http($method, $url, $data, $headers);
      while(is_wp_error($response) && $retries < $times) {
        sleep(pow(2,$retries+1));
        $response = $this->raw_call_http($method, $url, $data, $headers);
        $retries += 1;
      }
      return $response;
    }
    function raw_call_http($method, $url, $data, $headers) {
      $request = null;
      if(isset($GLOBALS["BITS_DEBUG"]) && $GLOBALS["BITS_DEBUG"]) {
        echo("<pre><code>");
        echo "PATH: $method $url\n";
        if($headers) {
          //echo "HEADERS:";
          //var_dump($headers);
        }
        echo("</code></pre>");
        if($data) {
          echo "<div><a onclick='javascript:jQuery(this).parent().find(\".debug\").toggle();'> Data </a><div class='debug' style='display:none;'><pre><code>";
          var_dump($data);
          echo "</code></pre></div></div>";
        }
      }

      if(!isset($headers["Content-Type"]) || $headers["Content-Type"] == null) {
        $headers["Content-Type"] = "application/json";
        $headers["Accept"] = "application/json";
        $headers["Accept-Encoding"] = "gzip";
        if($method == "POST") {
          $data = $this->json($data);
        }
      }

      $timeout = 60*5;

      switch ($method)
      {
          case "POST":
            $request = bits_remote_post($url, array( "body" => $data, "compress" => true, "headers" => $headers, "timeout" => $timeout ));
            break;
          case "GET":
            if ($data)
              $url_with_params = sprintf("%s?%s", $url, http_build_query($data));
            else
              $url_with_params = $url;
            $request = bits_remote_get($url_with_params, array("headers" => $headers, "timeout" => $timeout ));
      }

      if(is_wp_error($request)) {
        if(substr($url, -4) == "logs") {
          //ignore, error when logging
        } else {
          $this->log("error", "Error when calling $method $url : " .$request->get_error_message());
        }
        return $request;
      }
      $response = wp_remote_retrieve_body($request);
      if($request["response"]["code"] != 200) {
        if(isset($GLOBALS["BITS_DEBUG"]) && $GLOBALS["BITS_DEBUG"]) {
          echo("<pre><code style='width:800px;'>");
          echo "Error communicating\n";
          var_dump($request);
          echo("\n\nBody:\n");
          var_dump($response);
          echo("</code></pre>");
        }

        $status = $request["response"]["code"];
        return new WP_Error("error-communicating-with-anybackup", "response $status from anybackup server call to $method $url");
      }
      if(isset($GLOBALS["BITS_DEBUG"]) && $GLOBALS["BITS_DEBUG"]) {
        echo "<div><a onclick='javascript:jQuery(this).parent().find(\".debug\").toggle();'> Response </a><div class='debug' style='display:none;'><pre><code>";
        var_dump(json_decode($response));
        echo "</code></pre></div></div>";
        // TODO: deal with error codes
      }
      return $response;

    }

    /**
     * Used internally
     * $method is either 'POST' or 'GET'
     * $data is an array("param" => "value") which will map to index.php?param=value
     * or in the case of a POST, will be added to the body.
     */
    function call_api($method, $path, $data = array(), $headers = array())
    {
      if($this->api_key == null && $path != "site_servers") {
        trigger_error("Error: API key is not initialized.  You must call set_api_key before calling api method($method $path).", E_USER_ERROR);
        return null;
      }
      if($this->api_key != null && !is_wp_error($this->api_key)) {
        $headers['Authorization']="Token ".$this->api_key;
      }
      $url = $this->get_server()."/v".self::API_VERSION."/".$path;
      $headers["PROCESS_ID"] = getmypid();

      $response = $this->raw_call_http_retry(3, $method, $url, $data, $headers);
      if(is_wp_error($response)) {
        return $response;
      }
      $json_obj = $this->json_decode($response);
      if($json_obj == null) {
        return new WP_Error('bad json response from anybackup server', $response);
      }
      $return_value = $this->recurse_translate_object_to_array($json_obj);
      if(isset($return_value['status']) && 
        $return_value['status'] && 
        $return_value["status"] != 200 && 
        $return_value["status"] != 302 &&
        $return_value["status"] != 403) {
        if(isset($return_value['error'])) {
          $message = $return_value['error'];
        } else {
          $message = "unknown-error-in-api";
        }
        return new WP_Error($message, "Error in API call to $method $path: $response");
      }
      return $return_value;
    }

    /**
     * Used internally
     */
    function recurse_translate_object_to_array($obj) {
      if($obj instanceOf stdClass) {
        $array = get_object_vars($obj);
        foreach($array as $key => $value) {
          $array[$key] = $this->recurse_translate_object_to_array($value);
        }
        return $array;
      } elseif (is_array($obj)) {
        return array_map(array($this, "recurse_translate_object_to_array"), $obj);
      }
      return $obj;
    }

    function download_url($url, $destination, $timeout=300) {
      # from https://core.trac.wordpress.org/browser/tags/4.2/src/wp-admin/includes/file.php#L0
      # download_url writes to a temporary file and then returns that file.
      #
      # TODO: all the checks they do
      $headers['Authorization']="Token ".$this->api_key;
      return bits_remote_get($url, array( 'timeout' => $timeout, 'stream' => true, "compress" => true, "decompress" => true, 'filename' => $destination, 'headers' => $headers ));
    }

  }
?>

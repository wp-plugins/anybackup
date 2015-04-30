<?php

function bits_microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function bits_http_connection() {
  if(!isset($GLOBALS["bits_http_connection"])) {
    $GLOBALS["bits_http_connection"] = new BITS_Persistent_Http();
  }
  return $GLOBALS["bits_http_connection"];
}

function bits_remote_get($url, $args) {
  #return wp_remote_get($url, $args);
  $defaults = array('method' => 'GET');
  $r = wp_parse_args( $args, $defaults );
  return bits_http_connection()->get_request($url, $r);
}

function bits_remote_post($url, $args) {
  #return wp_remote_post($url, $args);
  $defaults = array('method' => 'POST');
  $r = wp_parse_args( $args, $defaults );
  return bits_http_connection()->post_request($url, $r);
}

class BITS_Persistent_Http {
  var $get_engine = null;
  var $post_engine = null;
  var $curl_installed = null;
  function BITS_Persistent_Http() {
    $this->curl_installed=function_exists('curl_version');
    $this->get_engine = new BITS_Http_Curl();
    $this->post_engine = new BITS_Http_Curl();
  }
  function get_request( $url, $args ) {
    if(!$this->curl_installed) {
      return wp_remote_get($url, $args);
    }
    $response = $this->get_engine->request($url, $args);
    if ( is_wp_error( $response ) ) {
      return $response;
    }
    return apply_filters( 'http_response', $response, $args, $url );
  }
  function post_request( $url, $args ) {
    if(!$this->curl_installed) {
      return wp_remote_post($url, $args);
    }
    $response = $this->post_engine->request($url, $args);
    if ( is_wp_error( $response ) ) {
      return $response;
    }
    return apply_filters( 'http_response', $response, $args, $url );
  }
}
/**
 * HTTP request method uses Curl extension to retrieve the url.
 *
 * Requires the Curl extension to be installed.
 *
 * @package WordPress
 * @subpackage HTTP
 * @since 2.7.0
 */
class BITS_Http_Curl {
  /**
   * Temporary header storage for during requests.
   *
   * @since 3.2.0
   * @access private
   * @var string
   */
  private $headers = '';
  /**
   * Temporary body storage for during requests.
   *
   * @since 3.6.0
   * @access private
   * @var string
   */
  private $body = '';
  /**
   * The maximum amount of data to receive from the remote server.
   *
   * @since 3.6.0
   * @access private
   * @var int
   */
  private $max_body_length = false;
  /**
   * The file resource used for streaming to file.
   *
   * @since 3.6.0
   * @access private
   * @var resource
   */
  private $stream_handle = false;
  /**
   * The total bytes written in the current request.
   *
   * @since 4.1.0
   * @access private
   * @var int
   */
  private $bytes_written_total = 0;

  /**
   * the curl $handle is shared
   */
  private $handle = null;

  /**
   * Send a HTTP request to a URI using cURL extension.
   *
   * @access public
   * @since 2.7.0
   *
   * @param string $url The request URL.
   * @param string|array $args Optional. Override the defaults.
   * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'. A WP_Error instance upon error
   */
  public function request($url, $args = array()) {
    $before = bits_microtime_float();
    $defaults = array(
      'method' => 'GET', 'timeout' => 5,
      'redirection' => 5, 'httpversion' => '1.1', 'decompress' => true,
      'blocking' => true,
      'headers' => array(), 'body' => null, 'cookies' => array(), 'stream' => false
    );
    $r = wp_parse_args( $args, $defaults );
    if ( isset( $r['headers']['User-Agent'] ) ) {
      $r['user-agent'] = $r['headers']['User-Agent'];
      unset( $r['headers']['User-Agent'] );
    } elseif ( isset( $r['headers']['user-agent'] ) ) {
      $r['user-agent'] = $r['headers']['user-agent'];
      unset( $r['headers']['user-agent'] );
    }
    // Construct Cookie: header if any cookies are set.
    WP_Http::buildCookieHeader( $r );
    if($this->handle == null) {
      $this->handle = curl_init();
    }
    // cURL offers really easy proxy support.
    $proxy = new WP_HTTP_Proxy();
    if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) {
      curl_setopt( $this->handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );
      curl_setopt( $this->handle, CURLOPT_PROXY, $proxy->host() );
      curl_setopt( $this->handle, CURLOPT_PROXYPORT, $proxy->port() );
      if ( $proxy->use_authentication() ) {
        curl_setopt( $this->handle, CURLOPT_PROXYAUTH, CURLAUTH_ANY );
        curl_setopt( $this->handle, CURLOPT_PROXYUSERPWD, $proxy->authentication() );
      }
    }
    $is_local = isset($r['local']) && $r['local'];
    $ssl_verify = isset($r['sslverify']) && $r['sslverify'];
    if ( $is_local ) {
      /** This filter is documented in wp-includes/class-http.php */
      $ssl_verify = apply_filters( 'https_local_ssl_verify', $ssl_verify );
    } elseif ( ! $is_local ) {
      /** This filter is documented in wp-includes/class-http.php */
      $ssl_verify = apply_filters( 'https_ssl_verify', $ssl_verify );
    }
    /*
     * CURLOPT_TIMEOUT and CURLOPT_CONNECTTIMEOUT expect integers. Have to use ceil since.
     * a value of 0 will allow an unlimited timeout.
     */
    $timeout = (int) ceil( $r['timeout'] );
    curl_setopt( $this->handle, CURLOPT_URL, $url);
    curl_setopt( $this->handle, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt( $this->handle, CURLOPT_TIMEOUT, $timeout );
    curl_setopt( $this->handle, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $this->handle, CURLOPT_SSL_VERIFYHOST, ( $ssl_verify === true ) ? 2 : false );
    curl_setopt( $this->handle, CURLOPT_SSL_VERIFYPEER, $ssl_verify );
    #curl_setopt( $this->handle, CURLOPT_CAINFO, $r['sslcertificates'] );
    #curl_setopt( $this->handle, CURLOPT_USERAGENT, $r['user-agent'] );
    #curl_setopt( $this->handle, CURLOPT_VERBOSE, true );


    /*
     * The option doesn't work with safe mode or when open_basedir is set, and there's
     * a bug #17490 with redirected POST requests, so handle redirections outside Curl.
     */
    curl_setopt( $this->handle, CURLOPT_FOLLOWLOCATION, false );
    if ( defined( 'CURLOPT_PROTOCOLS' ) ) // PHP 5.2.10 / cURL 7.19.4
      curl_setopt( $this->handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS );
    $method = $r['method'];
    switch ( $r['method'] ) {
      case 'HEAD':
        curl_setopt( $this->handle, CURLOPT_NOBODY, true );
        break;
      case 'POST':
        curl_setopt( $this->handle, CURLOPT_POST, true );
        curl_setopt( $this->handle, CURLOPT_POSTFIELDS, $r['body'] );
        #curl_setopt( $this->handle, CURLOPT_VERBOSE, true );
        break;
      case 'PUT':
        curl_setopt( $this->handle, CURLOPT_CUSTOMREQUEST, 'PUT' );
        curl_setopt( $this->handle, CURLOPT_POSTFIELDS, $r['body'] );
        break;
      default:
        curl_setopt( $this->handle, CURLOPT_CUSTOMREQUEST, $r['method'] );
        if ( ! is_null( $r['body'] ) )
          curl_setopt( $this->handle, CURLOPT_POSTFIELDS, $r['body'] );
        break;
    }
    if ( true === $r['blocking'] ) {
      curl_setopt( $this->handle, CURLOPT_HEADERFUNCTION, array( $this, 'stream_headers' ) );
      curl_setopt( $this->handle, CURLOPT_WRITEFUNCTION, array( $this, 'stream_body' ) );
    }
    curl_setopt( $this->handle, CURLOPT_HEADER, false );
    if ( isset( $r['limit_response_size'] ) )
      $this->max_body_length = intval( $r['limit_response_size'] );
    else
      $this->max_body_length = false;

    // If streaming to a file open a file handle, and setup our curl streaming handler.
    if ( $r['stream'] ) {
      $filename = $r['filename'];
      if ( ! WP_DEBUG )
        $this->stream_handle = @fopen( $r['filename'], 'w+' );
      else
        $this->stream_handle = fopen( $r['filename'], 'w+' );
      if ( ! $this->stream_handle )
        return new WP_Error( 'http_request_failed', sprintf( __( 'Could not open handle for fopen() to %s' ), $r['filename'] ) );
    } else {
            $this->stream_handle = false;
    }

    if ( !empty( $r['headers'] ) ) {
      // cURL expects full header strings in each element.
      $headers = array();
      foreach ( $r['headers'] as $name => $value ) {
        $headers[] = "{$name}: $value";
      }
      curl_setopt( $this->handle, CURLOPT_HTTPHEADER, $headers );
    }
    if ( $r['httpversion'] == '1.0' )
      curl_setopt( $this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
    else
      curl_setopt( $this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
    // We don't need to return the body, so don't. Just execute request and return.
    if ( ! $r['blocking'] ) {
      curl_exec( $this->handle );
      if ( $curl_error = curl_error( $this->handle ) ) {
        return new WP_Error( 'http_request_failed', $curl_error );
      }
      if ( in_array( curl_getinfo( $this->handle, CURLINFO_HTTP_CODE ), array( 301, 302 ) ) ) {
        return new WP_Error( 'http_request_failed', __( 'Too many redirects.' ) );
      }
      return array( 'headers' => array(), 'body' => '', 'response' => array('code' => false, 'message' => false), 'cookies' => array() );
    }
    curl_exec( $this->handle );
    $theHeaders = WP_Http::processHeaders( $this->headers, $url );
    $theBody = $this->body;
    $bytes_written_total = $this->bytes_written_total;
    $this->headers = '';
    $this->body = '';
    $this->bytes_written_total = 0;
    $curl_error = curl_errno( $this->handle );
    // If an error occurred, or, no response.
    if ( $curl_error || ( 0 == strlen( $theBody ) && empty( $theHeaders['headers'] ) ) ) {
      if ( CURLE_WRITE_ERROR /* 23 */ == $curl_error ) {
        if ( ! $this->max_body_length || $this->max_body_length != $bytes_written_total ) {
          if ( $r['stream'] ) {
            fclose( $this->stream_handle );
            return new WP_Error( 'http_request_failed', __( 'Failed to write request to temporary file.' ) );
          } else {
            return new WP_Error( 'http_request_failed', curl_error( $this->handle ) );
          }
        }
      } else {
        if ( $curl_error = curl_error( $this->handle ) ) {
          return new WP_Error( 'http_request_failed', $curl_error );
        }
      }
      if ( in_array( curl_getinfo( $this->handle, CURLINFO_HTTP_CODE ), array( 301, 302 ) ) ) {
        return new WP_Error( 'http_request_failed', __( 'Too many redirects.' ) );
      }
    }

    if ( $r['stream'] )
      fclose( $this->stream_handle );

    $response = array(
      'headers' => $theHeaders['headers'],
      'body' => null,
      'response' => $theHeaders['response'],
      'cookies' => $theHeaders['cookies']
    );
    // Handle redirects.
    if ( false !== ( $redirect_response = WP_HTTP::handle_redirects( $url, $r, $response ) ) )
      return $redirect_response;
    if ( true === $r['decompress'] && true === WP_Http_Encoding::should_decode($theHeaders['headers']) )
      $theBody = WP_Http_Encoding::decompress( $theBody );
    $response['body'] = $theBody;
    $time_taken = bits_microtime_float()-$before;

    #error_log("$url took $time_taken");
    return $response;
  }
  /**
   * Grab the headers of the cURL request
   *
   * Each header is sent individually to this callback, so we append to the $header property for temporary storage
   *
   * @since 3.2.0
   * @access private
   * @return int
   */
  private function stream_headers( $handle, $headers ) {
    $this->headers .= $headers;
    return strlen( $headers );
  }
  /**
   * Grab the body of the cURL request
   *
   * The contents of the document are passed in chunks, so we append to the $body property for temporary storage.
   * Returning a length shorter than the length of $data passed in will cause cURL to abort the request with CURLE_WRITE_ERROR
   *
   * @since 3.6.0
   * @access private
   * @return int
   */
  private function stream_body( $handle, $data ) {
    $data_length = strlen( $data );
    if ( $this->max_body_length && ( $this->bytes_written_total + $data_length ) > $this->max_body_length ) {
      $data_length = ( $this->max_body_length - $this->bytes_written_total );
      $data = substr( $data, 0, $data_length );
    }
    if ( $this->stream_handle ) {
      $bytes_written = fwrite( $this->stream_handle, $data );
    } else {
      $this->body .= $data;
      $bytes_written = $data_length;
    }
    $this->bytes_written_total += $bytes_written;
    // Upon event of this function returning less than strlen( $data ) curl will error with CURLE_WRITE_ERROR.
    return $bytes_written;
  }
  /**
   * Whether this class can be used for retrieving an URL.
   *
   * @static
   * @since 2.7.0
   *
   * @return boolean False means this class can not be used, true means it can.
   */
  public static function test( $args = array() ) {
    if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) )
      return false;
    $is_ssl = isset( $args['ssl'] ) && $args['ssl'];
    if ( $is_ssl ) {
      $curl_version = curl_version();
      // Check whether this cURL version support SSL requests.
      if ( ! (CURL_VERSION_SSL & $curl_version['features']) )
        return false;
    }
    /**
     * Filter whether cURL can be used as a transport for retrieving a URL.
     *
     * @since 2.7.0
     *
     * @param bool  $use_class Whether the class can be used. Default true.
     * @param array $args      An array of request arguments.
     */
    return apply_filters( 'use_curl_transport', true, $args );
  }
}
?>

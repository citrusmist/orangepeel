<?php

/**
 * Holds the parameters request has been invoked with
 */
class PL_Params implements ArrayAccess {
	
	private $container = array();

	function __construct( array $params ) {
    $this->container = array_merge( $params, $this->http_params() );
	}

  protected function http_params() {
    
    //Borrowed from http://stackoverflow.com/a/5932067
    $http_params = array();
    $method      = $_SERVER['REQUEST_METHOD'];

    if( $method == "PUT" || $method == "DELETE" ) {
      parse_str( file_get_contents('php://input' ), $http_params );
      $GLOBALS["_{$method}"] = $http_params;

      // Add these request vars into _REQUEST, mimicing default behavior, 
      //PUT/DELETE will override existing COOKIE/GET vars
      $_REQUEST = $http_params + $_REQUEST;
    } else if( $method == "GET" ) {
      $http_params = $_GET;
    } else if( $method == "POST" ) {
      $http_params = $_POST;
    }

    return $http_params;
  }

  public function set_defaults( array $defaults ) {

    foreach( $defaults as $key => $value ) {
      if( empty( $this->container[$key] ) ) {
        $this->container[$key] = $value;
      }  
    }
  }

  public function offsetSet($offset, $value) {
   
    if ( is_null( $offset ) ) {
      $this->container[] = $value;
    } else {
      $this->container[$offset] = $value;
    }
  }

  public function offsetExists( $offset ) {
    return isset( $this->container[$offset] );
  }

  public function offsetUnset( $offset ) {
    unset( $this->container[$offset] );
  }

  public function offsetGet( $offset ) {
    return isset( $this->container[$offset] ) ? $this->container[$offset] : null;
  }
}
<?php

/**
 * Holds the parameters request has been invoked with
 */
class PL_Params implements ArrayAccess {
	
	private $container = array();

	function __construct( array $params ) {
		$this->container = $params;
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
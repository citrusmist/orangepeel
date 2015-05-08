<?php 

/**
 * 
 */
abstract class PL_Registry {
 	abstract protected function get( $key );
  abstract protected function set( $key, $val );
}
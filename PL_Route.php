<?php

/**
 * 
 */
class PL_Route {
	
	protected $route;

	protected $plugin;

	protected $method;

	protected $rewrite;

	protected $controller;

	protected $action;

	protected $cpt;

	function __construct( $route, $args ) {

		$this->route = $route;

		foreach( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function __get( $prop ) {
		
		if( property_exists( $this, $prop ) ) {
			return $this->prop;
		}
	}

	public function __set( $prop, $val ) {
		
		$method = 'set_' . $prop;

		if( method_exists( $this, $method ) ) {
			$this->$method( $val );
		} else {
			$this->$prop = $val;
		}
	}

	public function calc_rewrite_rule() {
		# code...
	}
}
<?php

/**
 * 
 */
abstract class PL_Route {
	
	protected $route;

	protected $plugin;

	protected $method;

	protected $rewrite;

	protected $controller;

	protected $action;

	protected $cpt;

	protected $defaults;

	function __construct( $route, $args, $parser ) {

		$this->route  = $route;
		$this->parser = $parser;

		foreach( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function __get( $prop ) {
		
		if( property_exists( $this, $prop ) ) {
			return $this->$prop;
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

	public function parse() {
		return $this->parser->parse( $this );
	}

	abstract public function calc_rewrite();
}
<?php

class PL_Route {

	protected static $instance;

	protected $endpoints = array();
	protected $cpts      = array();
	
	private function __construct() {
		$this->register_callbacks();
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
      self::$instance = new self();
      //README should this be in the constructor
      self::$instance->register_callbacks();
    }

    return self::$instance;
	}


	protected function register_callbacks() {
		//README Kind of feel that maybe everything should be static
		//and it should be another class's role to register endpoints
		add_action( 'init', array( $this, 'register_endpoints' ) );
	}

	public function resource( $name ) {
		$endpoints[] = $name;
	}


	public function get( $name ) {
		$endpoints[] = $name;
	}

	public function post( $name ) {
		$endpoints[] = $name;
	}
	
	public function cpt( $name ) {
		$cpts[] = $name;
	}

	public function register_endpoints() {
		foreach ( $this->endpoints as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT );
		}
	}

	public function resolve( $query ) {
		
	}


}
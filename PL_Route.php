<?php

/**
 * Class responsiple for keeping keeping track of routes between
 * enpoints, CPT rewrites and controller actionts.
 *
 * README: A class containing all static methods could be a better design
 * as it would remove the need for a constructor which registers callbacks.
 * On the other hand it would prevent injection of the PL_Route object.
 */

class PL_Route {

	protected static $instance;

	protected $endpoints = array();
	protected $cpts      = array();
	protected $current;
	
	private function __construct() {
		$this->register_callbacks();
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
      self::$instance = new self();
    }

    return self::$instance;
	}


	protected function register_callbacks() {
		//README Kind of feel that maybe everything should be static
		//and it should be another class's role to register endpoints
		add_action( 'init', array( $this, 'register_endpoints' ) );
	}

	public function resource( $name ) {
		$this->endpoints[$name] = array();
	}


	public function get( $name ) {
		$this->endpoints[$name] = array();
	}

	public function post( $name ) {
		$this->endpoints[$name] = array();
	}
	
	public function cpt( $name, $action ) {
		$this->cpts[$name] = $action;
	}

	public function register_endpoints() {
		foreach ( $this->endpoints as $endpoint => $val ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT );
		}
	}

	public function resolve( $query ) {

		if( !empty( $query->query_vars['post_type'] ) 
			&& $query->query_vars['post_type'] 
			&& array_key_exists( $query->query_vars['post_type'], $this->cpts ) ) {
			log_me($query->query_vars);

			$this->current = array( $query->query_vars['post_type'], $this->cpts[$query->query_vars['post_type']] );

		} else {

			$params = array_intersect_key( $query->query_vars, $this->endpoints );

			if( !empty( $params ) ) {
				$this->current = $params;
			}
		}

		if( empty( $this->current ) ) {
			return false;
		} else {
			return $this->current;
		}
	}

}
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

	public function resource( $name, $action, $plugin ) {
		$this->endpoints[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin
		);
	}

	public function get( $name, $action, $plugin ) {
		$this->endpoints[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin
		);
	}

	public function post( $name, $action, $plugin ) {
		$this->endpoints[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin
		);
	}
	
	public function cpt( $name, $action, $plugin ) {
		$this->cpts[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin
		);
	}

	public function register_endpoints() {
		foreach ( $this->endpoints as $endpoint => $val ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT );
		}
	}

	public function resolve( $query ) {
		log_me($query->query_vars);

		if( !empty( $query->query_vars['post_type'] ) ) {
			
			if( array_key_exists( $query->query_vars['post_type'], $this->cpts ) ) {
			 	$path = $query->query_vars['post_type'];
			} elseif( array_key_exists( \PL_Inflector::pluralize( $query->query_vars['post_type'] ), $this->cpts ) ) {
				$path = \PL_Inflector::pluralize( $query->query_vars['post_type'] );
			}

			$this->current = array_merge( 
				array( 'path' => $path ),
				$this->cpts[$path] 
			);

		} else {

			$endpoint = array_intersect_key( $this->endpoints, $query->query_vars );

			if( !empty( $endpoint ) ) {
				
				$action   = array_values( $endpoint );
				$endpoint = array_keys( $endpoint );

				$this->current = array_merge(
					array( 'path' => $endpoint[0] ),
					$action[0]
				);
			}
		}

		if( empty( $this->current ) ) {
			return false;
		} else {
			return $this->current;
		}
	}

	public function get_current() {
		return $this->current;
	}
}
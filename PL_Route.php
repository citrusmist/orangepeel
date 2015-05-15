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
		//README Kind of feel that maybe everything shtould be static
		//and it should be another class's role to register endpoints
		add_action( 'init', array( $this, 'register_endpoints' ) );
	}

	public function resource( $name, $controller, $plugin ) {
		$this->endpoints[\PL_Inflector::pluralize( $name )] = array(
			'action'  => $controller . '#index',
			'plugin'  => $plugin,
			'method'  => 'GET',
			'rewrite' => $this->calc_rewrite_rule( \PL_Inflector::pluralize( $name ), $controller . '#index' )
		);	

		$this->endpoints[$name . '/{:id}'] = array(
			'action'  => $controller . '#show',
			'plugin'  => $plugin,
			'method'  => 'GET',
			'rewrite' => $this->calc_rewrite_rule( $name . '/{:id}', $controller . '#show' )
		);
	}

	public function get( $name, $action, $plugin ) {
		$this->endpoints[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin,
			'method'  => 'GET'
		);
	}

	public function post( $name, $action, $plugin ) {
		$this->endpoints[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin,
			'method'  => 'POST'
		);
	}
	
	public function cpt( $name, $action, $plugin ) {
		$this->cpts[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin,
			'method'  => 'GET'
		);
	}

	public function register_endpoints() {

		foreach ( $this->endpoints as $endpoint ) {
			add_rewrite_rule( 
				$endpoint['rewrite']['rule'], 
				$endpoint['rewrite']['redirect'], 
				'top' 
			);
		}
	}

	public function calc_rewrite_rule( $route, $action ) {
		
		$redirect = 'index.php?controllerAction=' . $action; 
		$rule     = $route;

		if( strpos( $rule, '{:id}' ) !== false ) {
			$rule      = str_replace( '{:id}', '([0-9]{1,})', $rule );
			$redirect .= '&id=$matches[1]';
		}

		return array (
			'rule'     => $rule . '/?$',
			'redirect' => $redirect,
		);
	}

	public function resolve( $wp ) {
		// log_me($wp);

		global $wp_rewrite;

		$endpoints = array_filter( $this->endpoints, function( $v ) {
				return $v['method'] == $_SERVER['REQUEST_METHOD'];
			}
		);

		$cpts = array_filter( $this->cpts, function( $v ) {
				return $v['method'] == $_SERVER['REQUEST_METHOD'];
			}
		);

		$possibilities = array_merge( $endpoints, $cpts );
		$matched_route = false;
		
		foreach( $possibilities as $route => $props ) {
			if ( $props['rewrite']['rule'] == $wp->matched_rule ) {
				$matched_route = $possibilities[$route];
			}
		}	

		if( $matched_route != false ) {
			$this->current = $matched_route;
		} 
		
		return $matched_route;

		//match request to either a built-in resource 
		//or a peel one

		//extract the parameters passed
		//merge with any _GET or _POST parameters

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
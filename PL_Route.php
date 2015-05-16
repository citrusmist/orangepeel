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
	
	public function cpt_builtin( $name, $action, $qv, $plugin ) {

		//README CPT should be registered automatically based on paramteres 
		//the cpt is registred with e.g. publicly_queriable etc.
		//This probably means that this func should maybe be quite diff and 
		//cpt_resource should be used to add actions such as create, new, edit, update

		$this->cpts[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin,
			'qv'      => $qv,
			'method'  => 'GET',
			'rewrite' => '_builtin'
		);
	}

	public function cpt_get( $route, $action, $qv, $plugin ) {
		
		$this->cpts[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin,
			'qv'      => $qv,
			'method'  => 'GET',
			'rewrite' => $this->calc_cpt_rewrite_rule( $route, $action )
		);
	}

	public function cpt_resource( $name, $controller, $plugin ) {
		# code...
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

		if( strpos( $rule, '{:slug}' ) !== false ) {
			$rule      = str_replace( '{:slug}', '([^/]+)', $rule );
			$redirect .= '&id=$matches[1]';
		}

		return array (
			'rule'     => $rule . '/?$',
			'redirect' => $redirect,
		);
	}

	public function calc_cpt_rewrite_rule( $route, $action ) {
	}

	public function resolve( $wp ) {
		log_me($wp);
		$matched_route = false;

		if( empty( $wp->query_vars ) ) {
			$mathced_route = $this->resolve_custom( $wp );
		} else {
			$matched_route = $this->resolve_cpt( $wp );
		}

		if( !empty( $matched_route ) ) {
			$this->current = $matched_route;
		}

		log_me( $matched_route );

		return $matched_route;
	}

	protected function resolve_cpt( $wp ) {
		
		$matched_route = false;

		log_me( __METHOD__ );
		
		//exclude routes where a cpt or request method don't match query_vars
		$possibilities = array_filter( 
			$this->cpts, 
			function( $val ) use( &$wp ) {
				
				$possibility = isset( $wp->query_vars['post_type'] )
					&& $val['qv']['post_type'] == $wp->query_vars['post_type'] 
					&& $val['method'] == $_SERVER['REQUEST_METHOD'];

				return $possibility;
			}
		);

		foreach( $possibilities as $route => $props ) {
			
			$old = array();
			$new = array_intersect_key( $props['qv'], $wp->query_vars );

			if( $matched_route ) {
				$old = array_intersect_key( $props['qv'], $wp->query_vars );
			}

			if( count( $new ) > count( $old ) ) {
				$matched_route = $possibilities[$route];
			} 
		}

		return $matched_route;
	}

	protected function resolve_custom( $wp ) {
		
		$matched_route = false;

		$possibilities = array_filter( $this->endpoints, function( $val ) {
				return $val['method'] == $_SERVER['REQUEST_METHOD'];
			}
		);

		foreach( $possibilities as $route => $props ) {
			if ( $props['rewrite']['rule'] == $wp->matched_rule ) {
				$matched_route = $possibilities[$route];
			}
		}	

		return $matched_route;
	}

	public function get_current() {
		return $this->current;
	}
}
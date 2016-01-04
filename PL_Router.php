<?php

/**
 * Class responsiple for keeping keeping track of routes between
 * enpoints, CPT rewrites and controller actionts.
 *
 * README: A class containing all static methods could be a better design
 * as it would remove the need for a constructor which registers callbacks.
 * On the other hand it would prevent injection of the PL_Router object.
 */

class PL_Router {

	protected static $instance = null;

	protected $routes     = array();
	
	protected $resource   = array(); 
	protected $get        = array(); 
	protected $post       = array(); 
	protected $cpts       = array();
	protected $current;

	protected $factory;
	
	private function __construct() {
		$this->register_callbacks();
		$this->factory = new PL_Route_Factory;
	}

	public static function get_instance() {
		if ( self::$instance === null ) {
      self::$instance = new self();
    }

    return self::$instance;
	}

	protected function register_callbacks() {
		//README Kind of feel that maybe everything shtould be static
		//and it should be another class's role to register endpoints

		/**
		 * Routes for registered CPTs must be calculated in calc_cpt_routes before 
		 * all routes are registered in the system. calc_cpt_routes must be called after
		 * all the CPT have been registered in order to retriece CPT objects
		 */
		add_action( 'init', array( $this, 'calc_routes' ), 90 );
		add_action( 'init', array( $this, 'register_routes' ), 100 );
	}

	public function calc_routes() {
		
		foreach( $this->resource as $name => $params ) {
			$this->routes = array_merge( 
				$this->routes,
				$this->factory->resource( $name, $params['args'] ) 
			);
		}

		foreach( $this->get as $route => $args ) {
			$this->routes[] = $this->factory->get( $route, $args );
		}
		
		foreach( $this->post as $route => $args ) {
			$this->routes[] = $this->factory->post( $route, $args );
		}
	}


	public function register_routes() {

		foreach( $this->routes as $route ) {

			if( $route->rewrite == "_builtin" ) {
				continue;
			}

			add_rewrite_rule( 
				$route->rewrite['rule'], 
				$route->rewrite['redirect'], 
				'top' 
			);
		}
	}

	public function resource( $name, $args, $plugin ) {

		$defaults = array(
			'plugin' => $plugin
		);

		if( is_string( $args ) ) {
			$args = array( 'controller' => $args );
		}

		$args = wp_parse_args( $args, $defaults );

		if( ! isset( $args['controller'] ) ) {
			error_log( $name . ' resource hasn\'t been assigned a controller' );
			return;
		}

		$this->resource[$name] = array( 
			'args'    => $args,
		);
	}

	public function get( $name, $args, $plugin ) {

		$defaults = array(
			'cpt'    => false,
			'plugin' => $plugin
		);

		$args = $this->parse_ctrl_action( $name, $args );

		if ( empty( $args['controller'] ) || empty( $args['action'] ) ) {
			error_log( "Invalid controller#action supplied for route {$name}" );
			return;
		}

		$args = wp_parse_args( $args, $defaults );

		$this->get[$name] = $args;
	}

	public function post( $name, $args, $plugin ) {

		$defaults = array(
			'cpt'    => false,
			'plugin' => $plugin
		);

		$args = $this->parse_ctrl_action( $name, $args );

		if ( empty( $args['controller'] ) || empty( $args['action'] ) ) {
			error_log( "Invalid controller#action supplied for route {$name}" );
			return;
		}

		$args = wp_parse_args( $args, $defaults );

		$this->post[$name] = $args;
	}

	protected function parse_ctrl_action( $name, $args ) {

		$ctrlAction = array();

		if( is_string( $args ) && stripos( $args, '#' ) !== false ) {
			$ctrlAction = explode( '#', $args );

			$ctrlAction = array( 
				'controller' => $ctrlAction[0],
				'action'     => $ctrlAction[1]
			);
		} elseif ( is_string( $args ) ) {
			//if action name isn't specified it is inferred from route name
			
			$ctrlAction = array( 
				'controller' => $args,
				'action'     => $name
			);
		} elseif ( is_array( $args ) && !empty( $args['controller'] ) ) {

			if( empty( $args['action'] ) ) {
				$args['action'] = $name;
			} 

			$ctrlAction = $args;
		}

		return $ctrlAction;
	}

	public function resolve( $wp ) {
		// log_me($wp);
		$matched_route = false;

		if( empty( $wp->query_vars ) ) {
			$matched_route = $this->resolve_custom( $wp );
		} else {
			$matched_route = $this->resolve_cpt( $wp );
		}

		if( !empty( $matched_route ) ) {
			$this->current = $matched_route;
		}

		// log_me( __METHOD__ );
		// log_me( $matched_route );

		return $matched_route;
	}

	protected function resolve_cpt( $wp ) {
		
		$matched_route = false;

		//exclude routes where a cpt or request method don't match query_vars
		$possibilities = array_filter( 
			$this->routes, 
			function( $route ) use( &$wp ) {
				
				$is_possible = isset( $wp->query_vars['post_type'] )
					&& $route->cpt == $wp->query_vars['post_type'] 
					&& $route->method == $_SERVER['REQUEST_METHOD'];

				return $is_possible;
			}
		);
		
		/*
		 * First check if there is a CPT route which has a rewrite rule 
		 * that exactly matches the the rule just completed in the request
		 * This prevents false positives that might happend when checking
		 * for presence to query vars to determine a builtin route
		 */
		$possibilities = array_filter( 
			$possibilities, 
			function( $route ) use( &$wp, &$matched_route ) {

				if( $route->rewrite['rule'] == $wp->matched_rule && $matched_route === false ) {
					$matched_route = $route;
					return false;
				}

				if ( in_array( $route->action, array( 'index', 'show' ) ) ) {
					return true;
				}
			}
		);

		if( $matched_route === false ) {

			foreach( $possibilities as $route ) {
				
				if( $route->action == 'show' && !empty( $wp->query_vars['name'] ) ) {
					$matched_route = $route;
					break;
				}

				if( $route->action == 'index' && empty( $wp->query_vars['name'] ) ) { 
					$matched_route = $route;
					break;
				}
			}
		}

		return $matched_route;
	}

	protected function resolve_custom( $wp ) {
		
		$matched_route = false;

		$possibilities = array_filter( $this->routes, function( $route ) {
				return $route->method == $_SERVER['REQUEST_METHOD']
					&& $route->cpt == false;
			}
		);

		foreach( $possibilities as $route ) {
			if ( $route->rewrite['rule'] == $wp->matched_rule ) {
				$matched_route = $route;
			}
		}	

		return $matched_route;
	}

	public function get_current() {
		return $this->current;
	}
}
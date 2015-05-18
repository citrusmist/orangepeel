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

	protected $endpoints  = array();
	protected $cpts       = array();
	protected $cpt_routes = array();
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

		/**
		 * Routes for registered CPTs must be calculated in calc_cpt_routes before 
		 * all routes are registered in the system. calc_cpt_routes must be called after
		 * all the CPT have been registered in order to retriece CPT objects
		 */
		add_action( 'init', array( $this, 'calc_cpts_routes' ), 90 );
		add_action( 'init', array( $this, 'register_routes' ), 100 );
	}

	public function calc_cpts_routes() {

		foreach( $this->cpts as $cpt => $val ) {

			$cpt_obj = get_post_type_object( $cpt );

			//In the post type object `rewrite` property is always computed and  
			//has the slug key however the `has_archive` can be a boolean or a string. 
			//In case it's true has_archive == rewrite['slug']
			$index_rewrite = $cpt_obj->has_archive === true ? $cpt_obj->rewrite['slug'] : $cpt_obj->has_archive;

			$this->cpt_routes[$index_rewrite . '/:name'] = array(
				'action'    => is_array( $val['actions'] ) ? $val['actions']['show'] : $val['actions'] . '#show',
				'plugin'    => $val['plugin'],
				'method'    => 'GET',
				'rewrite'   => '_builtin#show',
				'qv'        => array( 
					'post_type' => $cpt,
					'name'      => '' 
				)
			);

			if( $index_rewrite !== false ) {
				$this->cpt_routes[$index_rewrite] = array(
					'action'    => is_array( $val['actions'] ) ? $val['actions']['index'] : $val['actions'] . '#index',
					'plugin'    => $val['plugin'],
					'method'    => 'GET',
					'rewrite'   => '_builtin#index',
					'qv'        => array( 
						'post_type' => $cpt,
					)
				);	
			}

			if( $val['type'] == 'resource' ) {
				
				$rewrite = $this->calc_cpt_rewrite_rule(
					$cpt,
					$index_rewrite . '/:name/edit'
				);

				$this->cpt_routes[$index_rewrite . '/:name/edit'] = array(
					'action'    => $val['actions'] . '#edit',
					'plugin'    => $val['plugin'],
					'method'    => 'GET',
					'rewrite'   => $rewrite,
					'qv'        => array( 
						'post_type' => $cpt,
					)
				);
			}
		}
	}

	public function register_routes() {

		foreach( $this->endpoints as $endpoint ) {
			add_rewrite_rule( 
				$endpoint['rewrite']['rule'], 
				$endpoint['rewrite']['redirect'], 
				'top' 
			);
		}

		foreach( $this->cpt_routes as $endpoint ) {

			if( is_array($endpoint['rewrite']) ) {
				add_rewrite_rule( 
					$endpoint['rewrite']['rule'], 
					$endpoint['rewrite']['redirect'], 
					'top' 
				);
			}
		}
	}

	public function resource( $name, $controller, $plugin ) {
		$this->endpoints[\PL_Inflector::pluralize( $name )] = array(
			'action'  => $controller . '#index',
			'plugin'  => $plugin,
			'method'  => 'GET',
			'rewrite' => $this->calc_rewrite_rule( \PL_Inflector::pluralize( $name ), $controller . '#index' )
		);	

		$this->endpoints[$name . '/:id'] = array(
			'action'  => $controller . '#show',
			'plugin'  => $plugin,
			'method'  => 'GET',
			'rewrite' => $this->calc_rewrite_rule( $name . '/:id', $controller . '#show' )
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
	
	public function cpt_builtin( $cpt, $actions, $plugin ) {

		//README CPT should be registered automatically based on paramteres 
		//the cpt is registred with e.g. publicly_queriable etc.
		//This probably means that this func should maybe be quite diff and 
		//cpt_resource should be used to add actions such as create, new, edit, update

		/*$this->cpts[$name] = array(
			'action'  => $action,
			'plugin'  => $plugin,
			'qv'      => $qv,
			'method'  => 'GET',
			'rewrite' => '_builtin'
		);*/

		/*if( is_string( $actions ) ) {
			$actions = array(
				'index' => $actions . '#index',
				'show'  => $actions . '#show',
			);
		}

		$this->cpt_get( $cpt, '{:cpt_rewrite}', $actions['index'], $plugin );
		$this->cpt_get( $cpt, '{:cpt_rewrite}/{:slug}', $actions['show'], $plugin );*/
		$this->cpts[$cpt] = array( 
			'actions' => $actions, 
			'type'    => 'builtin', 
			'plugin'  => $plugin 
		);
	}

	public function cpt_resource( $cpt, $controller, $plugin ) {
		$this->cpts[$cpt] = array( 
			'actions' => $controller, 
			'type'    => 'resource', 
			'plugin'  => $plugin 
		);
	}

	public function cpt_get( $cpt, $route, $action, $plugin ) {
		
		$this->cpt_routes[$route] = array(
			'action'    => $action,
			'plugin'    => $plugin,
			'method'    => 'GET',
			'post_type' => $cpt
		);
	}


	public function calc_rewrite_rule( $route, $action ) {
		
		$redirect = 'index.php?controllerAction=' . $action; 
		$rule     = $route;

		if( strpos( $rule, ':id' ) !== false ) {
			$rule      = str_replace( ':id', '([0-9]{1,})', $rule );
			$redirect .= '&id=$matches[1]';
		}

		if( strpos( $rule, ':slug' ) !== false ) {
			$rule      = str_replace( ':slug', '([^/]+)', $rule );
			$redirect .= '&id=$matches[1]';
		}

		return array (
			'rule'     => $rule . '/?$',
			'redirect' => $redirect,
		);
	}

	public function calc_cpt_rewrite_rule( $cpt, $route, $args = array() ) {
		
		$redirect = 'index.php?post_type=' . $cpt; 
		$rule     = $route;

		/*foreach( $args as $key => $value ) {
			$redirect .= build_query( array( $key => $value['default'] ) );
		}

		if( strpos( $rule, '{:id}' ) !== false ) {
			$rule      = str_replace( '{:id}', '([0-9]{1,})', $rule );
			$redirect .= '&id=$matches[1]';
		}

		if( strpos( $rule, '{:slug}' ) !== false ) {
			$rule      = str_replace( '{:slug}', '([^/]+)', $rule );
			$redirect .= '&id=$matches[1]';
		}*/

		$count = 1;
		$rule = preg_replace_callback(
			'/:([^\/]+)(\/)?/', 
			function( $matches ) use ( &$redirect, &$args, &$count ) {

				for( $i = 1; $i < count( $matches ); $i++ ) { 
					
					if( empty( $matches[$i] ) || $matches[$i] == '/' ) {
						continue;
					}

					$redirect .= '&' . $matches[$i] . '=$matches[' . $count . ']';
					$count++;
				}

				return empty( $matches[2] ) ? '([^\/]+)' : '([^\/]+)' . $matches[2];
			}, 
			$rule
		);

		return array (
			'rule'     => $rule . '/?$',
			'redirect' => $redirect,
		);

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
			$this->cpt_routes, 
			function( $val ) use( &$wp ) {
				
				$is_possible = isset( $wp->query_vars['post_type'] )
					&& $val['qv']['post_type'] == $wp->query_vars['post_type'] 
					&& $val['method'] == $_SERVER['REQUEST_METHOD'];

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
			function( $props ) use( &$wp, &$matched_route ) {
			
				if( is_array( $props['rewrite'] ) ) {
					if( $props['rewrite']['rule'] == $wp->matched_rule && $matched_route === false ) {
						$matched_route = $props;
					}

					return false;
				}

				return true;	
			}
		);

		if( $matched_route === false ) {

			foreach( $possibilities as $route => $props ) {
				
				if( $props['rewrite'] == '_builtin#show' && !empty( $wp->query_vars['name'] ) ) {
					$matched_route = $props;
					break;
				}

				if( $props['rewrite'] == '_builtin#index' && empty( $wp->query_vars['name'] ) ) { 
					$matched_route = $props;
					break;
				}
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
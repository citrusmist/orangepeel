<?php

/**
* 
*/
class PL_Route_Factory {
	
	protected $resource_actions;
	protected $cpt_resource_actions;

	function __construct() {
		$this->resource_actions = array(
			'index'  => array( 'path' => '',          'method' => 'get' ),
			'create' => array( 'path' => '',          'method' => 'post' ),
			'new'    => array( 'path' => '/new',      'method' => 'get' ),
			'show'   => array( 'path' => '/:id',      'method' => 'get' ),
			'edit'   => array( 'path' => '/:id/edit', 'method' => 'get' ),
		);
	}

	public function resource( $name, $args ) {
		
		$defaults = array( 
			'type'        => 'custom',
			'cpt'					=> '',
			'only'        => array(),
			'defaults'    => array(),
			'paths'       => array(),
			'constraints' => array(),
			'params'      => array(),
		);
		$routes  = array();
		$args    = wp_parse_args( $args, $defaults );

		if( ! empty( $args['only'] ) ) {
			$actions = array_intersect_key( $this->resource_actions, array_flip( $args['only'] ) );
		} else {
			$actions = $this->resource_actions;
		}

		//Ensure CPT type is always in UPPERCASE
		if( strtolower( $args['type'] ) == 'cpt' ) {
			$args['type']   = strtoupper( $args['type'] );
			$args['cpt']    = $name;
			$args['params'] = array( 'id' => 'name' );
		}
		$actions = $this->action_paths( $name, $actions, $args['type'] );

		unset( $args['only'] );
		unset( $args['paths'] );

		foreach( $actions as $action => $props ) {
			$route_args = array_merge( $args, array( 'action' => $action ) );
			$routes[] = $this->$props['method']( $props['path'], $route_args );
		}

		return $routes;
	}

	public function action_paths( $name, $actions, $type ) {
		
		if( $type === 'CPT' ) {
			$actions = $this->cpt_action_paths( $name, $actions );
		} else {
			foreach( $actions as $action => $props ) {
				$actions[$action]['path'] = $name . $props['path'];
			}	
		}

		return $actions;
	}

	public function cpt_action_paths( $name, $actions ) {
		
		$cpt_obj = get_post_type_object( $name );

		//In the post type object `rewrite` property is always computed and  
		//has the slug key however the `has_archive` can be a boolean or a string. 
		//In case it's true has_archive == rewrite['slug']
		$index = $cpt_obj->has_archive === true ? $cpt_obj->rewrite['slug'] : $cpt_obj->has_archive;
		$slug  = $cpt_obj->rewrite['slug'];

		foreach( $actions as $action => $props ) {
			if( $action == 'index' ) { 
				$actions[$action]['path'] =  $index . $props['path'];
			}  else {
				$actions[$action]['path'] =  $slug . $props['path'];
			}
		}

		return $actions;
	}

	public function get( $route, $args ) {
		$args['method'] = 'GET';
		$class          = 'PL_Route_' . $args['type'];
		$parser         =  new \PL_Route_Parser( array(
			'constraints' => $args['constraints'],
			'params'      => $args['params']
		) );
		
		unset( $args['type'] );
		unset( $args['constraints'] );
		unset( $args['params'] );

		$route_obj = new $class( $route, $args, $parser );
		$route_obj->calc_rewrite();

		return $route_obj;
	}

	public function post( $route, $args ) {
		$args['method'] = 'POST';
		$class          = 'PL_Route_' . $args['type'];
		$parser         =  new \PL_Route_Parser( array(
			'constraints' => $args['constraints'],
			'params'      => $args['params']
		) );
		
		unset( $args['type'] );
		unset( $args['constraints'] );
		unset( $args['params'] );

		$route_obj = new $class( $route, $args, $parser );
		$route_obj->calc_rewrite();

		return $route_obj;
	}

	public function parse_ctrl_action( $ctrl_action ) {

		$parsed = $ctrl_action;

		if( is_string( $ctrl_action ) ) {
			$parts = explode( '#', $ctrl_action );
			$parsed = array( 
				'controller' => $parts[0],
				'action'     => $parts[1] 
			);
		}

		return $parsed;
	}
}
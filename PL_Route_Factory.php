<?php

/**
* 
*/
class PL_Route_Factory {
	
	protected $resource_actions;

	function __construct() {
		$this->resource_actions = array(
			'index'  => array( 'ext' => '',          'method' => 'get' ),
			'create' => array( 'ext' => '',          'method' => 'post' ),
			'new'    => array( 'ext' => '/new',      'method' => 'get' ),
			'show'   => array( 'ext' => '/:id',      'method' => 'get' ),
			'edit'   => array( 'ext' => '/:id/edit', 'method' => 'get' ),
		);
	}

	public function resource( $name, $args, $options = array() ) {
		
		$actions    = $this->resource_actions;
		$routes     = array();

		if( array_key_exists( 'only', $options ) && is_array( $options['only'] ) ) {
			$actions = array_intersect_key( $actions, array_flip( $options['only'] ) );
		}

		foreach( $actions as $action => $props ) {
			$route_args = array_merge( $args, array( 'action' => $action, 'method' => $props['method'] ) );
			$routes[] = $this->$props['method']( $name . $props['ext'], $route_args );
		}

		return $routes;
	}

	public function get( $route, $args ) {
		$args['method'] = 'GET';
		return new PL_Route( $route, $args );
	}

	public function post( $route, $args ) {
		$args['method'] = 'POST';
		return new PL_Route( $route, $args );
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
<?php

/**
* 
*/
class PL_Route_Factory {
	
	function __construct() {
		# code...
	}

	public function resource( $name, $args ) {
		
		log_me( __METHOD__ );

		$routes = array();

		$args['action'] = 'index';
		$routes[]       = $this->get( $name, $args );

		$args['action'] = 'create';
		$routes[]       = $this->post( $name, $args );

		$args['action'] = 'show';
		$routes[]       = $this->get( $name . '/:id', $args );

		$args['action'] = 'edit';
		$routes[]       = $this->get( $name . '/:id/edit', $args );

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
}
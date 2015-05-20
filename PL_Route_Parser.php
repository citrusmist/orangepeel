<?php

/**
* 
*/
class PL_Route_Parser {
	
	protected $params;
	protected $constraints;

	function __construct( $args ) {
		
		$defaults = array( 
			'constraints' => array(),
			'params'      => array()
		);

		$args = wp_parse_args( $args, $defaults );

		foreach( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function parse( $route ) {
		$rewrite  = array();
		$redirect = 'index.php?'; 
		$rule     = $route->route;

		$count = 1;
		$rewrite['rule'] = preg_replace_callback(
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
		) . '/?$';
		$rewrite['redirect'] = $redirect;

		return $rewrite;
	}
}
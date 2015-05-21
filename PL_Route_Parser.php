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
		$segments = array();
		$redirect = 'index.php?'; 
		$rule     = $route->route;

		$count = 1;
		$rewrite['rule'] = preg_replace_callback(
			'/:([^\/]+)(\/)?/', 
			function( $matches ) use ( &$redirect, &$segments, &$count ) {

				for( $i = 1; $i < count( $matches ); $i++ ) { 
					
					if( empty( $matches[$i] ) || $matches[$i] == '/' ) {
						continue;
					}
					
					$param_name = $matches[$i];
					$segments[] = $matches[$i];

					$constraint = '([^/]+)';

					if( isset( $this->params[$matches[$i]] ) ) {
						$param_name = $this->params[$matches[$i]];
					}

					if( isset( $this->constraints[$matches[$i]] ) ) {
						$constraint = $this->constraints[$matches[$i]];
					}

					$redirect .= $count == 1 ? '' : '&';
					$redirect .= $param_name . '=$matches[' . $count . ']';
					$count++;
				}

				return empty( $matches[2] ) ? $constraint : $constraint . $matches[2];
			}, 
			$rule
		) . '/?$';
		// ) . '(?:\.([^\/]+))?\/?$';

		$defaults = array_diff_key( $route->defaults, $segments );
		// $redirect .= $count === 1 ? '' : '&';
		// $redirect .= 'format=$matches[' + $count + 1 . ']';

		foreach( $defaults as $key => $value ) {
			$redirect .= $count == 1 ? '' : '&';
			$redirect .= $key . '=' . $value;
		}

		$rewrite['redirect'] = $redirect;

		return $rewrite;
	}
}
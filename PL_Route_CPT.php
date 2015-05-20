<?php

/**
* 
*/
class PL_Route_CPT extends \PL_Route {
	
	function __construct( $name, $args, $parser ) {
		
		$args['defaults'] = array_merge( array( 'post_type' => $args['cpt'] ), $args['defaults'] );

		parent::__construct( $name, $args, $parser );
	}

	public function calc_rewrite() {
		if( in_array( $this->action, array( 'index', 'show' ) ) ) {
			$this->rewrite = '_builtin';
		} else {
			$this->rewrite = $this->parse();
		}
	}
}
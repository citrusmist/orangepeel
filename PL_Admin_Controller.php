<?php 

abstract class PL_Controller_Admin extends PL_Action_Controller {

	public function render( $args ) {

		$defaults = array( 
			'file'         => '',
			'html'         => false,
			'plain'        => '',
		);

		if( !empty( $args['layout'] ) ) {
			$this->set_layout( $args['layout'] );
			unset( $args['layout'] );
		}

		if( !empty( $args['action'] ) ) {
			$this->set_view_template( $args['action'] );
			unset( $args['action'] );
		}
		
		if( empty( $args ) ) {
			return;
		}

		$args = wp_parse_args( $args, $defaults );
		$this->render_args = $args;
	}
}
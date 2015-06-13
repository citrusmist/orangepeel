<?php 

abstract class PL_Admin_Controller extends PL_Action_Controller {

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

	public function template_path( $plugin ) {
		return $this->view_path->plugin_admin( $plugin );
	}
}
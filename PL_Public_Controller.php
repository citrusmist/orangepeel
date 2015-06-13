<?php 

/**
* 
*/
abstract class PL_Public_Controller extends PL_Action_Controller {

	public function render( $args ) {

		$defaults = array( 
			'file'         => '',
			'html'         => false,
			'json'         => false,
			'xml'          => false,
			'plain'        => '',
			'status'       => '',
			'content-type' => ''
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
		
		$theme_path  = $this->view_path->theme( $plugin );
		$plugin_path = $this->view_path->plugin_public( $plugin );
		$path        = false;

		if( file_exists( $theme_path ) ) {
			$path = $theme_path;
		} else {
			$path = $plugin_path;
		}

		return $path;

	}
}
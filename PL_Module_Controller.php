<?php 
/* PL_Controller to become PL_Module_Controller */

abstract class PL_Module_Controller {

	protected $name;
	protected $view           = null;
	protected $layout         = null;
	protected $view_template  = null;
	protected $view_path      = null;
	protected $last_render    = null;
	protected $render_args    = null;

	public function __construct( $params ) {
		// $this->module = $module;
		$r_class           = new ReflectionClass( $this );
		$this->params      = $params;
		$this->view        = new stdClass();
		$this->layout      = 'module.php';
		$this->view_path   = new \PL_View_Path( strtolower( $r_class->getNamespaceName() ), $this->get_name() );
	}

	public function __call( $name, $arguments ) {

		if ( !method_exists( $this, $name . '_action' ) ) {
			return $name . " method doesn't exist!";
		}

		// $this->set_view_file( $name . '.php' );
		$this->set_view_template( $name );

		if( isset( $arguments ) ){
			call_user_func_array( array(  $this, $name . '_action' ), $arguments );
		} else {
			call_user_func( array(  $this, $name . '_action' ) );
		}
	}

/*	public function render() {
		$this->last_render = $this->view->render();
		return $this->last_render;
	}*/

	/*
	 * README Seems as these should be two separate things. One configures the render
	 * and one executes it. The controller actions should be able to configure it the render
	 * but I'm not sure if also executing it in one step is good practice...
	 * Rails mixes execution and configuration in one method, but since we are in WP 
	 * we might have do it differently
	 */
	public function render( $args ) {
		/*
		 * Some of these are mutually exclusive, e.g. if the layout or action are set then
		 * json can't be. If one of the formats is set it means that none of the others can be.
		 */

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

	public function get_render_args() {
		return $this->render_args;
	}

	public function get_view() {
		return $this->view;
	}

	public function get_view_data() { 
		return $this->view;
	}

	public function set_layout( $layout ) { 
		$this->layout = $layout . '.php';
	}

	public function get_layout() {
		return $this->layout;
	}

	public function get_name() {

		if( isset( $this->name ) ) {
			return $this->name;
		}

		$bits = explode( '_', substr( get_called_class(), strrpos( get_called_class(), '\\' ) + 1 ) );

		$bits = array_filter( $bits, function( $var ) {

			if( in_array( $var, array( 'Public', 'Admin', 'Controller' ) ) ) {
				return false;
			}

			return true;
		} );

		$this->name = strtolower( implode( '-', $bits ) );

		return $this->name;
	}

	public function set_view_template( $template ) {
		$this->view_path->set_template( $template . '.php' );
	}

	public function template_path( $plugin ) {

		if( stripos( get_called_class(), 'admin' ) !== FALSE ) {
			return $this->view_path->plugin_admin( $plugin );
		}

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
<?php 
/* PL_Controller to become PL_Module_Controller */

abstract class PL_Module_Controller {

	protected $plugin_path;
	protected $name;
	protected $view           = null;
	protected $layout         = null;
	protected $view_template  = null;
	protected $view_path      = null;
	protected $last_render    = null;
	protected $render_args    = null;

	public function __construct( $params, $plugin_path ) {
		// $this->module = $module;
		$this->params      = $params;
		$this->plugin_path = $plugin_path;
		$this->view        = new stdClass();
		$this->layout      = 'module.php';
	}

	public function __call( $name, $arguments ) {

		if ( !method_exists( $this, $name . '_action' ) ){
			return $name . " method doesn't exist!";
		}

		// $this->view_path = $this->get_view_path( $this->get_bootstrap('slug') . '/' . $name . '.php' );
		// $this->errorlist_path = $this->get_view_path( '/helpers/errorlist.php' );

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

		if( !empty( $args['layout'] ) ) {

			$this->set_layout( $args['layout'] );
			unset( $args['layout'] );

			if( empty( $args ) ) {
				return;
			}
		}

		if( !empty( $args['action'] ) ) {

			$this->set_layout( $args['layout'] );
			unset( $args['action'] );

			if( empty( $args ) ) {
				return;
			}
		}

		$defaults = array( 
			'action'       => '',
			'file'         => '',
			'html'         => false,
			'json'         => false,
			'xml'          => false,
			'plain'        => '',
			'status'       => '',
			'content_type' => ''
		);

		$args = $this->parse_render_args( $args );
		$args = wp_parse_args( $args, $defaults );
		$this->render_args = $args;
	}


	public function get_render_args() {
		return $this->render_args;
	}

	private function parse_render_args( $args ) {
		
		if ( !empty( $args['action'] ) ) {
			//set view path
		} 

		return $args;
	}

	function compile_view() {
		$this->last_render = $this->view->render();
	}

	public function get_render() {
		return $this->last_render;	
	}

	protected function module_view_path( $filename ) {
		
		$r_class   = new ReflectionClass( get_called_class() );
		$view_path = strtolower( $r_class->getNamespaceName() );

		if( stripos( get_called_class(), 'admin' ) === FALSE ){
			$view_path .= '/public/views/' . $this->get_name() . '/' . $filename;
		} else{
			$view_path .= '/admin/views/' .  $this->get_name() . '/' . $filename;
		}

		return $view_path;
	}

	protected function set_view_file( $filename ) {

		$this->view_template = $filename;		
		$module_path = $this->module_view_path( $filename );
		$plugin_path = $this->plugin_path . '/' . $module_path;
		$theme_path  = get_stylesheet_directory() . '/' . $module_path;
		$view_file   = '';

		//short circuit checking for presence of file in theme folder 
		//in case it's an admin action
		if( stripos( get_called_class(), 'admin' ) !== FALSE ){
			$view_file = $plugin_path;
		} else if ( file_exists( $theme_path ) ){
			$view_file = $theme_path;
		} else {
			$view_file = $plugin_path;
		}	

		$this->view->set_path( $view_file );
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

	function get_name() {

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
		$this->view_template = $template . '.php';
	}

	public function get_view_template() {
		return $this->view_template;
	}
}
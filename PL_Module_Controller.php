<?php 
/* PL_Controller to become PL_Module_Controller */

abstract class PL_Module_Controller {

	protected $module;
	protected $plugin_path;
	protected $view           = null;
	protected $view_path      = null;
	protected $errorlist_path = null;
	protected $last_render    = null;

	public function __construct( $params, $plugin_path ) {
		// $this->module = $module;
		$this->params      = $params;
		$this->plugin_path = $plugin_path;
		$this->view        = new PL_View();
	}

	public function __call( $name, $arguments ) {

		if ( !method_exists( $this, $name . '_action' ) ){
			return $name . " method doesn't exist!";
		}

		// $this->view_path = $this->get_view_path( $this->get_bootstrap('slug') . '/' . $name . '.php' );
		// $this->errorlist_path = $this->get_view_path( '/helpers/errorlist.php' );

		$this->set_view_file( '_' . $name . '.php' );

		if( isset( $arguments ) ){
			call_user_func_array( array(  $this, $name . '_action' ), $arguments );
		} else {
			call_user_func( array(  $this, $name . '_action' ) );
		}
	}

	public function render() {
		$this->last_render = $this->view->render();
		return $this->last_render;
	}

	public function get_render() {
		return $this->last_render;	
	}

	protected function module_view_path( $filename ) {
		
		$r_class   = new ReflectionClass( get_called_class() );
		$view_path = $this->plugin_path . '/' . strtolower( $r_class->getNamespaceName() );

		if( stripos( get_called_class(), 'admin' ) === FALSE ){
			$view_path .= '/public/views/' . $filename;
		} else{
			$view_path .= '/admin/views/' . $filename;
		}

		return $view_path;
	}

	protected function set_view_file( $filename ) {
		
		$plugin_path = $this->module_view_path( $filename );

		if( stripos( get_called_class(), 'admin' ) !== FALSE ){
			$this->view->set_path( $plugin_path );
			return;
		}

		$theme_path = get_stylesheet_directory() . '/' . $this->get_bootstrap( 'plugin_prop', 'slug' ) . '/' . $filename;

		if ( file_exists( $theme_path ) ){
			$this->view->set_path( $theme_path );
		} else {
			$this->view->set_path( $plugin_path );
		}	
	}

}
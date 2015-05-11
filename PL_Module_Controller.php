<?php 
/* PL_Controller to become PL_Module_Controller */

abstract class PL_Module_Controller {

	protected $module;
	protected $view           = null;
	protected $view_path      = null;
	protected $errorlist_path = null;
	protected $last_render    = null;

	public function __construct() {
		// $this->module = $module;
		$this->view = new PL_View();
	}

	public function __call( $name, $arguments ){

		if ( !method_exists( $this, $name . '_action' ) ){
			return $name . " method doesn't exist!";
		}

		// $this->view_path = $this->get_view_path( $this->get_bootstrap('slug') . '/' . $name . '.php' );
		// $this->errorlist_path = $this->get_view_path( '/helpers/errorlist.php' );

		$this->set_view_file( $this->get_bootstrap('slug') . '/' . $name . '.php' );

		if( isset( $arguments ) ){
			call_user_func_array( array(  $this, $name . '_action' ), $arguments );
		} else {
			call_user_func( array(  $this, $name . '_action' ) );
		}
	}

	protected function render(){
		$this->last_render = $this->view->render();
		return $this->last_render;
	}

	public function get_render(){
		return $this->last_render;	
	}

	protected function get_view_file( $filename ){
		
		$view_path = '';
		$plugin_path = $this->get_bootstrap('plugin_class') . '::get_path';

		if( stripos( get_called_class(), 'admin' ) === FALSE ){
			$view_path = call_user_func( $plugin_path ) . 'public/views/' . $filename;
		} else{
			$view_path = call_user_func( $plugin_path ) . 'admin/views/' . $filename;
		}

		return $view_path;
	}

	protected function set_view_file( $filename ){
		
		$plugin_path = $this->get_view_file( $filename );

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
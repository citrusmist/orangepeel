<?php
abstract class OP_Controller {

	private $_bootstrap = null;

	protected $view           = null;
	protected $view_path      = null;
	protected $errorlist_path = null;
	protected $last_render    = null;

	public function __construct(){
		$this->view = new OP_View();
	}

	public function set_bootstrap( $value ){
		$this->_bootstrap = $value;
	}

	public function get_bootstrap( $prop, $param = '' ){

		if( defined( $this->_bootstrap . '::' . strtoupper( $prop ) ) ) {

			$value = constant( $this->_bootstrap . '::' . strtoupper( $prop ) );

		} else if( method_exists( $this->_bootstrap, 'get_' . strtolower( $prop ) ) ) {

			if( empty( $param ) )
				$value = call_user_func( array( $this->_bootstrap, 'get_' . $prop ) );
			else
				$value = call_user_func( array( $this->_bootstrap, 'get_' . $prop ), $param );

		}

		return $value;
	}

	protected function reset_cookies( $name = '' ){

		$key = empty( $name ) ? '' : '[' . $name . ']';

		if( isset( $_COOKIE[$this->get_bootstrap('slug')][$name] ) ) {

			$cookie = $_COOKIE[$this->get_bootstrap('slug')][$name];

			foreach ( $cookie as $type => $message ) {
				setcookie( $this->get_bootstrap('slug') . $key . '[' . $type . ']', '', time() - 120 );	
  		}	
		}
	}

	protected function set_param_cookies( $params ) {

		foreach ($params as $key => $value) {
			setcookie( $this->get_bootstrap('slug') . '['. $key. ']', $value, time() + 120 );
		}
	}

	protected function set_error_cookies( $errors ) {

		if( isset( $_COOKIE[$this->get_bootstrap('slug')]['errors'] ) ) {
			foreach ( $_COOKIE[$this->get_bootstrap('slug')]['errors'] as $type => $message ) {
				setcookie( $this->get_bootstrap('slug') . '[errors][' . $type . ']', '', time() - 120 );	
  		}	
		}

		foreach ($errors as $type => $message) {
			setcookie( $this->get_bootstrap('slug') . '[errors][' . $type . ']', $message, time() + 120 );
		}
	}

	public function __call( $name, $arguments ){

		if ( !method_exists( $this, $name . '_action' ) ){
			return $name . " method doesn't exist!";
		}

		// $this->view_path = $this->get_view_path( $this->get_bootstrap('slug') . '/' . $name . '.php' );
		// $this->errorlist_path = $this->get_view_path( '/helpers/errorlist.php' );

		$this->set_view_path( $this->get_bootstrap('slug') . '/' . $name . '.php' );

		if( isset( $arguments ) ){
			call_user_func_array( array(  $this, $name . '_action' ), $arguments );
		} else {
			call_user_func( array(  $this, $name . '_action' ) );
		}
	}

	protected function get_view_path( $filename ){
		
		$view_path = '';
		$plugin_path = $this->get_bootstrap('plugin_class') . '::get_path';

		if( stripos( get_called_class(), 'admin' ) === FALSE ){
			$view_path = call_user_func( $plugin_path ) . 'public/views/' . $filename;
		} else{
			$view_path = call_user_func( $plugin_path ) . 'admin/views/' . $filename;
		}

		return $view_path;
	}

	protected function set_view_path( $filename ){
		
		$plugin_path = $this->get_view_path( $filename );

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

	protected function use_default_template() {
		$filename = $this->get_bootstrap('slug') . '/' . $this->get_bootstrap('slug') . '.php';
		$this->register_template( $filename );
	}

	protected function register_template( $filename ) {
		return new OP_Template_Include(
			$filename,
			$this->get_view_path( $filename )
		);
	}

	protected function render(){
		$this->last_render = $this->view->render();
		return $this->last_render;
	}

	public function get_render(){
		return $this->last_render;	
	}

	public static function get_current_url() {

		$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	  $url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
	  $url .= $_SERVER["REQUEST_URI"];
	  return $url;
	}
}
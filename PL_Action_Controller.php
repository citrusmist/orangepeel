<?php 

abstract class PL_Action_Controller {

	protected $name;
	protected $view;
	protected $layout;
	protected $view_path;
	protected $render_args;

	public function __construct( $params ) {
		// $this->module = $module;
		$r_class         = new ReflectionClass( $this );
		$this->params    = $params;
		$this->view      = new stdClass();
		$this->layout    = 'module.php';
		$this->view_path = new \PL_View_Path( strtolower( $r_class->getNamespaceName() ), $this->get_name() );
	}

	public function __call( $name, $arguments ) {

		if ( !method_exists( $this, $name . '_action' ) ) {
			return $name . " method doesn't exist!";
		}

		$this->set_view_template( $name );

		if( isset( $arguments ) ){
			call_user_func_array( array(  $this, $name . '_action' ), $arguments );
		} else {
			call_user_func( array(  $this, $name . '_action' ) );
		}
	}

	abstract public function render( $args );

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

	abstract public function template_path( $plugin );
}
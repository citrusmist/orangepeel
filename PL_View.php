<?php 

class PL_View {

	protected $_path = null;
	protected $_data = null;

	public function __construct(){
		$this->_data = array();
	}

	public function render(){
		return self::render( $this->_path, $this->_data );
	}

	public function set_path( $view_path ){
		$this->_path = $view_path;
	}

	public function set_data( array $data ){
		$this->_data = array_merge( $this->_data, $data);
	}

	public function __get( $name ) {
		return $this->_data[$name];
	}

	public function __set( $name, $value ) {

		if ( method_exists( $this, 'set_' . $name ) ){
			call_user_func( array( $this, 'set_' . $name ), $value );
		} else{
			$this->_data[$name] = $value;
		}
	}

	public function __isset( $name ) {
		return isset( $this->_data[$name] );
	}

	/**
	 * -------------------------------------
	 * Render a Template.
	 * -------------------------------------
	 * 
	 * @param $filePath - include path to the template.
	 * @param null $viewData - any data to be used within the template.
	 * @return string - 
	 * 
	 */
	public static function render_tmpl( $filePath, $viewData = null ) {

		// Was any data sent through?
		( $viewData ) ? extract( $viewData ) : null;

		ob_start();
		include ( $filePath );
		$template = ob_get_contents();
		ob_end_clean();

		//README: Probably can be refactored ot just 
		//$template = ob_end_clean();

		return $template;
	}

}
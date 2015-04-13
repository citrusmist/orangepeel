<?php 

/**
* 
*/
abstract class PL_Model implements PL_Recordable, PL_Validatable {

	protected $_errors         = array();
	protected $_changed_record = false;

	/*
	 * README: The methods defined below shouldn't be
	 * abstract as they are static, they however need to exist in
	 * the implementation
	 */

	// abstract public static function find( $id );

	// abstract public static function find_by( $property, $value );

	// abstract public static function all();
	
	// abstract protected static function describe_data();

	abstract public function save();

	abstract public function delete();
	
	public static function get_data_description() {
		return static::describe_data();
	}


	public function has_errors() {
		return !empty( $this->_errors );
	}


	public function get_errors(){

		if( $this->has_errors() ) {
			return $this->_errors;
		} else {
			return false;
		}
	}


	public function validate() {

		$descriptions = static::get_data_description();

		foreach ($descriptions as $key => $description) {

			$error = null;
			$validator = new PL_Validator( $key, $this->$key, $description );
			$error = $validator->validate();

		 	if( is_wp_error( $error ) ){
		 		$this->_errors[$key] = $error->get_error_message();
		 	}
		}
	}


	public static function __callStatic( $name, $arguments ) {

		if( strpos( $name, 'find_by_' ) !== FALSE ){

			$prop = str_replace( 'find_by_' , '', $name );
			return static::find_by( $prop, $arguments[0] );
		}
	}	
}

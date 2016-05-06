<?php 

/**
* 
*/
abstract class PL_Model implements PL_Recordable, PL_Validatable {

	protected $_errors         = array();
	protected $_changed_record = false;
	protected $_new_record     = true;
	protected $_validatable_props = array();

	/*
	 * README: The methods defined below shouldn't be
	 * abstract as they are static, they however need to exist in
	 * the implementation
	 */

	// abstract public static function find( $id );

	// abstract public static function find_by( $property, $value );

	// abstract public static function all();
	
	// abstract protected static function describe_data();

	// abstract public function save();

	// abstract public function delete();


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

	public static function get_data_description() {
		return static::describe_data();
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

	public static function get_data_associations() {

		if( method_exists( get_called_class(), 'describe_associations' ) ){
			return static::describe_associations();
		} else{
			return array();
		}
	}

	public static function has_association( $name ) {

		$assocs = static::get_data_associations();

		log_me( __METHOD__ );

		if( !empty( $assocs[$name] ) ) {
			log_me( $name . ' is associated with ' . get_called_class() );
			return true;
		}

		/*
		README: The snippet below returns true for if we pass in singular word for a has_many association
		think for now this isn't necessary
		$name_plural = \PL_Inflector::pluralize( $name );
		
		if( !empty( $assocs[$name_plural] ) 
				&& $assocs[$name_plural]['cardinality'] == 'has_many' ) {
			return true;
		} */

		return false;
	}

	public function is_record_new() {
		//FIXME: This won't work for PL_Std_Model as `id` property is in capitals: `ID`
		return empty( $this->id ) ? $this->_new_record : false;
	}

	public function is_record_changed() {
		return $_changed_record;
	}

	public static function get_record_count() {
		/*
		FIXME: table name is hard-coded here

		$result = self::query( array(
			'select' => 'COUNT(*) AS orgs_total_count', 
		) );

		return $result[0]->orgs_total_count;*/
	}

	public static function __callStatic( $name, $arguments ) {

		if( strpos( $name, 'find_by_' ) !== FALSE ){

			$prop = str_replace( 'find_by_' , '', $name );
			return static::find_by( $prop, $arguments[0] );
		}
	}	
}

<?php 
/**
 * 
 */
class PL_User_Model extends PL_Model {
	
	protected $_data;
	protected $_user;

	function __construct( $data = array() ) {

		$this->_data = (object) $data;
	}

	public function __get( $prop ) {

		if( is_a( $this->_user, "WP_User" ) ) {
			return $this->_user->$prop;
		}

		$value = "";

		if( isset( $this->_data->$prop ) ){
			$value = $this->_data->$prop;
		}

		return $value;
	}

	public function __set( $prop, $value ) {

		if( is_a( $this->_user, "WP_User" ) ) {
			$this->_user->$prop = $value;
		} else{
			$this->_data->$prop = $value;
		}
	}

	public function initialise() {
		$this->_user = new WP_User( $this->_data );
	}

	public static function get_current() {
		return new self( wp_get_current_user() );
	}

	public function save() {
		# code...
	}

	public function delete() {
		# code...
	}

	public static function find( $id ) {
		# code...
	}

	public static function find_by( $property, $value ){

	}

	public static function all() {
		# code...
	}
}
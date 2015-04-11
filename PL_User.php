<?php 
/**
* 
*/
class PL_User {
	
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


}
<?php

class PL_Dispatcher{
	private $_param;
	private $_request;
	// private $_controller;

	public function __construct ( $param, $request ) {
		$this->_param      = $param;
		$this->_request    = $request;
		// $this->_controller = $controller;
	}

	public function request_has_action() {
		
		// if( array_key_exists( $this->_param, $this->_request ) 
		// 	&& array_key_exists( $this->_request[$this->_param], 'action' ) ){ 

		if( array_key_exists( $this->_param, $this->_request ) 
			&& array_key_exists( 'action', $this->_request[$this->_param] ) ) {

			return true;
		}

		return false;
	}

	/*public function controller_has_action(){

		if( $this->request_has_action() ) {
			return method_exists( 
				$this->_controller, 
				$this->_request[$this->_param]['action'] 
			);
		}

		return false;
	}*/

	public function set_request( $request ) {
		$this->_request = $request;
	}

	public function get_action() {
		if( $this->request_has_action() ){
			return $this->_request[$this->_param]['action'];
		}

		return false;
	}

	public function invoke( $controller ) {

		if( $this->request_has_action() ) {

			$params = $this->_request[$this->_param];
			unset($params['action']);

			call_user_func( 
				array( $controller, $this->_request[$this->_param]['action'] ),
				$params
			); 

			return true;
		} 

		return false;
	}
}
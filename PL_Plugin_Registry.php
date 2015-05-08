<?php 

/**
* 
*/
class PL_Plugin_Registry extends PL_Registry {
	
	private static $_instance;
	private $_plugins = array();

	function __construct() {}

  static function get_instance() {
    
    if ( ! isset( self::$_instance ) ) { 
    	self::$_instance = new self(); 
    }

    return self::$_instance;
  }

	public function get( $key ) {

		if ( isset( $this->_plugins[$key] ) ) {
			return $this->_plugins[$key];
		}

		return null;
	}

	public function set( $plugin, $modules ) {
		
		if( array_key_exists( $plugin->get_name(), $this->_plugins ) ) {
			return;
		}

		$this->_plugins[$plugin->get_name()] = array(
			'plugin'  => $plugin,
			'modules' => $modules
		);
	}

}
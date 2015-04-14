<?php 

/**
	* Simple registry used to keep track of modules used by a plugin.
	*
	* Registries tend to be singletons. However, this is a framework that can 
	* be used by multiple plugins in the same WordPress install and we only need to 
	* keep track of the modules on a per plugin basis is why this registry does not
	* follow a singleton pattern.
	*
	* It may be something to consider should we want to share modules across plugins...
	*/
class PL_Module_Registry {

	protected $modules = array();
	
	function __construct( array $mods = null ) {
		$this->modules = $mods;
	}

	function get( $key ) {

		if ( isset( $this->modules[$key] ) ) {
			return $this->modules[$key];
		}

		return null;
	}

	function set( $key, $value ) {
		$this->modules[$key] = $value;
	}
}

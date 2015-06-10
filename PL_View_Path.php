<?php 

/**
 * 
 */
class PL_View_Path {
	
	protected $module;
	protected $controller;
	protected $template;

	function __construct( $module, $ctrl, $tmpl = '' ) {
		$this->module     = $module;
		$this->controller = $ctrl;
		$this->template   = $tmpl;
	}

	public function set_module( $val ) {
		$this->module = $val;
	}

	public function set_controller( $ctrl ) {		
		$this->controller = $ctrl; 
	}

	public function set_template( $val ) {

		$parsed = $this->parse_template( $val );

		foreach( $parsed as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function theme( $plugin ) {
		$path = $this->module . '/' . $this->controller . '/' . $this->template;
		return get_stylesheet_directory() . '/' . $plugin->get_name() . '/' . $path;
	}

	public function plugin_public( $plugin ) {
		$path = $this->module . '/public/views/' . $this->controller . '/' . $this->template;
		return $plugin->get_plugindir_path() . '/' . $path;
	}

	public function plugin_admin( $plugin ) { 
		$path = $this->module . '/admin/views/' . $this->controller . '/' . $this->template;
		return $plugin->get_plugindir_path() . '/' . $path;
	}

	protected function parse_template( $template ) {
		
		$parts  = explode( '/', $template );
		$parsed = array();

		if( count( $parts ) === 1 ) {
			$parsed['template'] = $parts[0];
		} else if( count( $parts ) === 2 ) {
			$parsed['controller'] = $parts[0];
			$parsed['template']        = $parts[1];
		} else if( count( $parts ) === 3 ) {
			$parsed['module']          = $parts[0];
			$parsed['controller'] = $parts[1];
			$parsed['template']        = $parts[2];
		}

		return $parsed;
	}
}
<?php 

/**
* 
*/
class PL_JS_Template_Include {
	
	protected static $instance  = false;
	protected $templates = array();

	private function __construct() {
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	}

	public static function get_instance() {
		if ( false === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_templates( $tmpls, $path ) {

		$tmpls = is_array( $tmpls ) ? $tmpls : (array) $tmpls;

		foreach ( $tmpls as $key => $value ) {
			$tmpls[$key] = $path . '/_' . $value  . '.tmpl.php';
		}

		$this->templates = array_merge( $tmpls, $this->templates );
	}

	public function admin_footer() {
		foreach ( $this->templates as $key => $path ) {
			include $path;
		}
	}
}
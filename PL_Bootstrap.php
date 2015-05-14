<?php 

/*
 * - All classes that belong to a certain plugin should have the 
 *   same prefix and words should be separated using underscores
 *   e.g. AP_Users_Bootstrap AP_Users_Controller
 * - This enables us to dynamically compute the name of the plugin 
 *   class without setting up loads of globals. It also allows us to
 *   move modules between plugins and only change the prefix 
 */


abstract class PL_Bootstrap {

	protected $public_controller = null;
	protected $admin_controller  = null;
	protected $dispatcher        = null;
	protected $query_map         = array();
	protected $_relevant_key     = null;

	//Module Controller a la Rails Application Controller
	protected $controller;

	protected $plugin;

	public function __construct( $plugin ) {

		//README: Instead of a Module Controller should we just have a singleton
		//front controller which we can configure withing the module bootstrap, 
		//so that dependency is inverted. We could just pass the endpoint, module slug,
		//controller(optional) name and action name(options)
		//If not optional args passed default behaviour assumed
		// $this->controller = new PL_Module_Controller( $this );
		$this->plugin = $plugin;

		$this->init();
	}

	/*
	 * Allows implementation to override the slug value by 
	 * setting a SLUG class constant, otherwise defaults to 
	 * the class name minus the '_Bootstrap'. For example
	 * AP_Users_Bootstrap defaults to slug being 'ap-users '
	 */
	public static function get_slug(){

		if( defined ( get_called_class() . '::SLUG' ) ){
			return constant( get_called_class() . '::SLUG' );
		}

		$slug = str_replace( 'bootstrap', '', strtolower( get_called_class() ) );
		$slug = str_replace( '_', '-', $slug );
		$slug = trim( $slug, '-' );
		return $slug;
	}



	public static function get_plugin_prop( $prop ) {

		$class = self::get_plugin_class();
		$value = NULL;

		if( defined( $class . '::' . strtoupper( $prop ) ) ) {
			$value = constant( $class . '::' . strtoupper( $prop ) );
		} else if( method_exists( $class, 'get_' . strtolower( $prop ) ) ) {
			$value = call_user_func( array( $class, 'get_' . $prop ) );
		}

		return $value;
	}

/*	protected static function setup_dependencies(){

		$public_controller = self::get_controller('public');
		
		if( isset ( $public_controller ) ){
			call_user_func_array( 
				array( $public_controller, 'set_bootstrap' ), 
				array( get_called_class() ) 
			);
		}
	}*/

}
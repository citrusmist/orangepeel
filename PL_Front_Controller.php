<?php 

/**
* 
*/
class PL_Front_Controller {

	protected static $instance;
	
	private function __construct() {}

	public static function get_instance() {
		if ( ! self::$instance ) {
      self::$instance = new self();
      //README should this be in the constructor
      self::$instance->register_callbacks();
    }

    return self::$instance;
	}

	public function register_callbacks() {
		add_action( 'wp',   array( $this, 'wp' ), 10, 1 );
	}

	/*
	 * Check for presence of a specific request, request resolves(maps) to 
	 * to an action call execute the corresponding method in the controller.
	 * The view is injected later with static::get_slug() . '_view' action
	 *
	 * @TODO: Refactor
	 * This probably can be simplified if we dont rely on static methods as much.
	 * We could have a function check if the request has anything to do with 
	 * this module during the 'wp' hook and store the result of this test. 
	 * Subsequent actions need only check this and get data this way rather than 
	 * always querying get_query_var
	 */
	public function wp( $wp ) {

		global $wp_query;
		
		if( !$wp_query->is_main_query() ) {
			return;
		}

		$route = PL_Route::get_instance()->resolve($wp_query);
	}
}
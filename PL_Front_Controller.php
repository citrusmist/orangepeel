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
		add_action( 'wp', array( $this, 'wp' ) );
		add_action( 'peel_view', array( $this, 'render_view' ) );
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
		
		if( ! $wp_query->is_main_query() ) {
			return;
		}

		$route = PL_Route::get_instance()->resolve( $wp_query );
		
		if ( $route == false ) {
			return;
		}

		$this->load_template( $route );
	}

	public function load_template( $route ) {
		
		if( is_admin() ) {
			return;
		}

		$plugin   = PL_Plugin_Registry::get_instance()->get( $route['plugin'] );
		//Infer module name from namespace part of classname
		$module   = strtolower( substr( $route['action'], 0, strrpos( $route['action'], '\\' ) ) );
		
		$template = $route['plugin'] . '/' . $module . '/template.php';
		$fallback = $plugin['instance']->get_plugindir_path() . '/' . $module . '/public/views/template.php';
		$tinc = new PL_Template_Include( $template, $fallback );

	}

	public function render_view() {

		log_me( __METHOD__ );

		$route = PL_Route::get_instance()->get_current();

		$controller_action = explode( '#', $route['action'] );
		$controller        = $controller_action[0];
		$action            = $controller_action[1];

		if( is_callable( $controller, $action ) ) {
			$controller = new $controller();
			$controller->$action();
			echo $controller->get_render();
		} else {
			log_me('bastard');
		}

	}

	public function controller_action_exists( $controller_action ) {
		
		$controller_action = explode( '#', $controller_action );
		$controller        = $controller_action[0];
		$action            = $controller_action[1];
		
		
		return is_callable( $controller, $action );
	}
}
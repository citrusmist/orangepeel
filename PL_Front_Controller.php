<?php 

/**
* 
*/
class PL_Front_Controller {

	protected static $instance;

	protected $params;
	
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
		add_action( 'parse_request', array( $this, 'parse_request' ) );
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
	public function parse_request( $wp ) {

		// global $wp_query;
		
		// if( ! $wp_query->is_main_query() ) {
		// 	return;
		// }
		// log_me( $req );
		// log_me( $_GET );
		// log_me( $_SERVER );
		$route = PL_Router::get_instance()->resolve( $wp );

		if ( $route == false ) { 
			return;
		}

		log_me( $route );

		parse_str( stripslashes( $wp->matched_query ), $this->params );

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
		$tinc     = new PL_Template_Include( $template, $fallback );

	}

	public function render_view() {

		$route             = PL_Router::get_instance()->get_current();
		$plugin            = PL_Plugin_Registry::get_instance()->get( $route['plugin'] );
		$controller_action = explode( '#', $route['action'] );
		$controller        = $controller_action[0];
		$action            = $controller_action[1];

		if( is_callable( $controller, $action ) ) {
			$controller = new $controller( $this->params, $plugin['instance']->get_path() );
			$controller->$action();
			echo $controller->render();
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
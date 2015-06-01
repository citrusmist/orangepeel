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
		add_filter( 'wp_headers', array( $this, 'wp_headers' ), 10, 2 );
	}

	public function parse_request( $wp ) {

		$route     = PL_Router::get_instance()->resolve( $wp );
		$wp_params = array();

		if ( $route == false ) { 
			return;
		}

		parse_str( stripslashes( $wp->matched_query ), $wp_params );

		$this->params = new \PL_Params( $wp_params );
		$this->params->set_defaults( array_merge( array( 'format' => 'html' ), $route->defaults ) );

		log_me( __METHOD__ );
		log_me( $this->params );

		$this->load_template( $route );
	}

	public function wp_headers( $headers, $wp ) {
		log_me( $headers );
		return $headers;
	}

	public function load_template( $route ) {
		
		if( is_admin() ) {
			return;
		}

		$plugin   = PL_Plugin_Registry::get_instance()->get( $route->plugin );
		//Infer module name from namespace part of classname
		$module   = strtolower( substr( $route->controller, 0, strrpos( $route->controller, '\\' ) ) );
		
		$template = $route->plugin . '/' . $module . '/template.php';
		$fallback = $plugin['instance']->get_plugindir_path() . '/' . $module . '/public/views/template.php';
		$tinc     = new PL_Template_Include( $template, $fallback );
	}

	public function render_view() {

		$route  = PL_Router::get_instance()->get_current();
		$plugin = PL_Plugin_Registry::get_instance()->get( $route->plugin );

		if( is_callable( $route->controller, $route->action ) ) {
			$controller = new $route->controller( $this->params, $plugin['instance']->get_path() );
			$controller->{$route->action}();
			echo $controller->get_render();
		} else {
			log_me('bastard');
		}
	}

}
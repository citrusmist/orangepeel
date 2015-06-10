<?php 

/**
* 
*/
class PL_Front_Controller {

	protected static $instance;

	protected $params;
	protected $controller;
	protected $compiled_view;
	
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
		add_action( 'peel_view', array( $this, 'print_view' ) );
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

		$this->dispatch_action( $route );
		$this->render( $route );
	}

	public function wp_headers( $headers, $wp ) {
		$r_args = $this->controller->get_render_args();
		
		if( isset( $r_args['content-type'] ) ) {
			$headers['Content-Type'] = $r_args['content-type'] . '; charset=' . get_option('blog_charset');
		}

		return $headers;
	}

	public function load_layout( $plugin ) {
		
		if( is_admin() ) {
			return;
		}

		$layout = $this->controller->get_layout();
		//Infer module name from namespace part of classname
		$module   = strtolower( substr( get_class( $this->controller ), 0, strrpos( get_class( $this->controller ), '\\' ) ) );
		
		$layout_path = $plugin->get_name() . '/' . $module . '/layouts/' . $layout;
		$fallback    = $plugin->get_plugindir_path() . '/' . $module . '/public/views/' . $layout;
		$tinc        = new PL_Template_Include( $layout_path, $fallback );
	}

	public function dispatch_action( $route ) {

		// $route  = PL_Router::get_instance()->get_current();
		$plugin = PL_Plugin_Registry::get_instance()->get( $route->plugin );

		if( is_callable( $route->controller, $route->action ) ) {
			$this->controller = new $route->controller( $this->params );
			$this->controller->{$route->action}();
		} else {
			//TODO throw an exception
			error_log( "{$route->action} action in {$route->controller} controller doesn't exist!" );
		}
	}

	public function render( $route ) {

		$plugin = PL_Plugin_Registry::get_instance()->get( $route->plugin );
		$r_args = $this->controller->get_render_args();

		if( $r_args === null ) {
			log_me( __METHOD__ );

			if( $this->params['format'] == 'json' ) {
				wp_send_json( $this->controller->get_view_data() );
				return;
			}

		} else {
			
			if( !empty( $r_args['status'] ) ) { 
				status_header( $r_args['status'] );
			}			

			if( !empty( $r_args['json'] ) ) {
				wp_send_json( $r_args['json'] );
				return;
			} 
		}

		$this->load_layout( $plugin['instance'] );
		$this->compile_view( $plugin['instance'] );
	}

	public function compile_view( $plugin ) {
		$view = new PL_View();
		$view->set_data( (array) $this->controller->get_view_data() );
		$view->set_path( $this->controller->template_path( $plugin ) );
		$this->compiled_view = $view->compile();
	}

	public function print_view() {
		echo $this->compiled_view;
	}
}
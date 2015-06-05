<?php 

/**
* 
*/
class PL_Front_Controller {

	protected static $instance;

	protected $params;
	protected $controller;
	
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
		log_me( $headers );
		return $headers;
	}

	public function load_layout( $plugin_name ) {
		
		if( is_admin() ) {
			return;
		}

		$layout = $this->controller->get_layout();

		$plugin   = PL_Plugin_Registry::get_instance()->get( $plugin_name );
		//Infer module name from namespace part of classname
		$module   = strtolower( substr( get_class( $this->controller ), 0, strrpos( get_class( $this->controller ), '\\' ) ) );
		
		$layout_path = $plugin_name . '/' . $module . '/layouts/' . $layout;
		$fallback    = $plugin['instance']->get_plugindir_path() . '/' . $module . '/public/views/' . $layout;
		$tinc        = new PL_Template_Include( $layout_path, $fallback );
	}

	public function dispatch_action( $route ) {

		// $route  = PL_Router::get_instance()->get_current();
		$plugin = PL_Plugin_Registry::get_instance()->get( $route->plugin );

		if( is_callable( $route->controller, $route->action ) ) {
			$this->controller = new $route->controller( $this->params, $plugin['instance']->get_path() );
			$this->controller->{$route->action}();
		} else {
			log_me('bastard');
		}
	}

	public function render( $route ) {
		$r_args = $this->controller->get_render_args();

		if( $r_args === null ) {
			log_me( __METHOD__ );

			switch ( $this->params['format'] ) {
				case 'json':
					log_me( $this->controller );
					wp_send_json( $this->controller->get_view_data() );
					break;
				case 'html':
					$this->load_layout( $route->plugin );
				default:
					$this->compile_view();
					break;
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
	}

	public function template_path( $filename ) {
		
		$r_class   = new ReflectionClass( get_called_class() );
		$view_path = strtolower( $r_class->getNamespaceName() );

		if( stripos( get_called_class(), 'admin' ) === FALSE ){
			$view_path .= '/public/views/' . $this->get_name() . '/' . $filename;
		} else{
			$view_path .= '/admin/views/' .  $this->get_name() . '/' . $filename;
		}

		return $view_path;
	}

	public function compile_view() {
		$view = new PL_View();
		$view->set_data( $this->controller->get_view_data() );
		$view->set_path();
	}

	public function print_view() {
		echo $this->controller->get_render();
	}
}
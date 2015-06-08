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
		log_me( $headers );
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
			$this->controller = new $route->controller( $this->params, $plugin['instance']->get_path() );
			$this->controller->{$route->action}();
		} else {
			log_me('bastard');
		}
	}

	public function render( $route ) {

		$plugin = PL_Plugin_Registry::get_instance()->get( $route->plugin );
		$r_args = $this->controller->get_render_args();


		if( $r_args === null ) {
			log_me( __METHOD__ );

			switch ( $this->params['format'] ) {
				case 'json':
					log_me( $this->controller );
					wp_send_json( $this->controller->get_view_data() );
					break;
				case 'html':
					$this->load_layout( $plugin['instance'] );
				default:
					$this->compile_view( $plugin['instance'] );
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


	public function template_path( $plugin ) {
		
		$r_class   = new ReflectionClass( $this->controller );
		$view_path = strtolower( $r_class->getNamespaceName() );
		$view_file = '';

		if( stripos( get_called_class(), 'admin' ) === FALSE ){
			$plugin_path = strtolower( $r_class->getNamespaceName() ) . '/public/views/' . $this->controller->get_name() . '/' . $this->controller->get_view_template();
			$theme_path  = strtolower( $r_class->getNamespaceName() ) . '/' . $this->controller->get_name() . '/' . $this->controller->get_view_template();
			// $view_path .= '/public/views/' . $this->controller->get_name() . '/' . $this->controller->get_view_template();
		} else{
			$plugin_path = strtolower( $r_class->getNamespaceName() ) . '/admin/views/' . $this->controller->get_name() . '/' . $this->controller->get_view_template();
			// $theme_path  = strtolower( $r_class->getNamespaceName() ) . '/' . $this->controller->get_name() . '/' . $this->controller->get_view_template();
			// $view_path .= '/admin/views/' .  $this->controller->get_name() . '/' . $this->controller->get_view_template();
		}

		$plugin_path = $plugin->get_plugindir_path() . '/' . $plugin_path;;
		$theme_path  = get_stylesheet_directory() . '/' . $plugin->get_name() . '/' . $theme_path;

		//short circuit checking for presence of file in theme folder 
		//in case it's an admin action
		if( stripos( get_called_class(), 'admin' ) !== FALSE ){
			$view_file = $plugin_path;
		} else if ( file_exists( $theme_path ) ){
			$view_file = $theme_path;
		} else {
			$view_file = $plugin_path;
		}	

		log_me( __METHOD__ );
		log_me( $theme_path );

		return $view_file;
	}


	public function compile_view( $plugin ) {
		$view = new PL_View();
		$view->set_data( (array) $this->controller->get_view_data() );
		$view->set_path( $this->template_path( $plugin ) );
		$this->compiled_view = $view->render();
	}

	public function print_view() {
		echo $this->compiled_view;
	}
}
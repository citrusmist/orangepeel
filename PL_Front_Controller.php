<?php 

/**
* 
*/
class PL_Front_Controller {

	protected static $instance = false;

	protected $params;
	protected $controller;
	protected $compiled_view;
	protected $is_peel_request = false;
	
	private function __construct() {}

	public static function get_instance() {
		if ( false === self::$instance ) {

			self::$instance = new self();
			//README should this be in the constructor
			self::$instance->register_callbacks();
		}

		return self::$instance;
	}

	public function register_callbacks() {
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'peel_view', array( $this, 'print_view' ) );

		add_action( 'wp_head',    array( $this, 'wp_head' ) );
		add_action( 'admin_head', array( $this, 'wp_head' ) );

		add_filter( 'wp_headers', array( $this, 'wp_headers' ), 10, 2 );


	}

	public function parse_request( $wp ) {

		$route     = PL_Router::get_instance()->resolve( $wp );
		$wp_params = array();

		if ( $route == false || is_admin() ) {
			$this->is_peel_request = false;
			return;
		}

		$this->is_peel_request = true;

		parse_str( stripslashes( $wp->matched_query ), $wp_params );

		$this->params = new \PL_Params( $wp_params );
		$this->params->set_defaults( array_merge( array( 'format' => 'html' ), $route->defaults ) );

		log_me( $this->params );

		$this->dispatch( $route );
		$this->render( $route->plugin );
	}

	public function wp_headers( $headers, $wp ) {

		if( ! $this->is_peel_request ) {
			return $headers;
		}

		$r_args = $this->controller->get_render_args();
		
		if( isset( $r_args['content-type'] ) ) {
			$headers['Content-Type'] = $r_args['content-type'] . '; charset=' . get_option('blog_charset');
		}

		return $headers;
	}

	public function load_layout( $plugin ) {

		$layout = $this->controller->get_layout();
		//Infer module name from namespace part of classname
		$module   = strtolower( substr( get_class( $this->controller ), 0, strrpos( get_class( $this->controller ), '\\' ) ) );

		if( is_admin() ) {
			$admin = $plugin->get_plugindir_path() . '/' . $module . '/admin/views/layouts/' . $layout;
			require $admin;
			PL_JS_Template_Include::get_instance()->register_templates( $this->controller->get_js_tmpls(), $plugin->get_plugindir_path() . '/' . $module . '/admin/views/' . $module);
		} else {
			$public          = $plugin->get_name() . '/' . $module . '/layouts/' . $layout;
			$public_fallback = $plugin->get_plugindir_path() . '/' . $module . '/public/views/layouts/' . $layout;
			$tinc            = new PL_Template_Include( $public, $public_fallback );
		}
	}

	public function dispatch( $route ) {

		if( is_callable( $route->controller, $route->action ) ) {
			$this->controller = new $route->controller( $this->params );
			$this->controller->{$route->action}();
		} else {
			//TODO throw an exception
			error_log( "{$route->action} action in {$route->controller} controller doesn't exist!" );
		}
	}

	public function render( $plugin_name ) {

		$plugin = PL_Plugin_Registry::get_instance()->get( $plugin_name );
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

		//README view has to be compiled before the layout is loaded
		//otherwise admin acitons won't render anything
		$this->compile_view( $plugin['instance'] );
		$this->load_layout( $plugin['instance'] );
	}

	public function compile_view( $plugin ) {
		$view = new PL_View();
		$view->set_data( (array) $this->controller->get_view_data() );
		$view->set_path( $this->controller->template_path( $plugin ) );
		$this->compiled_view = $view->compile();
	}

	public function wp_head() {
		echo '<meta name="PL-CSRF" content="' . wp_create_nonce( 'pl-action' ) . '">';
	}

	public function print_view() {
		echo $this->compiled_view;
	}
}
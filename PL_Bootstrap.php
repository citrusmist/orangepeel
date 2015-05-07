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

	protected $route;

	public function __construct( $route, $plugin ) {

		$this->route  = $route;
		//README: Instead of a Module Controller should we just have a singleton
		//front controller which we can configure withing the module bootstrap, 
		//so that dependency is inverted. We could just pass the endpoint, module slug,
		//controller(optional) name and action name(options)
		//If not optional args passed default behaviour assumed
		// $this->controller = new PL_Module_Controller( $this );
		$this->plugin = $plugin;

		$this->init();
	}

	abstract protected function init();

	public function get_controller_instance( $name ){

		$name = strtolower($name) . '_controller';

		if( isset( $this->$name ) ){
			return $this->$name;
		}
	}


	public function set_controller_instance( $name, $instance ){

		$name = strtolower( $name ) . '_controller';

		return $this->$name = $instance;
	}

	//Global dispatcher user for dispatching requests based on WP's query vars
	//Shouldn't be used for in page templates
	public function get_dispatcher() {

		$key = '';

		//@TODO: Remove dependency on a constant and just rely on
		//mapping key
		if( defined( get_called_class() . '::QUERY_VAR' ) ) {
			$key = constant( get_called_class() . '::QUERY_VAR' );
		}	else {
			$key = $this->get_relevant_mapping_key();
			$key = $key['key'];
		}	

		if( null === $this->dispatcher ) {
			$this->dispatcher = new PL_Dispatcher( $key, $_REQUEST );
		}

		return $this->dispatcher;
	}


	//TODO: Refactor
	//Request mapping and routing should probalbly be in it's own set
	//of classes which individual bootstrap would just register
	public function add_query_mapping( $name, $value = 'default', $action = '', $scope = 'public' ) {
		
		if( empty( $action ) ) {
			if( $value == 'default' ){
				$action = $name;
			} else {
				$action = $value;
			}
		}

		$this->query_map[$name][$value] = array(
			'value'  => $value,
			'action' => $action,
			'scope'  => $scope
		);
	}


	public function get_query_map() {
		return $this->query_map;
	}


	public function is_request_relevant( $wp_query ) {

		$relevant_key = $this->get_relevant_mapping_key( $wp_query );
		return !empty( $relevant_key );
	}


	public function get_relevant_mapping( $wp_query ) {

		$relevant_key = $this->get_relevant_mapping_key( $wp_query );

		if( empty( $relevant_key ) ) {
			return false;
		}

		$mapping = $this->query_map[$relevant_key['key']][$relevant_key['value']];
		$mapping['key'] = $relevant_key['key'];

		return $mapping;
	}


	public function get_relevant_mapping_key( $query = null ) {

		if( isset( $this->_relevant_key ) ) {
			return  $this->_relevant_key;
		}

		global $wp_query;

		if( !isset( $query ) ) {
			$query = $wp_query;
		}

		return $this->compute_relevant_mapping_key( $query );
	}

	private function compute_relevant_mapping_key( $query ) {

		$relevant_keys = array_intersect_key( $this->query_map, $query->query_vars );

		if( empty( $relevant_keys ) ) {
			return false;
		}

		/*
		 * As requests are meant to be atomic return after the 
		 * first satisfactory mapping is found as there shouldn't
		 * be two of the same
		 */
		foreach ( $relevant_keys as $key => $mappings ) {

			foreach( $mappings as $value => $mapping ) {

				$key_found = $mapping['scope'] == 'default' 
					|| ( $mapping['scope'] == 'public' && !is_admin() )
					|| ( $mapping['scope'] == 'admin' && is_admin() );

				//only check values if there is more than one mapping for a
				//particulat key
				if( count( $mappings ) == 1 ) {
					$key_value_found = true;
				} else {
					$key_value_found = ( $value == $query->query_vars[$key] )
						|| ( $value == 'default' && empty( $query->query_vars[$key] ) );	
				}
				

				// log_me( $this->query_map );

				// log_me($mapping['value']);
				// log_me($query->query_vars[$key]);

				if( $key_found && $key_value_found ) {
					$this->_relevant_key = array( 
						'key'   => $key,
						'value' => $value 
					);
					return $this->_relevant_key;
				}
			}
		}
	}


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		//Prevent abstract class from instantiating
		if( get_called_class() == __CLASS__ ){
			return false;
		}

		// If the single instance hasn't been set, set it now.
		if ( null == static::$instance ) {
			static::$instance = new static;
		}

		return static::$instance;
	}


	public static function dispatcher() {
		
		$instance = static::get_instance();

		return $instance->get_dispatcher();
	}


	public static function get_controller( $type ){

		$bootstrap = static::get_instance();

		if( $bootstrap->get_controller_instance( $type ) !== null ){
			return $bootstrap->get_controller_instance( $type );
		}

		$controller_class = '';

		if( defined( get_called_class() . '::' . strtoupper( $type ) . '_CONTROLLER' ) ){
			// return constant( get_called_class() . '::' . strtoupper( $type ) . '_CONTROLLER' );
			$controller_class = constant( get_called_class() . '::' . strtoupper( $type ) . '_CONTROLLER' );
		} else {
			$controller_class = static::controller_class( $type );
		}

		$controller = new $controller_class;
		$controller->set_bootstrap( get_called_class() );
		
		$bootstrap->set_controller_instance( $type, $controller );

		return $controller;
	}


	private static function controller_class( $type ){

		$type = ucfirst( strtolower( $type ) );

		$base = str_replace( 'Bootstrap', '', get_called_class() );
		$base = trim( $base, '_' );
		$base = explode( '_', $base );
		$base[] = ucfirst( 'controller' );
		array_splice( $base, 1, 0, $type );

		return implode( '_', $base );
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


/*	public static function get_plugin_class() {

		if( defined ( get_called_class() . '::PLUGIN' ) ){
			return constant( get_called_class() . '::PLUGIN' );
		}

		$prefix = explode( '_', get_called_class() );
		$prefix = array_shift( $prefix );

		return $prefix . '_Plugin';
	}
*/

/*	public static function get_plugin() {

		$class = self::get_plugin_class();

		return call_user_func( array( $class, 'get_instance' ) );
	}
*/

	public function get_action_path( $action ) {

		$plugin_class = static::get_plugin_class();
		$action_path = call_user_func_array( 
			array( $plugin_class, 'get_action_path' ), 
			array( static::get_slug(), $action ) 
		);

		return $action_path;
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
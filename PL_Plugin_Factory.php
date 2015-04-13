<?php 

abstract class PL_Plugin_Factory {
	
	/**
	 * Holds names of all modules used by the plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $modules;

	/**
	 * Holds path to root directory of plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugindir_path;

	public function __construct() {}

	public function bootstrap() {
		$plugin_class = substr_replace( get_called_class(), '',  strrpos( get_called_class(), '_' ) );
		$plugin = new $plugin_class();
		$plugin->run();
	}

	public function build($value='') {
		
	}

	/**
	 * Return an instance of this class. Registers autoloader responsible for
	 * loading of module files
	 *
	 * @since     1.0.0
	 *
	 * @param     array    Array of module name strings
	 */
	public function register_modules( $mods ) {
		//TODO: Maybe check that module folder exists
		$this->modules = $mods;
	}


	/**
	 * General plugin activator. (use period)
	 *
	 * Calls plugin activation method and cycles through the modules
	 * and calls their respecive activation methods.
	 *
	 * @since    0.0.1
	 */
	public static function activate() {
		
		$instance  = static::get_instance();
		$mods      = $instance->get_modules();
		$className = substr_replace( get_called_class(), '_Activator',  strrpos( get_called_class(), '_' ) );
		
		//Plugin deactivator
		if( method_exists( $className, 'activate' ) ) {
			call_user_func( array( $className, 'activate' ) );
		}

		foreach ( $mods as $mod ) {
			$ns = '\\' . $mod;

			if( method_exists( $ns . '\Bootstrap', 'activate' ) ) {
				call_user_func( array( $ns . '\Bootstrap', 'activate' ) );
			}
		}
	}

	/**
	 * General plugin deactivator. (use period)
	 *
	 * Calls plugin deactivation method and cycles through the modules
	 * and calls their respecive deactivation methods.
	 *
	 * @since    0.0.1
	 */
	public static function deactivate() {

		$instance  = static::get_instance();
		$mods      = $instance->get_modules();
		$className = substr_replace( get_called_class(), '_Deactivator',  strrpos( get_called_class(), '_' ) );

		//Plugin deactivator
		if( method_exists( $className, 'deactivate' ) ) {
			call_user_func( array( $className, 'deactivate' ) );
		}
		
		//Module deactivators
		foreach ( $mods as $mod ) {
			$ns = '\\' . $mod;

			if( method_exists( $ns . '\Bootstrap', 'deactivate' ) ) {
				call_user_func( array( $ns . '\Bootstrap', 'deactivate' ) );
			}
		}
	}

	/**
	 * Load file based on class name.
	 *
	 * In case the classname is namespaced it checks to see if file exists
	 * as part of as module. Alternatively it looks in the includes folder for a 
	 * file named according to WP naming standards.
	 *
	 * @since     1.0.0
	 *
	 * @param     string    Name of class to be loaded
	 */
	protected function autoloader( $className ) {

		log_me( __METHOD__ );

		if( stripos( $className, '\\' ) ) {
			$path = str_replace( '\\', '/', $className );
			$path = $this->get_plugindir_path() . '/' . $path . '.php';
		} else {
			$path = strtolower( str_replace( '_', '-', $className ) );
			$path = $this->get_plugindir_path() . '/includes/class-' . $path . '.php';
		}

		if( file_exists( $path ) ) {
			require( $path );
			return true;
		}
	
		return false;
	}

	public function get_plugindir_path() {

		if ( empty( $this->plugindir_path ) ) {
			$reflector = new ReflectionClass( get_called_class() );

			//remove trailing slash
			$this->plugindir_path = rtrim( plugin_dir_path( $reflector->getFileName() ), '/' );
		}

		return $this->plugindir_path;
	}

	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Return an instance of this class. Registers autoloader responsible for
	 * loading of module files
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		if( !property_exists( get_called_class(), 'instance' ) ) {
			error_log( get_called_class() . ' is missing a $instance static property' );
			return;
		}

		// If the static instance hasn't been set, set it now.
		if ( null == static::$instance ) {
			static::$instance = new static;
			spl_autoload_register( array( static::$instance, 'autoloader' ) );
			$reflector = new ReflectionClass( get_called_class() );


			register_activation_hook( $reflector->getFileName(), array( get_called_class(), 'activate' ) );
			register_deactivation_hook( $reflector->getFileName(), array( get_called_class(), 'deactivate' ) );
		}

		return static::$instance;
	}
}
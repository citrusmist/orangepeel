<?php 

abstract class PL_Plugin_Factory {
	
	/**
	 * Holds names of all modules used by the plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $modules = array();

	/**
	 * Holds path to root directory of plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugindir_path;

	public function __construct() {}

	public function bootstrap( $version ) {

		$route = PL_Route::get_instance(); 
		$plugin_class = substr_replace( get_called_class(), '',  strrpos( get_called_class(), '_' ) );
		$plugin = new $plugin_class( $version, $this->plugindir_path, $this->modules, $route );

		//This should only be called once all the plugins have been instantiated and register their routes
		$fc     = PL_Front_Controller::get_instance();

		//Don't acutally need this, we are only calling it so that it is able to add_action to 'init' hook
	}


	/**
	 * Return an instance of this class. Registers autoloader responsible for
	 * loading of module files
	 *
	 * @since     1.0.0
	 *
	 * @param     array    Array of module name strings
	 */
	public function register_modules( $modules ) {

		foreach ( $modules as $module ) {

			$class = '\\' . $module . '\Bootstrap';

			if( class_exists( $class ) ) {
				$this->modules[$module] = $class;
			} else {
				error_log( "Could not register module {$module} because it doesn't have a Bootstrap class" );
			}
		}
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
		$class     = substr_replace( get_called_class(), '_Activator',  strrpos( get_called_class(), '_' ) );
		
		//Plugin deactivator
		if( method_exists( $class, 'activate' ) ) {
			call_user_func( array( $class, 'activate' ) );
		}

		foreach ( $mods as $name => $class ) {

			if( method_exists( $class, 'activate' ) ) {
				call_user_func( array( $class, 'activate' ) );
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
		$class     = substr_replace( get_called_class(), '_Deactivator',  strrpos( get_called_class(), '_' ) );

		//Plugin deactivator
		if( method_exists( $class, 'deactivate' ) ) {
			call_user_func( array( $class, 'deactivate' ) );
		}
		
		//Module deactivators
		foreach ( $mods as $name => $class ) {

			if( method_exists( $class, 'deactivate' ) ) {
				call_user_func( array( $class, 'deactivate' ) );
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
	protected function autoloader( $class ) {

		if( stripos( $class, 'controller' ) ) {
			$module = explode( '\\', $class );
			

		} elseif( stripos( $class, '\\' ) ) {
			$path = str_replace( '\\', '/', $class );
			$path = $this->get_plugindir_path() . '/' . $path . '.php';
			log_me( $path );
		} else {
			$path = strtolower( str_replace( '_', '-', $class ) );
			$path = $this->get_plugindir_path() . '/includes/class-' . $path . '.php';
		} 

		if( file_exists( $path ) ) {
			require( $path );
			return true;
		}
	
		return false;
	}

	/**
	 * Returns absolute path to the plugin folder without the trailing slash.
	 */
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
	 * loading of module files and plugin activation and deactivation hooks.
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
			$reflector = new ReflectionClass( get_called_class() );

			spl_autoload_register( array( static::$instance, 'autoloader' ) );
			
			register_activation_hook( $reflector->getFileName(), array( get_called_class(), 'activate' ) );
			register_deactivation_hook( $reflector->getFileName(), array( get_called_class(), 'deactivate' ) );
		}

		return static::$instance;
	}
}
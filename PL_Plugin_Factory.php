<?php 

abstract class PL_Plugin_Factory {

	/**
	 * Holds names of all modules to be used by the plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $module_names = array();

	/**
	 * Holds path to root directory of plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugindir_path;

	/**
	 * Holds name of plugin class
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_class;

	/**
	 * Registry of all initialised plugins and modules 
	 * based on Peel framework
	 *
	 * @since    1.0.0
	 *
	 * @var      PL_Plugin_Registry
	 */
	protected $registry;

	public function __construct() {}

	public function bootstrap( $version ) {

		$plugin_class = $this->get_plugin_class();
		$plugin       = new $plugin_class( $version, $this->plugindir_path, \PL_Router::get_instance() );
		$modules      = $this->load_modules( $plugin );
		$registry     = PL_Plugin_Registry::get_instance();

		$registry->set( $plugin, $modules );
		
		//This should only be called once all the plugins have been instantiated and registered their routes
		$fc     = PL_Front_Controller::get_instance();
	}

	/**
	 * Return an instance of this class. Registers autoloader responsible for
	 * loading of module files.
	 *
	 * @since     1.0.0
	 *
	 * @param     array    Map of module name and their correspondin class name
	 */
	public function register_modules( $modules ) {

		foreach ( $modules as $name ) {

			$class = '\\' . ucfirst( $name ) . '\Bootstrap';

			if( class_exists( $class ) ) {
				$this->module_names[$name] = $class;
			} else {
				error_log( "Could not register module {$name} because it doesn't have a Bootstrap class" );
			}
		}
	}

	/**
	 * Instantiates modules from their class names.
	 *
	 * @since     1.0.0
	 *
	 * @param     array    Array of module bootstrap objects
	 */
	private function load_modules( $plugin ) {

		$modules = array();

		foreach( $this->module_names as $name => $class ) {
			$modules[$name] = new $class( $plugin );
		}

		return $modules;
	}

	/**
	 * Returns plugin class name based on class name of the factory
	 */

	public function get_plugin_class() {

		if ( ! isset( $this->plugin_class ) ) {
			$this->plugin_class = substr_replace( get_called_class(), '', strrpos( get_called_class(), '_' ) );
		}

		return $this->plugin_class;
	}

	/**
	 * General plugin activator.
	 *
	 * Calls plugin activation method and cycles through the modules
	 * and calls their respecive activation methods.
	 *
	 * @since    0.0.1
	 */
	public static function activate() {
		
		$instance        = static::get_instance();
		$activator_class = substr_replace( get_called_class(), '_Activator',  strrpos( get_called_class(), '_' ) );
		$plugin_class    = $instance->get_plugin_class(); 
		$plugin          = new $plugin_class( '', $instance->get_plugindir_path(), \PL_Router::get_instance() );
		$mods            = $instance->get_modules( $plugin );
		
		//Plugin deactivator
		if( method_exists( $activator_class, 'activate' ) ) {
			call_user_func( array( $activator_class, 'activate' ) );
		}

		foreach ( $mods as $name => $class ) {

			if( method_exists( $class, 'activate' ) ) {
				call_user_func( array( $class, 'activate' ), $plugin );
			}
		}
	}

	/**
	 * General plugin deactivator.
	 *
	 * Calls plugin deactivation method and cycles through the modules
	 * and calls their respecive deactivation methods.
	 *
	 * @since    0.0.1
	 */
	public static function deactivate() {

		$instance          = static::get_instance();
		$deactivator_class = substr_replace( get_called_class(), '_Activator',  strrpos( get_called_class(), '_' ) );
		$plugin_class      = $instance->get_plugin_class(); 
		$plugin            = new $plugin_class( '', $instance->get_plugindir_path(), \PL_Router::get_instance() );
		$mods              = $instance->get_modules( $plugin );

		//Plugin deactivator
		if( method_exists( $deactivator_class, 'deactivate' ) ) {
			call_user_func( array( $deactivator_class, 'deactivate' ) );
		}
		
		//Module deactivators
		foreach ( $mods as $name => $class ) {

			if( method_exists( $class, 'deactivate' ) ) {
				call_user_func( array( $class, 'deactivate' ), $plugin );
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
	 * README If two plugins are making use of the same module we are screwed
	 * Does that mean that we should add plugin namesspace to each module...
	 * That would make these modules less portable
	 * 
	 *
	 * @since     1.0.0
	 *
	 * @param     string    Name of class to be loaded
	 */
	protected function autoloader( $class ) {

		if( stripos( $class, '\\' ) !== false ) {
		
			$parts      = explode( '\\', strtolower( $class ) );
			$module     = $parts[0];
			$class_name = $parts[1];
			$path       = $this->get_plugindir_path() . '/' . $module . '/';

			if ( stripos( $class_name, 'controller' ) !== false ) {
				
				$parts = explode( '_',  $class_name );
				
				foreach( $parts as $i => $part ) {
					
					if ( $part == 'controller' ) {
						$part .= 's';
					}

					$path .= ( count( $parts ) - 1 > $i ) ? $part . '/' : $part . '.php';
				}
			
			} else {
				$path .= $class_name . '.php';
			}

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

	public function get_modules( $plugin ) {
		$registry = PL_Plugin_Registry::get_instance()->get( $plugin->get_name() );
		return $registry['modules'];
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
		if ( null === static::$instance ) {
			static::$instance = new static;
			$reflector = new ReflectionClass( get_called_class() );

			spl_autoload_register( array( static::$instance, 'autoloader' ) );
			
			register_activation_hook( $reflector->getFileName(), array( get_called_class(), 'activate' ) );
			register_deactivation_hook( $reflector->getFileName(), array( get_called_class(), 'deactivate' ) );
		}

		return static::$instance;
	}
}
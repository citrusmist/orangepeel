<?php 

/**
 * General plugin class. 
 *
 * This class imposes a singleton pattern on classes which extend it. Only one 
 * instance of a derived class should be able to be instantiated at time.
 *
 * Maintains a unique identifier of plugin as well as the current version 
 * of the plugin. Provides an interface for instantiating modules.
 */
abstract class PL_Plugin {
	
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      projects_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Holds path to root directory of plugin
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugindir_path;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;


	/**
	 * The array of modules registered with this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $modules    The modules this plugin consists of
	 */
	protected $modules;


	public function __construct() {
		
		$this->loader = new PL_Plugin_Loader();

		$this->set_locale();
		$this->load_modules();
	}

	public function get_plugindir_path() {

		if ( empty( $this->plugindir_path ) ) {
			$reflector = new ReflectionClass( get_called_class() );
			//remove trailing slash

			$path = rtrim( plugin_dir_path( $reflector->getFileName() ), '/' );

			//remove a folder depth
			$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
			$this->plugindir_path = rtrim( $path, '/' );
		}

		return $this->plugindir_path;
	}

	/**
	 * Return the plugin path.
	 *
	 * @since    0.0.1
	 *
	 * @return    Path to root directory of the plugin
	 */
	public static function get_path() {
		return $this->get_plugindir_path();
	}

	public static function add_action_path( $slug, $action, $path = '' ) {
		
		if( !property_exists( get_called_class(), 'action_paths' ) ) {
			error_log( get_called_class() . '::$action_path static property isn\'t declared' );
			return;
		}

		if( empty( $path ) ) {
			$path = '/' . $action;
		}

		static::$action_paths[$slug][$action] = $path;
	}

	public static function get_action_path( $slug, $action ) {

		if( !property_exists( get_called_class(), 'action_paths' ) ) {
			error_log( get_called_class() . '::$action_path static property isn\'t declared' );
			return;
		}

		if( !isset( static::$action_paths[$slug] ) ) {
			return '';
		} elseif( !isset( static::$action_paths[$slug][$action] ) ) {
			return '';
		}

		$path = site_url( static::$action_paths[$slug][$action] );
		return $path;
	}

	protected function register_modules( $mods ) {
		if( is_array( $mods ) ) {
			$this->modules = $mods;
		} else if (is_string( $mods ) ) {
			$$this->modules = array( $mods );
		}
	}

	protected function add_module( $mod ) {

		if ( !is_string( $mod ) ) {
			return  false;
			//TODO throw exception maybe or something
		}

		if( is_array( $modules ) ) {
			$this->modules[] = $mod;
		} else {
			$this->modules = array( $mod );
		}
	}

	protected function load_modules() {
		foreach( $this->modules as $module ) {
			# code...
		}
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->get_plugin_name();
	}


	public static function get_slug() {
		return self::get_instance()->get_plugin_slug();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    projects_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.0.1
	 */
	public function run() {
		$this->loader->run();
		$this->load_modules();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the SS_Shows_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new PL_Plugin_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$plugin_i18n->set_path( $this->get_plugindir_path() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}
}
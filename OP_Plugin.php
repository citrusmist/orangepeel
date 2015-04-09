<?php 

/**
 * 
 */
abstract class OP_Plugin {
	
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
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		if( !property_exists( get_called_class(), 'instance' ) ) {
			error_log( get_called_class() . ' is missing a $instace static property' );
			return;
		}

		// If the static instance hasn't been set, set it now.
		if ( null == static::$instance ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	public function get_plugindir_path() {
	
		if ( empty( $this->pluginroot_path ) ) {
			//remove trailing slash
			$path = rtrim( plugin_dir_path( __FILE__ ), '/' );

			//remove 2 folder depths
			$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
			$path = rtrim( $path, '/' );
			$this->plugindir_path = substr( $path, 0, strripos( $path, '/' ) + 1 );
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
		return static::get_instance()->get_plugindir_path();
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


	public static function get_slug(){
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
}
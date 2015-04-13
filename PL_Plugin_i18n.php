<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       
 * @since      0.0.1
 *
 * @package    Peel
 * @subpackage Peel
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.0.1
 * @package    Peel
 * @subpackage Peel
 * @author     Milos Soskic <milos@citrus-mist.com>
 */
class PL_Plugin_i18n {

	/**
	 * The domain specified for this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $domain    The domain identifier for this plugin.
	 */
	private $domain;

	/**
	 * Relative path to folder containing translated strings for this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $path    The path identifier for this plugin.
	 */
	private $path;

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.0.1
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->domain,
			false,
			$this->path
		);
	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @since    0.0.1
	 * @param    string    $domain    The domain that represents the locale of this plugin.
	 */
	public function set_domain( $domain ) {
		$this->domain = $domain;
	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @since    0.0.1
	 * @param    string    $domain    The domain that represents the locale of this plugin.
	 */
	public function set_path( $lang_path ) {
		$this->path = $lang_path;
	}
}

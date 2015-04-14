<?php 
/**
 * @package Peel
 * @version 0.2
 */

/*
Plugin Name: Peel
Plugin URI: https://github.com/citrusmist/peel
Description: MVC inspired plugin framework
Author: Milos Soskic
Version: 0.2
Author URI: http://citrus-mist.com
*/

namespace Peel;

/*----------------------------------------------------------------------------*
  OrangePeel subl Framework
 *----------------------------------------------------------------------------*/
// require_once( plugin_dir_path( __FILE__ ) . 'CMView.class.php' );
require_once( plugin_dir_path( __FILE__ ) . 'helper_funcs.php' );

require_once( plugin_dir_path( __FILE__ ) . 'PL_Plugin.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Plugin_Factory.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Plugin_Loader.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Plugin_i18n.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Module_Registry.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Template_Include.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Recordable.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Validatable.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Dispatcher.php' );
// require_once( plugin_dir_path( __FILE__ ) . 'includes/PL_Utility.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Inflector.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Flash.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Validator.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Model.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_CPT_Model.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Postmeta_Model.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Std_Model.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_User.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_View.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Module_Controller.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Controller.php' );
require_once( plugin_dir_path( __FILE__ ) . 'PL_Bootstrap.php' );

<?php 

/**
 * 
 */
class PL_Admin_Page_Factory {
	
	protected static $instance = null;
	protected $resource_actions;
	protected $resources = array();

	private function __construct() {
		$this->resource_actions = array(
			'index'  => array( 'path' => '' ),
			'new'    => array( 'path' => 'new' ),
			'edit'   => array( 'path' => 'edit' ),
		);	
		$this->register_callbacks();
	}

	public static function get_instance() {
		if ( self::$instance === null ) {
      self::$instance = new self();
    }

    return self::$instance;
	}

	protected function register_callbacks() {
		add_action( 'admin_menu', array( $this, 'create_pages' ) );
	}

	public function resource( $name, $args ) {

		$defaults = array(
			'capability'    => 'publish_posts', 
			'menu_icon'     => 'dashicons-admin-generic',
			'menu_position' => 12
		);

		$args = wp_parse_args( $args, $defaults );

		$this->resources[$name] = $args;
	}

	public function create_pages() {

		foreach( $this->resources as $name => $args ) {

			$name_titleized = \PL_Inflector::titleize( $name );

			add_menu_page(
				$name_titleized,
				$name_titleized,
				$args['capability'],
				$name,
				function() use( &$args ) {
					$fc = \PL_Front_Controller::get_instance();
					$fc->dispatch( (object) array(
						'controller' => $args['controller'], 
						'action'     => 'index' 
					) );
					$fc->render( $args['plugin'] );
				},
				$args['menu_icon'],
				$args['menu_position']
			);

			foreach( $this->resource_actions as $action => $props ) {
				
				switch ( $action ) {
					case 'index':
						$submenu_title = 'All ' . $name_titleized;
						break;
					case 'new':
						$submenu_title = 'Add New';
						break;
					default:
						break;
				}

				add_submenu_page( 
					$name, 
					$submenu_title, 
					$submenu_title, 
					$args['capability'], 
					$name . '_' . $props['path'], 
					function () use( &$args, &$name ) {
						$fc = \PL_Front_Controller::get_instance();
						$fc->dispatch( (object) array(
							'controller' => $args['controller'], 
							'action'     => 'index' 
						) );
						$fc->render( $args['plugin'] );
					}
				);
			}
		}

	}
}
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
			'index'  => array( 'path' => '',     'in_menu' => true ),
			'new'    => array( 'path' => 'new',  'in_menu' => true ),
			'edit'   => array( 'path' => 'edit', 'in_menu' => false ),
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
			'menu_position' => 12,
			'only'					=> array(),
		);	
		$args = wp_parse_args( $args, $defaults );

		$this->resources[$name] = $args;
	}

	public function create_pages() {

		foreach( $this->resources as $name => $args ) {

			$name_titleized = \PL_Inflector::titleize( $name );

			if( ! empty( $args['only'] ) ) {
				$action_pages = array_intersect_key( $this->resource_actions, array_flip( $args['only'] ) );
			} else {
				$action_pages = $this->resource_actions;
			}

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

			unset( $action_pages['index'] );

			foreach( $action_pages as $action => $props ) {

				$handle = $name . '_' . $props['path'];
				
				if( $props['in_menu'] == true ) {

					switch ( $action ) {
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
						$handle, 
						function () use( &$args, &$action ) {
							$fc = \PL_Front_Controller::get_instance();
							$fc->dispatch( (object) array(
								'controller' => $args['controller'], 
								'action'     => $action 
							) );
							$fc->render( $args['plugin'] );
						}
					);

				} else {
					$this->add_hidden_page( $handle, array_merge( $args, array( 'action' => $action ) ) );
				}
			}
		}
	}

	public function add_hidden_page( $handle, $args ) {

		global $_registered_pages;
		// It looks like there isn't a more native way of creating an admin page without
    // having it show up in the menu, but if there is, it should be implemented here.
    // To do: set up capability handling and page title handling for these pages that aren't in the menu
    $hookname = get_plugin_page_hookname( $handle, '' );
    if( !empty( $hookname ) ) {
      add_action( $hookname, function () use( &$args ) {
				$fc = \PL_Front_Controller::get_instance();
				$fc->dispatch( (object) array(
					'controller' => $args['controller'], 
					'action'     => $args['action'] 
				) );
				$fc->render( $args['plugin'] );
			} );
    }
    $_registered_pages[$hookname] = true;		
		# code...
	}
}
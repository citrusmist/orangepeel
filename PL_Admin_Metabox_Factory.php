<?php 

/**
 * 
 */
class PL_Admin_Metabox_Factory {
	
	protected static $instance = null;
	protected $metaboxes = array();

	private function __construct() {
		$this->register_callbacks();
	}

	public static function get_instance() {
		if ( self::$instance === null ) {
      self::$instance = new self();
    }

    return self::$instance;
	}

	protected function register_callbacks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	public function metabox( $name, $args ) {

		$defaults = array(
			'id'       => $name . '-' . $args['action'],
			'title'    => ucfirst( $args['action'] ),
			'screen'   => $name,
			'context'  => 'normal',
			'priority' => 'default',
			'params'   => array()
		);	
		$args = wp_parse_args( $args, $defaults );

		$this->metaboxes[] = $args;
	}

	public function add_meta_boxes() {

		foreach( $this->metaboxes as $key => $args ) {

			add_meta_box(
				$args['id'],
				$args['title'],
				function( $post, $metabox ) use( $args ) {

					$params = array_merge( 
						array( 'post' => $post ), 
						array( 'params' => $metabox['args'] ) 
					);

					$fc = \PL_Front_Controller::get_instance();

					// README: Should the params be passed through a 
					// dispatch function
					$fc->dispatch( 
						(object) array(
							'controller' => $args['controller'], 
							'action'     => $args['action'] 
						),
						$params
					);
					$fc->render( $args['plugin'] );
				},
				$args['screen'],
				$args['context'],
				$args['priority'],
				$args['params']
			);
		}
	}
}
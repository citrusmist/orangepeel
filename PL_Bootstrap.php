<?php 

/*
 * - All classes that belong to a certain plugin should have the 
 *   same prefix and words should be separated using underscores
 *   e.g. AP_Users_Bootstrap AP_Users_Controller
 * - This enables us to dynamically compute the name of the plugin 
 *   class without setting up loads of globals. It also allows us to
 *   move modules between plugins and only change the prefix 
 */


abstract class PL_Bootstrap {

	//Module Controller a la Rails Application Controller
	protected $controller;

	protected $plugin;

	protected $cpts = array();

	public function __construct( $plugin ) {

		//README: Instead of a Module Controller should we just have a singleton
		//front controller which we can configure withing the module bootstrap, 
		//so that dependency is inverted. We could just pass the endpoint, module slug,
		//controller(optional) name and action name(options)
		//If not optional args passed default behaviour assumed
		// $this->controller = new PL_Module_Controller( $this );
		$this->plugin = $plugin;

		$this->init();

		add_action( 'init', array( $this, 'register_cpts' ) );
	}

	/*
	 * Allows implementation to override the slug value by 
	 * setting a SLUG class constant, otherwise defaults to 
	 * the class name minus the '_Bootstrap'. For example
	 * AP_Users_Bootstrap defaults to slug being 'ap-users '
	 */
	public static function get_slug(){

		if( defined ( get_called_class() . '::SLUG' ) ){
			return constant( get_called_class() . '::SLUG' );
		}

		$slug = str_replace( 'bootstrap', '', strtolower( get_called_class() ) );
		$slug = str_replace( '_', '-', $slug );
		$slug = trim( $slug, '-' );
		return $slug;
	}

	public function register_cpts() {

		foreach ($this->cpts as $cpt => $args) {
			register_post_type( $cpt, $args );
		}
	}


	/*
	 * README
	 * Not sure if this belongs in Bootstrap class, feels like it maybe should be somewhere else...
	 * perhaps some kind of route factory and route resolver type of thing
	 */
	public function generate_cpt_builtin_routes( $slug, $args, $actions ) {
		
		$qv = array(
			'post_type' => $slug
		);

		if( $args['public'] === false || $args['rewrite'] === false ) {
			return;
		}

		$this->plugin->add_cpt_builtin_route( 
			$args['rewrite']['slug'] . '/{:slug}', 
			$actions['show'], 
			array_merge( $qv, array(
				'name' => ''
			) 
		) );

		if( $args['has_archive'] !== false ) {
			$this->plugin->add_cpt_builtin_route( 
				$args['has_archive'] === true ? $slug : $args['has_archive'], 
				$actions['index'], 
				$qv 
			);
		}
	}

	public function add_cpt( $slug, $args, $actions = 'default' ) {

		$plugin_slug = $this->plugin->get_name();
		$pl_slug     = \PL_Inflector::pluralize( $slug );

		$defaults = array(
			
			/**
			 * A short description of what your post type is. As far as I know, this isn't used anywhere 
			 * in core WordPress.  However, themes may choose to display this on post type archives. 
			 */
			'description'         => __( ucfirst( $pl_slug ), $plugin_slug ), // string
			
			/** 
			 * Whether the post type should be used publicly via the admin or by front-end users.  This 
			 * argument is sort of a catchall for many of the following arguments.  I would focus more 
			 * on adjusting them to your liking than this argument.
			 */
			'public'              => true, // bool (default is FALSE)
			
			/**
			 * Whether queries can be performed on the front end as part of parse_request(). 
			 */
			'publicly_queryable'  => true, // bool (defaults to 'public').
			
			/**
			 * Whether to exclude posts with this post type from front end search results.
			 */
			'exclude_from_search' => false, // bool (defaults to 'public')
			
			/**
			 * Whether individual post type items are available for selection in navigation menus. 
			 */
			'show_in_nav_menus'   => false, // bool (defaults to 'public')
			
			/**
			 * Whether to generate a default UI for managing this post type in the admin. You'll have 
			 * more control over what's shown in the admin with the other arguments.  To build your 
			 * own UI, set this to FALSE.
			 */
			'show_ui'             => true, // bool (defaults to 'public')
			
			/**
			 * Whether to show post type in the admin menu. 'show_ui' must be true for this to work. 
			 */
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			
			/**
			 * Whether to make this post type available in the WordPress admin bar. The admin bar adds 
			 * a link to add a new post type item.
			 */
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			
			/**
			 * The position in the menu order the post type should appear. 'show_in_menu' must be true 
			 * for this to work.
			 */
			'menu_position'       => null, // int (defaults to 25 - below comments)
			
			/**
			 * The URI to the icon to use for the admin menu item. There is no header icon argument, so 
			 * you'll need to use CSS to add one.
			 */
			// 'menu_icon'           => 'dashicons-slides', // string (defaults to use the post icon)
			
			/**
			 * Whether the posts of this post type can be exported via the WordPress import/export plugin 
			 * or a similar plugin. 
			 */
			'can_export'          => true, // bool (defaults to TRUE)
			
			/**
			 * Whether to delete posts of this type when deleting a user who has written posts. 
			 */
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			
			/**
			 * Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts. 
			 */
			'hierarchical'        => false, // bool (defaults to FALSE)
			
			/** 
			 * Whether the post type has an index/archive/root page like the "page for posts" for regular 
			 * posts. If set to TRUE, the post type name will be used for the archive slug.  You can also 
			 * set this to a string to control the exact name of the archive slug.
			 */
			'has_archive'         => $pl_slug, // bool|string (defaults to FALSE)
			
			/**
			 * Sets the query_var key for this post type. If set to TRUE, the post type name will be used. 
			 * You can also set this to a custom string to control the exact key.
			 */
			'query_var'           => $slug, // bool|string (defaults to TRUE - post type name)
			
			/**
			 * Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for 
			 * you.  If set to FALSE, you'll need to roll your own handling of this by filtering the 
			 * 'map_meta_cap' hook.
			 */
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			
			/** 
			 * How the URL structure should be handled with this post type.  You can set this to an 
			 * array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite 
			 * rules from being created.
			 */
			'rewrite' => array(
			
				/* The slug to use for individual posts of this type. */
				'slug'       => $pl_slug, // string (defaults to the post type name)
			
				/* Whether to show the $wp_rewrite->front slug in the permalink. */
				'with_front' => false, // bool (defaults to TRUE)
			
				/* Whether to allow single post pagination via the <!--nextpage--> quicktag. */
				'pages'      => true, // bool (defaults to TRUE)
			
				/* Whether to create feeds for this post type. */
				'feeds'      => false, // bool (defaults to the 'has_archive' argument)
			
				/* Assign an endpoint mask to this permalink. */
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)
			),
			
			/**
			 * What WordPress features the post type supports.  Many arguments are strictly useful on 
			 * the edit post screen in the admin.  However, this will help other themes and plugins 
			 * decide what to do in certain situations.  You can pass an array of specific features or 
			 * set it to FALSE to prevent any features from being added.  You can use 
			 * add_post_type_support() to add features or remove_post_type_support() to remove features 
			 * later.  The default features are 'title' and 'editor'.
			 */
			'supports' => array(
			
				/* Post titles ($post->post_title). */
				'title',
			
				/* Post content ($post->post_content). */
				'editor',
			
				/* Featured images (the user's theme must support 'post-thumbnails'). */
				'thumbnail',
			),
			
			/**
			 * Labels used when displaying the posts in the admin and sometimes on the front end.  These 
			 * labels do not cover post updated, error, and related messages.  You'll need to filter the 
			 * 'post_updated_messages' hook to customize those.
			 */
			'labels' => array(
				'name'               => __( ucfirst( $pl_slug ),                   $plugin_slug ),
				'singular_name'      => __( ucfirst( $slug ),                      $plugin_slug ),
				'menu_name'          => __( ucfirst( $pl_slug ),                   $plugin_slug ),
				'name_admin_bar'     => __( ucfirst( $pl_slug ),                   $plugin_slug ),
				'add_new'            => __( 'Add New',                             $plugin_slug ),
				'add_new_item'       => __( 'Add New '. ucfirst( $slug ),          $plugin_slug ),
				'edit_item'          => __( 'Edit '. ucfirst( $slug ),             $plugin_slug ),
				'new_item'           => __( 'New '. ucfirst( $slug ),              $plugin_slug ),
				'view_item'          => __( 'View '. ucfirst( $slug ),             $plugin_slug ),
				'search_items'       => __( 'Search' . ucfirst( $pl_slug ),        $plugin_slug ),
				'not_found'          => __( 'No ' . $pl_slug . ' found',           $plugin_slug ),
				'not_found_in_trash' => __( 'No ' . $pl_slug . ' found in trash',  $plugin_slug ),
				'all_items'          => __( 'All'. ucfirst( $pl_slug ),            $plugin_slug ),
			
				/* Labels for hierarchical post types only. */
				'parent_item'        => __( 'Parent '. ucfirst( $slug ),           $plugin_slug ),
				'parent_item_colon'  => __( 'Parent '. ucfirst( $slug ),           $plugin_slug ),
			
				/* Custom archive label.  Must filter 'post_type_archive_title' to use. */
				'archive_title'      => __( ucfirst( $pl_slug ),                   $plugin_slug ),
			)
		);

		$this->cpts[$slug] = wp_parse_args( $args, $defaults );

		if( $actions == 'default' ) {
			$class =  \PL_Inflector::default_ctrl_class( $slug, $this );

			$actions = array(
				'index' => $class . '#index',
				'show'  => $class . '#show' 
			);
		}

		$this->generate_cpt_builtin_routes( $slug, $this->cpts[$slug], $actions );
	}

/*	protected static function setup_dependencies(){

		$public_controller = self::get_controller('public');
		
		if( isset ( $public_controller ) ){
			call_user_func_array( 
				array( $public_controller, 'set_bootstrap' ), 
				array( get_called_class() ) 
			);
		}
	}*/

}
<?php 

/**
* 
*/
// abstract class PL_CPT_Model implements PL_Recordable, PL_Validatable {
abstract class PL_CPT_Model extends PL_Model {
	
	protected $_post;
	protected $_post_format;
	protected $_file_props = null;

	// protected static $_data_desc = null;

	function __construct( $data = null ) {

		if( is_string( $data ) ){
			$data = intval( $data );
		}
		
		if ( is_int($data) ){
			$this->_post = WP_Post::get_instance($data);
			return;
		}

		if($data === null){
			//README: probably a bad idea to be initialising with stdClass but 
			//hey its also bad to smoke
			$this->_post = new WP_Post( new stdClass() );
			$this->_new_record = true;
			return;
		}

		if( is_a( $data, 'WP_Post' ) ) {
			$this->_post = $data;
		} else {

			foreach( $data as $key => $value ) {

				$do_unset = false;

				/* *
				 * @README
				 * I wonder if this could be simplified to 
				 * if( method_exists( $this, 'set_' . $key ) || property_exists( $this, $key ) ) {
				 *	$do_unset = true;
				 *  $this->$key = $value;
				 * }
				 * as the magic __set will result in calling $this->set_$name
				 * */
				if( method_exists( $this, 'set_' . $key ) ) {
					call_user_func_array( array( $this, 'set_' . $key  ), array( $value ) );
					$do_unset = true;
				} elseif( property_exists( $this, $key ) ) {
					$this->$key = $value;
					$do_unset = true;
				}

				if( $do_unset ){

 					if( is_object( $data ) ) {
 						unset( $data->$key );
					} elseif ( is_array( $data ) ) {
						unset( $data[$key] );
					}
				}
			}
	
			$this->_post = new WP_Post( ( object ) $data );

			if( !isset( $this->_post->ID ) ) {
				$this->_new_record = true;
			} 
		}

		$this->_errors = array();
	}


	public static function get_instance( $ID ) {
		return new self( $ID );
	}


	public static function find( $id ) {
		return self::get_instance( $id );
	}


	public static function find_by( $property, $value ) {

		// $associations = static::get_data_associations();
		$plural_prop = PL_Inflector::pluralize( $property );

		if( property_exists( get_called_class(), $property ) ) {

			return static::find_by_property( $property, $value );

		} 

		/*
		 * TODO:
		 *
		 * 1. Check if property exists on $this->_post
		 * 2. Check if $propertly metadata exists on posts
		 * 3. Check for associations
		 * 4. Use WP_Query or a custom query based on the result
		 */
	}


	public static function find_by_property( $property, $value ) { 

	}


	protected static function query( $args = array() ) { 

		$defaults = array(
			
			//Type & Status Parameters
			'post_type'   => static::post_type_from_classname(),
			'post_status' => 'publish',
			
			//Pagination Parameters
			'posts_per_page'         => 10,

			//Parameters relating to caching
			'no_found_rows'          => false,
			'cache_results'          => true,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
		);

		$args  = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );
		$posts = $query->get_posts();

		foreach ($posts as $key => $post) {
			$posts[$key] = new static( $post );
		}

		return $posts;
	}


	public static function all( $args = array() ) {

		$defaults = array(
			'posts_per_page' => -1,
			'order'          => 'DESC',
			'orderby'        => 'date',
		);

		$args  = wp_parse_args( $args, $defaults );

		return static::query( $args );
	}


	public static function post_type_from_classname() {
		
		$post_type = explode( '_', get_called_class() );

		array_shift( $post_type ); //remove prefix
		$post_type = implode( '-',  $post_type );
		return strtolower($post_type);
	}


	public function get_post_format() {

		if( !empty( $this->_post_format ) || !isset( $this->_post->ID ) ) {
			return $this->_post_format;
		}

		if( post_type_supports( $this->_post->post_type, 'post-formats' ) ) {
			$this->set_post_format( get_post_format( $this->_post->ID ) );
		} else {
			$this->set_post_format( 'standard' );
		}

		return $this->_post_format;
	}


	public function set_post_format( $format ) {
		$this->_post_format = $format;
	}


	public function has_file_props() {
		
		if ( $this->_file_props === null ) {
			$this->get_file_props();
		}

		return !empty( $this->_file_props );
	}


	public function get_file_props() {

		if( $this->_file_props !== null ) {
			return $this->_file_props;
		}

		$data_desc    = static::get_data_description();

		if( $this->_file_props === null ) {
			$this->_file_props = array();
		}

		foreach( $data_desc as $key => $desc ) {
			if( isset( $desc['format'] ) && $desc['format'] == 'file' ) {
				$this->_file_props[] = $key;
			}
		}

		return $this->_file_props;
	}

	
	// abstract protected static function describe_data();

	public function save() {

		$value = false;

		if( $this->post_status != 'draft' ){
			$this->validate();
		}

		if( $this->has_errors() ) { 
			return $value;
		}

		$do_attach = $this->_new_record;

		$this->save_file_props();

		$value = wp_insert_post( $this->_post->to_array(), true );

		if( is_wp_error( $value ) ){
			return $value;
		} else {

			$this->_post->ID = $value;

			if( $do_attach ) {
				$this->attach_files();
			}

			$this->save_meta();
			$this->save_post_format();

			//README must happen only after save_meta & save_post_format
			//we are only doing this so the date_created fields are right
			//perhaps we can get rid of it
			$this->_post = WP_Post::get_instance( $value );

			$this->_new_record     = false;
			$this->_changed_record = false;

			$value = $this;
		}

		return $value;
	}


	protected function save_meta() {

		log_me( __METHOD__ );

		$data_desc = self::get_data_description();

		foreach ( $data_desc as $key => $desc ) {

			if( property_exists( 'WP_Post', $key ) ) {
				continue;
			}

			if( is_a( $this->$key, 'PL_Postmeta_Model' ) ) {
				$this->$key->set_post( $this );
				$this->$key->save();
				continue;
			}

			if( !empty( $this->_post->$key ) ) {
				log_me( 'saving regular meta' );

				update_post_meta( $this->_post->ID, $key, $this->_post->$key );
			}
		}
		
	}


	public function save_post_format() {

		$do_nothing = $this->_post_format == 'standard'
			|| !post_type_supports( $this->_post->post_type, 'post-formats' );

		if( $do_nothing ) {
			return true;
		}

		return set_post_format( $this->_post->ID, $this->_post_format );
	}


	public function save_file_props() {

		if( ! $this->has_file_props() ) {
			return true;
		}
		
		$file_props = $this->get_file_props(); 
		$data_desc  = static::get_data_description();

		foreach( $file_props as $prop ) {

			if( method_exists( $this, 'save_' . $prop ) ) {
				call_user_func( array( $this, 'save_' . $prop ) );
			} else {
				$this->save_file_prop( $prop );
			}
		}
	}


	private function save_file_prop( $prop ) {

		log_me( __METHOD__ );

		$data_desc = static::get_data_description();
		$key       = isset( $data_desc[$prop]['key'] ) ? $data_desc[$prop]['key'] : $prop;
		$post_id   = isset( $this->_post->ID ) ? $this->_post->ID : 0;

		if( empty( $_FILES[$key]['name'] ) ) {
			return false;
		}

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$attachment_id = media_handle_upload( $key, $post_id );

		if( !is_wp_error( $attachment_id ) ) {
			$this->$prop = $attachment_id;
		}
	}


	//README: pretty hacky and ineffiecient
	//We only need this because our validation isn't very advanced
	private function attach_files() {

		$file_props = $this->get_file_props(); 
		$data_desc  = static::get_data_description();

		foreach( $file_props as $prop ) {

			if( empty( $this->$prop ) ) {
				continue;
			}

			$attachment = WP_Post::get_instance( $this->$prop );

			if( $attachment->post_type == 'attachment' ) {

				$attachment->post_parent = $this->ID;
				wp_insert_post( $attachment->to_array() );
			}
		}
	}

	public function delete() {

		$deleted_post =  wp_delete_post( $this->_post->ID, true );

		if ( is_object( $deleted_post ) ){
			log_me( $deleted_post );
		} else {
			return false;
		}
	}


	public function __set( $name, $value ){

		if( method_exists( $this, 'set_' . $name ) ) {
			call_user_func_array( array( $this, 'set_' . $name ), array( $value ) );
		} elseif( property_exists( $this, $name ) ){
			$this->$name = $value;
		} else{
			$this->_post->$name = $value;
		}

		$this->_changed_record = true;
	}


	public function __get( $name ) {

		if( method_exists( $this, 'get_' . $name ) ) {
			return call_user_func( array( $this, 'get_' . $name ) );
		} if ( property_exists( $this, $name ) ) {
			return $this->$name;
		} else{
			return $this->_post->$name;
		}
	}


	public function __isset( $name ) {

		if( method_exists( $this, 'get_' . $name ) ) {
			$value = call_user_func( array( $this, 'get_' . $name ) );
			return isset( $value );
		} elseif( property_exists( $this->_post, $name ) ) {
			return isset( $this->_post->$name );
		}

		//Finally query if there is metadata
		return isset( $this->_post->$name );
	}


	public function has_errors() {

		$props_errors = false;
		$data_desc    = static::get_data_description();

		foreach( $this->get_validatable_props() as $key => $value ) {
			if( $this->$key->has_errors() ) {
				$props_errors = true;
			}
		}

		return !empty( $this->_errors ) || $props_errors;
	}


	public function get_errors(){

		if( !$this->has_errors() ) {
			return false;
		} 

		$errors = $this->_errors;

		$props = $this->get_validatable_props();

		foreach( $props as $key => $value ) {
			if( $this->$key->has_errors() ) {
				$errors = array_merge( $errors, $this->$key->get_errors() );
			}
		}

		return $errors;
	}


	public function to_array(){

		$post = array( 'ID' => $this->ID );
		$description = self::describe_data();

		foreach( $description as $key => $value ) {

			if( isset( $this->$key ) ){
				$post[$key] = $this->$key;
			} else{
				$post[$key] = '';
			}
		}

		return $post;
	}


	public function validate() {

		$descriptions = self::get_data_description();

		foreach( $descriptions as $key => $description ) {

			$error     = null;
			$validator = new PL_Validator( $key, $this->$key, $description );
			$error     = $validator->validate();

		 	if( is_wp_error( $error ) ){
		 		$this->_errors[$key] = $error->get_error_message();
		 	}

			/***
			 * README:
			 * In case of CPT postmeta serves as if it was additional column data 
			 * (i.e. it enhances posts as if it was an super attribute rather than relationship)
			 * this is why it makes sense to validate it here
			 *
			 * TODO: Refactor
			 */
		 	if( !empty( $description['format'] ) && $description['format'] == 'object' ) {

		 		if( is_a( $this->$key, 'PL_Validatable' ) ) {
		 			$this->$key->validate();
		 		}
		 	}
		}
	}

	protected function get_validatable_props() {

		$data_desc = static::get_data_description();

		foreach( $data_desc as $key => $desc ) {

			if( !empty( $desc['format'] ) && $desc['format'] == 'object' ) {
				if( is_a( $this->$key, 'PL_Validatable' ) ) {

		 			$this->_validatable_props[$key] = true;
		 		}
			}
		}

		return $this->_validatable_props;
	}
/*
	public static function __callStatic( $name, $arguments ) {

		if( strpos( $name, 'find_by_') !== FALSE ){

			$prop = str_replace( 'find_by_' , '', $name );
			return static::find_by( $prop, $arguments[0] );
		}
	}*/
}
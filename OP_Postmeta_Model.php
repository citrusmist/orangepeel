<?php 

/**
 * Models based on data found in postmeta table. 
 *
 * README: The intention behing Post Meta is that it's only meant 
 * to be accessed through posts (ie it's only meant as a post enhancement)
 * The metadata is not meant to be a separate resource of the app, 
 * for this purpose we would need to use a custom table. However, metadata
 * like other data neeeds to be saved, deleted and validated
 */
abstract class OP_Postmeta_Model extends OP_Model {
	
	protected $_key;
	protected $_post;
	protected $_data;

	function __construct( array $data = array(), $post = null ) {

		$this->_post = $post;

		foreach( $data as $key => $value ) {
			if( !empty( $value ) ){
				$this->$key = $value;
			}
		}
	}


	public function get_key() {
		
		if( !isset( $this->_key ) ) {
			$this->_key = strtolower( get_class( $this ) );
		}	

		return $this->_key;
	}

	public function set_post( $post ) {

		if( is_a( $post, 'WP_Post') || is_a( $post, 'OP_CPT_Model' ) ) {
			$this->_post = $post;
		}
	}

	public function __get( $name ) {

		$value = null;

		if( method_exists( $this, 'get_' . $name ) ) {
			$value = call_user_func( array( $this, 'get_' . $name ) );
		} elseif( property_exists( $this, $name ) ) {
			$value = $this->$name;
		} elseif( isset( $this->_data[$name] ) ) {
			$value = $this->_data[$name];	
		}

		return $value;
	}

	public function __set( $name, $value ) {

		if( method_exists( $this, 'set_' . $name ) ) {
			call_user_func_array( array( $this, 'set_' . $name ), array( $value ) );
		} elseif( property_exists( $this, $name ) ){
			$this->$name = $value;
		} else{
			$this->_data[$name] = $value;	
		}

		$this->_changed_record = true;
	}

	public function __isset( $name ) {

		if( method_exists( $this, 'get_' . $name ) ) {
			$value = call_user_func( array( $this, 'get_' . $name ) );
			return isset( $value );
		}

		return isset( $this->_data[$name] );
	}

	public static function find( $id ) {
		return false;
	}
 
	public static function find_by( $property, $value ) {
		return false;
	}

	public static function all() {
		return false;
	}
	
	public function save() {

		$success = false;

		if( empty( $this->_post ) ) {
			return false;
		}

		if( !$this->has_errors() ) {
			$success = update_post_meta( $this->_post->ID, $this->key, $this->_data );
		}

		if( $success ) {
			$this->_changed_record = false;
		}

		return $success;
	}

 	public function delete() {

	}

}
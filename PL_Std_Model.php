<?php 

/**
 * 
 */

abstract class PL_Std_Model implements PL_Recordable, PL_Validatable {
	
	protected $_errors = array();

	private $_new_record = false;
	private $_changed_record = false;

	public function __construct( $props = array() ) {
		
		$assocs = static::get_data_associations();

		if( !empty( $assocs ) ) {

			foreach( $assocs as $name => $props ) {

				if( $props['cardinality'] == 'has_many' ) {
					$this->$name = array();
				} else{
					$this->$name = null;
				}

			}
		}
	}

	public static function find( $id = '' ) {

		global $wpdb;
		$table = self::get_table_name();

		$sql = $wpdb->prepare(
			" 
			SELECT * FROM {$wpdb->$table}
			WHERE id = %d
			",
			$id
		);

		$result = $wpdb->get_results( $sql );

		if( $result ){
			return new static( $result );
		} else {
			return $result;
		}
	}

	public function is_record_new() {

		if( property_exists( $this, 'id' ) ) {
			return empty( $this->id );
		}

		return $_new_record;
	}

	public function is_record_changed() {
		return $_changed_record;
	}

	protected static function query( $args = array() ) {

		$defaults = array(
			'select'     => '*',
			'join'       => '',
			'conditions' => '',
			'order'      => '',
			'group'      => '',
			'having'     => '',
			'offset'     => 0,
			'limit'      => ''
		);

		$args = wp_parse_args( $args, $defaults );

		global $wpdb;

		$sql = '';
		$table = static::get_table_name();
		$results = array();

		if ( ! empty( $args['select'] ) ) {

			$sql .= "
				SELECT {$args['select']}
				FROM $table ";
		}

		if( !empty( $args['join' ] ) ) {
			$sql .= $args['join'];
		}

		if( !empty( $args['conditions' ] ) ) {

			$sql .= "
				WHERE 1=1 {$args['conditions' ]} ";
		}

		if( !empty( $args['group' ] ) ) {

			$sql .= "
				GROUP BY {$args['group' ]} ";
		}
		
		if( !empty( $args['having' ] ) ) {

			$sql .= "
				HAVING {$args['having' ]} ";
		}

		if( !empty( $args['order' ] ) ) {

			$sql .= "
				ORDER BY {$args['order' ]} ";
		}

		if( !empty( $args['offset'] ) && empty( $args['limit' ] ) ) {

			$sql .= "
				LIMIT {$args['offset']}, 18446744073709551615 ";
		} elseif( !empty( $args['limit' ] ) ) {
			$sql .= "
				LIMIT {$args['offset']}, {$args['limit']}";
		}

		log_me( $sql );

		return $wpdb->get_results( $sql );
	}

	public static function all( $args = array() ) {

		$db_results = static::query( $args );

		if( $db_results === false ) {
			return $db_results;
		} else {
			foreach ( $db_results as $result ) {
				$results[] = new static( $result );
			}

			return $results;
		}
	}


	public static function find_by( $property, $value ) {

		global $wpdb;

		$associations = static::get_data_associations();
		$plural_prop = PL_Inflector::pluralize( $property );

		if( property_exists( get_called_class(), $property ) ){

			return static::find_by_property( $property, $value );

		} else if( array_key_exists( $plural_prop, $associations ) 
			&& property_exists( $wpdb, $plural_prop ) ) {

			return  static::find_by_association( $property, $value );

		}
	}


	public static function find_by_property( $property, $value ) {
		
		$results = static::all( array(
			'conditions' => ' AND ' . $property . '=' . $value
		) );

		if( strtolower( $property ) == 'id' ){
			return $results[0];
		} else{
			return $results;
		}
	}


	protected static function find_by_association( $property, $value ){

		global $wpdb;

		$associations = static::get_data_associations();
		
		$table       = static::get_table_name();
		$class       = strtolower( get_called_class() ); 
		$prop_table  = PL_Inflector::pluralize( $property );
		$association = $associations[$prop_table]; 

		if( property_exists( $value, 'ID') ){
			$prop_id_col = 'ID';
			$prop_id = $value->ID;
		} elseif ( property_exists( $value, 'id') ) {
			$prop_id_col = 'id';
			$prop_id = $value->id;
		}

		$select = "
			SELECT $table.*
			FROM $table ";

		$join	= "";

		$where = "";

		if( isset( $association['through'] ) ) {

			$through_table = static::get_assoc_table_name( $association['through'] );

			$join = "
				INNER JOIN ( {$through_table}, {$wpdb->$prop_table} )
					ON 
					( 
					$table.id = {$through_table}.{$class}_id 
					AND {$through_table}.{$property}_id = {$wpdb->$prop_table}.{$prop_id_col}
					) 
			";
		}

		//TODO Add JOIN statement which will handle "foreign key scenario"
		$where = $wpdb->prepare(
			"
			WHERE {$wpdb->$prop_table}.{$prop_id_col}=%d 
			",
			$prop_id
		);

		$result = $wpdb->get_results( $select . $join . $where );

		//FIXME: dangerous to assume array returned or is it?
		$object = new static( (array) $result[0] );
		$object->$property = $value;

		return $object;
	}
	
	public static function get_data_description() {
		return static::describe_data();		
	}


	public static function get_data_associations() {

		if( method_exists( get_called_class(), 'describe_associations' ) ){
			return static::describe_associations();
		} else{
			return array();
		}
	}

	public static function get_record_count() {
		$result = self::query( array(
			'select' => 'COUNT(*) AS orgs_total_count', 
		) );

		return $result[0]->orgs_total_count;
	}


	// abstract protected static function describe_data();

	public function save() {

		$this->validate();
		if( $this->has_errors() ){
			return false;
		}

		global $wpdb;

		$desc  = $this->get_data_description();
		$data  = array();

		foreach ( array_keys( $desc ) as $prop ) {

			if( !empty( $this->$prop ) ){
				$data[$prop] = stripslashes( $this->$prop );
			}
		}

		if( !empty( $this->id ) ) {
			
			$result = $wpdb->update(
				static::get_table_name(),
				$data,
				array( 'id' => $this->id ),
				array( '%s' ),
				array( '%d' )	
			);

			if( $result === false ) {
				$this->_errors['save'] = "Something went wrong when updating item";
			}
		} else {

			$result = $wpdb->insert(
				static::get_table_name(),
				$data,
				array( '%s' )
			);

			if( $result === false ) {
				$this->_errors['save'] = "Something went wrong when creating item";
			} else {

				$this->id = $wpdb->insert_id;
			}
		}

		if( $result !== false ){
			$this->save_associations();
		}

		return $result;
	}


	/*
	 * FIXME for now we are assuming that if this method is called
	 * associations need to be saved. THIS IS WRONG 
	 */
	protected function save_associations() {

		global $wpdb;

		$relationships = static::get_data_associations();

		if( empty( $relationships ) ) {
			return; 
		}

		foreach( $relationships as $name => $props ) {

			if( $props['cardinality'] != 'has_many' ) {
				continue;
			}

			if( empty( $this->$name ) 
				|| !is_a( $this->{$name}[0], 'PL_Std_Model' ) ) {
				continue;
			}

			foreach ( $this->$name as $association ) {

				$do_save = $association->is_record_new() || $association->is_record_changed();

				if( $do_save ) {
					$key_name = strtolower( get_class( $this ) ) . '_id';
					$association->$key_name = $this->id;
					$association->save();
				}
			}			
		}
	}


	public static function delete_all( $args = array() ) {

		if( empty( $args['conditions'] ) ) {
			return false;
		}

		global $wpdb;

		$associations = static::get_data_associations();

		$table = static::get_table_name();
		$sql = "";

		$sql = "
			DELETE FROM {$table}
			WHERE {$args['conditions']}
		";

		log_me( $sql );
		return $wpdb->query( $sql );
	}


	public static function delete_associations( $keys = array() ) {

		if( empty( $keys ) ) {
			return false;
		}

		global $wpdb;

		$associations = static::get_data_associations();

		$table = static::get_table_name();
		$sql = "";
		$result = false;

		foreach ( $associations as $name => $association ) {

			if( isset( $association['through'] ) ) {

				$through_table = static::get_assoc_table_name( $association['through'] );
				
				$result = $wpdb->delete(
					$through_table,
					$keys
				);
			}

		}

		return $result;
	}


	public function delete() {

		$id = $this->id;

		$result = static::delete_all( array (
			'conditions' => "id = {$this->id}"
		) );

		if( $result === false ) {

			$this->_errors['delete'] = "Something went wrong";
		} else {

			static::delete_associations( array(
				strtolower( get_called_class() ) . '_id' => $this->id
			) );

			$this->id = -1;
		}

		return $result;
	}

	public function get_errors() {

		if( $this->has_errors() ){
			return $this->_errors;
		} else {
			return false;
		}
	}


	public function has_errors() {
		return !empty( $this->_errors );
	}


	public function validate(){

		$descriptions = self::get_data_description();

		foreach ($descriptions as $key => $description) {

			$error = null;
			$validator = new PL_Validator( $key, $this->$key, $description );
			$error = $validator->validate();

		 	if( is_wp_error( $error ) ){
		 		$this->_errors[$key] = $error->get_error_message();
		 	}
		}
	}


	/*
	 * FIXME: Make sure this is the desired login
	 */
	protected static function get_table_name() {

		global $wpdb;

		$table_name = null;
		$plural_class = strtolower( PL_Inflector::pluralize( get_called_class() ) );

		if ( isset( static::$table_name ) ){
			$table_name = $wpdb->{static::$table_name};
		} elseif( property_exists( $wpdb, $plural_class ) ) {
			$table_name = $wpdb->$plural_class;
		} else {
			$table_name = $wpdb->prefix . $plural_class;
		}

		return $table_name;
	}


	protected static function get_assoc_table_name( $name ) {

		global $wpdb;

		$assoc = static::get_data_associations();

		if ( isset( $assoc[$name]['table'] ) ) {
			$table_name = $assoc[$name]['table'];
		} else {
			$table_name = $name;
		} 

		if ( property_exists( $wpdb, $table_name ) ) {
			$table_name = $wpdb->{$table_name};
		} else {
			$table_name = $wpdb->prefix . $table_name;
		}

		return $table_name;
	}


	public function __get( $name ) {

		if( method_exists( $this, 'get_' . $name ) ) {
			return call_user_func( array( $this, 'get_' . $name ) ); 
		}

		return $this->$name;
	}

	public static function __callStatic( $name, $arguments ) {

		if( strpos( $name, 'find_by_') !== FALSE ){

			$prop = str_replace( 'find_by_' , '', $name );
			return static::find_by( $prop, $arguments[0] );
		}
	}


} 


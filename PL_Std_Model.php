<?php 

/**
 * 
 */

abstract class PL_Std_Model extends PL_Model {
	
	public function __construct( $data = array() ) {
		
		$assocs = static::get_data_associations();

		if( !empty( $assocs ) ) {
			$this->build_associations( $assocs, $data );
		}

		foreach( $data as $key => $value ) {

			if( !array_key_exists( $key, $assocs ) ) {
				//If property isn't an associated object assign passed in value
				$this->$key = $value;
			} else {

			}
		}
	}

	protected function build_associations( $assocs, $data ) {
		
		if( empty( $assocs ) ) { 
			return;
		}

		$namespace = substr( get_called_class(), 0, strrpos( get_called_class(), '\\' ) );

		foreach( $assocs as $name => $props ) {

			if( $props['cardinality'] == 'has_many' ) {

				$this->$name   = array();
				$name_singular = \PL_Inflector::singularize( $name );

				if( array_key_exists( $name_singular, $data ) ) {
					
					$rc = new ReflectionClass( $namespace . '\\' . \PL_Inflector::classify( $name_singular ) );

					foreach( $data[$name_singular] as $assoc_data ) {
						$this->{$name}[] = $rc->newInstance( $assoc_data );
					}
				}
			} else {
				$rc          = new ReflectionClass( $namespace . '\\' . \PL_Inflector::classify( $name ) );
				$this->$name = array_key_exists( $name, $data ) ? $rc->newInstance( $data[$name] ) : false;
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
			'joins'      => '',
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

		if( !empty( $args['joins'] ) ) {
			$sql .= $args['joins'];
		}

		if( !empty( $args['conditions'] ) ) {

			$sql .= "
				WHERE 1=1 {$args['conditions']} ";
		}

		if( !empty( $args['group'] ) ) {

			$sql .= "
				GROUP BY {$args['group']} ";
		}
		
		if( !empty( $args['having'] ) ) {

			$sql .= "
				HAVING {$args['having']} ";
		}

		if( !empty( $args['order'] ) ) {

			$sql .= "
				ORDER BY {$args['order']} ";
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
		$results    = array();

		if( $db_results === false ) {
			return $db_results;
		}

		foreach ( $db_results as $result ) {
			$results[] = new static( $result );
		}

		if( !empty( $args['includes'] ) ) {
			//TODO query and build associations

			foreach( $args['includes'] as $include ) {

				if( !static::has_association( $include ) ) {
					continue;
				}

				static::include_association( $include, $results );
			}
		}

		log_me( $results );
		return $results;
	}


	public static function include_association( $include, &$results ) {

		if( !static::has_association( $include ) ) {
			continue;
		}

		$assocs     = static::get_data_associations();
		$rc_this = new \ReflectionClass( get_called_class() ); 

		if( $assocs[$include]['cardinality'] == "has_many" ) {
			$class = $rc_this->getNamespaceName() . '\\' . \PL_Inflector::pl_classify( \PL_Inflector::singularize( $include ) );
		} else {
			$class = $rc_this->getNamespaceName() . '\\' . \PL_Inflector::pl_classify( $include );
		}

		$query = array( 
			'select' => $class::get_table_name() . ".*"
		);

		$ids = array_map( 
			function( $result ) {
				return $result->id;
			}, 
			$results 
		);

		log_me( $ids );

		if( $assocs[$include]['cardinality'] == "has_many" ) {

			$foreign_key = strtolower( $rc_this->getShortName() ) . "_id";
			$query       = array_merge( $query, array(
				'conditions' => " AND " . $class::get_table_name() . "." . $foreign_key . " IN (" . implode( ',',  $ids ) . ")"
			) );
			$results_assoc = $class::all( $query );

			array_walk( 
				$results_assoc,
				function( &$assoc, $key ) use ( &$results, $foreign_key, $include ) {

					foreach( $results as $key => $result ) {
						//Probably more efficient if we could remove assigned association from $results_assoc

						if( $assoc->{$foreign_key} == $result->id ) {
							$results[$key]->{$include}[] = $assoc;
							break;
						}
					}
				}
			);

		} elseif( $assocs[$include]['cardinality'] == "belongs_to" ) {

			$foreign_key = strtolower( $include ) . '_id';
			$query       = array_merge( $query, array(
				'joins'      => 'INNER JOIN ' . static::get_table_name() . ' ON ' . $class::get_table_name() . '.id=' . static::get_table_name() . '.' . $foreign_key,
				'conditions' => ' AND ' . static::get_table_name() . "." . $foreign_key . " IN (" . implode( ',',  $ids ) . ")"
			) );
			
			$results_assoc = $class::all( $query );

			//TODO: refactor so list each is used for perfromance optimisation
			array_walk( 
				$results,
				function( &$result, $key ) use ( $results_assoc, $foreign_key, $include ) {

					foreach( $results_assoc as $key => $assoc ) {
						//Probably more efficient if we could remove assigned association from $results_assoc

						if( $result->{$foreign_key} == $assoc->id ) {
							$result->{$include} = $assoc;
							break;
						}
					}
				}
			);
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

		global $wpdb;
		
		/*$results = static::all( array(
			'conditions' => " AND $property = " . is_numeric( $value ) ? $value : "'$value'"
		) );*/


		$conditions = $wpdb->prepare(
			"	AND $property=" . ( is_numeric( $value ) ? '%d' : '%s' ),
			$value
		);

		$results = static::all( array(
			'conditions' => $conditions
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
	

	// abstract protected static function describe_data();

	public function save() {

		$this->validate();
		if( $this->has_errors() ){
			return false;
		}

		global $wpdb;

		$this->save_associations( array('belongs_to') );

		$data = $this->extract_for_saving();

		if( !$this->is_record_new() ) {
			
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
			$this->save_associations( array('has_many') );
		}

		return $result;
	}

	protected function extract_for_saving() {
		$data          = array();
		$desc          = $this->get_data_description();
		$relationships = static::get_data_associations();

		foreach ( array_keys( $desc ) as $prop ) {
			if( !empty( $this->$prop ) ){
				$data[$prop] = stripslashes( $this->$prop );
			}
		}

		foreach( $relationships as $name => $props ) {
			if( $props['cardinality'] == 'belongs_to' && !empty( $this->{$name . "_id"} )  ) {
				$data[$name. "_id"] = $this->{$name . "_id"};
			}
		}

		return $data;
	}

	/*
	 * FIXME for now we are assuming that if this method is called
	 * associations need to be saved. THIS IS WRONG 
	 */
	protected function save_associations( array $filters ) {

		global $wpdb;

		$relationships = static::get_data_associations();

		if( empty( $relationships ) ) {
			return; 
		}

		foreach( $relationships as $name => $props ) {

			if( empty( $this->$name ) || !in_array( $props['cardinality'], $filters ) ) {
				continue;
			}

			if( $props['cardinality'] == 'has_many' ) {

				if( !is_a( $this->{$name}[0], 'PL_Std_Model' ) ) {
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
			} else if ( $props['cardinality'] == 'belongs_to' ) {

				if( !is_a( $this->$name, 'PL_Std_Model' ) ) {
					continue;
				}

				$do_save = $this->$name->is_record_new() || $this->$name->is_record_changed();

				if( $do_save ) {
					$this->$name->save();

					$key_name = strtolower( ( new \ReflectionClass( $this->$name ) )->getShortName() ) . '_id';
					$this->$key_name = $this->$name->id;

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


	protected static function get_table_name() {

		global $wpdb;
		$reflect = new \ReflectionClass( get_called_class() );

		$table_name = null;
		$plural_class = strtolower(  PL_Inflector::pluralize( $reflect->getShortName() ) );
		// $plural_class = strtolower( PL_Inflector::pluralize( get_called_class() ) );

		if ( isset( static::$table_name ) ){
			$table_name = $wpdb->{static::$table_name};
		} elseif( property_exists( $wpdb, 'pl_' . $plural_class ) ) {
			$table_name = $wpdb->{'pl_' . $plural_class};
		} else {
			$table_name = $wpdb->prefix . 'pl_' . $plural_class;
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


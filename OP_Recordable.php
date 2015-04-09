<?php 

interface OP_Recordable {

	public static function find( $id );

	public static function find_by( $property, $value );

	public static function all();
	
	public static function get_data_description();

	public function save();

	public function delete();

}

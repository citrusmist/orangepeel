<?php

interface OP_CSV_Exportable{
	public static function export_csv( $fp, array $items );
	public function to_array();
}
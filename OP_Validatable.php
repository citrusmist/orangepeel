<?php 

interface OP_Validatable {

	

	public function get_errors();

	public function has_errors();

	public function validate();

}

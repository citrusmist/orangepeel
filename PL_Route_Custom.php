<?php

/**
* 
*/
class PL_Route_Custom extends \PL_Route {
	
	function calc_rewrite() {
		$this->rewrite = $this->parse();
	}
}
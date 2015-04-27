<?php 
/* PL_Controller to become PL_Module_Controller */

abstract class PL_Module_Controller {

	protected $module;
	protected $view           = null;
	protected $view_path      = null;
	protected $errorlist_path = null;
	protected $last_render    = null;

	public function __construct( $module ) {
		$this->module = $module;
		$this->view = new PL_View();
	}

}
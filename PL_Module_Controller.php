<?php 


class PL_Module_Controller {

	protected $module;

	public function __construct( $module ) {
		$this->module = $module;
		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'wp', array( $this, 'handle_request' ), 10, 1 );
		add_action( $this->module->get_slug(), array( $this, 'handle_action' ), 10, 10 );
	}

	public function handle_request() {
		//See PL_Bootstrap::wp in Letter project for some ideas
	}

	public function handle_action() {
		//See PL_Bootstrap::dispatch_public_action in Letter project for some ideas
	}
}
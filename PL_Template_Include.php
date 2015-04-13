<?php

class PL_Template_Include{

	protected $template_path;
	protected $fallback_path;

	public function __construct( $template_path, $fallback_path ){

		$this->template_path = $template_path;
		$this->fallback_path = $fallback_path;

		add_action( 'template_include', array( $this, 'include_template' ) );
	}

	public function include_template() {

		//@TODO should maybe check for existence of the fallback_path
		$template_path = $this->fallback_path;

		// checks if the file exists in the theme first,
		// otherwise serve the file from the plugin
		if ( $theme_file = locate_template( $this->template_path ) ) {
			$template_path = $theme_file;
		} 
	
	  return $template_path;
	}  	
}
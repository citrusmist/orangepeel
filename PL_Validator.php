<?php 

class PL_Validator{

	protected $_key;
	protected $_value;
	protected $_description;

	protected $_error = null;

	public function __construct( $key, $value = '', array $description ) {

		$this->_key         = $key;
		$this->_value       = $value;
		$this->_description = $description; 	
	}

	public function validate(){

		if( $this->_description['required'] === true && empty( $this->_value ) ){
			return new WP_Error( $this->_key, __( $this->_description['label'] . " can't be empty" ) );
		}

		foreach( $this->_description['validator'] as $validator => $rules ) {

			$is_file = isset( $this->_description['format'] ) 
				&& $this->_description['format'] == 'file';

			if( !empty( $this->_value ) || $is_file ) {
				call_user_func( array( $this, 'validate_' . $validator ), $rules );
			}
		}

		if( is_wp_error( $this->_error ) ){
			return $this->_error;
		} else{ 
			return true;
		}
	}

	public function validate_wordcount( $rules ) {

		$text      = strip_tags( $this->_value );
		$wordcount = str_word_count( $text );

		if( isset( $rules['max'] ) && $wordcount > $rules['max'] ) {

			$this->_error = new WP_Error( 
				$this->_key, 
				__( $this->_description['label'] . " can't be over " .  $rules['max'] . " words" ) 
			);

		} else if( isset( $rules['min'] ) && $rules['min'] > $wordcount ) {

			$this->_error = new WP_Error( 
				$this->_key, 
				__( $this->_description['label'] . " can't be less " .  $rules['min'] . " words" ) 
			);

		}
	}

	public function validate_email( $rules ) {

		if( !is_email( $this->_value ) ){

			$this->_error = new WP_Error( 
				$this->_key, 
				__( $this->_description['label'] . " has to be a valid email address" ) 
			);
		}
	}

	public function validate_numeric( $rules ) {

		if( !is_numeric( $this->_value ) ){

			$this->_error = new WP_Error( 
				$this->_key, 
				__( $this->_description['label'] . " has to be a number" ) 
			);
		}
	}

	//http://www.php.net/manual/en/features.file-upload.errors.php
	protected function check_uploaded_file( $key ) {

		$file = $_FILES[$key];

		$upload_ok = $file['error'] == UPLOAD_ERR_NO_FILE 
			|| $file['error'] == UPLOAD_ERR_OK;

		if( $upload_ok ) {
			return $file;
		}

		if( $file['error'] == UPLOAD_ERR_FORM_SIZE 
			|| $file['error'] == UPLOAD_ERR_INI_SIZE ) {

			$file = new WP_Error(
				$key,
				__( $this->_description['label'] . " is too large. Try a smaller file."  )
			);
		} else {

			$file = new WP_Error(
				$key,
				__( $this->_description['label'] . " failed to upload. Please try again later."  )
			);
		}

		return $file;
	}

	public function validate_file( $rules ) {

		$file = $this->check_uploaded_file( $this->_description['key'] );

		if( is_wp_error( $file ) ) {
			$this->_error = $file;
			return;
		} elseif( $file['error'] == UPLOAD_ERR_NO_FILE ) {
			return;
		}

		//TODO: validate file based on $rules
	}

	
	public function validate_image( $rules ) {
		
		$file = $this->check_uploaded_file( $this->_description['key'] );

		if( is_wp_error( $file ) ) {
			$this->_error = $file;
			return;
		} elseif( $file['error'] == UPLOAD_ERR_NO_FILE ) {
			return;
		}

		if( !$this->file_is_displayable_image( $file['tmp_name'] ) ) {

			$this->_error = new WP_Error( 
				$this->_key, 
				__( $this->_description['label'] . " has to be a valid image file." ) 
			);
		} elseif( isset( $rules['max_size'] ) && $file['size'] > $rules['max_size'] ) {

			$this->_error = new WP_Error(
				$this->_key,
				__( $this->_description['label'] . " is too large. Try a smaller file."  )
			);
		}
	}

	/**
	 * Copy of the implementation found in wp-admin/includes/image.php,
	 * to prevent having to includ 
	 */

	protected function file_is_displayable_image( $path ) {

		$info = @getimagesize($path);

		if ( empty($info) ) {
			$result = false;
		} elseif ( !in_array($info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)) ) {	// only gif, jpeg and png images can reliably be displayed
			$result = false;
		} else {
			$result = true;
		}

		/**
		 * Filter whether the current image is displayable in the browser.
		 *
		 * @since 2.5.0
		 *
		 * @param bool   $result Whether the image can be displayed. Default true.
		 * @param string $path   Path to the image.
		 */
		return apply_filters( 'file_is_displayable_image', $result, $path );
	}

	public function validate_postcode( $rules ) {

		$value = trim( $this->_value );

		if( !is_string( $rules ) ) {
			$rules = "/\A[A-Z]{1,2}[0-9][0-9A-Z]?\s?[0-9][A-Z]{2}/i";
		}

		$result = preg_match( $rules, $value, $matches );

		if( $result === 0 ) {

			$this->_error = new WP_Error(
				$this->_key,
				__( $this->_description['label'] . " isn't a valid postcode" )
			);
		}
	}


}
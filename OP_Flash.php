<?php

class OP_Flash {

	protected static $flash = null;

	public static function init(){
		if( isset( $_COOKIE['cm_flash'] ) ){
			static::set_flash( $_COOKIE['cm_flash'] );
			self::reset_cookies();
		}
	}

	protected static function set_flash( $flash ) {
		self::$flash = stripslashes_deep( $flash );
	}

	//max $data depth = 3
	public static function set( $slug, array $data ){

		$data = stripslashes_deep( $data );

		foreach( $data as $key1 => $value1 ) {

			$cookie_name = 'cm_flash[' . $slug . '][' . $key1 . ']';

			if( is_array( $value1 ) ){

				foreach( $value1 as $key2 => $value2 ) {

					$cookie_name = 'cm_flash[' . $slug . '][' . $key1 . '][' . $key2 . ']';

					if( is_array( $value2 ) ) {
						
						foreach( $value2 as $key3 => $value3 ) {

							$cookie_name = 'cm_flash[' . $slug . '][' . $key1 . '][' . $key2 . '][' . $key3 . ']';
							setcookie( $cookie_name, $value3, time() + 120, COOKIEPATH );
						}
					} else {
						setcookie( $cookie_name, $value2, time() + 120, COOKIEPATH );
					}
				}
			}else{
				setcookie( $cookie_name, $value1, time() + 120, COOKIEPATH );	
			}
		}
	}

	public static function get( $slug = '' ){

		if( empty( $slug ) ){
			return self::$flash;
		} elseif( isset( self::$flash[$slug] ) ) {
			return self::$flash[$slug];
		} else {
			return false;
		}
	}

	protected static function reset_cookies(){

		if( !is_array( self::$flash ) ){ 
			return;
		}

		foreach( self::$flash as $slug => $data ) {

			foreach ($data as $key1 => $value1) {

				//level 1

				$cookie_name = 'cm_flash[' . $slug . '][' . $key1 . ']';
			
				if( is_array( $value1 ) ){

					foreach( $value1 as $key2 => $value2 ) {

						//level 2
						
						$cookie_name = 'cm_flash[' . $slug . '][' . $key1 . '][' . $key2 . ']';

						if( is_array( $value2 ) ) {
							
							//level 3

							foreach( $value2 as $key3 => $value3 ) {

								$cookie_name = 'cm_flash[' . $slug . '][' . $key1 . '][' . $key2 . '][' . $key3 . ']';
								setcookie( $cookie_name, false, 100, COOKIEPATH );
							}
						} else {
							setcookie( $cookie_name, false, 100, COOKIEPATH );
						}
					}

				}else{
					setcookie( $cookie_name, false, 100, COOKIEPATH );
				}

			}
		}

	}

}
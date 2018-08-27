<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 1.0.0
# -----------------------
# Create & validate a token string for user's data
# -----------------------

namespace Sincco\Tools;

final class Tokenizer extends \stdClass {

	private static $instance;
	// Default values (allows to use functions in simple form)
	private $salt = 'ya3R0AKGCHBETW5vJb4eMVY7Qvp9mEVv6LjeSKilrnS9Z98txIBfLgTe4DaYO1zQ';
	private $password = '66Oetf#kI6U4wYH';

	/**
	 * Creates a token string
	 * @param  array  $data     User's data that will be parsed on string
	 * @param  string  $password String password for encryption (it must be same on validation side)
	 * @param  integer $duration Duration time in minutes
	 * @return string            Token string
	 */
	public static function create( $data, $password = '', $duration = 3 ) {
		if(! self::$instance instanceof self )
			self::$instance = new self();
		if( trim( $password ) == '' )
			$password = self::$instance->password;
		$data[ 'exp' ] = time() + ( $duration * 60 );
		return self::$instance->Encrypt( http_build_query( $data ), $password );
	}

	/**
	 * Validates a token string
	 * @param  string $token    Token to validate
	 * @param  string $password String password for decrypt data
	 * @return mixed           Returns array with data if token is valid or FALSE if not
	 */
	public static function validate( $token, $password = '' ) {
		if(! self::$instance instanceof self )
			self::$instance = new self();
		if( trim( $password ) == '' )
			$password = self::$instance->password;
		$token = self::$instance->Decrypt( $token, $password );
		parse_str( $token, $data );
		if( isset( $data[ 'exp' ] ) ) {
			if( intval( time() ) <= intval( $data[ 'exp' ] ) )
				return $data;
			else
				return FALSE;
		} else
			return FALSE;
	}

	/**
	 * Creates a string to be used a salt data on encryption / decryption
	 * @return string
	 */
	public function getSalt() {
		$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$randStringLen = 64;
		$randString = "";
		for ($i = 0; $i < $randStringLen; $i++)
			$randString .= $charset[mt_rand(0, strlen($charset) - 1)];
		return $randString;
	}

	/**
	 * Sets salt string to be used on encryption / decryption
	 * @param string $salt
	 */
	public function setSalt( $salt ) {
		if(!self::$instance instanceof self)
			self::$instance = new self();
		self::$instance->$salt = $salt;
	}

	/**
	 * Encrypt data
	 */
	private static function encrypt( $data, $password ) {
		$salt = self::$instance->salt;
		$password = substr( hash( 'sha256', $salt . $password . $salt ), 0, 32 );
		$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
		$iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
		$encrypted = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $password, $data, MCRYPT_MODE_ECB, $iv ) );
		return self::$instance->cleanData( $encrypted );
	}

	/**
	 * Decrypt data
	 */
	private static function decrypt( $data, $password ) {
		$data = self::$instance->cleanData( $data, TRUE );
		$salt = self::$instance->salt;
		$password = substr( hash( 'sha256', $salt . $password . $salt ), 0, 32 );
		$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
		$iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
		$decrypted = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $password, base64_decode( $data ), MCRYPT_MODE_ECB, $iv );
		return $decrypted;
	}

	/**
	 * Clean data replacing URL characters, allowing using token as URL param
	 */
	private function cleanData( $data, $decrypt = FALSE ) {
		$dirty = array("+", "/", "=");
		$clean = array("_PLS_", "_SLH_", "_EQL_");
		if( $decrypt )
			$response = str_replace($clean, $dirty, $data);
		else
			$response = str_replace($dirty, $clean, $data);
		return $response;
	}
}
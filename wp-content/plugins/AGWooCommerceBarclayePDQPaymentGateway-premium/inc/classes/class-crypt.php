<?php
/*-----------------------------------------------------------------------------------*/

/*	AG encrypt functions
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ePDQ_crypt' ) ) {
	return;
}

class ePDQ_crypt {

	/**
	 * ePDQ return hash
	 *
	 * @param $check_data
	 * @param $sha_out
	 * @param $sha_method
	 *
	 * @return void
	 */
	public static function epdq_hash( $check_data, $sha_out, $sha_method ): string {

		$data = '';

		foreach( $check_data as $key => $value ) {
			if( $value === '' ) {
				continue;
			}
			$data .= strtoupper( $key ) . '=' . $value . $sha_out;
		}

		$value = hash( self::get_sha_method(), $data );

		return $value;
	}

	/**
	 * @return string
	 */
	public static function get_sha_method() {

		$settings = self::key_settings();
		$sha_method = $settings['shamethod'];

		$shasign_method = 'sha512';
		$sha_method = (int) $sha_method;

		if( $sha_method === 0 ) {
			$shasign_method = 'sha1';
		} elseif( $sha_method === 1 ) {
			$shasign_method = 'sha256';
		} elseif( $sha_method === 2 ) {
			$shasign_method = 'sha512';
		}

		return $shasign_method;

	}

	/**
	 * @return array
	 */
	public static function key_settings(): array {

		if( defined( 'secure_ePDQ_PSPID' ) && defined( 'secure_ePDQ_SHA_in' ) && defined( 'secure_ePDQ_SHA_out' ) && defined( 'secure_ePDQ_SHA_method' ) ) {

			$settings = array(
				'pspid'     => secure_ePDQ_PSPID ?? '',
				'shain'     => secure_ePDQ_SHA_in ?? '',
				'shaout'    => secure_ePDQ_SHA_out ?? '',
				'shamethod' => secure_ePDQ_SHA_method ?? '',
			);

		} else {

			$ePDQ_settings = new epdq_checkout();

			$settings = array(
				'pspid'     => $ePDQ_settings->access_key ?? '',
				'shain'     => $ePDQ_settings->sha_in ?? '',
				'shaout'    => $ePDQ_settings->sha_out ?? '',
				'shamethod' => $ePDQ_settings->sha_method ?? '',
			);

		}

		return $settings;

	}

	/**
	 * Password generator
	 *
	 * @return void
	 */
	public static function encrypt_password_gen(): string {

		return base64_encode( bin2hex( openssl_random_pseudo_bytes( 16 ) ) );
	}

	/**
	 * OpenSSL encryption function
	 *
	 * @param [type] $input
	 * @param [type] $password
	 *
	 * @return string
	 */
	public static function openssl_crypt( $input, $password ) {

		$key = hash( 'sha256', $password, TRUE );
		$iv = openssl_random_pseudo_bytes( 16 );

		$cipherText = base64_encode( openssl_encrypt( $input, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv ) );
		$hash = hash_hmac( 'sha256', $cipherText, $key, TRUE );

		return $iv . $hash . $cipherText;
	}

	/**
	 * Libsodium encryption function
	 *
	 * @param $message
	 * @param $secret_key
	 * @param integer $block_size
	 *
	 * @return string
	 */
	public static function sodium_crypt( $message, $secret_key, $block_size = 1 ) {

		$nonce = hash( 'sha256', $secret_key );
		$padded_message = sodium_pad( $message, $block_size <= 512 ? $block_size : 512 );
		$cipher = base64_encode( $nonce . sodium_crypto_secretbox( $padded_message, $nonce, $secret_key ) );

		// cleanup
		sodium_memzero( $message );
		sodium_memzero( $secret_key );

		return $cipher;
	}

	public static function refund_settings() {

		if( defined( 'secure_ePDQ_PSPID' ) && defined( 'secure_ePDQ_SHA_in' ) && defined( 'secure_ePDQ_SHA_out' ) && defined( 'secure_ePDQ_SHA_method' ) ) {

			$settings = array(
				'USERID' => secure_ePDQ_userid ?? '',
				'PSWD'   => secure_ePDQ_pswd ?? '',
				'REFID'  => secure_ePDQ_refid ?? '',
			);

		} else {

			$ePDQ_settings = new epdq_checkout();

			$settings = array(
				'USERID' => $ePDQ_settings->api_user ?? '',
				'PSWD'   => $ePDQ_settings->api_password ?? '',
				'REFID'  => $ePDQ_settings->api_REFID ?? '',
			);

		}

		return $settings;

	}

	public static function complus_decrypt() {

		$settings = ePDQ_crypt::key_settings();

		// Hash
		if( defined( 'ePDQ_custom_order_id' ) ) {
			$hash_fields = array(
				$settings['pspid'],
				date( 'Y:m:d' ),
				AG_ePDQ_Helpers::AG_get_request( 'idOrder' ),
				$settings['shain'],
				get_bloginfo( 'name' )
			);
		} else {
			$hash_fields = array(
				$settings['pspid'],
				date( 'Y:m:d' ),
				AG_ePDQ_Helpers::AG_get_request( 'idOrder' ),
				$settings['shain'],
				get_bloginfo( 'name' )
			);
		}

		return ePDQ_crypt::ripemd_crypt( implode( $hash_fields ), $settings['shain'] );

	}

	/**
	 * RIPEMD 160 encryption function
	 *
	 * @param $input
	 * @param $password
	 *
	 * @return string
	 */
	public static function ripemd_crypt( $input, $password ) {

		$key = hash( 'sha256', $password );
		$encrypted = base64_encode( hash_hmac( 'ripemd160', $input, $key ) );

		return $key . $encrypted;
	}


}

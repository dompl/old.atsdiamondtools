<?php
/*-----------------------------------------------------------------------------------*/
/*	AG encrypt functions
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('ePDQ_crypt')) {
    return;
}

class ePDQ_crypt
{

        /**
     * ePDQ return hash
     *
     * @param $check_data
     * @param $sha_out
     * @param $sha_method
     * @return void
     */
	public static function epdq_hash($check_data, $sha_out, $sha_method) {
		
        $data = '';

		foreach ($check_data as $key => $value) {
			if ($value === '')	continue;
			$data .= strtoupper($key) . '=' . $value . $sha_out;
		}

		return hash(self::get_sha_method(), $data);
    }
    

    /**
     * Password generator 
     *
     * @return void
     */
    public static function encrypt_password_gen()
    {
        return base64_encode(bin2hex(openssl_random_pseudo_bytes(16)));
    }

    /**
     * OpenSSL encryption function
     *
     * @param [type] $input
     * @param [type] $password
     * @return void
     */
    public static function openssl_crypt($input, $password)
    {
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(16);

        $cipherText = base64_encode(openssl_encrypt($input, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv));
        $hash = hash_hmac('sha256', $cipherText, $key, true);

        return $iv . $hash . $cipherText;
    }

    /**
     * Libsodium encryption function
     *
     * @param $message
     * @param $secret_key
     * @param integer $block_size
     * @return void
     */
    public static function sodium_crypt($message, $secret_key, $block_size = 1)
    {
        $nonce = hash('sha256', $secret_key);
        $padded_message = sodium_pad($message, $block_size <= 512 ? $block_size : 512);
        $cipher = base64_encode($nonce . sodium_crypto_secretbox($padded_message, $nonce, $secret_key));

        // cleanup
        sodium_memzero($message);
        sodium_memzero($secret_key);

        return $cipher;
    }

    /**
     * RIPEMD 160 encryption function
     *
     * @param $input
     * @param $password
     * @return void
     */
    public static function ripemd_crypt($input, $password)
    {

        $key = hash('sha256', $password);
        $encrypted = base64_encode(hash_hmac('ripemd160', $input, $key));

        return $key . $encrypted;
    }


    public static function key_settings() {

        if(defined('secure_ePDQ_PSPID') && defined('secure_ePDQ_SHA_in') && defined('secure_ePDQ_SHA_out') && defined('secure_ePDQ_SHA_method') ) {

            $pspid = secure_ePDQ_PSPID;
            $shain = secure_ePDQ_SHA_in;
            $shaout = secure_ePDQ_SHA_out;
            $shamethod = secure_ePDQ_SHA_method;

            $settings = array(
                'pspid' => isset($pspid) ? $pspid : '',
                'shain' => isset($shain) ? $shain : '',
                'shaout' => isset($shaout) ? $shaout : '',
                'shamethod' => isset($shamethod) ? $shamethod : '',
            );

            return $settings;

        } else {
            
            $ePDQ_settings = new epdq_checkout();

            $settings = array(
                'pspid' => isset($ePDQ_settings->access_key) ? $ePDQ_settings->access_key : '',
                'shain' => isset($ePDQ_settings->sha_in) ? $ePDQ_settings->sha_in : '',
                'shaout' => isset($ePDQ_settings->sha_out) ? $ePDQ_settings->sha_out : '',
                'shamethod' => isset($ePDQ_settings->sha_method) ? $ePDQ_settings->sha_method : '',
            );

            return $settings;

        }

    }


    public static function refund_settings() {

        if(defined('secure_ePDQ_PSPID') && defined('secure_ePDQ_SHA_in') && defined('secure_ePDQ_SHA_out') && defined('secure_ePDQ_SHA_method') ) {

            $userid = secure_ePDQ_userid;
            $pswd = secure_ePDQ_pswd;
            $refid = secure_ePDQ_refid;

            $settings = array(
                'USERID' => isset($userid) ? $userid : '',
                'PSWD' => isset($pswd) ? $pswd : '',
                'REFID' => isset($refid) ? $refid : '',
            );

            return $settings;

        } else {
            
            $ePDQ_settings = new epdq_checkout();

            $settings = array(
                'USERID' => isset($ePDQ_settings->api_user) ? $ePDQ_settings->api_user : '',
                'PSWD' => isset($ePDQ_settings->api_password) ? $ePDQ_settings->api_password : '',
                'REFID' => isset($ePDQ_settings->api_REFID) ? $ePDQ_settings->api_REFID : '',
            );

            return $settings;

        }

    }



    public static function get_sha_method() {

        $settings = self::key_settings();
        $sha_method = $settings['shamethod'];

        $shasign_method = 'sha512';
        $sha_method = (int) $sha_method;

        if ($sha_method == 0) {
			$shasign_method = 'sha1';
		} elseif ($sha_method == 1) {
			$shasign_method = 'sha256';
		} elseif ($sha_method == 2) {
			$shasign_method = 'sha512';
        }

        return $shasign_method;
        
    }


}

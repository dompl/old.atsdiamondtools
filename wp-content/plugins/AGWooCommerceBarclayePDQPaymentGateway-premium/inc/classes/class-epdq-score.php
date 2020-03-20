<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ score functions for 3DS score function
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') or die("No script kiddies please!");


if (class_exists('ePDQ_score')) {
    return;
}

class ePDQ_score
{

    public static function get_eci_3d_data($data, $score) {
           
		$data_set =  array(
            5  => array('5', 'Cardholder identification successful', $score),
            6  => array('6', 'Proof of authentication attempt', $score),
            12 => array('12', 'Issuer is not enrolled', ePDQ_3D_score::strip_points($score, '95') ),
            91 => array('91', 'Cardholder identification failed, but continue anyway', ePDQ_3D_score::strip_points($score, '50') ),
            92 => array('92', 'ACS page temporarily unavailable, but continue anyway', ePDQ_3D_score::strip_points($score, '25') ),
        );

		if (isset($data_set[$data]))
			return $data_set[$data];
		else
            return array('Not set', 'No data was sent back for this dataset', ePDQ_3D_score::strip_points($score, '-60') );


    }


    public static function get_cvccheck_data($data, $score) {
            
		$data_set =  array(
            'KO' => array('KO', 'The CVC has been sent but the acquirer has given a negative response i.e. the CVC is wrong.', ePDQ_3D_score::strip_points($score, '25')),
            'OK' => array('OK', 'The CVC has been sent and the acquirer has given a positive response i.e. the CVC is correct', $score),
            'NO' => array('NO', 'No CVC transmitted, the acquirer has replied that a CVC check was not possible, the acquirer declined the authorisation but did not provide a specific result for the CVC check', ePDQ_3D_score::strip_points($score, '75')),
        );

		if (isset($data_set[$data]))
			return $data_set[$data];
		else
            return array('Not set', 'No data was sent back for this dataset', ePDQ_3D_score::strip_points($score, '-60') );


    }


    public static function get_aavcheck_data($data, $score) {
           
		$data_set =  array(
            'KO' => array('KO', 'The address has been sent but the acquirer has given a negative response i.e. the address is wrong.', ePDQ_3D_score::strip_points($score, '25')),
            'OK' => array('OK', 'The address has been sent and the acquirer has returned a positive response i.e. the address is correct', $score),
            'NO' => array('NO', 'No address transmitted; the acquirer has replied that an address check was not possible; the acquirer declined the authorization but did not provide a specific result for the address check', ePDQ_3D_score::strip_points($score, '75'))
        );

		if (isset($data_set[$data]))
			return $data_set[$data];
		else
            return array('Not set', 'No data was sent back for this dataset', ePDQ_3D_score::strip_points($score, '-60') );


    }

    public static function get_aavzip_data($data, $score) {
            
		$data_set =  array(
            'KO' => array('KO', 'The postcode has been sent but the acquirer has given a negative response i.e. the postcode is wrong.', ePDQ_3D_score::strip_points($score, '25')),
            'OK' => array('OK', 'The postcode has been sent and the acquirer has returned a positive response i.e. the postcode is correct', $score),
            'NO' => array('NO', 'No postcode transmitted; the acquirer has replied that an postcode check was not possible; the acquirer declined the authorization but did not provide a specific result for the postcode check', ePDQ_3D_score::strip_points($score, '75'))
        );

		if (isset($data_set[$data]))
			return $data_set[$data];
		else
            return array('Not set', 'No data was sent back for this dataset', ePDQ_3D_score::strip_points($score, '-60') );

    }

    public static function get_vc_data($data, $score) {
         
		$data_set =  array(
            'ECB' => array('ECB', 'For E Carte Bleue', ePDQ_3D_score::strip_points($score, '95')),
            'ICN' => array('ICN', 'For Internet City Number', ePDQ_3D_score::strip_points($score, '95')),
            'NO'  => array('NO', 'The card is not a virtual card / the card is a type of virtual card not known to us.', $score),
        );

		if (isset($data_set[$data]))
			return $data_set[$data];
		else
			return array('Not set', 'No data was sent back for this dataset', ePDQ_3D_score::strip_points($score, '-60') );

    }


    public static function check_ePDQ_order_data($order, $score) {

        if($order == '')
            return;

        $data = array();
        $data['ECI_data'] = ePDQ_score::get_eci_3d_data($order->get_meta('ECI'), $score);
        $data['CVCcheck'] = ePDQ_score::get_cvccheck_data($order->get_meta('CVCCHECK'), $score);
        $data['AAVcheck'] = ePDQ_score::get_aavcheck_data($order->get_meta('AAVADDRESS'), $score);
        $data['AAVzip']   = ePDQ_score::get_aavzip_data($order->get_meta('AAVZIP'), $score);
        $data['VC_data']  = ePDQ_score::get_vc_data($order->get_meta('VC'), $score);

        //wp_die(var_dump($data));
        
        return $data;

    }

}
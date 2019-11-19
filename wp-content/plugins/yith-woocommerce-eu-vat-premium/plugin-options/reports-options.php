<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$reports_settings = array(

	'reports' => array(
		'report_panel' => array(
			'type'         => 'custom_tab',
			'action'       => 'yith_ywev_reports_panel',
			'hide_sidebar' => true
		)
	)
);

return apply_filters( 'yith_ywev_reports_options', $reports_settings );
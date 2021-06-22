<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ settings
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('AG_ePDQ_Settings')) {
        return;
}

class AG_ePDQ_Settings
{

        /**
         * Plugin settings
         *
         * @return void
         */
        public static function form_fields()
        {


                return array(
                        'enabled' => array(
                                'title' => __('Enable/Disable', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => __('Enable ePDQ Checkout', 'ag_epdq_server'),
                                'default' => 'no'
                        ),
                        'logo' => array(
                                'title' => __('Logo for payment page', 'ag_epdq_server'),
                                'type' => 'text',
                                'label' => __('Upload image for payment page', 'ag_epdq_server'),
                                'default' => '',
                                'description' => 'Add an image to display on the payment page - paste in the full image url <strong>You must have an SSL for this to work correctly.</strong>',
                                'desc_tip' => false
                        ),
                        'title' => array(
                                'title' => __('Title', 'ag_epdq_server'),
                                'type' => 'text',
                                'description' => __('Title of the payment process. This name will be visible throughout the site and the payment page.', 'ag_epdq_server'),
                                'default' => 'AG ePDQ Checkout',
                                'desc_tip'      => true
                        ),
                        'description' => array(
                                'title' => __('Description', 'ag_epdq_server'),
                                'type' => 'textarea',
                                'description' => __('Description of the payment process. This description will be visible throughout the site and the payment page.', 'ag_epdq_server'),
                                'default' => 'Use the secure payment processor of Barclaycard and checkout with your debit/credit card.',
                                'desc_tip'      => true
                        ),
                        'access_key' => array(
                                'title' => __('PSPID', 'ag_epdq_server'),
                                'type' => 'text',
                                'description' => __('The PSPID for your barclays account. This is the id which you use to login the admin panel of the barclays bank. <small><strong>Remember you must test using the test ePDQ account Barclays supply you</strong></small>', 'ag_epdq_server'),
                                'default' => '',
                                //'desc_tip'      => true
                        ),
                        'status' => array(
                                'title' => __('Store Status', 'ag_epdq_server'),
                                'type' => 'select',
                                'options' => array('test' => 'Test Environment', 'live' => 'Live Store'),
                                'description' => __('The status of your store tells that are you actually ready to run your shop or its still a test environment. If the test is selected then no payments will be processed. For details please refer to the user guide provided by the Barclays EPDQ servise.', 'ag_epdq_server'),
                                'default' => '',
                                'css'  => 'height: 35px;',
                                'desc_tip'      => true,
                        ),
                        'sha_in' => array(
                                'title' => __('SHA-IN Passphrase', 'ag_epdq_server'),
                                'type' => 'password',
                                'description' => __('The SHA-IN signature will encode the data passed to the payment processor to ensure better security.', 'ag_epdq_server'),
                                //'desc_tip'      => true
                        ),
                        'sha_out' => array(
                                'title' => __('SHA-OUT Passphrase', 'ag_epdq_server'),
                                'type' => 'password',
                                'description' => __('The SHA-OUT signature will encode the data passed back from the payment processor to ensure better security.', 'ag_epdq_server'),
                                //'desc_tip'      => true
                        ),
                        'sha_method' => array(
                                'title' => __('SHA encryption method', 'ag_epdq_server'),
                                'type' => 'select',
                                'css'  => 'height: 35px;',
                                'options' => array(0 => 'SHA-1 (We do not recommend this option, please use another)', 1 => 'SHA-256', 2 => 'SHA-512'),
                                'description' => __('Sha encryption method - this needs to match what you have set in the ePDQ back office.', 'ag_epdq_server'),
                                'default' => '',
                                'desc_tip'      => true,
                        ),
                        'tip' => array(
                                'title'              => __('Tip', 'ag_epdq_server'),
                                'type'               => 'hidden',
                                'description'        => __('Want to store your API details in a more secure way, read through our doc <a href="https://we-are-ag.helpscoutdocs.com/article/161-storing-strong-api-credentials" target="_blank">here</a>', 'ag_epdq_server'),
                        ),
                        'template' => array(
                                'title' => __('Dynamic template URL', 'ag_epdq_server'),
                                'type' => 'text',
                                'description' => __('The dynamic template page allows you to customise the design of the payment pages in a more advanced way than the static template does.

                    When you use a dynamic template page, you fully design your own template page, leaving just one area in that page to be completed by our system. The URL of your template page needs to be sent to us in the hidden fields for each transaction.

                    Please bear in mind that using a dynamic template page involves an additional request from our system to look up your template page. This increases the time needed for the payment process.', 'ag_epdq_server'),
                                'desc_tip'      => true
                        ),
                        'pmlisttype'        => array(
                                'title'                 => __('Layout of the payment methods', 'ag_epdq_server'),
                                'type'                         => 'select',
                                'class'                        => 'chosen_select',
                                'css'                 => 'width: 350px;',
                                'description'         => __('You can arrange the layout/list of the payment methods on Barclays payment page', 'ag_epdq_server'),
                                'default'                 => '0',
                                'options'                 => array(
                                        '0'      => __('Horizontally grouped logos with the group name on the left (default value)', 'ag_epdq_server'),
                                        '1'      => __('Horizontally grouped logos with no group names', 'ag_epdq_server'),
                                        '2'      => __('Vertical list of logos with specific payment method or brand name', 'ag_epdq_server'),
                                ),
                        ),
                        'cardtypes'        => array(
                                'title'                 => __('Accepted Cards', 'ag_epdq_server'),
                                'type'                         => 'multiselect',
                                'class'                        => 'chosen_select',
                                'css'                 => 'width: 350px;',
                                'description'         => __('Select which card types to accept. This is to show card icons on the checkout only.', 'ag_epdq_server'),
                                'default'                 => '',
                                'options'                 => array(
                                        'mastercard'                => __('MasterCard', 'ag_epdq_server'),
                                        'amex'         => __('American Express', 'ag_epdq_server'),
                                        'maestro'                        => __('Maestro', 'ag_epdq_server'),
                                        'visa'                                => __('Visa', 'ag_epdq_server'),
                                        'jcb'                                => __('JCB', 'ag_epdq_server'),
                                        'diners'                                => __('Diners', 'ag_epdq_server'),
                                        'discover'                                => __('Discover', 'ag_epdq_server'),
                                ),
                        ),
                        'notice' => array(
                                'title' => __('Enable the redirect notice', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => __('Show customers a notice at checkout that they will be redirected to Barclays server to complete the payment securely', 'ag_epdq_server'),
                                'default' => 'no'
                        ),
                        'debug' => array(
                                'title' => __('Enable Debug', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => 'Enable debug reporting',
                                'default' => 'no',
                                'description' => 'To view the log go <a href="' . site_url('/wp-admin/admin.php?page=wc-status&tab=logs') . '">here</a> and find <strong>AG-WooCommerce-Barclays-ePDQ-Payment-Gateway</strong> in the WooCommerce logs',
                                'desc_tip' => false
                        ),
                        'refund' => array(
                                'title' => __('Refunds', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => __('Process refunds in order screen', 'ag_epdq_server'),
                                'default' => '',
                                'description' => 'Once the save changes button has been clicked two new fields will show which will need to be set to process refunds on the website.',
                                'desc_tip' => true
                        ),
                        'threeds' => array(
                                'title' => __('Enable AG 3Ds score report', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'description' => 'This new feature is coming soon.',
                                'default' => 'no',
                                'desc_tip' => false
                        ),

                );
        }

        /**
         * Refund settings
         *
         * @return void
         */
        public static function form_fields_refund()
        {

                return array(
                        'enabled' => array(
                                'title' => __('Enable/Disable', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => __('Enable ePDQ Checkout', 'ag_epdq_server'),
                                'default' => 'no'
                        ),
                        'logo' => array(
                                'title' => __('Logo for payment page', 'ag_epdq_server'),
                                'type' => 'text',
                                'label' => __('Upload image for payment page', 'ag_epdq_server'),
                                'default' => '',
                                'description' => 'Add an image to display on the payment page - paste in the full image url <strong>You must have an SSL for this to work correctly.</strong>',
                                'desc_tip' => false
                        ),
                        'title' => array(
                                'title' => __('Title', 'ag_epdq_server'),
                                'type' => 'text',
                                'description' => __('Title of the payment process. This name will be visible throughout the site and the payment page.', 'ag_epdq_server'),
                                'default' => 'AG ePDQ Checkout',
                                'desc_tip'      => true
                        ),
                        'description' => array(
                                'title' => __('Description', 'ag_epdq_server'),
                                'type' => 'textarea',
                                'description' => __('Description of the payment process. This description will be visible throughout the site and the payment page.', 'ag_epdq_server'),
                                'default' => 'Use the secure payment processor of Barclaycard and checkout with your debit/credit card.',
                                'desc_tip'      => true
                        ),
                        'access_key' => array(
                                'title' => __('PSPID', 'ag_epdq_server'),
                                'type' => 'text',
                                'description' => __('The PSPID for your barclays account. This is the id which you use to login the admin panel of the barclays bank. <small><strong>Remember you must test using the test ePDQ account Barclays supply you</strong></small>', 'ag_epdq_server'),
                                'default' => '',
                                //'desc_tip'      => true
                        ),
                        'status' => array(
                                'title' => __('Store Status', 'ag_epdq_server'),
                                'type' => 'select',
                                'css'  => 'height: 35px;',
                                'options' => array('test' => 'Test Environment', 'live' => 'Live Store'),
                                'description' => __('The status of your store tells that are you actually ready to run your shop or its still a test environment. If the test is selected then no payments will be processed. For details please refer to the user guide provided by the Barclays EPDQ servise.', 'ag_epdq_server'),
                                'default' => '',
                                'desc_tip'      => true,
                        ),
                        'sha_in' => array(
                                'title' => __('SHA-IN Passphrase', 'ag_epdq_server'),
                                'type' => 'password',
                                'description' => __('The SHA-IN signature will encode the data passed to the payment processor to ensure better security.', 'ag_epdq_server'),
                                //'desc_tip'      => true
                        ),
                        'sha_out' => array(
                                'title' => __('SHA-OUT Passphrase', 'ag_epdq_server'),
                                'type' => 'password',
                                'description' => __('The SHA-OUT signature will encode the data passed back from the payment processor to ensure better security.', 'ag_epdq_server'),
                                //'desc_tip'      => true
                        ),
                        'sha_method' => array(
                                'title' => __('SHA encryption method', 'ag_epdq_server'),
                                'type' => 'select',
                                'css'  => 'height: 35px;',
                                'options' => array(0 => 'SHA-1 (We do not recommend this option, please use another)', 1 => 'SHA-256', 2 => 'SHA-512'),
                                'description' => __('Sha encryption method - this needs to match what you have set in the epdq back office.', 'ag_epdq_server'),
                                'default' => '',
                                'desc_tip'      => true,
                        ),
                        'tip' => array(
                                'title'              => __('Tip', 'ag_epdq_server'),
                                'type'               => 'hidden',
                                'description'        => __('Want to store your API details in a more secure way, read through our doc <a href="https://we-are-ag.helpscoutdocs.com/article/161-storing-strong-api-credentials" target="_blank">here</a>', 'ag_epdq_server'),
                        ),
                        'template' => array(
                                'title' => __('Dynamic template URL', 'ag_epdq_server'),
                                'type' => 'text',
                                'description' => __('The dynamic template page allows you to customise the design of the payment pages in a more advanced way than the static template does.

                When you use a dynamic template page, you fully design your own template page, leaving just one area in that page to be completed by our system. The URL of your template page needs to be sent to us in the hidden fields for each transaction.

                Please bear in mind that using a dynamic template page involves an additional request from our system to look up your template page. This increases the time needed for the payment process.', 'ag_epdq_server'),
                                'desc_tip'      => true
                        ),
                        'pmlisttype'        => array(
                                'title'                 => __('Layout of the payment methods', 'ag_epdq_server'),
                                'type'                         => 'select',
                                'class'                        => 'chosen_select',
                                'css'                 => 'width: 350px;',
                                'description'         => __('You can arrange the layout/list of the payment methods on Barclays payment page', 'ag_epdq_server'),
                                'default'                 => '0',
                                'options'                 => array(
                                        '0'      => __('Horizontally grouped logos with the group name on the left (default value)', 'ag_epdq_server'),
                                        '1'      => __('Horizontally grouped logos with no group names', 'ag_epdq_server'),
                                        '2'      => __('Vertical list of logos with specific payment method or brand name', 'ag_epdq_server'),
                                ),
                        ),
                        'cardtypes'        => array(
                                'title'                 => __('Accepted Cards', 'ag_epdq_server'),
                                'type'                         => 'multiselect',
                                'class'                        => 'chosen_select',
                                'css'                 => 'width: 350px;',
                                'description'         => __('Select which card types to accept. This is to show card icons on the checkout only.', 'ag_epdq_server'),
                                'default'                 => '',
                                'options'                 => array(
                                        'mastercard'                => __('MasterCard', 'ag_epdq_server'),
                                        'amex'         => __('American Express', 'ag_epdq_server'),
                                        'maestro'                        => __('Maestro', 'ag_epdq_server'),
                                        'visa'                           => __('Visa', 'ag_epdq_server'),
                                        'jcb'                            => __('JCB', 'ag_epdq_server'),
                                        'diners'                         => __('Diners', 'ag_epdq_server'),
                                        'discover'                       => __('Discover', 'ag_epdq_server'),
                                ),
                        ),
                        'notice' => array(
                                'title' => __('Enable the redirect notice', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => __('Show customers a notice at checkout that they will be redirected to Barclays server to complete the payment securely', 'ag_epdq_server'),
                                'default' => 'no'
                        ),
                        'debug' => array(
                                'title' => __('Enable Debug', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => 'Enable debug reporting',
                                'default' => 'no',
                                'description' => 'To view the log go <a href="' . site_url('/wp-admin/admin.php?page=wc-status&tab=logs') . '">here</a> and find <strong>AG-WooCommerce-Barclays-ePDQ-Payment-Gateway</strong> in the WooCommerce logs',
                                'desc_tip' => false
                        ),
                        'refund' => array(
                                'title' => __('Refunds', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'label' => __('Process refunds in order screen', 'ag_epdq_server'),
                                'default' => '',
                                'description' => 'Once the save changes button has been clicked two new fields will show which will need to be set to process refunds on the website.',
                                'desc_tip' => true
                        ),
                        'threeds' => array(
                                'title' => __('Enable AG 3Ds score report', 'ag_epdq_server'),
                                'type' => 'checkbox',
                                'description' => 'This new feature is coming soon.',
                                'default' => 'no',
                                'desc_tip' => false
                        ),
                        'api_user' => array(
                                'title'                => __('API User ID', 'ag_epdq_server'),
                                'type'                => 'text',
                                'desc_tip'        => __('This is to enable you to process refunds via the order screen', 'ag_epdq_server'),
                        ),
                        'api_password' => array(
                                'title'                => __('API User Password', 'ag_epdq_server'),
                                'type'                => 'password',
                                'desc_tip'        => __('This is to enable you to process refunds via the order screen', 'ag_epdq_server'),
                        ),
                        'api_REFID' => array(
                                'title'                => __('API REFID', 'ag_epdq_server'),
                                'type'                => 'text',
                                'desc_tip'        => __('you will fine this under the user ID in edit user on the ePDQ back office.', 'ag_epdq_server'),
                        ),

                );
        }
}

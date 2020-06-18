<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ Direct Wizard
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('AG_ePDQ_Wizard_steps')) {
	return;
}


class AG_ePDQ_Wizard_steps
{
	public static $single_instance = null;
	public static $args = array();
    public static $ag_svg = AG_ePDQ_server_path . 'img/ag-logo.png';
    public static $settings_key = 'woocommerce_epdq_checkout_settings';
    public $shaMethod = 1;
    
    
    /**
	 * run
	 */
	 public static function run_instance( $args = array() ) {
		if ( self::$single_instance === null ) {
			self::$args            = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}
	

	public static function steps() {

		$steps = array(
			'wizard_welcome' => array(
				'name'    => '',
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_welcome_screen' ),
				'handler' => '',
			),
			'step_one'    => array(
				'name'    => __( 'Step One', 'AG_ePDQ' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_one' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_one_save' ),
			),
            'step_two'    => array(
				'name'    => __( 'Step Two', 'AG_ePDQ' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_two' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_two_save' ),
            ),
            'step_three'    => array(
				'name'    => __( 'Step Three', 'AG_ePDQ' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_three' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_three_save' ),
			),
			'step_four'     => array(
				'name'    => __( 'Step Four', 'AG_ePDQ' ),
                'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_four' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_four_save' ),
            ),
            'step_five'     => array(
				'name'    => __( 'Step Five', 'AG_ePDQ' ),
                'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_five' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_five_save' ),
            ),
            'step_six'     => array(
				'name'    => __( 'Step Six', 'AG_ePDQ' ),
                'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_six' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_six_save' ),
            ),
            'step_finish'     => array(
				'name'    => __( 'Finish', 'AG_ePDQ' ),
                'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_epdq_step_finish' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_epdq_step_finish_save' ),
			),
        );
        
        return $steps;
        
    }

    public static function wizard_welcome_screen() {
        update_option( 'AG_epdq_setup_wizard_shown', true ); ?>
            <div class="ag_wizard-step__wizard_welcome-wrapper">
                <h1 class="ag-logo"><a href="https://weareag.co.uk/"><img src="<?php echo esc_attr(self::$ag_svg); ?>" /></a></h1>
                <p class="ag_wizard-step__wizard_welcome-welcome">Welcome to our setup wizard for <?php echo self::$args['plugin_name']; ?></p>
                <p>Get your payment gateway up and running in no time. Go grab a cuppa and click the button when you are ready.</p>
    
                <form method="post" class="activate-wizard_welcome">
                    <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
                    <input type="hidden" name="save_step" value="step_one" />
                    <p class="ag_wizard-actions step">
                        <a href="<?php echo esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_one' ); ?>" class="button-primary button button-large" value="" name="save_step">Ready</a>
                    </p>
                </form>
            </div>
        <?php
    }


    public static function wizard_step_one() { 
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>
    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_one" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Step 1</h1>
			<p class="setup-text">
                The ePDQ team will supply you with two logins, one for test and the other for live.<br/>
                It is always best practice when setting up a payment gateway to do this in test mode first and run several orders before switching to live mode.
            </p>
            <section class="callout-yellow">
                <h3>Note</h3>
                <p>You must first do a test transaction using the test ePDQ account.</p>
                <p>Whatever changes to make in the ePDQ test account you must match in the ePDQ live account.</p>
            </section>

            <p class="setup-text">
            We now need to make changes to your test ePDQ account, you can login to the test account <a target="_blank" href="https://mdepayments.epdq.co.uk/Ncol/Test/BackOffice/login/">here</a>.<br /><br /> 
            Once logged in you will see something like below. 
            </p>

            <a href="#img1">
                <img src="https://s3.amazonaws.com/helpscout.net/docs/assets/5bc91552042863158cc79df7/images/5d163d4c2c7d3a6ebd22b54e/file-NHMlQDZl5q.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img1">
                <img src="https://s3.amazonaws.com/helpscout.net/docs/assets/5bc91552042863158cc79df7/images/5d163d4c2c7d3a6ebd22b54e/file-NHMlQDZl5q.png" />
            </a>

            <section class="callout-yellow">
                <h3>Note</h3>
                <p>As mentioned above its important to make sure you use the plugin in test mode and use the ePDQ test account, linking the plugin in test mode to the live ePDQ account will not work.</p>
            </section>

            <p class="setup-text">
                How to know if you are using the ePDQ test account? When you have logged in look to the Barclaycard logo, to the right of it you will see TEST in red like this:
            </p>

                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163d7f2c7d3a6ebd22b551/file-Ye1qV2nsp7.png" class="thumbnail" style="max-width: 250px;" />
     


            <section class="callout-yellow">
                <h3>Note</h3>
                <p>If you don’t have access to the test account, you/your client will need to email the ePDQ team here: <a href="mailto:epdqsupport@barclaycard.co.uk">epdqsupport@barclaycard.co.uk</a> and ask for logins again. Its also important to note that if the ePDQ account is new you won’t be able to switch to live mode until you have done several test orders and applied to go live (You will see the option the main ePDQ Home screen).</p>
            </section>

            <p class="setup-text">
                Lets start with your PSPID, this is your ePDQ login ID.
            </p>

            <div class="input-wrap">
                <label class="wizard-prompt" for="pspid">PSPID</label>
                <input type="text" id="pspid" class="wizard-input" name="pspid" required value="<?php echo isset($saved_settings['access_key']) ? $saved_settings['access_key'] : ''; ?>" />
            </div>

			<p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Let's go!" name="save_step">Let's go!</button>
			</p>
        </form>
        
		<?php
	}



	public static function wizard_step_one_save() {

        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        
        $pspid = isset( $_POST['pspid'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['pspid'] ) : '';
        $settings = array( 'enabled' => 'yes', 'access_key' => $pspid );

        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
        update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_two' ) );
        exit;
        
    }


    public static function wizard_step_two() { 
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>
    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_two" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Step 2</h1>
			<p class="setup-text">
                In this section, we pick and match the SHA encryption method we set in the plugin settings.<br/>
                Technical information > Global transaction parameters to set this in the ePDQ back office.
            </p>

            <a href="#img3">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ddc04286305cb87dbe2/file-conJqXDz1U.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img3">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ddc04286305cb87dbe2/file-conJqXDz1U.png" />
            </a>

            <p class="setup-text">
             Based on these two steps, you can choose between two default operation codes:
            </p>

            <ol> 
                <li><strong>Authorisation</strong>:&nbsp;our system will only ask for an authorisation, in order to have the authorisation and data capture (payment request) steps performed separately at different times (the money remains in the customer’s account until a data capture (payment request) has been performed).</li> 
                <li><strong>Sale</strong>:&nbsp;our system automatically requests the payment (transfer of the amount) immediately after a successful authorisation. This procedure is often used for goods/services delivered online.</li>
            </ol>

            <section class="callout-yellow">
                <h3>Note</h3>
                <p>If you are unsure what to enable ask your client what they have been using, most users enable the sale method but it is down to how the business operates.</p>
            </section>

            <p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
			</p>

            
        </form>
        
		<?php
	}



	public static function wizard_step_two_save() {

        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        
        //$api_user = isset( $_POST['api_user'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['api_user'] ) : '';
        //$api_password = isset( $_POST['api_password'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['api_password'] ) : '';
        //$settings = array( 'enabled' => 'yes', 'api_user' => $api_user, 'api_password' => $api_password );

        //$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
        //update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_three' ) );
        exit;
        
    }


    public static function wizard_step_three() { 
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>

    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_three" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Step 3</h1>
			<p class="setup-text">
                Next we are going to pick the SHA encryption method.<br />
                Technical information > Global security parameters to set this in the ePDQ back office.
            </p>

            <a href="#img5">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163e312c7d3a6ebd22b559/file-NY6nfp5G1m.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img5">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163e312c7d3a6ebd22b559/file-NY6nfp5G1m.png" />
            </a>

            <section class="callout-yellow">
                <h3>Note</h3>
                <p>We recommend using SHA-256 or SHA-512.</p>
            </section>

            <div class="input-wrap">
                <label for="sha_method">SHA encryption method</label>
                <select class="select " name="sha_method" id="sha_method">
                    <?php 
                    $sha_method = isset($saved_settings['sha_method']) ? $saved_settings['sha_method'] : '0';
                    echo '<option value="" disabled>Select One</option>
                    <option value="" disabled>----------------</option>
                    <option value="0" ' . ($sha_method == '0' ? 'selected' : '') . '>SHA-1</option>
                    <option value="1" ' . ($sha_method == '1' ? 'selected' : '') . '>SHA-256</option>
                    <option value="2" ' . ($sha_method == '2' ? 'selected' : '') . '>SHA-512</option>'; 
                    ?>
                </select>
            </div>

			<p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
			</p>
        </form>
        
		<?php
	}



	public static function wizard_step_three_save() {

        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        
        $sha_method = isset( $_POST['sha_method'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['sha_method'] ) : $shaMethod;
        $settings = array( 'sha_method' => $sha_method );
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
        update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_four' ) );
        exit;
        
    }


    public static function wizard_step_four() { 
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>
    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_four" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Step 4</h1>
			<p class="setup-text">
                Next we are going to set the SHA-IN pass phrase and the URL of the merchant page.<br />
                Technical information > Data and origin verification to set this in the ePDQ back office.
            </p>

            <p class="setup-text">
                The first item is setting the payment page URL, this is the last URL on the website before the customer is sent to the ePDQ servers to make the payment, It normally looks like the following: <strong><?php echo wc_get_checkout_url() . 'order-pay/'; ?></strong>
            </p>

            <p class="setup-text">
            without this set correctly the payment gateway will not work, it is possible to have multiple domains/websites in this field also. Simply add a ; between the URL's as shown.
            </p>

            <a href="#img6">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163e8704286305cb87dbee/file-V3W66lDkDe.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img6">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163e8704286305cb87dbee/file-V3W66lDkDe.png" />
            </a>

            <p class="setup-text">
                Next item here is the SHA-IN (this is a basically a password), this must match what you have in the plugin settings, We recommend that you use letters and numbers only in this setting and that it has a maximum of 16 characters.
            </p>

            <a href="#imgsha">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ea82c7d3a6ebd22b566/file-hAVxJDbYTw.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="imgsha">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ea82c7d3a6ebd22b566/file-hAVxJDbYTw.png" />
            </a>


            <section class="callout-yellow">
                <h3>Note</h3>
                <p>You only need to edit the top two fields 'URL of the merchant page containing the payment form that will call the page...' and the 'SHA-IN pass phrase'.<br />
                <strong>Do not</strong> place the SHA-IN pass phrase on the fields under the 'Checks For Barclaycard Direct Link' as this may cause your payments to not go through.</p>
            </section>

            <div class="input-wrap">
                <label class="wizard-prompt" for="sha_in">SHA-IN</label>
			    <input type="password" id="sha_in" class="wizard-input" name="sha_in" required value="<?php echo isset($saved_settings['sha_in']) ? $saved_settings['sha_in'] : ''; ?>" />
            </div>

			<p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
			</p>
        </form>
        
		<?php
	}



	public static function wizard_step_four_save() {

        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        
        $sha_in = isset( $_POST['sha_in'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['sha_in'] ) : '';
        $settings = array( 'sha_in' => $sha_in );
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
        update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_five' ) );
        exit;
        
    }

    public static function wizard_step_five() { 

        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>
    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_five" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Step 5</h1>
			<p class="setup-text">
                Next we we have a few settings to set in the transaction feedback section.<br />
                Technical information > Transaction feedback to set this in the ePDQ back office.
            </p>

            <a href="#img7">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ecb04286305cb87dbf2/file-Z5gKVy1npw.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img7">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ecb04286305cb87dbf2/file-Z5gKVy1npw.png" />
            </a>

            <p class="setup-text">
                First we need to make sure that the tick box labelled "I would like to receive transaction feedback parameters on the redirection URLs." is ticked.
            </p>

            <div class="input-wrap">
                <label for="ePDQ_redirect">I have ticked the checkbox in the ePDQ back office.</label>
                <input  type="checkbox" name="ePDQ_redirect" id="ePDQ_redirect"  value="1" <?php if (get_option('ag_ePDQ_redirect') == 1) { echo "checked='checked'"; } ?> required>
            </div>

            <section class="callout-yellow" style="margin-top: 20px;">
                <h3>Note</h3>
                <p>Without this ticked the ePDQ system won't send data back to the website. This means you will get orders with pending payment and then fail.</p>
                <p>There should be nothing in the four fields above this tick box, the plugin will do all the hard work of setting these URLs on its own.</p>
            </section>

            <p class="setup-text">
                Next we are going to set the SHA-OUT, you will need to scroll down a little to see the option.
            </p>

            <a href="#img8">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163eff04286305cb87dbf3/file-BZeCzr2Zux.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img8">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163eff04286305cb87dbf3/file-BZeCzr2Zux.png" />
            </a>

            <section class="callout-yellow">
                <h3>Note</h3>
                <p>We recommend that you use letters and numbers only in this setting and that it has a maximum of 16 characters.</p>
            </section>

            <div class="input-wrap">
                <label class="wizard-prompt" for="sha_out">SHA-OUT</label>
                <input type="password" id="sha_out" class="wizard-input" name="sha_out" required value="<?php echo isset($saved_settings['sha_out']) ? $saved_settings['sha_out'] : ''; ?>" />
            </div>

            <p class="setup-text">
                The final item in this step is setting the parameters which will be sent back to the website.<br />
                This is in the Dynamic e-Commerce parameters section.
            </p>

            <a href="#img9">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163f492c7d3a6ebd22b56d/file-esNEnYMZEL.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img9">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163f492c7d3a6ebd22b56d/file-esNEnYMZEL.png" />
            </a>

            <p class="setup-text">
                You <strong>only</strong> want the following in the selected section:<br />
                AAVADDRESS<br />
                ACCEPTANCE<br />
                COMPLUS<br />
                NCERROR<br />
                ORDERID<br />
                PAYID<br />
                STATUS
            </p>

            <section class="callout-yellow">
                <h3>Note</h3>
                <p>On PC/Windows you can select multiple by holding CTRL and clicking the items you want.</p>
                <p>On Mac you can select multiple by holding Command key and clicking the items you want.</p>
            </section>

            <div class="input-wrap">
                <label for="ePDQ_pram">I have set the correct parameters in the ePDQ back office.</label>
                <input  type="checkbox" name="ePDQ_pram" id="ePDQ_pram"  value="1" <?php if (get_option('ag_ePDQ_pram') == 1) { echo "checked='checked'"; } ?> required>
            </div>

			<p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
			</p>
        </form>
        
		<?php
	}



	public static function wizard_step_five_save() {

        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        
        $sha_out = isset( $_POST['sha_out'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['sha_out'] ) : '';
        $settings = array( 'sha_out' => $sha_out );
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
        update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

        $ePDQ_redirect = isset( $_POST['ePDQ_redirect'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['ePDQ_redirect'] ) : '';
        update_option( 'ag_ePDQ_redirect', $ePDQ_redirect );

        $ePDQ_pram = isset( $_POST['ePDQ_pram'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['ePDQ_pram'] ) : '';
        update_option( 'ag_ePDQ_pram', $ePDQ_pram );

		wp_safe_redirect( esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_six' ) );
        exit;
        
    }


    public static function wizard_step_six() { 
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>
    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_six" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Step 6</h1>
            <p class="setup-text">
                We are almost done now, well done!
            </p>
			<p class="setup-text">
                We now need to make sure that we have 3D Secure enabled.<br />
                Advanced > Fraud detection to set this in the ePDQ back office.
            </p>

            <a href="#img10">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d16550e04286305cb87dcf2/file-HcmFu7t97B.png" class="thumbnail" />
            </a>
            <a href="#_" class="lightbox" id="img10">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d16550e04286305cb87dcf2/file-HcmFu7t97B.png" />
            </a>

            <p class="setup-text">
                The key here is to make sure that 3D secure is active on the card brands you wish to use on the website.
            </p>

            <div class="input-wrap">
                <label for="3dsecure">I have 3D Secure enabled in the ePDQ back office.</label>
                <input  type="checkbox" name="3dsecure" id="3dsecure"  value="1" <?php if (get_option('ag_3dsecure') == 1) { echo "checked='checked'"; } ?> required>
            </div>

            <p class="setup-text">
                Now the important thing to do is test the payment gateway a few times before going live.<br /><br/>
                If you have any errors/issues you first want to check the ePDQ error log section in the back office.
            </p>

            <section class="callout-yellow">
                <h3>Note</h3>
                <p>In the description field, you will most likely find an error code, something like <strong>unknown order/1/r</strong> if you do you can have a look <a href="https://we-are-ag.helpscoutdocs.com/article/41-error-codes" target="_blank">here</a> at the troubleshooting error codes. There you will find out what the issues is and what is needed to fix it.</p>
            </section>

            <p class="setup-text">
                The plugin has a debug log system that catches most errors or issues, this is a good starting point should you have any issues.
            </p>

            <div class="input-wrap">
                <label for="debug">I would like to enable the plugins error log.</label>
                <input type="checkbox" name="debug" id="debug" <?php 
                $debug = isset($saved_settings['debug']) ? $saved_settings['debug'] : 'no';
                if ($debug == 'yes') { echo "checked='checked'"; } ?> value="yes">
            </div>

            <div class="input-wrap">
                <label for="product">I would like to add a test product.</label>
                <input  type="checkbox" name="product" id="product"  <?php if (get_option('ag_product_added') == 'yes') { echo "checked='checked'"; } ?> value="yes">
            </div>

			<p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
			</p>
        </form>
        
		<?php
	}



	public static function wizard_step_six_save() {

        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        
        $debug = isset( $_POST['debug'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['debug'] ) : '';
        $settings = array( 'debug' => $debug );
        
        $saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
        update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

        $product = isset( $_POST['product'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['product'] ) : '';
        if($product === 'yes') {

            $args = array(	   
                'post_author' => 1, 
                'post_content' => 'This is a test product to test a We are AG payment gateway.',
                'post_status' => "private",
                'post_title' => 'We are AG test product',
                'post_parent' => '',
                'post_type' => "product"
            ); 
            
            $post_id = wp_insert_post( $args );
            wp_set_object_terms( $post_id, 'simple', 'product_type' );
            update_post_meta( $post_id, '_price', 1 );
            
        }

        
        $secure = isset( $_POST['3dsecure'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['3dsecure'] ) : '';
        update_option( 'ag_3dsecure', $secure );

        
        $product = isset( $_POST['product'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['product'] ) : '';
        update_option( 'ag_product_added', $product );

		wp_safe_redirect( esc_url_raw( '?page='.self::$args['plugin_name'].'-wizard&step=step_finish' ) );
        exit;
        
    }


    public static function wizard_epdq_step_finish() { ?>
    
		<form method="post" class="step">
			<input type="hidden" name="save_step" value="step_finish" />
            <?php wp_nonce_field( self::$args['plugin_name'].'-wizard' ); ?>
            <h1>Congratulations!</h1>
            <p class="setup-text">
                The plugin has been setup and your gateways is ready.<br />
                Tell the world how easy this setup was!
            </p>

            <a href="https://twitter.com/share" class="twitter-share-button"
			   data-url="https://weareag.co.uk"
			   data-text="<?php echo esc_attr( 'I just setup the ' . self::$args['plugin_name'] . ' #WooCommerce payment gateway from #We_are_AG' ); ?>"
			   data-via="We_are_AG" data-size="large">Tweet</a>
			<script>
            !function (d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (!d.getElementById(id)) {
						js = d.createElement(s);
						js.id = id;
						js.src = "//platform.twitter.com/widgets.js";
						fjs.parentNode.insertBefore(js, fjs);
					}
                }(document, "script", "twitter-wjs");
            </script>

            <a href="#" 
            onclick="
            window.open(
                'https://www.facebook.com/sharer/sharer.php?u=https://weareag.co.uk&quote=I just setup the <?php echo self::$args['plugin_name']; ?> WooCommerce payment gateway from We are AG and it was super simple!', 
                'facebook-share-dialog', 
                'width=626,height=436'); 
                return false;">
            <img class="fb-share" src="<?php echo AG_ePDQ_server_path . 'img/fb-share.png'; ?>" />
            </a>                

			<p class="setup-text">
                Please come back and <a href="https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/" target="_blank">leave a 5-star rating</a> if you are happy with this plugin.
            </p>



            <h2>More Resources</h2>
				<ul>
                    <li class="documentation">
                        <a href="https://we-are-ag.helpscoutdocs.com/article/45-setup" target="_blank">
                            Read the Documentation
                        </a>
                    </li>
                    <li class="troubleshooting">
                        <a href="https://we-are-ag.helpscoutdocs.com/category/33-category" target="_blank">
                            See the troubleshooting section
                        </a>
					</li>
                    <li class="rating">
                        <a href="https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/" target="_blank">
                            Leave a review
                        </a>
                    </li>
                    <li class="support">
                        <a href="https://weareag.co.uk/support/" target="_blank">
                            Get Help and Support
                        </a>
                    </li>
                </ul>
                



			<p class="ag_wizard-actions step">
				<button class="button-primary button button-large" value="Finish!" name="save_step">Finish!</button>
			</p>
        </form>
        
		<?php
	}



	public static function wizard_epdq_step_finish_save() {
        
        check_admin_referer( self::$args['plugin_name'].'-wizard' );
        wp_safe_redirect( esc_url_raw( admin_url() ) );
        exit;
        
    }
    
}
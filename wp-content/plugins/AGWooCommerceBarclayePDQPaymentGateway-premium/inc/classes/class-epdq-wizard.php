<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Wizard
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );


if ( class_exists( 'AG_ePDQ_Wizard_steps' ) ) {
	return;
}


class AG_ePDQ_Wizard_steps {
	public static $single_instance = null;
	public static $args = array();
	public static $ag_svg = AG_ePDQ_server_path . 'inc/assets/img/ag-logo.png'; // @phpstan-ignore-line
	public static $settings_key = 'woocommerce_epdq_checkout_settings';
	public static $shaMethod = 1;


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
			'step_one'       => array(
				'name'    => __( 'Step One', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_one' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_one_save' ),
			),
			'step_two'       => array(
				'name'    => __( 'Step Two', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_two' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_two_save' ),
			),
			'step_three'     => array(
				'name'    => __( 'Step Three', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_three' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_three_save' ),
			),
			'step_four'      => array(
				'name'    => __( 'Step Four', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_four' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_four_save' ),
			),
			'step_five'      => array(
				'name'    => __( 'Step Five', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_five' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_five_save' ),
			),
			'step_six'       => array(
				'name'    => __( 'Step Six', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_six' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_step_six_save' ),
			),
			'step_finish'    => array(
				'name'    => __( 'Finish', 'ag_epdq_server' ),
				'view'    => array( 'AG_ePDQ_Wizard_steps', 'wizard_epdq_step_finish' ),
				'handler' => array( 'AG_ePDQ_Wizard_steps', 'wizard_epdq_step_finish_save' ),
			),
		);

		return $steps;

	}

	public static function wizard_welcome_screen() {
		update_option( 'AG_epdq_setup_wizard_shown', true ); ?>
        <div class="ag_wizard-step__wizard_welcome-wrapper">
            <h1 class="ag-logo"><a href="https://weareag.co.uk/"><img
                            src="<?php echo esc_attr( self::$ag_svg ); ?>"/></a></h1>
            <p class="ag_wizard-step__wizard_welcome-welcome"><?php echo __( 'Welcome to our setup wizard for AG ePDQ', 'ag_epdq_server' ); ?></p>
            <p><?php echo __( 'Get your payment gateway up and running in no time. Go grab a cuppa and click the button when you are ready.', 'ag_epdq_server' ); ?></p>

            <form method="post" class="activate-wizard_welcome">
				<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
                <input type="hidden" name="save_step" value="step_one"/>
                <p class="ag_wizard-actions step">
                    <a href="<?php echo esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_one' ); ?>"
                       class="button-primary button button-large" value="" name="save_step">Ready</a>
                </p>
            </form>
        </div>
		<?php
	}


	public static function wizard_step_one() {

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>

        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_one"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Step 1', 'ag_epdq_server' ) ?></h1>
            <p class="setup-text">
				<?php echo __( 'The ePDQ team will supply you with two logins, one for test and the other for live.<br/>
                It is always best practice when setting up a payment gateway to do this in test mode first and run several orders before switching to live mode.', 'ag_epdq_server' ); ?>
            </p>
            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'You must first do a test transaction using the test ePDQ account.', 'ag_epdq_server' ); ?></p>
                <p><?php echo __( 'Whatever changes to make in the ePDQ test account you must match in the ePDQ live account.', 'ag_epdq_server' ); ?></p>
            </section>

            <p class="setup-text">
				<?php echo __( 'We now need to make changes to your test ePDQ account, you can login to the test account <a target="_blank" href="https://mdepayments.epdq.co.uk/Ncol/Test/BackOffice/login/">here</a>.<br /><br /> 
            Once logged in you will see something like below.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img1">
                <img src="https://s3.amazonaws.com/helpscout.net/docs/assets/5bc91552042863158cc79df7/images/5d163d4c2c7d3a6ebd22b54e/file-NHMlQDZl5q.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img1">
                <img src="https://s3.amazonaws.com/helpscout.net/docs/assets/5bc91552042863158cc79df7/images/5d163d4c2c7d3a6ebd22b54e/file-NHMlQDZl5q.png"/>
            </a>

            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'As mentioned above its important to make sure you use the plugin in test mode and use the ePDQ test account, linking the plugin in test mode to the live ePDQ account will not work.', 'ag_epdq_server' ); ?></p>
            </section>

            <p class="setup-text">
				<?php echo __( 'How to know if you are using the ePDQ test account? When you have logged in look to the Barclaycard logo, to the right of it you will see TEST in red like this:', 'ag_epdq_server' ); ?>
            </p>

            <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163d7f2c7d3a6ebd22b551/file-Ye1qV2nsp7.png"
                 class="thumbnail" style="max-width: 250px;"/>


            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'If you don’t have access to the test account, you/your client will need to email the ePDQ team here: <a href="mailto:epdqsupport@barclaycard.co.uk">epdqsupport@barclaycard.co.uk</a> and ask for logins again. Its also important to note that if the ePDQ account is new you won’t be able to switch to live mode until you have done several test orders and applied to go live (You will see the option the main ePDQ Home screen).', 'ag_epdq_server' ); ?></p>
            </section>

            <p class="setup-text">
				<?php echo __( 'Lets start with your PSPID, this is your ePDQ login ID.', 'ag_epdq_server' ); ?>
            </p>

            <div class="input-wrap">
                <label class="wizard-prompt" for="pspid">PSPID</label>
                <input type="text" id="pspid" class="wizard-input" name="pspid" required
                       value="<?php echo $saved_settings['access_key'] ?? ''; ?>"/>
            </div>

            <p class="ag_wizard-actions step">
                <button class="button-primary button button-large" value="Let's go!" name="save_step">Let's go!</button>
            </p>
        </form>

		<?php
	}


	public static function wizard_step_one_save() {

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );

		$pspid    = isset( $_POST['pspid'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['pspid'] ) : '';
		$settings = array( 'enabled' => 'yes', 'access_key' => $pspid );

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
		update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_two' ) );
		exit;

	}


	public static function wizard_step_two() {

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>

        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_two"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Step 2', 'ag_epdq_server' ); ?></h1>
            <p class="setup-text">
				<?php echo __( 'In this section, we pick and match the SHA encryption method we set in the plugin settings.<br/>
                Technical information > Global transaction parameters to set this in the ePDQ back office.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img3">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ddc04286305cb87dbe2/file-conJqXDz1U.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img3">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ddc04286305cb87dbe2/file-conJqXDz1U.png"/>
            </a>

            <p class="setup-text">
				<?php echo __( 'Based on these two steps, you can choose between two default operation codes:', 'ag_epdq_server' ); ?>
            </p>

            <ol>
                <li><?php echo __( '<strong>Authorisation</strong>:&nbsp;our system will only ask for an authorisation, in order to have the authorisation and data capture (payment request) steps performed separately at different times (the money remains in the customer’s account until a data capture (payment request) has been performed).', 'ag_epdq_server' ); ?></li>
				<?php echo __( '<li><strong>Sale</strong>:&nbsp;our system automatically requests the payment (transfer of the amount) immediately after a successful authorisation. This procedure is often used for goods/services delivered online.', 'ag_epdq_server' ); ?></li>
            </ol>

            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'If you are unsure what to enable ask your client what they have been using, most users enable the sale method but it is down to how the business operates.', 'ag_epdq_server' ); ?></p>
            </section>

            <p class="ag_wizard-actions step">
                <button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
            </p>


        </form>

		<?php
	}


	public static function wizard_step_two_save() {

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );

		//$api_user = isset( $_POST['api_user'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['api_user'] ) : '';
		//$api_password = isset( $_POST['api_password'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['api_password'] ) : '';
		//$settings = array( 'enabled' => 'yes', 'api_user' => $api_user, 'api_password' => $api_password );

		//$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
		//update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_three' ) );
		exit;

	}


	public static function wizard_step_three() {

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>


        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_three"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Step 3', 'ag_epdq_server' ); ?></h1>
            <p class="setup-text">
				<?php echo __( 'Next we are going to pick the SHA encryption method.<br />
                Technical information > Global security parameters to set this in the ePDQ back office.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img5">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163e312c7d3a6ebd22b559/file-NY6nfp5G1m.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img5">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163e312c7d3a6ebd22b559/file-NY6nfp5G1m.png"/>
            </a>

            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'We recommend using SHA-256 or SHA-512.', 'ag_epdq_server' ); ?></p>
            </section>

            <div class="input-wrap">
                <label for="sha_method"><?php echo __( 'SHA encryption method', 'ag_epdq_server' ); ?></label>
                <select class="select " name="sha_method" id="sha_method">
					<?php
					$sha_method = $saved_settings['sha_method'] ?? '0';
					echo '<option value="" disabled>' . __( 'Select One', 'ag_epdq_server' ) . '</option>
                    <option value="" disabled>----------------</option>
                    <option value="0" ' . ( $sha_method === '0' ? 'selected' : '' ) . '>SHA-1</option>
                    <option value="1" ' . ( $sha_method === '1' ? 'selected' : '' ) . '>SHA-256</option>
                    <option value="2" ' . ( $sha_method === '2' ? 'selected' : '' ) . '>SHA-512</option>';
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

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );

		$sha_method = isset( $_POST['sha_method'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['sha_method'] ) : self::$shaMethod;
		$settings   = array( 'sha_method' => $sha_method );

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
		update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_four' ) );
		exit;

	}


	public static function wizard_step_four() {

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>

        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_four"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Step 4', 'ag_epdq_server' ); ?></h1>
            <p class="setup-text">
				<?php echo __( 'Next we are going to set the SHA-IN pass phrase and the URL of the merchant page.<br />
                Technical information > Data and origin verification to set this in the ePDQ back office.', 'ag_epdq_server' ); ?>
            </p>

            <p class="setup-text">
				<?php echo __( 'The first item is setting the payment page URL, this is the domain before the customer is sent to the ePDQ servers to make the payment, It normally looks like the following:', 'ag_epdq_server' ); ?>
                <strong><?php echo site_url(); ?></strong>
            </p>

            <p class="setup-text">
				<?php echo __( 'without this set correctly the payment gateway will not work, it is possible to have multiple domains/websites in this field also. Simply add a ; between the URLs as shown.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img6">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5f6dce13cff47e00168fa561/file-wkhS8JsD0D.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img6">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5f6dce13cff47e00168fa561/file-wkhS8JsD0D.png"/>
            </a>

            <p class="setup-text">
				<?php echo __( 'Next item here is the SHA-IN (this is a basically a password), this must match what you have in the plugin settings, We recommend that you use letters and numbers only in this setting and that it has a maximum of 16 characters.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#imgsha">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ea82c7d3a6ebd22b566/file-hAVxJDbYTw.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="imgsha">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ea82c7d3a6ebd22b566/file-hAVxJDbYTw.png"/>
            </a>


            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'You only need to edit the top two fields "URL of the merchant page containing the payment form that will call the page..." and the "SHA-IN pass phrase".<br />
                <strong>Do not</strong> place the SHA-IN pass phrase on the fields under the "Checks For Barclaycard Direct Link" as this may cause your payments to not go through.', 'ag_epdq_server' ); ?></p>
            </section>

            <div class="input-wrap">
                <label class="wizard-prompt" for="sha_in">SHA-IN</label>
                <input type="password" id="sha_in" class="wizard-input" name="sha_in" required
                       value="<?php echo $saved_settings['sha_in'] ?? ''; ?>"/>
            </div>

            <p class="ag_wizard-actions step">
                <button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
            </p>
        </form>

		<?php
	}


	public static function wizard_step_four_save() {

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );

		$sha_in   = isset( $_POST['sha_in'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['sha_in'] ) : '';
		$settings = array( 'sha_in' => $sha_in );

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
		update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		wp_safe_redirect( esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_five' ) );
		exit;

	}

	public static function wizard_step_five() {

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>

        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_five"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Step 5', 'ag_epdq_server' ); ?></h1>
            <p class="setup-text">
				<?php echo __( 'Next we we have a few settings to set in the transaction feedback section.<br />
                Technical information > Transaction feedback to set this in the ePDQ back office.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img7">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ecb04286305cb87dbf2/file-Z5gKVy1npw.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img7">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163ecb04286305cb87dbf2/file-Z5gKVy1npw.png"/>
            </a>

            <p class="setup-text">
				<?php echo __( 'First we need to make sure that the tick box labelled "I would like to receive transaction feedback parameters on the redirection URLs." is ticked.', 'ag_epdq_server' ); ?>
            </p>

            <div class="input-wrap">
                <label for="ePDQ_redirect"><?php echo __( 'I have ticked the checkbox in the ePDQ back office.', 'ag_epdq_server' ); ?></label>
                <input type="checkbox" name="ePDQ_redirect" id="ePDQ_redirect"
                       value="1" <?php if ( get_option( 'ag_ePDQ_redirect' ) === 1 ) {
					echo "checked='checked'";
				} ?> required>
            </div>

            <section class="callout-yellow" style="margin-top: 20px;">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'Without this ticked the ePDQ system wont send data back to the website. This means you will get orders with pending payment and then fail.', 'ag_epdq_server' ); ?></p>
                <p><?php echo __( 'There should be nothing in the four fields above this tick box, the plugin will do all the hard work of setting these URLs on its own.', 'ag_epdq_server' ); ?></p>
            </section>

            <p class="setup-text">
				<?php echo __( 'Next we are going to set the SHA-OUT, you will need to scroll down a little to see the option.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img8">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163eff04286305cb87dbf3/file-BZeCzr2Zux.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img8">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163eff04286305cb87dbf3/file-BZeCzr2Zux.png"/>
            </a>

            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'We recommend that you use letters and numbers only in this setting and that it has a maximum of 16 characters.', 'ag_epdq_server' ); ?></p>
            </section>

            <div class="input-wrap">
                <label class="wizard-prompt" for="sha_out">SHA-OUT</label>
                <input type="password" id="sha_out" class="wizard-input" name="sha_out" required
                       value="<?php echo $saved_settings['sha_out'] ?? ''; ?>"/>
            </div>

            <p class="setup-text">
				<?php echo __( 'The final item in this step is setting the parameters which will be sent back to the website.<br />
                This is in the Dynamic e-Commerce parameters section.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img9">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163f492c7d3a6ebd22b56d/file-esNEnYMZEL.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img9">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d163f492c7d3a6ebd22b56d/file-esNEnYMZEL.png"/>
            </a>

            <p class="setup-text">
				<?php echo __( 'You <strong>only</strong> want the following in the selected section:<br />', 'ag_epdq_server' ); ?>
                AAVADDRESS<br/>
                ACCEPTANCE<br/>
                COMPLUS<br/>
                NCERROR<br/>
                ORDERID<br/>
                PAYID<br/>
                STATUS
            </p>

            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'On PC/Windows you can select multiple by holding CTRL and clicking the items you want.', 'ag_epdq_server' ); ?></p>
                <p><?php echo __( 'On Mac you can select multiple by holding Command key and clicking the items you want.', 'ag_epdq_server' ); ?></p>
            </section>

            <div class="input-wrap">
                <label for="ePDQ_pram"><?php echo __( 'I have set the correct parameters in the ePDQ back office.', 'ag_epdq_server' ); ?></label>
                <input type="checkbox" name="ePDQ_pram" id="ePDQ_pram"
                       value="1" <?php if ( get_option( 'ag_ePDQ_pram' ) === 1 ) {
					echo "checked='checked'";
				} ?> required>
            </div>

            <p class="ag_wizard-actions step">
                <button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
            </p>
        </form>

		<?php
	}


	public static function wizard_step_five_save() {

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );

		$sha_out  = isset( $_POST['sha_out'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['sha_out'] ) : '';
		$settings = array( 'sha_out' => $sha_out );

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
		update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		$ePDQ_redirect = isset( $_POST['ePDQ_redirect'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['ePDQ_redirect'] ) : '';
		update_option( 'ag_ePDQ_redirect', $ePDQ_redirect );

		$ePDQ_pram = isset( $_POST['ePDQ_pram'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['ePDQ_pram'] ) : '';
		update_option( 'ag_ePDQ_pram', $ePDQ_pram );

		wp_safe_redirect( esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_six' ) );
		exit;

	}


	public static function wizard_step_six() {

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) ); ?>

        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_six"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Step 6', 'ag_epdq_server' ); ?></h1>
            <p class="setup-text">
				<?php echo __( ' We are almost done now, well done!', 'ag_epdq_server' ); ?>
            </p>
            <p class="setup-text">
				<?php echo __( 'We now need to make sure that we have 3D Secure enabled.<br />
                Advanced > Fraud detection to set this in the ePDQ back office.', 'ag_epdq_server' ); ?>
            </p>

            <a href="#img10">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d16550e04286305cb87dcf2/file-HcmFu7t97B.png"
                     class="thumbnail"/>
            </a>
            <a href="#_" class="lightbox" id="img10">
                <img src="https://d33v4339jhl8k0.cloudfront.net/docs/assets/5bc91552042863158cc79df7/images/5d16550e04286305cb87dcf2/file-HcmFu7t97B.png"/>
            </a>

            <p class="setup-text">
				<?php echo __( 'The key here is to make sure that 3D secure is active on the card brands you wish to use on the website.', 'ag_epdq_server' ); ?>
            </p>

            <div class="input-wrap">
                <label for="3dsecure"><?php echo __( 'I have 3D Secure enabled in the ePDQ back office.', 'ag_epdq_server' ); ?></label>
                <input type="checkbox" name="3dsecure" id="3dsecure"
                       value="1" <?php if ( get_option( 'ag_3dsecure' ) === 1 ) {
					echo "checked='checked'";
				} ?> required>
            </div>

            <p class="setup-text">
				<?php echo __( 'Now the important thing to do is test the payment gateway a few times before going live.<br /><br/>
                If you have any errors/issues you first want to check the ePDQ error log section in the back office.', 'ag_epdq_server' ); ?>
            </p>

            <section class="callout-yellow">
                <h3><?php echo __( 'Note', 'ag_epdq_server' ); ?></h3>
                <p><?php echo __( 'In the description field, you will most likely find an error code, something like <strong>unknown order/1/r</strong> if you do you can have a look <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/error-codes/" target="_blank">here</a> at the troubleshooting error codes. There you will find out what the issues is and what is needed to fix it.', 'ag_epdq_server' ); ?></p>
            </section>

            <p class="setup-text">
				<?php echo __( 'The plugin has a debug log system that catches most errors or issues, this is a good starting point should you have any issues.', 'ag_epdq_server' ); ?>
            </p>

            <div class="input-wrap">
                <label for="debug"><?php echo __( 'I would like to enable the plugins error log.', 'ag_epdq_server' ); ?></label>
                <input type="checkbox" name="debug" id="debug" <?php
				$debug = $saved_settings['debug'] ?? 'no';
				if ( $debug === 'yes' ) {
					echo "checked='checked'";
				} ?> value="yes">
            </div>

            <div class="input-wrap">
                <label for="product"><?php echo __( 'I would like to add a test product.', 'ag_epdq_server' ); ?></label>
                <input type="checkbox" name="product"
                       id="product" <?php if ( get_option( 'ag_product_added' ) === 'yes' ) {
					echo "checked='checked'";
				} ?> value="yes">
            </div>

            <p class="ag_wizard-actions step">
                <button class="button-primary button button-large" value="Next Step" name="save_step">Next Step</button>
            </p>
        </form>

		<?php
	}


	public static function wizard_step_six_save() {

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );

		$debug    = isset( $_POST['debug'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['debug'] ) : '';
		$settings = array( 'debug' => $debug );

		$saved_settings = array_filter( (array) get_option( self::$settings_key, array() ) );
		update_option( self::$settings_key, array_merge( $saved_settings, $settings ) );

		$product = isset( $_POST['product'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['product'] ) : '';
		if ( $product === 'yes' && ! get_page_by_title( 'We are AG test product', OBJECT, 'product' ) ) {

			$args = array(
				'post_author'  => 1,
				'post_content' => 'This is a test product to test a We are AG payment gateway.',
				'post_status'  => "private",
				'post_title'   => 'We are AG test product',
				'post_parent'  => '',
				'post_type'    => "product"
			);

			$post_id = wp_insert_post( $args );
			wp_set_object_terms( $post_id, 'simple', 'product_type' );
			update_post_meta( $post_id, '_price', 1 );

		}


		$secure = isset( $_POST['3dsecure'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['3dsecure'] ) : '';
		update_option( 'ag_3dsecure', $secure );


		$product = isset( $_POST['product'] ) ? AG_ePDQ_Helpers::AG_escape( $_POST['product'] ) : '';
		update_option( 'ag_product_added', $product );

		wp_safe_redirect( esc_url_raw( '?page=' . self::$args['plugin_name'] . '-wizard&step=step_finish' ) );
		exit;

	}


	public static function wizard_epdq_step_finish() { ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
        <script src="<?php echo AG_ePDQ_server_path . 'inc/assets/js/confetti.js';  // @phpstan-ignore-line ?>"></script> 

        <form method="post" class="step">
            <input type="hidden" name="save_step" value="step_finish"/>
			<?php wp_nonce_field( self::$args['plugin_name'] . '-wizard' ); ?>
            <h1><?php echo __( 'Congratulations! 🎉', 'ag_epdq_server' ); ?></h1>
            <p class="setup-text">
				<?php echo __( 'The plugin has been setup and your gateways is ready.<br />
                Tell the world how easy this setup was!', 'ag_epdq_server' ); ?>
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
                <img class="fb-share" src="<?php echo AG_ePDQ_server_path . 'inc/assets/img/fb-share.png';  // @phpstan-ignore-line ?>"/>
            </a>

            <p class="setup-text">
				<?php echo __( 'Please come back and <a href="https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/" target="_blank">leave a 5-star rating</a> if you are happy with this plugin.', 'ag_epdq_server' ); ?>
            </p>


            <h2><?php echo __( 'More Resources', 'ag_epdq_server' ); ?></h2>
            <ul>
                <li class="documentation">
                    <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/" target="_blank">
						<?php echo __( 'Read the Documentation', 'ag_epdq_server' ); ?>
                    </a>
                </li>
                <li class="troubleshooting">
                    <a href="https://weareag.co.uk/docs/troubleshooting-barclays-epdq-payment-gateway/" target="_blank">
						<?php echo __( 'See the troubleshooting section', 'ag_epdq_server' ); ?>
                    </a>
                </li>
                <li class="rating">
                    <a href="https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/"
                       target="_blank">
						<?php echo __( 'Leave a review', 'ag_epdq_server' ); ?>
                    </a>
                </li>
                <li class="support">
                    <a href="https://weareag.co.uk/support/" target="_blank">
						<?php echo __( 'Get Help and Support', 'ag_epdq_server' ); ?>
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

		check_admin_referer( self::$args['plugin_name'] . '-wizard' );
		wp_safe_redirect( esc_url_raw( admin_url() ) );
		exit;

	}

}
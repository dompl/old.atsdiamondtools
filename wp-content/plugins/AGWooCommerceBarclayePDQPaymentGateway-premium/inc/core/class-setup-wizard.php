<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ Setup Wizard
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('AG_ePDQ_Wizard')) {
	return;
}


class AG_ePDQ_Wizard
{

	public static $single_instance = null;
	public static $args = array();
	public static $ag_png = AG_ePDQ_server_path . 'inc/assets/img/ag-logo.png';
	public static $ourSteps = array(); 
	public $steps = array();
	public $step = array();
    
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
	

        
	public function __construct() {
			add_action( 'admin_menu', array( $this, 'wizard_page' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wizard_scripts' ) );
	}
        

	public function wizard_page() {
		add_submenu_page( null, '', '', 'manage_options', self::$args['plugin_name'].'-wizard', '' );
	}
	

	public function enqueue_wizard_scripts() {

		if ( empty( $_GET['page'] ) || self::$args['plugin_name'].'-wizard' !== $_GET['page'] ) { 
			return;
		}

		wp_enqueue_style( self::$args['plugin_name'].'-wizard', AG_ePDQ_server_path . 'inc/assets/css/wizard-style.css', array( 'dashicons', 'install' ), self::$args['plugin_version'] );

	}



	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || self::$args['plugin_name'].'-wizard' !== $_GET['page'] ) { 
			return;
		}

		$ourSteps = AG_ePDQ_Wizard_steps::steps();


		if ( ! current_user_can( 'install_plugins' ) ) {
			unset( $default_steps['activate'] );
		}

		$this->steps = apply_filters( 'ag_wizard_steps', $ourSteps );
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $ourSteps ) );

		//$step = array();

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		ob_start();
		$this->wizard_header();
		$this->wizard_steps();
		$this->wizard_content();
		$this->wizard_footer();
		ob_end_flush();
		exit;
	}

	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
	}


	public function wizard_header() {
		set_current_screen();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title>AG <?php echo self::$args['plugin_name']; ?> Setup Wizard</title>
			<?php do_action( 'admin_enqueue_scripts' ); ?>
			<?php wp_print_scripts( self::$args['plugin_name'].'-wizard' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="ag_wizard wp-core-ui <?php echo esc_attr( 'ag_wizard-step__' . $this->step ); ?>">
		<h1 class="ag-logo"><a href="https://weareag.co.uk/"><img src="<?php echo esc_attr(self::$ag_png); ?>" /></a></h1>
		<?php
	}


	public function wizard_footer() {
		$current_step = $this->step; ?>

			<?php if ( 'wizard_welcome' === $current_step ) : ?>
				<a class="ag_wizard-footer-links" href="<?php echo esc_url( admin_url() ); ?>">I don't need help setting up.</a>
			<?php endif; ?>

			</body>
		</html>
		<?php
	}
	


	public function wizard_steps() {
		$output_steps      = $this->steps;
		unset( $output_steps['wizard_welcome'] ); ?>
		<ol class="ag_wizard-steps">
			<?php
			foreach ( $output_steps as $step_key => $step ) {
				$is_completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true );

				if ( $step_key === $this->step ) {
					?>
					<li class="active"><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				} elseif ( $is_completed ) {
					?>
					<li class="done">
						<a href="<?php echo esc_url( add_query_arg( 'step', $step_key, remove_query_arg( 'activate_error' ) ) ); ?>"><?php echo esc_html( $step['name'] ); ?></a>
					</li>
					<?php
				} else {
					?>
					<li><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				}
			}
			?>
		</ol>
		<?php
	} 


   	public function wizard_content() {

		$steps      = $this->steps;
		$step      = $this->step;
		echo '<div class="ag_wizard-content">';
		if ( ! empty( $steps[ $step ]['view'] ) ) {
			call_user_func( $steps[ $step ]['view'], $this );
		}
		echo '</div>';
   }
 
}
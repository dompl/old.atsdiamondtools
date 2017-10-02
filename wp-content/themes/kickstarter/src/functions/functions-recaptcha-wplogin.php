<?php
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
/*  ********************************************************
 *   Add extra fields to General settings for captach
 *  ********************************************************
 */

add_action('admin_init', 'my_general_section');
function my_general_section() {
  add_settings_section(
    '_captach_setttings_section',
    'Add reCAPTCHA to your site',
    'my_section_options_callback',
    'general'
  );

  add_settings_field(
    'sitekey_1',
    'Site key',
    'my_textbox_callback',
    'general',
    '_captach_setttings_section',
    array(
      'sitekey_1'
    )
  );

  add_settings_field(
    'sitekey_2',
    'Secret key',
    'my_textbox_callback',
    'general',
    '_captach_setttings_section',
    array(
      'sitekey_2'
    )
  );

  register_setting('general','sitekey_1', 'esc_attr');
  register_setting('general','sitekey_2', 'esc_attr');
}

function my_section_options_callback() { // Section Callback
  echo '<p>Activate captach on login screen.</p>';
}

function my_textbox_callback($args) {  // Textbox Callback
  $option = get_option($args[0]);
  echo '<input type="text" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" />';
}

// Deploy re-captacha

if ( 'VOIDX_ADD_RECAPTCHA_ON_LOGIN' && get_option('sitekey_1') != '' && get_option('sitekey_2') != '' ) {

  function login_recaptcha_script() {
    wp_register_script("recaptcha_login", "https://www.google.com/recaptcha/api.js");
    wp_enqueue_script("recaptcha_login");
  }
  add_action("login_enqueue_scripts", "login_recaptcha_script");

  function display_login_captcha() { ?>
  <div class="g-recaptcha" data-sitekey="<?php echo get_option('sitekey_1') ?>"></div>
  <?php }
  add_action( "login_form", "display_login_captcha" );

  function verify_login_captcha($user, $password) {
    if (isset($_POST['g-recaptcha-response'])) {
      $recaptcha_secret = get_option('sitekey_2');
      $response = wp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret=". $recaptcha_secret ."&response=". $_POST['g-recaptcha-response']);
      $response = json_decode($response["body"], true);
      if (true == $response["success"]) {
        return $user;
      } else {
        return new WP_Error("Captcha Invalid", __("<strong>ERROR</strong>: You are acting like a bot!"));
      }
    } else {
      return new WP_Error("Captcha Invalid", __("<strong>ERROR</strong>: You are actiong like a bot. If not then enable JavaScript"));
    }
  }
  add_filter("wp_authenticate_user", "verify_login_captcha", 10, 2);
}

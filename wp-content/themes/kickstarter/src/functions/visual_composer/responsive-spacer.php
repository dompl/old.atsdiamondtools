<?php

/*
 * Help : https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332#vc_map()-param-dependencies
 */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

add_shortcode('responsive_spacer_shortcode', 'res_spacer_function');
function res_spacer_function($atts, $item = null)
{

  extract(shortcode_atts(array(
    'height_on_1200' => '30px',
    'height_on_1024'  => '',
    'height_on_992'  => '',
    'height_on_768'  => '',
    'height_on_576'  => '',
    'height_on_0'    => '',
  ), $atts));

  $item .= $height_on_1200 ? '<div class="height_on_1200" style="margin-top:' . $height_on_1200 . '"></div>' : '';
  $item .= $height_on_1024 ? '<div class="height_on_1024" style="margin-top:' . $height_on_1024 . '"></div>' : '';
  $item .= $height_on_992 ? '<div class="height_on_992" style="margin-top:' . $height_on_992 . '"></div>' : '';
  $item .= $height_on_768 ? '<div class="height_on_768" style="margin-top:' . $height_on_768 . '"></div>' : '';
  $item .= $height_on_576 ? '<div class="height_on_576" style="margin-top:' . $height_on_576 . '"></div>' : '';
  $item .= $height_on_0 ? '<div class="height_on_0" style="margin-top:' . $height_on_0 . '"></div>' : '';

  return $item;
}

add_action('vc_before_init', 'resp_spacer_params');

function resp_spacer_params()
{
  vc_map(array(
    'name'        => __('Responsive Spacer', 'TEXT_DOMAIN'),
    'base'        => 'responsive_spacer_shortcode',
    'class'       => '',
    'icon'        => get_template_directory_uri() . '/img/theme/vc-icon.png',
    'category'    => __('CWTCH', 'TEXT_DOMAIN'),
    'description' => __('Set vertical space for specific screen sizes', 'TEXT_DOMAIN'),
    'params'      => array(
      array(
        'type'       => 'textfield',
        'holder'     => 'div',
        'class'      => 'vc_admin_label',
        'heading'    => __('Height on 1200px', 'TEXT_DOMAIN'),
        'param_name' => 'height_on_1200',
        'value'      => '',
      ),
      array(
        'type'       => 'textfield',
        'holder'     => 'div',
        'class'      => 'vc_hidden',
        'heading'    => __('Height on 1024px', 'TEXT_DOMAIN'),
        'param_name' => 'height_on_1024',
        'value'      => '',
      ),
      array(
        'type'       => 'textfield',
        'holder'     => 'div',
        'class'      => 'vc_hidden',
        'heading'    => __('Height on 992px', 'TEXT_DOMAIN'),
        'param_name' => 'height_on_992',
        'value'      => '',
      ),
      array(
        'type'       => 'textfield',
        'holder'     => 'div',
        'class'      => 'vc_hidden',
        'heading'    => __('Height on 768px', 'TEXT_DOMAIN'),
        'param_name' => 'height_on_768',
        'value'      => '',
      ),
      array(
        'type'       => 'textfield',
        'holder'     => 'div',
        'class'      => 'vc_hidden',
        'heading'    => __('Height on 576px', 'TEXT_DOMAIN'),
        'param_name' => 'height_on_576',
        'value'      => '',
      ),

      array(
        'type'       => 'textfield',
        'holder'     => 'div',
        'class'      => 'vc_admin_label admin_label_text',
        'heading'    => __('Height under 576px', 'TEXT_DOMAIN'),
        'param_name' => 'height_on_0',
        'value'      => '',
      ),
    ),
  )
);
}

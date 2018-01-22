<?php
/*
 * Help : https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332#vc_map()-param-dependencies
 * TODO
 * Resolve when the value for units is qeual 0
 * Add Responsive
 */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
// Element Class
class vcCustomHeading extends WPBakeryShortCode
{
  // Element Init
  public function __construct()
  {
    add_action('init', array($this, 'vc_custom_heading_mapping'));
    add_shortcode('vc_custom_heading_shortcode', array($this, 'vc_custom_heading_html'));
  }

  // Element Mapping
  public function vc_custom_heading_mapping()
  {
    global $vc_param;
    // Stop all if VC is not enabled
    if (!defined('WPB_VC_VERSION'))
    {
      return;
    }

    // Map the block with vc_map()
    vc_map(
      array(
        'name'              => __('Custom Heading', 'TEXT_DOMAIN'),
        'base'              => 'vc_custom_heading_shortcode',
        'class'             => '',
        'category'          => __('CWTCH', 'TEXT_DOMAIN'),
        'icon'              => get_template_directory_uri() . '/img/theme/vc-icon.png',
        'admin_enqueue_css' => array(get_template_directory_uri() . '/admin-vc.css'),
        'description'       => __('Create custom heading for your section', 'TEXT_DOMAIN'),
        'params'            => array(
          array(
            'type'        => 'textarea',
            'holder'      => 'div',
            'class'       => 'vc_admin_label',
            'heading'     => __('Heading text', 'TEXT_DOMAIN'),
            'param_name'  => 'custom_heading_text',
            'value'       => __('', 'TEXT_DOMAIN'),
            'description' => __('Add your custom heading text', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'dropdown',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Element tag', 'TEXT_DOMAIN'),
            'param_name'  => 'tag',
            'value'       => array('h1', 'h2', 'h3', 'h4', 'h5', 'p', 'div'),
            'description' => __('Select element tag.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'dropdown',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Text align', 'TEXT_DOMAIN'),
            'param_name'  => 'alignment',
            'value'       => array('left', 'right', 'center', 'justify'),
            'description' => __('Select text alignment.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'textfield',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Font size', 'TEXT_DOMAIN'),
            'param_name'  => 'font_size',
            'value'       => '',
            'description' => __('Enter font size.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'textfield',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Line height', 'TEXT_DOMAIN'),
            'param_name'  => 'line_height',
            'value'       => '',
            'description' => __('Enter line height.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'dropdown',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Font weight', 'TEXT_DOMAIN'),
            'param_name'  => 'font_weight',
            'value'       => __(array(
              'Normal' => '400',
              'Thin'   => '300',
              'Bold'   => '600',
            ), 'TEXT_DOMAIN'),
            'description' => __('Enter font weight.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'textfield',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Top margin', 'TEXT_DOMAIN'),
            'param_name'  => 'margin_top',
            'value'       => '',
            'description' => __('Enter top margin.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'textfield',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Bottom margin', 'TEXT_DOMAIN'),
            'param_name'  => 'margin_bottom',
            'value'       => '',
            'description' => __('Enter bottom margin.', 'TEXT_DOMAIN'),
          ),
          array(
            'type'        => 'colorpicker',
            'holder'      => 'div',
            'class'       => 'vc_hidden',
            'heading'     => __('Text color', 'TEXT_DOMAIN'),
            'param_name'  => 'text_color',
            'value'       => '',
            'description' => __('Select heading color.', 'TEXT_DOMAIN'),
          ),
          $vc_param['customid'],
          $vc_param['cssclass'],
          $vc_param['animation'],
          $vc_param['css'],
        ),
      )
);
}

// Element HTML
public function vc_custom_heading_html($atts, $item = null)
{
    // Params extraction
  extract(
    shortcode_atts(array(
      'custom_heading_text' => '',
      'tag'                 => '',
      'alignment'           => '',
      'font_size'           => '',
      'line_height'         => '',
      'margin_top'          => '',
      'margin_bottom'       => '',
      'font_weight'         => '',
      'text_color'          => '',
      'customid'            => '',
      'cssclass'            => '',
      'css'                 => '',
      'animation'           => '',
    ), $atts)
  );

    //***********************//
    // MANAGE ANIMATION DATA //
    //***********************//

    // Build the animation classes
  $animation_classes = $this->getCSSAnimation($animation);

  $style                                   = array();
  $font_size ? $style['font-size']         = $font_size : '';
  $line_height ? $style['line-height']     = $line_height : '';
  $margin_top ? $style['margin-top']       = $margin_top : '';
  $font_weight ? $style['font-weight']     = $font_weight : '';
  $margin_bottom ? $style['margin-bottom'] = $margin_bottom : '';
  $text_color ? $style['color']            = $text_color : '';
  $alignment ? $style['text-align']        = $alignment : '';

  $text_tag = $tag ? $tag : 'p';

  $css_class           = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, vc_shortcode_custom_css_class($css, ' '), $this->settings['base'], $atts);

  $item .= $customid || $css_class || $cssclass  || $animation_classes ? '<div ' . ($customid ? ' id="' . $customid . '"' : '') . ($css_class || $cssclass ? ' class="' . ($css_class ? $css_class : '') . ($cssclass ? ' ' . $cssclass : '') . ($animation_classes ? ' ' . $animation_classes : '') . '"' : '') . '>' : '';

  $item .= '<' . $text_tag;

  if (!empty($style))
  {
    $item .= ' style="';
    foreach ($style as $key => $value)
    {
      if (strlen($value) > 0)
      {
        if ('font-size' === $key || 'margin-top' == $key || 'margin-bottom' == $key || 'line-height' == $key)
        {
          $pattern = '/^(\d*(?:\.\d+)?)\s*(px|\%|in|cm|mm|em|rem|ex|pt|pc|vw|vh|vmin|vmax)?$/';
            // allowed metrics: http://www.w3schools.com/cssref/css_units.asp
          $regexr = preg_match($pattern, $value, $matches);
          $value  = isset($matches[1]) ? (float) $matches[1] : (float) $value;
          $unit   = isset($matches[2]) ? $matches[2] : 'px';
          $value  = $value . $unit;
        }

        $item .= $key . ':' . $value . ';';
      }
    }
    $item .= '"';
  }
  $item .= '>' . $custom_heading_text . '</' . $text_tag . '>';

  $item .= $customid || $css_class || $cssclass || $animation ? '</div>' : '';

  $shortcodes_custom_css = get_post_meta(get_the_ID(), '_wpb_shortcodes_custom_css', true);
  return $item;
}

} // End Element Class

// Element Class Init
new vcCustomHeading();

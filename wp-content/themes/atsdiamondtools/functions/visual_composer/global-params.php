<?php

$vc_param = array();

$vc_param['customid'] = array(
  'type'        => 'textfield',
  'heading'     => __('Element ID', 'TEXT_DOMAIN'),
  'param_name'  => 'customid',
  'weight'      => 8,
  'group'       => __('Settings', 'TEXT_DOMAIN'),
  'description' => __('Enter element ID (Note: make sure it is unique and valid according to <a href="https://www.w3schools.com/tags/att_global_id.asp" target="_blank">w3c specification</a>).', 'TEXT_DOMAIN'),
);

$vc_param['cssclass'] = array(
  'type'        => 'textfield',
  'heading'     => __('Extra class name', 'TEXT_DOMAIN'),
  'param_name'  => 'cssclass',
  'weight'      => 9,
  'group'       => __('Settings', 'TEXT_DOMAIN'),
  'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'TEXT_DOMAIN'),
);

$vc_param['animation'] = array(
  'type'        => 'animation_style',
  'heading'     => __('Animation Style', 'TEXT_DOMAIN'),
  'param_name'  => 'animation',
  'description' => __('Select type of animation for element to be animated when it "enters" the browsers viewport (Note: works only in modern browsers).', 'TEXT_DOMAIN'),
  'admin_label' => false,
  'weight'      => 10,
  'group'       => 'Settings',
);

$vc_param['css'] = array(
  'type'       => 'css_editor',
  'heading'    => __('CSS', 'TEXT_DOMAIN'),
  'param_name' => 'css',
  'weight'     => 10,
  'group'      => __('Design options', 'TEXT_DOMAIN'),
);

<!DOCTYPE html>
<!--[if IE 8 ]><html <?php language_attributes();?> class="ie8"><![endif]-->
<!--[if IE 9 ]><html <?php language_attributes();?> class="ie9"><![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
<html <?php language_attributes();?> class="no-js">
<head>
  <title><?php wp_title('');?></title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta charset="<?php bloginfo('charset');?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link href="//www.google-analytics.com" rel="dns-prefetch">
  <link rel="profile" href="http://gmpg.org/xfn/11" />
  <link rel="pingback" href="<?php bloginfo('pingback_url');?>" />
  <?php wp_head();?>
</head>
<body <?php body_class()?>>
  <header id="masthead" class="clx">
    <?php get_template_part( 'modules/header/top'); ?>
    <?php get_template_part( 'modules/header/middle'); ?>
    <?php get_template_part( 'modules/header/navigation'); ?>
  </header>
  <?php !is_front_page() ? do_action('page_header') : '' ?>
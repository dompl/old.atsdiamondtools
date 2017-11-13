<!DOCTYPE html>
<html <?php language_attributes();?> class="no-js">
<head>
  <title><?php wp_title('');?></title>
  <?php
if (file_exists(dirname(__FILE__) . '/../../../../../env_staging'))
{
  echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
}
?>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta charset="<?php bloginfo('charset');?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="//www.google-analytics.com" rel="dns-prefetch">
  <link rel="profile" href="http://gmpg.org/xfn/11" />
  <link rel="pingback" href="<?php bloginfo('pingback_url');?>" />
  <?php wp_head();?>
</head>
<body <?php body_class()?>>
  <div class="container">
    <div class="susy-reset">
  <?php do_action('top')?>
  </div>
  </div>
  <header id="masthead" class="clx">
    <div class="container">
      <div class="susy-reset">
      <div class="logo"><?php do_action('logo', true); ?></div>
      <div class="search"><?php do_action('search') ?></div>
      <div class="cart-colleterals"><?php do_action('cart')?></div>
      </div>
    </div>
  </header>
  <?php do_action('navigation', 768, true) ?>
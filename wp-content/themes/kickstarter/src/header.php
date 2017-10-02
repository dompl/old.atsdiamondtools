<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
  <title><?php wp_title(''); ?></title>
  <?php if ( file_exists( dirname( __FILE__ ) . '/../../../../env_staging' ) ) echo '<meta name="robots" content="noindex, nofollow" />' . "\n"; ?>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta charset="<?php bloginfo( 'charset' ); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <link href="//www.google-analytics.com" rel="dns-prefetch">
  <link rel="profile" href="http://gmpg.org/xfn/11" />
  <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <header id="masthead">
    <?php echo !is_front_page() ? '<a href="'. esc_url( home_url( '/' ) ) . '" rel="home">' : ''?>
      <?php bloginfo( 'name' ); ?>
      <?php echo is_front_page() ?  '</a>' : ''?>
      <?php get_template_part( 'inc/navigation'); ?>
    </header>
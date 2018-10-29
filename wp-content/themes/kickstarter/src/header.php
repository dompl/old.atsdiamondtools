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
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/img/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri(); ?>/img/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri(); ?>/img/favicons/favicon-16x16.png">
  <link rel="mask-icon" href="<?php echo get_template_directory_uri(); ?>/img/favicons/safari-pinned-tab.svg" color="#4c3948">
  <meta name="theme-color" content="#ffffff">
  <script>(function(n,t,i,r){var u,f;n[i]=n[i]||{},n[i].initial={accountCode:"ATSDI11112",host:"ATSDI11112.pcapredict.com"},n[i].on=n[i].on||function(){(n[i].onq=n[i].onq||[]).push(arguments)},u=t.createElement("script"),u.async=!0,u.src=r,f=t.getElementsByTagName("script")[0],f.parentNode.insertBefore(u,f)})(window,document,"pca","//ATSDI11112.pcapredict.com/js/sensor.js")</script>
  <link rel="pingback" href="<?php bloginfo('pingback_url');?>" />
  <?php wp_head();?>
 <script type="text/javascript">
  <!--//--><![CDATA[//><!--
  (function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,"script","//www.google-analytics.com/analytics.js","ga");ga("create", "UA-56621-9", {"cookieDomain":"auto"});ga("send", "pageview");
  //--><!]]>
</script>
</head>

<body <?php body_class()?>>
  <header id="masthead" class="clx">
    <?php get_template_part( 'modules/header/top'); ?>
    <?php get_template_part( 'modules/header/middle'); ?>
    <?php get_template_part( 'modules/header/navigation'); ?>
  </header>
  <?php !is_front_page() ? do_action('page_header') : '' ?>
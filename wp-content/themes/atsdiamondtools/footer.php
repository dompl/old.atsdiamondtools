<section class="container">
<div id="footer-signup" class="clx">
    <div><p><?php _e('Get the latest on ats diamond tools right into your inbox', 'TEXT_DOMAIN')?></p></div>
   <?php echo do_shortcode('[gravityform id="1" title="false" description="false" ajax="true"]') ?>
</div>
</section>
<?php do_action('ats_after_email_signup'); ?>
<footer id="footer" data-responsive="769">
  <div class="container">
    <div class="susy-reset">
     <?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('sidebar-footer')) {} ?>
   </div>
 </div>
 <div class="copyright">
   <div class="container">
     <div class="susy-reset">
       <div class="left clx">
        <span><?php echo str_replace('%year%', date('Y'), get_field('copyright_notice', 'options')); ?></span>
        <?php do_action('footer_navigation')?>
      </div>
      <div class="right"><p><a href="https://www.redfrogstudio.co.uk" target="_blank"><?php esc_html_e('Website by Red Frog', 'TEXT_DOMAIN')?></a></p></div>
    </div>
  </div>
</div>
</footer>
<?php wp_footer();?>
</body>
</html>
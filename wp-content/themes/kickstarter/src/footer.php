<footer id="footer" data-responsive="769">
  <div class="container">
    <div class="susy-reset">
     <?php if ( ! function_exists( 'dynamic_sidebar' ) || ! dynamic_sidebar( 'sidebar-footer' ) ) ?>
   </div>
 </div>
 <div class="copyright">
   <div class="container">
     <div class="susy-reset">
       <div class="left clx">
        <span><?php echo str_replace('%year%', get_the_date('Y'), get_field('copyright_notice', 'options') ); ?></span>
        <?php do_action('footer_navigation') ?>
      </div>
      <div class="right"><p><a href="https://www.redfrogstudio.co.uk" target="_blank"><?php esc_html_e( 'Website by Red Frog', 'TEXT_DOMAIN') ?></a></p></div>
    </div>
  </div>
</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
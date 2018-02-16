 <div id="top-banner">
  <div class="container">
<?php esc_html_e( 'We will be moving premises on Thursday 22nd February. All orders placed up until 3pm on Wednesday 21st February will be processed and shipped. Any orders placed after this date will be processed when we reopen on Monday 26th February.', 'TEXT_DOMAIN') ?>
  </div>
 </div>
 <div class="container" id="header-top">
   <div class="susy-reset clx">
     <div class="left">
      <nav id="top-navigation">
        <div class="nav-menus-wrapper">
          <?php wp_nav_menu(array('theme_location' => 'header', 'menu_id' => 'menu-header'));?>
        </div>
      </nav>
    </div>
    <div class="right">
      <?php do_action('top'); ?>
    </div>
  </div>
</div>
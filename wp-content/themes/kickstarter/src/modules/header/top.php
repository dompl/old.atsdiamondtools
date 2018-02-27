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
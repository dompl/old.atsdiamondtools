<div id="main-nav-container">
  <div class="container">
    <nav id="navigation" class="navigation navigation-landscape" data-breakpoint="768">
     <div class="nav-header">
      <?php echo main_logo(false, 30, 170); ?>

      <div class="nav-toggle"><span><?php _e('Menu', 'TEXT_DOMAIN')?></span></div>
    </div>
    <div class="nav-menus-wrapper">
     <?php wp_nav_menu(array('theme_location' => 'main', 'menu_id' => 'menu-header', 'menu_class' => 'nav-menu align-to-left'));?>
   </div>
 </nav>
</div>
</div>
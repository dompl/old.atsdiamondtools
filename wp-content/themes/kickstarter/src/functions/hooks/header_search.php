<?php
/*  ********************************************************
 *   Header Search
 *  ********************************************************
 */
add_action('search', 'header_search');
function header_search() { ?>
<form class="search" method="get" action="<?php echo home_url(); ?>">
  <div role="search">
    <input class="search-input" type="search" name="s" aria-label="<?php esc_html_e( 'Search site for:', 'TEXT_DOMAIN' ); ?>" placeholder="<?php esc_html_e( 'search our store', 'TEXT_DOMAIN' ); ?>">
    <button class="search-submit" type="submit"><i class="icon-search"></i></button>
    <i class="icon-caret-left"></i>
  </div>
</form>
<?php }
<?php
/* ajax search = remove basic styles */
add_action('wp_print_styles', 'mytheme_dequeue_css_from_plugins', 100);
function mytheme_dequeue_css_from_plugins()
{
  wp_dequeue_style('dgwt-wcas-style');
}

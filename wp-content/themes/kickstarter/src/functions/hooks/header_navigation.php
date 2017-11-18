<?php
/*  ********************************************************
 *   Main Navigation function
 *  ********************************************************
 */

/* Top collereals ( telephone numer and email ) */
add_action('top', 'top_colleterals', 10);
function top_colleterals()
{
  $email     =  get_field('header_email', 'options');
  $telephone =  get_field('header_phone', 'options');

  echo $email || $telephone ? '<div class="clx">' : '';
  // If tel number is present

  if ($telephone)
  {
    echo '<div class="tel-number">
    <div class="mobile clx"><a href="tel:' . str_replace(' ', '', $telephone) . '"><i class="icon-phone"></i><span>' . esc_html($telephone) . '</span></a></div>
    </div>';
  }
  if ( $email )
  {
    echo '<div class="email clx"><a href="mailto:' . antispambot($email) . '"><i class="icon-envelope"></i><span>' . antispambot($email) . '</span></a></div>';
  }
  echo $email || $telephone ?  '</div>' : '';
}

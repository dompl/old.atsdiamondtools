<?php
/*  ********************************************************
 *   Social Icons
 *  ********************************************************
 */

add_action( 'social_shares', 'ats_social_media_');
function ats_social_media_() {
  global $product, $post;

 $product_name = str_replace('&', 'and', $product->get_name())   . ' ' . __('from ATS Diamonds Tools', 'TEXT_DOMAIN') ;
 $link = urlencode(get_permalink());
 $email_body = esc_html__(sprintf(
  'Hey,
  Check out %s from ATS Disamond Tools. It\'s available here : %s',
  esc_attr($product->get_name()),
  get_permalink()
));
?>
  <div class="social-media aside-likes">
    <p><?php _e( 'Share it', 'TEXT_DOMAIN') ?></p>
    <ul>
      <li><a class="customer share twitter-link" href="https://twitter.com/intent/tweet?text=<?php echo $product_name ?>&via=atsdiamondtools"><i class="icon-twitter"></i></a></li>
      <li><a class="customer share facebook-link" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $link ?>?quote=<?php echo $product_name ?>&description=asasas"><i class="icon-facebook"></i></a></li>
      <li><a class="customer share linkedin-link" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo $link ?>&title=<?php echo $product_name ?>&source=ATS Diamond Tools&summary=<?php echo get_the_excerpt()?>"><i class="icon-linkedin"></i></a></li>
      <li><a class="customer share google-link" href="https://plus.google.com/share?url=<?php echo $link ?>"><i class="icon-google-plus-g"></i></a></li>
      <li><a class="email-link" href="mailto:?body=<?php echo $link  ?>&subject=<?php echo $product_name ?>"><i class="icon-envelope"></i></a></li>
    </ul>
  </div>
<?php
 }
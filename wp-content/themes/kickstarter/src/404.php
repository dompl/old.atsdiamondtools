<?php get_header();?>
<main>
  <article class="container" id="page-404">
    <div class="image_404">
      <a href="<?php echo esc_url(home_url()) ?>">
      <img src="<?php echo get_template_directory_uri(); ?>/img/theme/404.png" alt="<?php esc_html_e('Page not faund', TEXT_DOMAIN)?>">
      </a>
    </div>
    <p><?php esc_html_e('We\'re Sorry...', TEXT_DOMAIN)?></p>
    <p><?php esc_html_e('We can\'t seem to find the page you are looking for', TEXT_DOMAIN)?></p>
    <a href="<?php echo esc_url(home_url()) ?>" class="button"><?php esc_html_e('Return to home page', TEXT_DOMAIN)?></a>
  </article>
</main>
<?php get_footer();
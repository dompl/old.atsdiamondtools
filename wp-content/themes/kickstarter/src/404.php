<?php get_header();?>
<main>
  <article class="container" id="page-404">
    <div>
      <a href="<?php echo esc_url(home_url()) ?>">
       <h1>404</h1>
      </a>
    </div>
    <p><?php esc_html_e('We can\'t seem to find the page you are looking for', 'TEXT_DOMAIN')?></p>
    <a href="<?php echo esc_url(home_url()) ?>" class="button"><?php esc_html_e('Return to home page', 'TEXT_DOMAIN') ?></a>
  </article>
</main>
<?php get_footer();
<?php get_header(); ?>
<main role="main" aria-label="Content">
    <?php if ( have_posts() ): ?>
      <?php while ( have_posts() ) : the_post() ?>
          <?php the_content() ?>
      <?php endwhile; ?>
    <?php endif ?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
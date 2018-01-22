<?php /* Template Name: Left-Right */ ?>

<?php get_header(); ?>
<main role="main" aria-label="Content">
    <?php if ( have_posts() ): ?>
      <?php while ( have_posts() ) : the_post() ?>
        <div class="container left-right-template">
          <div class="left"><?php the_content() ?></div>
          <div class="right">
            <?php
            if (get_post_thumbnail_id()) :
              echo image_figure(get_post_thumbnail_id(), '', 500, 500);
            else:
              echo do_shortcode('[gravityform id=2 title=false description=false ajax=true tabindex=49]');
            endif;
            ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
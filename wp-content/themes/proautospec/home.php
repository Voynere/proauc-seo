<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>
  
 

<section class="b-blog-hero py-6 text-center">
  <div class="container">
    <?php proauc_render_blog_breadcrumbs(); ?>
    <?php
    $blog_title = proauc_get_blog_posts_page_title();
    $blog_lead  = 'Покупка авто с аукционов Японии, Кореи и Китая: этапы, цены, документы и советы экспертов Proauc.';
    $posts_page = (int) get_option( 'page_for_posts' );
    if ( $posts_page ) {
        $blog_page = get_post( $posts_page );
        if ( $blog_page ) {
            $blog_title = $blog_page->post_title;
            if ( $blog_page->post_content ) {
                $blog_lead = wp_strip_all_tags( $blog_page->post_content );
            }
        }
    }
    ?>
    <h1><?php echo esc_html( $blog_title ); ?></h1>
    <div class="lead col-md-8 offset-md-2 mx-auto archive-description"><?php echo esc_html( $blog_lead ); ?></div>
 
  </div>
</section>

<section class="b-auto-news b-blog-list py-5">
  <div class="container">
    <div class="row">
    <?php 
        if ( have_posts() ) : 
            while ( have_posts() ) : the_post();
                
              get_template_part('loops/cards');
                
            endwhile;
        else :
            _e( 'Sorry, no posts matched your criteria.', 'textdomain' );
        endif;
        ?>
    </div>

    <div class="row">
      <div class="col lead text-center w-100">
        <div class="d-inline-block"><?php picostrap_pagination() ?></div>
      </div><!-- /col -->
    </div> <!-- /row -->
  </div>
</section>
 
<?php get_footer();

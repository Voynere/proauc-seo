<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>



<section>
  <div class="container">
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
	</div>  
	<div class="section-title">
		<h1>Автодома в наличии</h1>
	
	</div>
    <div class="row">
    <?php 
		$args = array(
			'post_type' => 'avto',
			'posts_per_page' => 20,
			'category__in' => 1
		);

		$the_query = new WP_Query( $args );

		?>

		<?php if ( $the_query->have_posts() ) : ?>

			<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
				<div class="col-lg-3">
					<?php get_template_part('template-parts/loops/avto');?>
				</div>
			<?php endwhile; ?>

			<?php wp_reset_postdata(); ?>
		
        <?php else: ?>
		<div class="col-12">
            <p>Автодомов в наличии на данный момент нет.</p>
		</div>
        <?php 
		endif;
		
        ?>
		</div>
    </div>

    <div class="row mt-lg-4">
      <div class="col text-center w-100">
        <div><?php picostrap_pagination() ?></div>
      </div><!-- /col -->
    </div> <!-- /row -->
  </div>

</section>

 
<?php get_footer();

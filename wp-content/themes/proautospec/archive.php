<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>


<section class="">

  <div class="container">
  			<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
				<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
			</div>
			
			
    <h1><?php the_archive_title() ?></h1>
    <div class="lead text-muted col-md-8 offset-md-2 archive-description"><?php echo category_description(); ?></div>


  </div>
</section>

<?php

$category = get_category_by_slug( get_query_var( 'category_name' ) );


$archive_id = $category->cat_ID;


if ( get_field( 'seo-text','category_'.$archive_id ) ) {
	$seoText = get_field( 'seo-text', 'category_'.$archive_id );
}
?>


<section class="album pb-5">
  <div id="container-content-archive" class="container">
    <div class="row">
    <?php 
		$args = array(
			'post_type' => 'avto',
			'posts_per_page' => 20,
			'category__in' => $category->cat_ID
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
        <?php else :
            _e( 'Не найдено', 'textdomain' );
        endif;
        ?>
    </div>
	
    <div class="row">
      <div class="col lead text-center w-100">
        <div class="d-inline-block"><?php picostrap_pagination() ?></div>
      </div>
    </div>
	<?php if ($seoText):?>
	<div>
		<p>
			<?php echo $seoText; ?>
			
		</p>
	</div>
	<?php endif;?>
  </div>
</section>
 
<?php get_footer();

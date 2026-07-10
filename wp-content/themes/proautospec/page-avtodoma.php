<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

$filters = proautospec_avtodoma_sanitize_filters( $_GET );
$facets  = proautospec_avtodoma_get_facets();
$args    = proautospec_avtodoma_query_args( $_GET );
$the_query = new WP_Query( $args );
$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$has_active_filters = ! empty( $filters['mark'] ) || ! empty( $filters['model'] ) || ! empty( $filters['year'] );
?>



<section>
  <div class="container">
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
	</div>  
	<div class="section-title">
		<h1>Автодома в наличии</h1>
	</div>
  </div>
</section>

<section class="mt-0 pt-0">
  <div class="container">
	<div class="b-cars-catalog-filter b-cars-catalog-filter--goto">
		<h2>Подберите автодом по параметрам</h2>
		<form method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php
			get_template_part(
				'template-parts/avtodoma-form-filter',
				null,
				array(
					'facets'  => $facets,
					'filters' => $filters,
				)
			);
			?>
		</form>
	</div>
  </div>
</section>

<section>
  <div class="container">
	<?php if ( $has_active_filters ) : ?>
		<p class="cars-listing__total mb-3">
			Найдено автодомов: <var><?php echo (int) $the_query->found_posts; ?></var>
			<a class="ms-2" href="<?php echo esc_url( get_permalink() ); ?>">Сбросить фильтр</a>
		</p>
	<?php endif; ?>
    <div class="row">
		<?php if ( $the_query->have_posts() ) : ?>

			<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
				<div class="col-lg-3">
					<?php get_template_part('template-parts/loops/avto');?>
				</div>
			<?php endwhile; ?>

			<?php wp_reset_postdata(); ?>

        <?php else: ?>
		<div class="col-12">
            <p><?php echo $has_active_filters ? 'Автодомов по выбранным параметрам не найдено.' : 'Автодомов в наличии на данный момент нет.'; ?></p>
		</div>
        <?php endif; ?>
	</div>

    <?php if ( $the_query->max_num_pages > 1 ) : ?>
    <div class="row mt-lg-4">
      <div class="col text-center w-100">
        <div>
			<?php
			picostrap_pagination(
				array(
					'total'     => $the_query->max_num_pages,
					'current'   => $paged,
					'add_args'  => proautospec_avtodoma_pagination_args( $filters ),
				)
			);
			?>
		</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

 
<?php get_footer();

<?php
/**
 * Category archive: blog clusters use post cards; others fall back to archive.php (avto catalog).
 */
defined( 'ABSPATH' ) || exit;

$term = get_queried_object();
if ( ! $term || ! function_exists( 'proauc_blog_cluster_slugs' ) || ! in_array( $term->slug, proauc_blog_cluster_slugs(), true ) ) {
	include get_template_directory() . '/archive.php';
	return;
}

get_header();

$cat_lead = category_description();
if ( ! $cat_lead && function_exists( 'proauc_get_blog_category_lead' ) ) {
	$cat_lead = proauc_get_blog_category_lead( $term->slug );
}
?>
<section class="b-blog-hero py-6 text-center">
	<div class="container">
		<?php
		if ( function_exists( 'proauc_render_breadcrumbs' ) && function_exists( 'proauc_get_blog_category_breadcrumb_items' ) ) {
			proauc_render_breadcrumbs( proauc_get_blog_category_breadcrumb_items() );
		}
		?>
		<h1><?php single_cat_title(); ?></h1>
		<?php if ( $cat_lead ) : ?>
			<div class="lead col-md-8 offset-md-2 mx-auto archive-description"><?php echo esc_html( wp_strip_all_tags( $cat_lead ) ); ?></div>
		<?php endif; ?>
	</div>
</section>

<section class="b-auto-news b-blog-list py-5">
	<div class="container">
		<div class="row">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				get_template_part( 'loops/cards' );
			endwhile;
		else :
			echo '<div class="col-12 text-center lead text-muted">В этой рубрике пока нет статей.</div>';
		endif;
		?>
		</div>

		<div class="row">
			<div class="col lead text-center w-100">
				<div class="d-inline-block"><?php picostrap_pagination(); ?></div>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();

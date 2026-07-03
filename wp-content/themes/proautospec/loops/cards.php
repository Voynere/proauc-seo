<?php
/**
 * Blog post card — used on the posts index (home.php /blog/).
 */
defined( 'ABSPATH' ) || exit;

$permalink = get_permalink();
$image_url = function_exists( 'proauc_blog_card_image_url' ) ? proauc_blog_card_image_url() : '';
$excerpt   = function_exists( 'proauc_blog_card_excerpt' ) ? proauc_blog_card_excerpt() : '';
$cluster   = function_exists( 'proauc_get_blog_post_cluster' ) ? proauc_get_blog_post_cluster() : '';
$has_thumb = (bool) ( has_post_thumbnail() || $image_url );
?>
<div class="col-lg-4 col-md-6">
	<article class="news-card blog-card<?php echo $has_thumb ? '' : ' blog-card--no-thumb'; ?><?php echo $cluster ? ' blog-card--' . esc_attr( $cluster ) : ''; ?>">
		<a class="news-card__pic<?php echo $has_thumb ? '' : ' news-card__pic--placeholder'; ?>" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium_large', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
			<?php elseif ( $image_url ) : ?>
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" width="640" height="432">
			<?php endif; ?>
		</a>
		<div class="news-card__desc">
			<time class="news-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></time>
			<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php the_title(); ?></a></h3>
			<?php if ( $excerpt ) : ?>
				<p class="news-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<hr>
			<a class="btn btn-outline-primary" href="<?php echo esc_url( $permalink ); ?>">Читать далее</a>
		</div>
	</article>
</div>

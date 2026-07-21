<?php
/**
 * One-shot: seed wave 8, schedule, covers meta, Santa Fe content sync.
 * Usage: wp eval-file scripts/seed-blog-wave8.php
 */

if ( ! function_exists( 'proauc_get_blog_article_seeds_wave8' ) ) {
	require_once get_template_directory() . '/inc/blog-articles.php';
}

$seeds = proauc_get_blog_article_seeds_wave8();
echo 'wave8 seeds: ' . count( $seeds ) . PHP_EOL;

if ( ! get_option( 'proauc_blog_seed_v8' ) ) {
	proauc_seed_blog_posts( $seeds );
	update_option( 'proauc_blog_seed_v8', 1 );
	echo "seeded v8\n";
} else {
	echo "seed v8 already set\n";
}

if ( ! get_option( 'proauc_blog_wave8_schedule_v1' ) ) {
	proauc_migrate_blog_wave8_schedule();
	update_option( 'proauc_blog_wave8_schedule_v1', 1 );
	echo "schedule v8 done\n";
} else {
	proauc_migrate_blog_wave8_schedule();
	echo "schedule v8 refreshed\n";
}

if ( function_exists( 'proauc_sync_blog_post_content' ) ) {
	proauc_sync_blog_post_content( 'obzor-hyundai-santa-fe-iz-korei' );
	update_option( 'proauc_blog_content_santa_fe_v1', 1 );
	echo "santa fe content synced\n";
}

$slugs = array(
	'avto-iz-yaponii-v-yuzhno-sahalinske',
	'skolko-stoit-privezti-avto-iz-korei',
	'obzor-hyundai-santa-fe-iz-korei',
	'oformlenie-epts-avto-iz-yaponii',
);

foreach ( $slugs as $slug ) {
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	if ( ! $post ) {
		echo $slug . " MISSING\n";
		continue;
	}

	foreach ( $seeds as $seed ) {
		if ( empty( $seed['slug'] ) || $seed['slug'] !== $slug ) {
			continue;
		}
		if ( function_exists( 'proauc_save_blog_post_thumbnail' ) && function_exists( 'proauc_get_blog_seed_thumbnail' ) ) {
			proauc_save_blog_post_thumbnail( (int) $post->ID, proauc_get_blog_seed_thumbnail( $seed ) );
		}
		break;
	}

	$gmt     = get_gmt_from_date( $post->post_date );
	$publish = strtotime( $gmt ) <= time();
	$status  = $publish ? 'publish' : 'publish'; // force early publish for wave 8

	wp_update_post(
		array(
			'ID'          => (int) $post->ID,
			'post_status' => $status,
		)
	);

	$fresh = get_post( $post->ID );
	echo $slug . ' id=' . $fresh->ID . ' status=' . $fresh->post_status . ' date=' . $fresh->post_date . PHP_EOL;
}

if ( function_exists( 'proauc_invalidate_rank_math_post_sitemap' ) ) {
	proauc_invalidate_rank_math_post_sitemap();
	echo "sitemap cache invalidated\n";
}

$urls = array( home_url( '/blog/' ) );
foreach ( $slugs as $slug ) {
	$urls[] = home_url( '/' . $slug . '/' );
}

if ( function_exists( 'proauc_submit_urls_to_indexnow' ) ) {
	$result = proauc_submit_urls_to_indexnow( $urls );
	echo 'IndexNow: ' . ( is_string( $result ) ? $result : wp_json_encode( $result ) ) . PHP_EOL;
	if ( is_array( $result ) ) {
		print_r( $result );
	}
} else {
	echo "IndexNow helper missing\n";
}

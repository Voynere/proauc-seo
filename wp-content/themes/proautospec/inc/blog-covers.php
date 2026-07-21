<?php
/**
 * Blog covers: photorealistic JPG (preferred) or branded SVG fallback.
 */

defined( 'ABSPATH' ) || exit;

function proauc_get_blog_cover_cluster_style( $cluster ) {
	$map = array(
		'yaponiya'    => array(
			'accent' => '#E84E0E',
			'label'  => 'Япония',
			'bg1'    => '#141414',
			'bg2'    => '#252525',
		),
		'koreya'      => array(
			'accent' => '#C62828',
			'label'  => 'Корея',
			'bg1'    => '#141418',
			'bg2'    => '#252530',
		),
		'kitaj'       => array(
			'accent' => '#00A896',
			'label'  => 'Китай',
			'bg1'    => '#121618',
			'bg2'    => '#1e2828',
		),
		'spectehnika' => array(
			'accent' => '#F59502',
			'label'  => 'Спецтехника',
			'bg1'    => '#161412',
			'bg2'    => '#282420',
		),
		'mototsikly'  => array(
			'accent' => '#E84E0E',
			'label'  => 'Мотоциклы',
			'bg1'    => '#141414',
			'bg2'    => '#222222',
		),
		'obzory'      => array(
			'accent' => '#5C6BC0',
			'label'  => 'Обзор',
			'bg1'    => '#14141a',
			'bg2'    => '#242430',
		),
		'kejsy'       => array(
			'accent' => '#43A047',
			'label'  => 'Кейс',
			'bg1'    => '#121614',
			'bg2'    => '#222824',
		),
	);

	return isset( $map[ $cluster ] ) ? $map[ $cluster ] : $map['yaponiya'];
}

function proauc_blog_cover_wrap_title( $title, $max_chars = 30, $max_lines = 4 ) {
	$title = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $title ) ) );
	if ( '' === $title ) {
		return array( 'Proauc' );
	}

	$words  = preg_split( '/\s+/u', $title );
	$lines  = array();
	$current = '';

	foreach ( $words as $word ) {
		$test = '' === $current ? $word : $current . ' ' . $word;
		if ( mb_strlen( $test ) > $max_chars && '' !== $current ) {
			$lines[] = $current;
			$current = $word;
		} else {
			$current = $test;
		}
	}

	if ( '' !== $current ) {
		$lines[] = $current;
	}

	return array_slice( $lines, 0, $max_lines );
}

function proauc_blog_cover_svg_escape( $text ) {
	return htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
}

function proauc_build_blog_cover_svg( $title, $cluster = 'yaponiya' ) {
	$style = proauc_get_blog_cover_cluster_style( $cluster );
	$lines = proauc_blog_cover_wrap_title( $title );

	$line_height = 58;
	$start_y     = 300 - ( ( count( $lines ) - 1 ) * $line_height ) / 2;
	$tspans      = '';

	foreach ( $lines as $i => $line ) {
		$y       = $start_y + ( $i * $line_height );
		$tspans .= sprintf(
			'<tspan x="72" y="%s">%s</tspan>',
			round( $y, 1 ),
			proauc_blog_cover_svg_escape( $line )
		);
	}

	$accent = proauc_blog_cover_svg_escape( $style['accent'] );
	$label  = proauc_blog_cover_svg_escape( $style['label'] );
	$bg1    = proauc_blog_cover_svg_escape( $style['bg1'] );
	$bg2    = proauc_blog_cover_svg_escape( $style['bg2'] );

	return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="{$label}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$bg1}"/>
      <stop offset="100%" stop-color="{$bg2}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <path d="M820 0L1200 380V630H520L820 0Z" fill="{$accent}" opacity="0.92"/>
  <path d="M900 0L1200 300V630H680L900 0Z" fill="#ffffff" opacity="0.06"/>
  <text x="72" y="84" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="700">{$label}</text>
  <text fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="46" font-weight="700" letter-spacing="-0.5">{$tspans}</text>
  <text x="72" y="582" fill="#888888" font-family="Arial, Helvetica, sans-serif" font-size="20" font-weight="600">proauc.ru</text>
</svg>
SVG;
}

function proauc_get_blog_covers_dir() {
	return get_template_directory() . '/images/blog';
}

function proauc_get_blog_cover_extensions() {
	return array( 'jpg', 'jpeg', 'webp', 'png', 'svg' );
}

function proauc_get_blog_cover_file_extension( $slug ) {
	$slug = sanitize_file_name( $slug );
	if ( ! $slug ) {
		return '';
	}

	$dir = proauc_get_blog_covers_dir();
	foreach ( proauc_get_blog_cover_extensions() as $ext ) {
		if ( file_exists( $dir . '/' . $slug . '.' . $ext ) ) {
			return $ext;
		}
	}

	return '';
}

function proauc_get_blog_cover_relative_path( $slug ) {
	$slug = sanitize_file_name( $slug );
	$ext  = proauc_get_blog_cover_file_extension( $slug );
	if ( ! $ext ) {
		$ext = 'svg';
	}

	return 'blog/' . $slug . '.' . $ext;
}

function proauc_write_blog_cover_file( $slug, $title, $cluster = 'yaponiya' ) {
	$slug = sanitize_file_name( $slug );
	if ( ! $slug ) {
		return false;
	}

	$dir = proauc_get_blog_covers_dir();
	if ( ! wp_mkdir_p( $dir ) ) {
		return false;
	}

	$path = $dir . '/' . $slug . '.svg';
	$svg  = proauc_build_blog_cover_svg( $title, $cluster );

	return false !== file_put_contents( $path, $svg );
}

function proauc_get_blog_cover_url( $slug ) {
	$slug = sanitize_file_name( $slug );
	if ( ! $slug || ! proauc_get_blog_cover_file_extension( $slug ) ) {
		return '';
	}

	return get_template_directory_uri() . '/images/' . proauc_get_blog_cover_relative_path( $slug );
}

function proauc_refresh_blog_cover_meta() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds() as $seed ) {
		if ( empty( $seed['slug'] ) ) {
			continue;
		}

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post || ! function_exists( 'proauc_save_blog_post_thumbnail' ) ) {
			continue;
		}

		if ( ! proauc_get_blog_cover_url( $seed['slug'] ) ) {
			continue;
		}

		proauc_save_blog_post_thumbnail( (int) $post->ID, proauc_get_blog_seed_thumbnail( $seed ) );
	}
}

function proauc_get_blog_seed_thumbnail( array $seed ) {
	if ( ! empty( $seed['slug'] ) && proauc_get_blog_cover_url( $seed['slug'] ) ) {
		return proauc_get_blog_cover_relative_path( $seed['slug'] );
	}

	if ( ! empty( $seed['thumbnail'] ) ) {
		return $seed['thumbnail'];
	}

	if ( ! empty( $seed['cluster'] ) && function_exists( 'proauc_get_blog_cluster_card_image' ) ) {
		return basename( (string) wp_parse_url( proauc_get_blog_cluster_card_image( $seed['cluster'] ), PHP_URL_PATH ) );
	}

	return 'bg-alpha-cars.svg';
}

function proauc_migrate_blog_covers_v1() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds() as $seed ) {
		if ( empty( $seed['slug'] ) || empty( $seed['title'] ) ) {
			continue;
		}

		$cluster = ! empty( $seed['cluster'] ) ? $seed['cluster'] : 'yaponiya';
		proauc_write_blog_cover_file( $seed['slug'], $seed['title'], $cluster );

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post || ! function_exists( 'proauc_save_blog_post_thumbnail' ) ) {
			continue;
		}

		proauc_save_blog_post_thumbnail( (int) $post->ID, proauc_get_blog_seed_thumbnail( $seed ) );
	}
}

function proauc_migrate_blog_covers_v2() {
	proauc_refresh_blog_cover_meta();
}

/**
 * Re-bind post meta to photorealistic JPG covers added after wave seeds
 * (wave 7 had SVG fallbacks while images/blog/{slug}.jpg already on disk).
 */
function proauc_migrate_blog_covers_v3() {
	proauc_refresh_blog_cover_meta();
}

function proauc_get_blog_content_dir() {
	return get_template_directory() . '/images/blog/content';
}

function proauc_get_blog_content_image_url( $filename ) {
	$filename = sanitize_file_name( (string) $filename );
	if ( ! $filename ) {
		return '';
	}

	$path = proauc_get_blog_content_dir() . '/' . $filename;
	if ( ! file_exists( $path ) ) {
		return '';
	}

	return get_template_directory_uri() . '/images/blog/content/' . $filename;
}

/**
 * In-body blog image: responsive figure with alt and optional caption.
 */
function proauc_blog_content_figure( $filename, $alt, $caption = '' ) {
	$url = proauc_get_blog_content_image_url( $filename );
	if ( ! $url ) {
		return '';
	}

	$html  = '<figure class="proauc-blog-figure my-4">';
	$html .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" width="1536" height="1024">';
	if ( '' !== trim( (string) $caption ) ) {
		$html .= '<figcaption class="proauc-blog-figure__caption text-muted small mt-2">' . esc_html( $caption ) . '</figcaption>';
	}
	$html .= '</figure>';

	return $html;
}

function proauc_get_blog_hero_image_url( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	if ( has_post_thumbnail( $post ) ) {
		$url = get_the_post_thumbnail_url( $post, 'full' );
		if ( $url ) {
			return $url;
		}
	}

	if ( function_exists( 'proauc_blog_card_image_url' ) ) {
		return proauc_blog_card_image_url( $post );
	}

	return '';
}

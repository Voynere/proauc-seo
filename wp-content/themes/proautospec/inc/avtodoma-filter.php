<?php
/**
 * Filter helpers for /avtodoma/ motorhome listing.
 *
 * @package proautospec
 */

defined( 'ABSPATH' ) || exit;

/** Category ID for «Автодома». */
const PROAUTOSPEC_AVTODOMA_CATEGORY_ID = 1;

/**
 * Base chassis model → manufacturer.
 *
 * @return array<string, string>
 */
function proautospec_avtodoma_model_mark_map() {
	return array(
		'Hiace'          => 'Toyota',
		'Camroad'        => 'Toyota',
		'Bongo'          => 'Mazda',
		'Baneto'         => 'Mazda',
		'Delica D:5'     => 'Mitsubishi',
		'Delica'         => 'Mitsubishi',
		'Hijet'          => 'Daihatsu',
		'Every'          => 'Suzuki',
		'NV200'          => 'Nissan',
		'Vito'           => 'Mercedes-Benz',
		'Sprinter'       => 'Mercedes-Benz',
		'Vitz'           => 'Toyota',
		'Regius Ace'     => 'Toyota',
		'Caravan'        => 'Nissan',
		'Serena'         => 'Nissan',
		'Stepwgn'        => 'Honda',
		'Freed'          => 'Honda',
		'Grand Starex'   => 'Hyundai',
		'Starex'         => 'Hyundai',
		'Staria'         => 'Hyundai',
		'Porter II'      => 'Hyundai',
		'Porter'         => 'Hyundai',
		'County'         => 'Hyundai',
		'e-County'       => 'Hyundai',
		'Solati'         => 'Hyundai',
		'Carnival'       => 'Kia',
		'Master'         => 'Renault',
	);
}

/**
 * Aliases in titles → canonical model name.
 *
 * @return array<string, string>
 */
function proautospec_avtodoma_model_aliases() {
	return array(
		'HiaceRV'     => 'Hiace',
		'Haiesu'      => 'Hiace',
		'Kamroad'     => 'Camroad',
		'Baneto'      => 'Bongo',
		'GrandStarex' => 'Grand Starex',
	);
}

/**
 * Whether a post belongs on the motorhome listing.
 *
 * @param WP_Post|int $post Post object or ID.
 */
function proautospec_avtodoma_is_listing( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'avto' !== $post->post_type ) {
		return false;
	}

	$source = get_post_meta( $post->ID, '_source', true );
	if ( in_array( $source, array( 'fujicars', 'bobaedream', 'encar' ), true ) ) {
		return true;
	}

	$title = $post->post_title;
	if ( preg_match( '/^(Автодом|キャンピングカー)/u', $title ) ) {
		return true;
	}

	return (bool) preg_match( '/\bCamroad\b/i', $title );
}

/**
 * Resolve manufacturer for a canonical model name.
 */
function proautospec_avtodoma_model_to_mark( $model ) {
	$map = proautospec_avtodoma_model_mark_map();
	return $map[ $model ] ?? '';
}

/**
 * Normalize first model token from a title tail.
 */
function proautospec_avtodoma_normalize_model_token( $token ) {
	$token = trim( (string) $token );
	if ( '' === $token ) {
		return '';
	}

	$aliases = proautospec_avtodoma_model_aliases();
	if ( isset( $aliases[ $token ] ) ) {
		return $aliases[ $token ];
	}

	foreach ( array_keys( proautospec_avtodoma_model_mark_map() ) as $known ) {
		if ( 0 === strcasecmp( $known, $token ) ) {
			return $known;
		}
	}

	return $token;
}

/**
 * Known model names sorted longest-first (for multi-word match).
 *
 * @return string[]
 */
function proautospec_avtodoma_known_models_longest() {
	$models = array_keys( proautospec_avtodoma_model_mark_map() );
	usort(
		$models,
		static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);
	return $models;
}

/**
 * Find the first known chassis model inside free text.
 *
 * @return array{mark: string, model: string}
 */
function proautospec_avtodoma_match_known_model( $text ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return array(
			'mark'  => '',
			'model' => '',
		);
	}

	foreach ( proautospec_avtodoma_known_models_longest() as $model ) {
		// Unicode-safe boundaries: PHP \b is ASCII-only and breaks near Cyrillic.
		$pattern = '/(?<![\p{L}\p{N}])' . preg_quote( $model, '/' ) . '(?![\p{L}\p{N}])/iu';
		if ( preg_match( $pattern, $text ) ) {
			return array(
				'mark'  => proautospec_avtodoma_model_to_mark( $model ),
				'model' => $model,
			);
		}
	}

	return array(
		'mark'  => '',
		'model' => '',
	);
}

/**
 * Parse Japanese camping-car listing titles.
 *
 * @return array{mark: string, model: string}
 */
function proautospec_avtodoma_parse_japanese_title( $title ) {
	$patterns = array(
		'Hiace'      => '/ハイエース|ﾊｲｴｰｽ/u',
		'Bongo'      => '/ボンゴ|ﾎﾞﾝｺﾞ/u',
		'NV200'      => '/NV200/u',
		'Delica'     => '/デリカ|ﾃﾞﾘｶ/u',
		'Hijet'      => '/ハイゼット|ﾊｲｼﾞｪ/u',
		'Every'      => '/エブリィ|ｴﾌﾞﾘ/u',
		'Camroad'    => '/キャンロード|ｷｬﾝﾛｰﾄﾞ/u',
	);

	foreach ( $patterns as $model => $pattern ) {
		if ( preg_match( $pattern, $title ) ) {
			return array(
				'mark'  => proautospec_avtodoma_model_to_mark( $model ),
				'model' => $model,
			);
		}
	}

	return array(
		'mark'  => '',
		'model' => '',
	);
}

/**
 * Parse Korean Encar camping-car listing titles (fallback before KO→RU translate).
 *
 * @return array{mark: string, model: string}
 */
function proautospec_avtodoma_parse_korean_title( $title ) {
	$patterns = array(
		'Grand Starex' => '/그랜드\s*스타렉스/u',
		'Starex'       => '/스타렉스/u',
		'Staria'       => '/스타리아/u',
		'Porter'       => '/포터/u',
		'Bongo'        => '/봉고/u',
		'Carnival'     => '/카니발/u',
		'County'       => '/카운티/u',
		'Solati'       => '/쏠라티/u',
		'Master'       => '/마스터/u',
		'Sprinter'     => '/스프린터/u',
	);

	foreach ( $patterns as $model => $pattern ) {
		if ( preg_match( $pattern, $title ) ) {
			$mark = proautospec_avtodoma_model_to_mark( $model );
			if ( preg_match( '/기아/u', $title ) ) {
				$mark = 'Kia';
			} elseif ( preg_match( '/현대/u', $title ) ) {
				$mark = 'Hyundai';
			} elseif ( preg_match( '/벤츠|메르세데스/u', $title ) ) {
				$mark = 'Mercedes-Benz';
			}
			return array(
				'mark'  => $mark,
				'model' => $model,
			);
		}
	}

	return array(
		'mark'  => '',
		'model' => '',
	);
}

/**
 * Parse model + mark from the remainder of an «Автодом …» title.
 *
 * @return array{mark: string, model: string}
 */
function proautospec_avtodoma_parse_model_rest( $rest ) {
	$rest = trim( (string) $rest );
	if ( '' === $rest ) {
		return array(
			'mark'  => '',
			'model' => '',
		);
	}

	if ( preg_match( '/^Toyo\s+Factory\b/i', $rest ) ) {
		return array(
			'mark'  => 'Toyota',
			'model' => 'Hiace',
		);
	}

	if ( preg_match( '/^Delica\s+D:?-?5\b/i', $rest ) ) {
		return array(
			'mark'  => 'Mitsubishi',
			'model' => 'Delica D:5',
		);
	}

	$known = proautospec_avtodoma_match_known_model( $rest );
	if ( '' !== $known['model'] ) {
		return $known;
	}

	$parts = preg_split( '/\s+/u', $rest );
	$token = proautospec_avtodoma_normalize_model_token( $parts[0] ?? '' );

	return array(
		'mark'  => proautospec_avtodoma_model_to_mark( $token ),
		'model' => $token,
	);
}

/**
 * Extract mark and model from a listing title.
 *
 * @return array{mark: string, model: string}
 */
function proautospec_avtodoma_parse_title( $title ) {
	$title = trim( (string) $title );
	if ( '' === $title ) {
		return array(
			'mark'  => '',
			'model' => '',
		);
	}

	if ( preg_match( '/^(Toyota|Hino|Mitsubishi|Nissan|Honda|Hyundai|Mazda|Daihatsu|Suzuki|Mercedes-Benz|Isuzu|Kia|Renault)\s+(.+)$/iu', $title, $matches ) ) {
		$brand  = ucwords( strtolower( $matches[1] ) );
		$parsed = proautospec_avtodoma_parse_model_rest( $matches[2] );
		if ( '' !== $parsed['model'] ) {
			return array(
				'mark'  => $parsed['mark'] ? $parsed['mark'] : $brand,
				'model' => $parsed['model'],
			);
		}
		return array(
			'mark'  => $brand,
			'model' => trim( $matches[2] ),
		);
	}

	if ( preg_match( '/^Автодом\s+(?:ван-кон|кабина-кон|лёгкий кемпер|автобус-кон)\s+(.+)$/iu', $title, $matches ) ) {
		return proautospec_avtodoma_parse_model_rest( $matches[1] );
	}

	if ( preg_match( '/^Автодом\s+(.+)$/iu', $title, $matches ) ) {
		return proautospec_avtodoma_parse_model_rest( $matches[1] );
	}

	if ( preg_match( '/^キャンピングカー/u', $title ) ) {
		return proautospec_avtodoma_parse_japanese_title( $title );
	}

	if ( preg_match( '/[가-힣]/u', $title ) ) {
		return proautospec_avtodoma_parse_korean_title( $title );
	}

	$known = proautospec_avtodoma_match_known_model( $title );
	if ( '' !== $known['model'] ) {
		return $known;
	}

	return array(
		'mark'  => '',
		'model' => '',
	);
}

/**
 * Listing year from ACF / post meta.
 *
 * @param WP_Post|int $post Post object or ID.
 */
function proautospec_avtodoma_get_year( $post ) {
	$post_id = $post instanceof WP_Post ? $post->ID : (int) $post;

	$year = get_post_meta( $post_id, 'properties_year', true );
	if ( is_numeric( $year ) && (int) $year > 0 ) {
		return (int) $year;
	}

	$year = get_field( 'year', $post_id );
	if ( is_numeric( $year ) && (int) $year > 0 ) {
		return (int) $year;
	}

	$props = get_field( 'properties', $post_id );
	if ( is_array( $props ) && ! empty( $props['year'] ) && is_numeric( $props['year'] ) ) {
		return (int) $props['year'];
	}

	return 0;
}

/**
 * Cached motorhome posts for the listing (category 1, motorhome-like only).
 *
 * @return WP_Post[]
 */
function proautospec_avtodoma_get_posts() {
	static $posts = null;

	if ( null !== $posts ) {
		return $posts;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'avto',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'category__in'           => array( PROAUTOSPEC_AVTODOMA_CATEGORY_ID ),
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	$posts = array_values(
		array_filter(
			$query->posts,
			'proautospec_avtodoma_is_listing'
		)
	);

	return $posts;
}

/**
 * Build filter facets from inventory.
 *
 * @return array{marks: array<int, array{id: string, text: string}>, models: array<string, array<int, array{id: string, text: string}>>, years: int[]}
 */
function proautospec_avtodoma_get_facets() {
	$marks   = array();
	$models  = array();
	$years   = array();

	foreach ( proautospec_avtodoma_get_posts() as $post ) {
		$parsed = proautospec_avtodoma_parse_title( $post->post_title );
		$mark   = trim( $parsed['mark'] );
		$model  = trim( $parsed['model'] );
		$year   = proautospec_avtodoma_get_year( $post );

		if ( '' !== $mark ) {
			$marks[ $mark ] = $mark;
		}

		if ( '' !== $mark && '' !== $model ) {
			if ( ! isset( $models[ $mark ] ) ) {
				$models[ $mark ] = array();
			}
			$models[ $mark ][ $model ] = $model;
		}

		if ( $year > 0 ) {
			$years[ $year ] = $year;
		}
	}

	natcasesort( $marks );
	foreach ( $models as $mark => $mark_models ) {
		natcasesort( $mark_models );
		$models[ $mark ] = $mark_models;
	}
	krsort( $years, SORT_NUMERIC );

	$mark_options = array();
	foreach ( $marks as $mark ) {
		$mark_options[] = array(
			'id'   => sanitize_title( $mark ),
			'text' => $mark,
		);
	}

	$model_options = array();
	foreach ( $models as $mark => $mark_models ) {
		$model_options[ sanitize_title( $mark ) ] = array();
		foreach ( $mark_models as $model ) {
			$model_options[ sanitize_title( $mark ) ][] = array(
				'id'   => sanitize_title( $model ),
				'text' => $model,
			);
		}
	}

	return array(
		'marks'  => array_values( $mark_options ),
		'models' => $model_options,
		'years'  => array_values( $years ),
	);
}

/**
 * Sanitize GET filter values for the listing.
 *
 * @param array<string, mixed> $request Request params (typically $_GET).
 * @return array{mark: string, model: string, year: int}
 */
function proautospec_avtodoma_sanitize_filters( $request ) {
	$mark  = isset( $request['mark'] ) ? sanitize_text_field( wp_unslash( $request['mark'] ) ) : '';
	$model = isset( $request['model'] ) ? sanitize_text_field( wp_unslash( $request['model'] ) ) : '';
	$year  = 0;

	// «year» is a reserved WordPress query var (date archives) — use car-year in URLs/forms.
	if ( isset( $request['car-year'] ) ) {
		$year = (int) $request['car-year'];
	} elseif ( isset( $request['year'] ) ) {
		$year = (int) $request['year'];
	}

	return array(
		'mark'  => $mark,
		'model' => $model,
		'year'  => $year > 0 ? $year : 0,
	);
}

/**
 * Match a post against active filters.
 *
 * @param WP_Post              $post    Post object.
 * @param array<string, mixed> $filters Sanitized filters.
 */
function proautospec_avtodoma_post_matches_filters( $post, $filters ) {
	$parsed = proautospec_avtodoma_parse_title( $post->post_title );
	$mark   = sanitize_title( $parsed['mark'] );
	$model  = sanitize_title( $parsed['model'] );
	$year   = proautospec_avtodoma_get_year( $post );

	if ( $filters['mark'] && $filters['mark'] !== $mark ) {
		return false;
	}

	if ( $filters['model'] && $filters['model'] !== $model ) {
		return false;
	}

	if ( $filters['year'] && (int) $filters['year'] !== $year ) {
		return false;
	}

	return true;
}

/**
 * Post IDs matching filters (empty filters → all motorhome IDs).
 *
 * @param array<string, mixed> $filters Sanitized filters.
 * @return int[]
 */
function proautospec_avtodoma_filtered_post_ids( $filters ) {
	$ids = array();

	foreach ( proautospec_avtodoma_get_posts() as $post ) {
		if ( proautospec_avtodoma_post_matches_filters( $post, $filters ) ) {
			$ids[] = $post->ID;
		}
	}

	return $ids;
}

/**
 * Build WP_Query args for the motorhome listing.
 *
 * @param array<string, mixed> $request Request params (typically $_GET).
 * @return array<string, mixed>
 */
function proautospec_avtodoma_query_args( $request ) {
	$filters = proautospec_avtodoma_sanitize_filters( $request );
	$post_ids = proautospec_avtodoma_filtered_post_ids( $filters );

	$paged = max(
		1,
		(int) get_query_var( 'paged' ),
		(int) get_query_var( 'page' )
	);

	$args = array(
		'post_type'              => 'avto',
		'post_status'            => 'publish',
		'posts_per_page'         => 20,
		'paged'                  => $paged,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => true,
	);

	if ( empty( $post_ids ) ) {
		$args['post__in'] = array( 0 );
	} else {
		$args['post__in'] = $post_ids;
	}

	return $args;
}

/**
 * Preserve active filter params in pagination links.
 *
 * @param array<string, mixed> $filters Sanitized filters.
 * @return array<string, int|string>
 */
function proautospec_avtodoma_pagination_args( $filters ) {
	$args = array();

	if ( ! empty( $filters['mark'] ) ) {
		$args['mark'] = $filters['mark'];
	}
	if ( ! empty( $filters['model'] ) ) {
		$args['model'] = $filters['model'];
	}
	if ( ! empty( $filters['year'] ) ) {
		$args['car-year'] = (int) $filters['year'];
	}

	return $args;
}

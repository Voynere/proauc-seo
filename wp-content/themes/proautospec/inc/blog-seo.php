<?php
/**
 * Блог proauc.ru: bootstrap, CTA, FAQ, schema.
 */

defined( 'ABSPATH' ) || exit;

function proauc_blog_cluster_slugs() {
	return array( 'yaponiya', 'koreya', 'kitaj', 'spectehnika', 'mototsikly', 'obzory', 'kejsy' );
}

function proauc_setup_blog() {
	$page_id = (int) get_option( 'page_for_posts' );
	if ( ! $page_id ) {
		$existing = get_page_by_path( 'blog' );
		if ( $existing ) {
			$page_id = (int) $existing->ID;
		} else {
			$page_id = wp_insert_post(
				array(
					'post_title'   => 'Статьи и полезное',
					'post_name'    => 'blog',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => 'Полезные материалы о покупке автомобилей и спецтехники с аукционов Японии, Кореи и Китая: этапы сделки, цены, документы и советы экспертов Proauc.',
				)
			);
		}
		if ( ! is_wp_error( $page_id ) && $page_id ) {
			update_option( 'page_for_posts', (int) $page_id );
		}
	}

	$labels = array(
		'yaponiya'    => 'Япония',
		'koreya'      => 'Корея',
		'kitaj'       => 'Китай',
		'spectehnika' => 'Спецтехника',
		'mototsikly'  => 'Мотоциклы',
		'obzory'      => 'Обзоры',
		'kejsy'       => 'Кейсы',
	);

	foreach ( $labels as $slug => $name ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	$category_base = get_option( 'category_base' );
	if ( '' === $category_base || '.' === $category_base ) {
		update_option( 'category_base', 'blog' );
	}
}

function proauc_seed_blog_posts( $seeds = null ) {
	if ( null === $seeds ) {
		if ( ! function_exists( 'proauc_get_blog_article_seeds' ) ) {
			require_once get_template_directory() . '/inc/blog-articles.php';
		}
		$seeds = proauc_get_blog_article_seeds();
	}
	foreach ( $seeds as $seed ) {
		$existing = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( $existing ) {
			continue;
		}

		$cat_id = 0;
		if ( ! empty( $seed['category'] ) ) {
			$term = get_term_by( 'slug', $seed['category'], 'category' );
			if ( $term ) {
				$cat_id = (int) $term->term_id;
			}
		}

		$post_id = wp_insert_post(
			array(
				'post_title'    => $seed['title'],
				'post_name'     => $seed['slug'],
				'post_content'  => $seed['content'],
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_category' => $cat_id ? array( $cat_id ) : array(),
				'post_author'   => 1,
				'post_date'     => ! empty( $seed['post_date'] ) ? $seed['post_date'] : current_time( 'mysql' ),
				'post_date_gmt' => ! empty( $seed['post_date'] ) ? get_gmt_from_date( $seed['post_date'] ) : current_time( 'mysql', 1 ),
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		if ( ! empty( $seed['seo_title'] ) ) {
			update_post_meta( $post_id, 'rank_math_title', $seed['seo_title'] );
		}
		if ( ! empty( $seed['seo_description'] ) ) {
			update_post_meta( $post_id, 'rank_math_description', $seed['seo_description'] );
		}
		if ( ! empty( $seed['cluster'] ) ) {
			update_post_meta( $post_id, 'proauc_blog_cluster', $seed['cluster'] );
		}
		if ( ! empty( $seed['thumbnail'] ) && function_exists( 'proauc_save_blog_post_thumbnail' ) ) {
			proauc_save_blog_post_thumbnail( $post_id, $seed['thumbnail'] );
		}
	}
}

/**
 * URL обложки статьи (тема /images/ или полный URL).
 */
function proauc_resolve_blog_thumbnail_url( $thumbnail ) {
	if ( ! $thumbnail ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $thumbnail ) ) {
		return $thumbnail;
	}
	return get_template_directory_uri() . '/images/' . ltrim( $thumbnail, '/' );
}

function proauc_save_blog_post_thumbnail( $post_id, $thumbnail ) {
	$url = proauc_resolve_blog_thumbnail_url( $thumbnail );
	if ( $url ) {
		update_post_meta( $post_id, 'proauc_blog_thumbnail', $url );
	}
}

function proauc_get_blog_cluster_card_image( $cluster ) {
	$map = array(
		'yaponiya'    => 'bg-alpha-cars.svg',
		'koreya'      => 'bg-alpha-red.svg',
		'kitaj'       => 'bg-alpha-red.svg',
		'spectehnika' => 'bg-alpha-vehicles.svg',
		'mototsikly'  => 'bg-alpha-cars.svg',
		'obzory'      => 'bg-recently-bought-card.svg',
		'kejsy'       => 'bg-recently-bought-card.svg',
	);

	$file = isset( $map[ $cluster ] ) ? $map[ $cluster ] : 'bg-alpha-cars.svg';
	return get_template_directory_uri() . '/images/' . $file;
}

function proauc_migrate_blog_post_thumbnails() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds() as $seed ) {
		if ( empty( $seed['slug'] ) || empty( $seed['thumbnail'] ) ) {
			continue;
		}

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post ) {
			continue;
		}

		proauc_save_blog_post_thumbnail( (int) $post->ID, $seed['thumbnail'] );
	}
}

function proauc_migrate_blog_wave3_dates() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds_wave3' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds_wave3() as $seed ) {
		if ( empty( $seed['slug'] ) || empty( $seed['post_date'] ) ) {
			continue;
		}

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_status'   => 'publish',
				'post_date'     => $seed['post_date'],
				'post_date_gmt' => get_gmt_from_date( $seed['post_date'] ),
			)
		);
	}

	flush_rewrite_rules( false );
}

function proauc_migrate_blog_wave4_schedule() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds_wave4' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds_wave4() as $seed ) {
		if ( empty( $seed['slug'] ) || empty( $seed['post_date'] ) ) {
			continue;
		}

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post ) {
			continue;
		}

		$gmt     = get_gmt_from_date( $seed['post_date'] );
		$publish = strtotime( $gmt ) <= time();

		wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_status'   => $publish ? 'publish' : 'future',
				'post_date'     => $seed['post_date'],
				'post_date_gmt' => $gmt,
			)
		);
	}

	flush_rewrite_rules( false );
}

function proauc_migrate_blog_wave5_schedule() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds_wave5' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds_wave5() as $seed ) {
		if ( empty( $seed['slug'] ) || empty( $seed['post_date'] ) ) {
			continue;
		}

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post ) {
			continue;
		}

		$gmt     = get_gmt_from_date( $seed['post_date'] );
		$publish = strtotime( $gmt ) <= time();

		wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_status'   => $publish ? 'publish' : 'future',
				'post_date'     => $seed['post_date'],
				'post_date_gmt' => $gmt,
			)
		);
	}

	flush_rewrite_rules( false );
}

function proauc_get_blog_posts_page_title() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page ) {
		$blog_page = get_post( $posts_page );
		if ( $blog_page && $blog_page->post_title ) {
			return $blog_page->post_title;
		}
	}

	return 'Статьи и полезное';
}

function proauc_get_blog_posts_page_url() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page ) {
		return get_permalink( $posts_page );
	}

	return home_url( '/blog/' );
}

function proauc_get_blog_breadcrumb_items() {
	$items = array(
		array(
			'name' => 'Главная',
			'url'  => home_url( '/' ),
		),
		array(
			'name' => proauc_get_blog_posts_page_title(),
			'url'  => proauc_get_blog_posts_page_url(),
		),
	);

	if ( is_singular( 'post' ) ) {
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	}

	return $items;
}

function proauc_render_breadcrumbs( array $items ) {
	if ( count( $items ) < 2 ) {
		return;
	}

	$last = count( $items ) - 1;
	?>
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php
		foreach ( $items as $i => $item ) :
			if ( empty( $item['name'] ) ) {
				continue;
			}
			$is_last = ( $i === $last );
			?>
			<span property="itemListElement" typeof="ListItem">
				<?php if ( ! $is_last && ! empty( $item['url'] ) ) : ?>
					<a property="item" typeof="WebPage" href="<?php echo esc_url( $item['url'] ); ?>">
						<span property="name"><?php echo esc_html( $item['name'] ); ?></span>
					</a>
				<?php else : ?>
					<span property="name"><?php echo esc_html( $item['name'] ); ?></span>
				<?php endif; ?>
				<meta property="position" content="<?php echo (int) ( $i + 1 ); ?>">
			</span>
			<?php if ( ! $is_last ) : ?> &gt; <?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

function proauc_render_blog_breadcrumbs() {
	proauc_render_breadcrumbs( proauc_get_blog_breadcrumb_items() );
}

function proauc_get_blog_author_display_name() {
	return 'Редакция Proauc';
}

function proauc_get_blog_category_breadcrumb_items() {
	$term  = get_queried_object();
	$items = array(
		array(
			'name' => 'Главная',
			'url'  => home_url( '/' ),
		),
		array(
			'name' => proauc_get_blog_posts_page_title(),
			'url'  => proauc_get_blog_posts_page_url(),
		),
	);

	if ( $term && ! is_wp_error( $term ) ) {
		$items[] = array(
			'name' => $term->name,
			'url'  => get_term_link( $term ),
		);
	}

	return $items;
}

function proauc_get_blog_category_lead( $slug ) {
	$map = array(
		'yaponiya'    => 'Материалы о покупке и доставке автомобилей с аукционов Японии: торги, документы, таможня и советы экспертов.',
		'koreya'      => 'Статьи об импорте автомобилей из Кореи: подбор, проверка, доставка и оформление «под ключ».',
		'kitaj'       => 'Обзоры и инструкции по заказу автомобилей из Китая, включая электромобили и новые модели.',
		'spectehnika' => 'Полезные материалы о покупке спецтехники с японских аукционов: экскаваторы, краны, самосвалы и другое.',
		'mototsikly'  => 'Статьи о мотоциклах с аукционов Японии: подбор, доставка и оформление.',
		'obzory'      => 'Обзоры моделей, рынков и трендов импорта автомобилей и спецтехники.',
		'kejsy'       => 'Реальные кейсы покупки и доставки автомобилей и техники клиентами Proauc.',
	);

	return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
}

function proauc_render_blog_expert( $cluster = '' ) {
	if ( ! $cluster ) {
		$cluster = proauc_get_blog_post_cluster();
	}
	$config = proauc_get_blog_cta_config( $cluster );
	?>
	<aside class="proauc-blog-expert my-5 p-4 p-lg-5 rounded">
		<div class="proauc-blog-expert__inner d-flex flex-column flex-md-row align-items-md-center gap-4">
			<div class="proauc-blog-expert__avatar flex-shrink-0" aria-hidden="true">
				<span class="proauc-blog-expert__icon"></span>
			</div>
			<div class="proauc-blog-expert__body flex-grow-1">
				<p class="proauc-blog-expert__label mb-1">Эксперт Proauc</p>
				<h2 class="h5 mb-2">Команда Proauc</h2>
				<p class="proauc-blog-expert__bio mb-3">Более 15 лет помогаем покупать автомобили и спецтехнику с аукционов Японии, Кореи и Китая. Проверяем лоты, ведём торги, оформляем доставку и таможню — с прозрачной сметой до начала сделки.</p>
				<div class="d-flex flex-wrap gap-2">
					<a class="btn btn-blue btn-sm" href="<?php echo esc_url( home_url( $config['catalog']['url'] ) ); ?>"><?php echo esc_html( $config['catalog']['label'] ); ?></a>
					<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#order-dialog">Консультация</button>
				</div>
			</div>
		</div>
	</aside>
	<?php
}

function proauc_migrate_blog_post_dates() {
	if ( ! function_exists( 'proauc_get_blog_article_seeds' ) ) {
		require_once get_template_directory() . '/inc/blog-articles.php';
	}

	foreach ( proauc_get_blog_article_seeds() as $seed ) {
		if ( empty( $seed['slug'] ) || empty( $seed['post_date'] ) ) {
			continue;
		}

		$post = get_page_by_path( $seed['slug'], OBJECT, 'post' );
		if ( ! $post ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_date'     => $seed['post_date'],
				'post_date_gmt' => get_gmt_from_date( $seed['post_date'] ),
			)
		);
	}
}

/**
 * Изображение статьи для schema (без SVG-заглушки темы).
 */
function proauc_get_post_schema_image( $post = null ) {
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

	$meta_thumb = get_post_meta( $post->ID, 'proauc_blog_thumbnail', true );
	if ( $meta_thumb ) {
		return $meta_thumb;
	}

	if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches ) ) {
		return $matches[1];
	}

	return '';
}

/**
 * BlogPosting для одной статьи ($standalone — обернуть в @context).
 */
function proauc_build_blogposting_schema( $post, $standalone = true ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$permalink = get_permalink( $post );
	$site_url  = home_url( '/' );
	$author_id = (int) $post->post_author;

	$schema = array(
		'@type'            => 'BlogPosting',
		'@id'              => $permalink . '#article',
		'headline'         => get_the_title( $post ),
		'url'              => $permalink,
		'datePublished'    => get_post_time( 'c', true, $post ),
		'dateModified'     => get_post_modified_time( 'c', true, $post ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $author_id ),
		),
		'publisher'        => array( '@id' => $site_url . '#organization' ),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $permalink . '#webpage',
		),
		'inLanguage'       => 'ru-RU',
	);

	$author_url = get_author_posts_url( $author_id );
	if ( $author_url ) {
		$schema['author']['url'] = $author_url;
	}

	$description = $post->post_excerpt
		? wp_strip_all_tags( $post->post_excerpt )
		: wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '…' );
	if ( $description ) {
		$schema['description'] = $description;
	}

	$image = proauc_get_post_schema_image( $post );
	if ( $image ) {
		$schema['image'] = $image;
	}

	if ( $standalone ) {
		$schema = array( '@context' => 'https://schema.org' ) + $schema;
	}

	return $schema;
}

/**
 * Дополняет BlogPosting/Article из Rank Math недостающими полями.
 */
function proauc_enrich_blogposting_entity( array &$entity, $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}

	$permalink = get_permalink( $post );
	$site_url  = home_url( '/' );
	$author_id = (int) $post->post_author;

	if ( empty( $entity['@id'] ) ) {
		$entity['@id'] = $permalink . '#article';
	}
	if ( empty( $entity['headline'] ) ) {
		$entity['headline'] = get_the_title( $post );
	}
	if ( empty( $entity['url'] ) ) {
		$entity['url'] = $permalink;
	}
	if ( empty( $entity['datePublished'] ) ) {
		$entity['datePublished'] = get_post_time( 'c', true, $post );
	}
	if ( empty( $entity['dateModified'] ) ) {
		$entity['dateModified'] = get_post_modified_time( 'c', true, $post );
	}
	if ( empty( $entity['author'] ) ) {
		$entity['author'] = array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $author_id ),
		);
	}
	if ( empty( $entity['publisher'] ) || ! is_array( $entity['publisher'] ) ) {
		$entity['publisher'] = array( '@id' => $site_url . '#organization' );
	} else {
		$entity['publisher']['@id'] = $site_url . '#organization';
	}
	if ( empty( $entity['mainEntityOfPage'] ) ) {
		$entity['mainEntityOfPage'] = array(
			'@type' => 'WebPage',
			'@id'   => $permalink . '#webpage',
		);
	}
	$entity['inLanguage'] = 'ru-RU';

	if ( empty( $entity['image'] ) ) {
		$image = proauc_get_post_schema_image( $post );
		if ( $image ) {
			$entity['image'] = $image;
		}
	}
}

function proauc_build_blogposting_list_item_schema( $post, $position ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$item = proauc_build_blogposting_schema( $post, false );
	if ( ! $item ) {
		return null;
	}

	return array(
		'@type'    => 'ListItem',
		'position' => (int) $position,
		'item'     => $item,
	);
}

/**
 * CollectionPage + ItemList(BlogPosting) для /blog/.
 */
function proauc_build_blog_index_schema() {
	global $wp_query;

	$page_url  = proauc_get_blog_posts_page_url();
	$title     = proauc_get_blog_posts_page_title();
	$site_url  = home_url( '/' );
	$site_name = get_bloginfo( 'name' );

	$description = '';
	if ( class_exists( '\RankMath\Paper\Paper' ) ) {
		$paper = \RankMath\Paper\Paper::get();
		$description = $paper->get_description();
	}
	if ( ! $description && function_exists( 'proauc_get_static_landing_meta' ) ) {
		$description = proauc_get_static_landing_meta( 'description' );
	}

	$list_items = array();
	if ( ! empty( $wp_query->posts ) ) {
		$pos = 1;
		foreach ( $wp_query->posts as $post ) {
			$list_item = proauc_build_blogposting_list_item_schema( $post, $pos++ );
			if ( $list_item ) {
				$list_items[] = $list_item;
			}
		}
	}

	$graph = array(
		array(
			'@type' => 'Organization',
			'@id'   => $site_url . '#organization',
			'name'  => $site_name,
			'url'   => home_url( '/kompaniya/' ),
		),
		array(
			'@type'     => 'WebSite',
			'@id'       => $site_url . '#website',
			'url'       => $site_url,
			'name'      => $site_name,
			'publisher' => array( '@id' => $site_url . '#organization' ),
		),
		array(
			'@type'      => 'CollectionPage',
			'@id'        => $page_url . '#webpage',
			'url'        => $page_url,
			'name'       => $title,
			'isPartOf'   => array( '@id' => $site_url . '#website' ),
			'inLanguage' => 'ru-RU',
			'mainEntity' => array( '@id' => $page_url . '#itemlist' ),
		),
	);

	if ( $description ) {
		$graph[2]['description'] = $description;
	}

	if ( $list_items ) {
		$graph[] = array(
			'@type'           => 'ItemList',
			'@id'             => $page_url . '#itemlist',
			'itemListElement' => $list_items,
		);
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
}

function proauc_output_blog_list_schema() {
	if ( ! is_home() || is_front_page() || ! function_exists( 'proauc_print_json_ld' ) ) {
		return;
	}

	if ( ! is_paged() ) {
		$index = proauc_build_blog_index_schema();
		if ( $index ) {
			proauc_print_json_ld( $index );
		}
	}

	$bc = proauc_build_breadcrumb_list_schema( proauc_get_blog_breadcrumb_items() );
	if ( $bc ) {
		proauc_print_json_ld( $bc );
	}
}

function proauc_output_single_post_schema() {
	if ( ! is_singular( 'post' ) || ! function_exists( 'proauc_print_json_ld' ) ) {
		return;
	}

	if ( empty( $GLOBALS['proauc_rank_math_blogposting'] ) ) {
		$post   = get_queried_object();
		$schema = proauc_build_blogposting_schema( $post, true );
		if ( $schema ) {
			proauc_print_json_ld( $schema );
		}
	}

	if ( empty( $GLOBALS['proauc_rank_math_breadcrumbs'] ) ) {
		$bc = proauc_build_breadcrumb_list_schema( proauc_get_blog_breadcrumb_items() );
		if ( $bc ) {
			proauc_print_json_ld( $bc );
		}
	}
}

function proauc_output_blog_faq_schema() {
	if ( ! is_singular( 'post' ) || ! function_exists( 'proauc_print_json_ld' ) ) {
		return;
	}

	$faqs = proauc_get_blog_post_faq();
	if ( ! $faqs ) {
		return;
	}

	$schema = proauc_build_faqpage_schema( get_permalink(), $faqs );
	if ( $schema ) {
		proauc_print_json_ld( $schema );
	}
}

function proauc_get_blog_post_cluster( $post = null ) {
	if ( null === $post ) {
		$post = get_post();
	}
	if ( ! $post ) {
		return '';
	}

	$cluster = get_post_meta( $post->ID, 'proauc_blog_cluster', true );
	if ( $cluster ) {
		return $cluster;
	}

	$terms = get_the_category( $post->ID );
	if ( $terms ) {
		return $terms[0]->slug;
	}

	return '';
}

function proauc_get_blog_cta_config( $cluster ) {
	$map = array(
		'yaponiya' => array(
			'headline' => 'Подберём авто с аукциона Японии под ваш бюджет',
			'catalog'  => array(
				'label' => 'Каталог авто из Японии',
				'url'   => '/avto-iz-yaponii/catalog/',
			),
			'extra'    => array(
				'label' => 'Как читать аукционный лист',
				'url'   => '/kak-chitat-aukczionnyj-list/',
			),
		),
		'koreya'   => array(
			'headline' => 'Поможем купить и привезти авто из Кореи',
			'catalog'  => array(
				'label' => 'Каталог авто из Кореи',
				'url'   => '/avto-iz-korei/catalog/',
			),
			'extra'    => array(
				'label' => 'Авто из Кореи — обзор раздела',
				'url'   => '/avto-iz-korei/',
			),
		),
		'kitaj'    => array(
			'headline' => 'Под заказ из Китая: подбор, проверка и доставка',
			'catalog'  => array(
				'label' => 'Каталог авто из Китая',
				'url'   => '/avto-iz-kitaya/catalog/',
			),
			'extra'    => array(
				'label' => 'Авто из Китая — обзор раздела',
				'url'   => '/avto-iz-kitaya/',
			),
		),
		'spectehnika' => array(
			'headline' => 'Подберём спецтехнику с аукциона Японии',
			'catalog'  => array(
				'label' => 'Каталог спецтехники',
				'url'   => '/spectehnika/',
			),
			'extra'    => array(
				'label' => 'Все категории техники',
				'url'   => '/spectehnika/catalog/',
			),
		),
		'mototsikly'  => array(
			'headline' => 'Подберём мотоцикл с аукциона Японии',
			'catalog'  => array(
				'label' => 'Каталог мотоциклов',
				'url'   => '/motorcycles/',
			),
			'extra'    => array(
				'label' => 'Как читать аукционный лист',
				'url'   => '/kak-chitat-aukczionnyj-list/',
			),
		),
		'obzory'      => array(
			'headline' => 'Подберём авто под ваш запрос',
			'catalog'  => array(
				'label' => 'Каталог авто из Японии',
				'url'   => '/avto-iz-yaponii/catalog/',
			),
			'extra'    => array(
				'label' => 'Статьи и полезное',
				'url'   => '/blog/',
			),
		),
		'kejsy'       => array(
			'headline' => 'Поможем повторить успешную сделку',
			'catalog'  => array(
				'label' => 'Каталог авто из Японии',
				'url'   => '/avto-iz-yaponii/catalog/',
			),
			'extra'    => array(
				'label' => 'Как купить с аукциона',
				'url'   => '/kak-kupit-avto-s-aukcziona-yaponii/',
			),
		),
	);

	return isset( $map[ $cluster ] ) ? $map[ $cluster ] : $map['yaponiya'];
}

function proauc_render_blog_cta( $cluster = '' ) {
	if ( ! $cluster ) {
		$cluster = proauc_get_blog_post_cluster();
	}
	$config = proauc_get_blog_cta_config( $cluster );
	?>
	<aside class="proauc-blog-cta my-5 p-4 p-lg-5 rounded bg-light border">
		<h2 class="h4 mb-3"><?php echo esc_html( $config['headline'] ); ?></h2>
		<p class="mb-4 text-muted">Оставьте заявку — менеджер Proauc рассчитает стоимость «под ключ» и предложит варианты в каталоге.</p>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn btn-blue" href="<?php echo esc_url( home_url( $config['catalog']['url'] ) ); ?>"><?php echo esc_html( $config['catalog']['label'] ); ?></a>
			<?php if ( ! empty( $config['extra'] ) ) : ?>
				<a class="btn btn-outline-secondary" href="<?php echo esc_url( home_url( $config['extra']['url'] ) ); ?>"><?php echo esc_html( $config['extra']['label'] ); ?></a>
			<?php endif; ?>
			<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#order-dialog">Оставить заявку</button>
		</div>
	</aside>
	<?php
}

function proauc_get_blog_post_faq( $slug = '' ) {
	if ( ! $slug && is_singular( 'post' ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
	}

	$faqs = array(
		'kak-kupit-avto-s-aukcziona-yaponii' => array(
			array(
				'q' => 'Сколько времени занимает покупка авто с аукциона Японии?',
				'a' => 'Обычно от 4 до 8 недель: подбор и торги, оплата, доставка до Владивостока, таможня и перегон по России. Срок зависит от маршрута и загрузки линий.',
			),
			array(
				'q' => 'Нужно ли самому разбираться в аукционных листах?',
				'a' => 'Нет. Менеджер подбирает варианты, проверяет лист и согласовывает с вами до торгов. Подробная расшифровка — в материале «Как читать аукционный лист» на нашем сайте.',
			),
			array(
				'q' => 'Что входит в услугу «под ключ»?',
				'a' => 'Подбор, участие в торгах, оплата на площадке, доставка, таможенное оформление и сопровождение документов. Итоговая смета фиксируется до начала сделки.',
			),
		),
		'skolko-stoit-privezti-avto-iz-yaponii' => array(
			array(
				'q' => 'Из чего складывается цена авто из Японии?',
				'a' => 'Стоимость лота на аукционе, фрахт, таможенные платежи, услуги брокера и доставка по РФ. Мы даём расчёт по каждой строке до покупки.',
			),
			array(
				'q' => 'Можно ли уложиться в фиксированный бюджет?',
				'a' => 'Да. На этапе подбора согласуем потолок по «под ключ» и подбираем марки и годы выпуска, которые реально укладываются в сумму с учётом курса и пошлин.',
			),
			array(
				'q' => 'Есть ли скрытые платежи после доставки во Владивосток?',
				'a' => 'Нет. В смету входят известные на момент расчёта расходы. Дополнительно могут понадобиться только услуги по вашему желанию — например, детейлинг или перегон в другой регион.',
			),
		),
		'kak-kupit-avto-iz-korei' => array(
			array(
				'q' => 'Чем покупка из Кореи отличается от Японии?',
				'a' => 'В Корее чаще продаются автомобили с внутреннего рынка, а не классические аукционные листы Японии. Проверяем историю, пробег и состояние по базам и отчётам дилера.',
			),
			array(
				'q' => 'Можно ли купить Hyundai или Kia с левым рулём из Кореи?',
				'a' => 'Ассортимент зависит от модели и года. На этапе заявки уточняем, какие комплектации доступны под ваш запрос.',
			),
			array(
				'q' => 'Сколько длится доставка из Кореи?',
				'a' => 'Морская доставка во Владивосток обычно занимает меньше, чем из Японии, но полный цикл «под ключ» всё равно планируйте от нескольких недель с учётом оплаты и таможни.',
			),
		),
		'kak-kupit-avto-iz-kitaya' => array(
			array(
				'q' => 'Покупаете только новые авто из Китая?',
				'a' => 'В каталоге есть и новые, и автомобили с небольшим пробегом. Фильтры на сайте и менеджер помогут сузить выбор.',
			),
			array(
				'q' => 'Как проверяют авто перед покупкой в Китае?',
				'a' => 'Запрашиваем отчёты, фото и видео, сверяем VIN и комплектацию. Для электромобилей отдельно проверяем состояние батареи и заявленный пробег.',
			),
			array(
				'q' => 'Какие бренды чаще заказывают из Китая?',
				'a' => 'BYD, Zeekr, Li Auto, Voyah, а также европейские и японские бренды, собранные на местных заводах. Актуальный список — в каталоге на сайте.',
			),
		),
		'rastamozka-avto-iz-yaponii' => array(
			array(
				'q' => 'Сколько длится растаможка авто из Японии?',
				'a' => 'Обычно несколько рабочих дней после прихода груза в порт при готовом пакете документов. Сложные случаи могут занять дольше.',
			),
			array(
				'q' => 'Можно ли заранее узнать сумму пошлины?',
				'a' => 'Да. Мы включаем прогноз таможенных платежей в смету «под ключ» до покупки лота на аукционе.',
			),
			array(
				'q' => 'Какие документы нужны от покупателя?',
				'a' => 'Паспортные данные, при оформлении не на владельца — доверенность. Остальное готовит брокер на основании договора и инвойсов по сделке.',
			),
		),
		'chem-avto-iz-korei-otlichaetsya-ot-yaponii' => array(
			array(
				'q' => 'Где дешевле — Корея или Япония?',
				'a' => 'Зависит от модели, года, объёма двигателя и курса. Сравниваем по смете «под ключ» для вашего запроса, а не по цене лота отдельно.',
			),
			array(
				'q' => 'В Корее есть аукционные листы как в Японии?',
				'a' => 'Основной формат другой: внутренний рынок и дилеры. Проверяем историю и состояние по отчётам и базам, а не по японскому аукционному листу.',
			),
			array(
				'q' => 'Какой вариант быстрее по доставке?',
				'a' => 'Морская линия из Кореи часто короче, но полный цикл с оплатой и таможней всё равно планируйте от нескольких недель.',
			),
		),
		'elektromobili-iz-kitaya-byd-zeekr' => array(
			array(
				'q' => 'Какие электромобили из Китая заказывают чаще всего?',
				'a' => 'BYD, Zeekr, Li Auto, Voyah — по спросу и наличию на площадках. Актуальный список смотрите в каталоге.',
			),
			array(
				'q' => 'Как проверить батарею перед покупкой?',
				'a' => 'Запрашиваем отчёты о ёмкости и циклах зарядки, фото панели приборов и при необходимости видеоосмотр на площадке.',
			),
			array(
				'q' => 'Подходит ли китайский EV для зимы в России?',
				'a' => 'Зависит от модели и условий эксплуатации. На этапе подбора обсуждаем зимнюю дальность и варианты с подогревом батареи.',
			),
		),
		'kak-kupit-spectehniku-s-aukcziona-yaponii' => array(
			array(
				'q' => 'Какую спецтехнику можно привезти с аукциона?',
				'a' => 'Экскаваторы, автокраны, самосвалы, погрузчики, седельные тягачи и другие категории — см. каталог на сайте.',
			),
			array(
				'q' => 'Что важнее — моточасы или год выпуска?',
				'a' => 'Оба параметра. Смотрим на наработку, состояние гидравлики и ходовой, а также на соответствие бюджету и задаче.',
			),
			array(
				'q' => 'Оформление такое же, как для легкового авто?',
				'a' => 'Таможенная процедура схожа, но код ТН ВЭД и требования к учёту зависят от типа техники. Сопровождаем оформление под ваш сценарий.',
			),
		),
		'obzor-toyota-land-cruiser-iz-yaponii' => array(
			array(
				'q' => 'Какую оценку на листе искать для Land Cruiser?',
				'a' => 'Ориентир — 4.0–4.5 и выше по кузову и салону, без серьёзных отметок на схеме. Конкретный лот согласуем до торгов.',
			),
			array(
				'q' => 'Сколько стоит Land Cruiser «под ключ» из Японии?',
				'a' => 'Зависит от года, мотора и оценки на листе. Даём смету до покупки — см. также материал о стоимости привоза из Японии.',
			),
			array(
				'q' => 'Чем обзор отличается от каталога на сайте?',
				'a' => 'В обзоре — критерии выбора и советы; в каталоге — актуальные лоты и цены. Оба дополняют друг друга.',
			),
		),
		'kejs-pokupka-toyota-iz-yaponii' => array(
			array(
				'q' => 'Это реальная сделка?',
				'a' => 'Сценарий типичный для клиентов Proauc; персональные данные обезличены. Сроки и этапы соответствуют практике 2025–2026 годов.',
			),
			array(
				'q' => 'Сколько длилась доставка в кейсе?',
				'a' => 'Около 6 недель от заявки до получения во Владивостоке — в пределах обычного диапазона для авто из Японии.',
			),
			array(
				'q' => 'Можно ли повторить такой сценарий?',
				'a' => 'Да. Опишите модель и бюджет в заявке — менеджер подберёт лоты и зафиксирует смету до торгов.',
			),
		),
		'kak-kupit-mototsikl-s-aukcziona-yaponii' => array(
			array(
				'q' => 'Какие марки мото чаще заказывают из Японии?',
				'a' => 'Honda, Yamaha, Kawasaki, Suzuki, а также премиальные бренды — по запросу и наличию на аукционах.',
			),
			array(
				'q' => 'Нужна ли отдельная упаковка для морской доставки?',
				'a' => 'Да. Мото готовят к перевозке на площадке — это входит в логистику сделки «под ключ».',
			),
			array(
				'q' => 'Где смотреть актуальные лоты?',
				'a' => 'В разделе мотоциклов на сайте и по подбору менеджера на японских аукционах.',
			),
		),
		'obzor-hyundai-palisade-iz-korei' => array(
			array(
				'q' => 'Чем Palisade из Кореи отличается от японского кроссовера?',
				'a' => 'Другой рынок подбора: проверка по отчётам дилера, а не аукционный лист. Часто больше леворульных комплектаций.',
			),
			array(
				'q' => 'Какие моторы Palisade встречаются чаще?',
				'a' => 'Бензиновые V6 и дизель — выбор зависит от бюджета и сценария. Уточняем на этапе подбора.',
			),
			array(
				'q' => 'Где посмотреть предложения Palisade?',
				'a' => 'В каталоге авто из Кореи на сайте или по заявке менеджеру.',
			),
		),
		'obzor-byd-seal-iz-kitaya' => array(
			array(
				'q' => 'Какой запас хода у BYD Seal в реальных условиях?',
				'a' => 'Зависит от версии батареи и климата. На этапе подбора уточняем комплектацию и обсуждаем зимнюю дальность.',
			),
			array(
				'q' => 'Можно ли заказать Seal с быстрой DC-зарядкой?',
				'a' => 'Да, поддержка DC зависит от версии. Сверяем комплектацию до оплаты.',
			),
			array(
				'q' => 'Где смотреть актуальные предложения BYD?',
				'a' => 'В каталоге авто из Китая на сайте или по заявке менеджеру.',
			),
		),
		'kejs-pokupka-kia-sorento-iz-korei' => array(
			array(
				'q' => 'Сколько длилась сделка в кейсе?',
				'a' => 'Около 5 недель от заявки до получения во Владивостоке — типичный диапазон для Кореи.',
			),
			array(
				'q' => 'Проверяли ли историю Sorento перед покупкой?',
				'a' => 'Да. Сверяем пробег, страховые случаи и количество владельцев по базам до оплаты.',
			),
			array(
				'q' => 'Можно ли организовать доставку в другой город ДВ?',
				'a' => 'Да. По запросу организуем перегон из Владивостока после таможни.',
			),
		),
		'obzor-komatsu-pc200-iz-yaponii' => array(
			array(
				'q' => 'Какие моточасы считаются нормальными для PC200?',
				'a' => 'До 8–10 тыс. моточасов при хорошем отчёте по гидравлике — ориентир для подбора без сюрпризов.',
			),
			array(
				'q' => 'Чем PC200 отличается от PC210 или Hitachi ZX200?',
				'a' => 'Класс машины схожий; сравниваем по моточасам, году, состоянию и итогу «под ключ» для вашей задачи.',
			),
			array(
				'q' => 'Где смотреть экскаваторы на аукционе?',
				'a' => 'В каталоге спецтехники на сайте или по подбору менеджера.',
			),
		),
		'dostavka-avto-v-regiony-dalnego-vostoka' => array(
			array(
				'q' => 'Можно ли сразу получить авто не во Владивостоке?',
				'a' => 'Сначала растаможка во Владивостоке. Перегон в ваш город — отдельный этап после выдачи документов.',
			),
			array(
				'q' => 'Сколько едет автовоз до Хабаровска?',
				'a' => 'Обычно 1–2 дня после погрузки. Точный срок зависит от расписания перевозчика.',
			),
			array(
				'q' => 'Доставляете ли на Сахалин или в Магадан?',
				'a' => 'Да, по запросу — маршрут и сроки согласуем индивидуально с учётом паромной логистики.',
			),
		),
		'obzor-toyota-alphard-iz-yaponii' => array(
			array(
				'q' => 'Какие серии Alphard чаще на аукционе?',
				'a' => '30 и 40 серии — выбор зависит от бюджета и желаемого года выпуска.',
			),
			array(
				'q' => 'Нужен ли полный привод для Alphard?',
				'a' => 'Для Дальнего Востока и зимы — желательно. Уточняем комплектацию на этапе подбора.',
			),
			array(
				'q' => 'Где смотреть лоты Alphard?',
				'a' => 'В каталоге авто из Японии или по заявке менеджеру.',
			),
		),
		'obzor-nissan-x-trail-iz-yaponii' => array(
			array(
				'q' => 'Есть ли гибридный X-Trail на аукционе?',
				'a' => 'Да, встречаются гибридные и бензиновые версии — уточняем при подборе.',
			),
			array(
				'q' => 'Чем X-Trail отличается от RAV4?',
				'a' => 'Схожий класс; сравниваем по состоянию на листе, пробегу и итогу «под ключ».',
			),
			array(
				'q' => 'Какая оценка на листе оптимальна?',
				'a' => 'Ориентир 4.0–4.5 при пробеге, согласованном с бюджетом.',
			),
		),
		'obzor-honda-vezel-iz-yaponii' => array(
			array(
				'q' => 'Vezel и HR-V — это одна модель?',
				'a' => 'Да, Vezel — японское название линейки, на ряде рынков известна как HR-V.',
			),
			array(
				'q' => 'Стоит ли брать гибридный Vezel?',
				'a' => 'Выгоден при городском пробеге; для дальних поездок обсудим бензиновую версию.',
			),
			array(
				'q' => 'Где смотреть предложения?',
				'a' => 'В каталоге Японии на сайте Proauc.',
			),
		),
		'obzor-kia-carnival-iz-korei' => array(
			array(
				'q' => 'Чем Carnival отличается от Alphard?',
				'a' => 'Carnival часто доступнее «под ключ»; Alphard — премиум-сегмент с аукциона Японии.',
			),
			array(
				'q' => 'Сколько мест в Carnival?',
				'a' => 'Встречаются 7- и 8-местные конфигурации — уточняем на подборе.',
			),
			array(
				'q' => 'Проверяете ли историю в Корее?',
				'a' => 'Да, сверяем пробег, ДТП и владельцев по базам до оплаты.',
			),
		),
		'byd-seal-i-zeekr-001-sravnenie' => array(
			array(
				'q' => 'Что выбрать — Seal или Zeekr 001?',
				'a' => 'Seal — седан с упором на запас хода; Zeekr 001 — простор и премиум-формат. Зависит от сценария.',
			),
			array(
				'q' => 'Какая зимняя дальность у этих EV?',
				'a' => 'Зависит от версии батареи и климата — обсуждаем на этапе подбора.',
			),
			array(
				'q' => 'Где заказать обе модели?',
				'a' => 'В каталоге авто из Китая или по заявке менеджеру.',
			),
		),
	);

	return isset( $faqs[ $slug ] ) ? $faqs[ $slug ] : array();
}

function proauc_build_faqpage_schema( $page_url, array $items ) {
	$entities = array();
	foreach ( $items as $item ) {
		if ( empty( $item['q'] ) || empty( $item['a'] ) ) {
			continue;
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['a'],
			),
		);
	}

	if ( ! $entities ) {
		return null;
	}

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'@id'        => $page_url . '#faq',
		'url'        => $page_url,
		'inLanguage' => 'ru-RU',
		'mainEntity' => $entities,
	);
}

function proauc_render_blog_faq( $slug = '' ) {
	$items = proauc_get_blog_post_faq( $slug );
	if ( ! $items ) {
		return;
	}
	?>
	<section class="proauc-blog-faq my-5" id="faq">
		<h2 class="h4 mb-4">Частые вопросы</h2>
		<div class="accordion" id="blogFaqAccordion">
			<?php foreach ( $items as $i => $item ) : ?>
				<div class="accordion-item">
					<h3 class="accordion-header" id="blog-faq-heading-<?php echo (int) $i; ?>">
						<button class="accordion-button<?php echo 0 === $i ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#blog-faq-collapse-<?php echo (int) $i; ?>" aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-controls="blog-faq-collapse-<?php echo (int) $i; ?>">
							<?php echo esc_html( $item['q'] ); ?>
						</button>
					</h3>
					<div id="blog-faq-collapse-<?php echo (int) $i; ?>" class="accordion-collapse collapse<?php echo 0 === $i ? ' show' : ''; ?>" aria-labelledby="blog-faq-heading-<?php echo (int) $i; ?>" data-bs-parent="#blogFaqAccordion">
						<div class="accordion-body">
							<?php echo esc_html( $item['a'] ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

function proauc_get_blog_category_seo( $slug ) {
	$map = array(
		'yaponiya'    => array(
			'title'       => 'Статьи об авто из Японии — аукционы, доставка, документы',
			'description' => 'Материалы о покупке автомобилей с аукционов Японии: торги, аукционный лист, доставка, таможня и советы экспертов Proauc.',
		),
		'koreya'      => array(
			'title'       => 'Статьи об авто из Кореи — покупка и доставка под заказ',
			'description' => 'Статьи об импорте автомобилей из Кореи: подбор Hyundai, Kia, Genesis, проверка истории и оформление «под ключ».',
		),
		'kitaj'       => array(
			'title'       => 'Статьи об авто из Китая — электромобили и новые модели',
			'description' => 'Обзоры и инструкции по заказу автомобилей из Китая: BYD, Zeekr, доставка и таможенное оформление.',
		),
		'spectehnika' => array(
			'title'       => 'Статьи о спецтехнике с аукционов Японии',
			'description' => 'Полезные материалы о покупке спецтехники: экскаваторы, краны, самосвалы — подбор, доставка и документы.',
		),
		'mototsikly'  => array(
			'title'       => 'Статьи о мотоциклах с аукционов Японии',
			'description' => 'Мотоциклы Honda, Yamaha, Kawasaki с японских аукционов: подбор, доставка и оформление во Владивостоке.',
		),
		'obzory'      => array(
			'title'       => 'Обзоры автомобилей и техники — Proauc',
			'description' => 'Обзоры моделей для покупателей: Land Cruiser, Palisade, BYD Seal, Komatsu PC200 и другие — критерии выбора и ссылки на каталог.',
		),
		'kejsy'       => array(
			'title'       => 'Кейсы покупки авто и техники — Proauc',
			'description' => 'Реальные сценарии привоза автомобилей и спецтехники клиентами Proauc: этапы, сроки и итоги сделок.',
		),
	);

	return isset( $map[ $slug ] ) ? $map[ $slug ] : array();
}

function proauc_render_blog_related() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$cats = get_the_category();
	if ( ! $cats ) {
		return;
	}

	$query = new WP_Query(
		array(
			'category__in'   => array( (int) $cats[0]->term_id ),
			'post__not_in'   => array( (int) get_the_ID() ),
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	if ( ! $query->have_posts() ) {
		return;
	}
	?>
	<aside class="proauc-blog-related my-5 p-4 p-lg-5 rounded">
		<h2 class="h5 mb-4">Читайте также</h2>
		<ul class="proauc-blog-related__list list-unstyled mb-0">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<li class="proauc-blog-related__item mb-3 pb-3 border-bottom">
					<time class="proauc-blog-related__date d-block small text-muted mb-1" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></time>
					<a class="proauc-blog-related__link" href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
	</aside>
	<?php
}

function proauc_append_auction_list_blog_links( $content ) {
	if ( ! is_singular() || ! function_exists( 'proauc_request_path' ) ) {
		return $content;
	}
	if ( proauc_request_path() !== '/kak-chitat-aukczionnyj-list/' ) {
		return $content;
	}

	$links = array(
		array(
			'url'   => home_url( '/kak-kupit-avto-s-aukcziona-yaponii/' ),
			'title' => 'Как купить авто с аукциона Японии под ключ',
		),
		array(
			'url'   => home_url( '/skolko-stoit-privezti-avto-iz-yaponii/' ),
			'title' => 'Сколько стоит привезти авто из Японии',
		),
		array(
			'url'   => home_url( '/rastamozka-avto-iz-yaponii/' ),
			'title' => 'Растаможка авто из Японии: документы и платежи',
		),
	);

	$html = '<aside class="proauc-auction-list-related my-5 p-4 p-lg-5 rounded border"><h2 class="h5 mb-3">Полезные статьи</h2><ul class="mb-0">';
	foreach ( $links as $link ) {
		$html .= '<li class="mb-2"><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['title'] ) . '</a></li>';
	}
	$html .= '</ul></aside>';

	return $content . $html;
}

add_filter( 'the_content', 'proauc_append_auction_list_blog_links', 25 );

add_action(
	'init',
	function () {
		proauc_setup_blog();
		if ( ! get_option( 'proauc_blog_seed_v1' ) ) {
			if ( ! function_exists( 'proauc_get_blog_article_seeds_wave1' ) ) {
				require_once get_template_directory() . '/inc/blog-articles.php';
			}
			proauc_seed_blog_posts( proauc_get_blog_article_seeds_wave1() );
			update_option( 'proauc_blog_seed_v1', 1 );
			flush_rewrite_rules( false );
		}
		if ( ! get_option( 'proauc_blog_seed_v2' ) ) {
			if ( ! function_exists( 'proauc_get_blog_article_seeds_wave2' ) ) {
				require_once get_template_directory() . '/inc/blog-articles.php';
			}
			proauc_seed_blog_posts( proauc_get_blog_article_seeds_wave2() );
			update_option( 'proauc_blog_seed_v2', 1 );
			flush_rewrite_rules( false );
		}
		if ( ! get_option( 'proauc_blog_dates_v1' ) ) {
			proauc_migrate_blog_post_dates();
			update_option( 'proauc_blog_dates_v1', 1 );
		}
		if ( ! get_option( 'proauc_blog_seed_v3' ) ) {
			if ( ! function_exists( 'proauc_get_blog_article_seeds_wave3' ) ) {
				require_once get_template_directory() . '/inc/blog-articles.php';
			}
			proauc_seed_blog_posts( proauc_get_blog_article_seeds_wave3() );
			update_option( 'proauc_blog_seed_v3', 1 );
			flush_rewrite_rules( false );
		}
		if ( ! get_option( 'proauc_blog_thumbs_v1' ) ) {
			proauc_migrate_blog_post_thumbnails();
			update_option( 'proauc_blog_thumbs_v1', 1 );
		}
		if ( ! get_option( 'proauc_blog_wave3_dates_v1' ) ) {
			proauc_migrate_blog_wave3_dates();
			update_option( 'proauc_blog_wave3_dates_v1', 1 );
		}
		if ( ! get_option( 'proauc_blog_seed_v4' ) ) {
			if ( ! function_exists( 'proauc_get_blog_article_seeds_wave4' ) ) {
				require_once get_template_directory() . '/inc/blog-articles.php';
			}
			proauc_seed_blog_posts( proauc_get_blog_article_seeds_wave4() );
			update_option( 'proauc_blog_seed_v4', 1 );
			flush_rewrite_rules( false );
		}
		if ( ! get_option( 'proauc_blog_wave4_schedule_v1' ) ) {
			proauc_migrate_blog_wave4_schedule();
			update_option( 'proauc_blog_wave4_schedule_v1', 1 );
		}
		if ( ! get_option( 'proauc_blog_seed_v5' ) ) {
			if ( ! function_exists( 'proauc_get_blog_article_seeds_wave5' ) ) {
				require_once get_template_directory() . '/inc/blog-articles.php';
			}
			proauc_seed_blog_posts( proauc_get_blog_article_seeds_wave5() );
			update_option( 'proauc_blog_seed_v5', 1 );
			flush_rewrite_rules( false );
		}
		if ( ! get_option( 'proauc_blog_wave5_schedule_v1' ) ) {
			proauc_migrate_blog_wave5_schedule();
			update_option( 'proauc_blog_wave5_schedule_v1', 1 );
		}
	},
	20
);

/**
 * URL обложки для карточки в списке блога.
 */
function proauc_blog_card_image_url( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	if ( has_post_thumbnail( $post ) ) {
		return (string) get_the_post_thumbnail_url( $post, 'medium_large' );
	}

	$meta_thumb = get_post_meta( $post->ID, 'proauc_blog_thumbnail', true );
	if ( $meta_thumb ) {
		return $meta_thumb;
	}

	if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches ) ) {
		return $matches[1];
	}

	$cluster = function_exists( 'proauc_get_blog_post_cluster' ) ? proauc_get_blog_post_cluster( $post ) : '';
	return proauc_get_blog_cluster_card_image( $cluster );
}

/**
 * Краткий анонс без кнопки Read More из picostrap.
 */
function proauc_blog_card_excerpt( $post = null, $words = 22 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	if ( $post->post_excerpt ) {
		return wp_trim_words( wp_strip_all_tags( $post->post_excerpt ), $words, '…' );
	}

	return wp_trim_words( wp_strip_all_tags( $post->post_content ), $words, '…' );
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_category() ) {
			$term = get_queried_object();
			if ( $term && in_array( $term->slug, proauc_blog_cluster_slugs(), true ) ) {
				$classes[] = 'blog';
			}
		}
		return $classes;
	}
);

add_action( 'wp_head', 'proauc_output_blog_list_schema', 96 );
add_action( 'wp_head', 'proauc_output_single_post_schema', 99 );
add_action( 'wp_head', 'proauc_output_blog_faq_schema', 97 );

add_filter(
	'rank_math/frontend/title',
	function ( $title ) {
		if ( ! is_category() ) {
			return $title;
		}
		$term = get_queried_object();
		if ( ! $term || ! function_exists( 'proauc_blog_cluster_slugs' ) || ! in_array( $term->slug, proauc_blog_cluster_slugs(), true ) ) {
			return $title;
		}
		$seo = proauc_get_blog_category_seo( $term->slug );
		if ( ! empty( $seo['title'] ) ) {
			return $seo['title'];
		}
		return $title;
	},
	30
);

add_filter(
	'rank_math/frontend/description',
	function ( $description ) {
		if ( ! is_category() ) {
			return $description;
		}
		$term = get_queried_object();
		if ( ! $term || ! function_exists( 'proauc_blog_cluster_slugs' ) || ! in_array( $term->slug, proauc_blog_cluster_slugs(), true ) ) {
			return $description;
		}
		$seo = proauc_get_blog_category_seo( $term->slug );
		if ( ! empty( $seo['description'] ) ) {
			return $seo['description'];
		}
		return $description;
	},
	30
);

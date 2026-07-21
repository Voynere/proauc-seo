<?php
/**
 * SEO-логика каталога и ключевых разделов proauc.ru.
 * Подключается Rank Math автоматически (theme/rank-math.php).
 * Динамические title, description, canonical и schema — в теме; Rank Math остаётся
 * базовым слоём вывода meta-тегов (плагин не отключаем).
 */

function proauc_catalog_post_ids() {
	return array( 41, 43, 45, 46, 48, 51, 70 );
}

function proauc_is_catalog_page( $post = null ) {
	if ( null === $post ) {
		global $post;
	}
	return $post && in_array( (int) $post->ID, proauc_catalog_post_ids(), true );
}

/**
 * Чистый URL каталога без query-параметров фильтров.
 */
function proauc_get_catalog_canonical_url() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}
	$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	if ( empty( $path ) || '/' === $path ) {
		return '';
	}
	return user_trailingslashit( home_url( $path ) );
}

/**
 * Query-параметры каталога, для которых canonical — чистый path (страница не должна индексироваться).
 */
function proauc_catalog_has_noncanonical_query() {
	$filter_keys = array(
		'page',
		'year-start',
		'year-end',
		'mileage-start',
		'mileage-end',
		'engine-start',
		'engine-end',
		'price-start',
		'price-end',
		'rate',
		'auction_date',
		'category',
		'mark-id',
		'model-id',
		's',
	);

	foreach ( $filter_keys as $key ) {
		if ( isset( $_GET[ $key ] ) && $_GET[ $key ] !== '' ) {
			return true;
		}
	}

	if ( isset( $_GET['pn'] ) && (int) $_GET['pn'] > 1 ) {
		return true;
	}

	return false;
}

/**
 * Служебные страницы WordPress, которые не должны попадать в индекс.
 */
function proauc_get_noindex_page_ids() {
	return array(
		798, // /spasibo/ — страница после отправки формы
	);
}

/**
 * Пустые шаблоны карточек (/car-lot/, /moto-lot/, /hdm-lot/ без номера лота).
 */
function proauc_is_lot_shell_page() {
	global $post, $wp;

	if ( ! $post ) {
		return false;
	}

	$post_id = (int) $post->ID;

	if ( 60 === $post_id && empty( $wp->query_vars['car-lot'] ) ) {
		return true;
	}
	if ( 62 === $post_id && empty( $wp->query_vars['moto-lot'] ) ) {
		return true;
	}
	if ( 70 === $post_id && empty( $wp->query_vars['hdm-lot'] ) && empty( $wp->query_vars['hdm-slug'] ) ) {
		return true;
	}

	return false;
}

/**
 * Страницы и URL, которые не должны индексироваться.
 */
function proauc_should_noindex_request() {
	global $post;

	if ( proauc_is_catalog_page() && proauc_catalog_has_noncanonical_query() ) {
		return true;
	}

	if ( proauc_is_lot_shell_page() ) {
		return true;
	}

	if ( $post && in_array( (int) $post->ID, proauc_get_noindex_page_ids(), true ) ) {
		return true;
	}

	if ( ! empty( $_GET['sitemap-create'] ) || ! empty( $_GET['sitemap-lots-create'] ) || ! empty( $_GET['sitemap-hdm-create'] ) ) {
		return true;
	}

	if ( is_paged() && ! is_singular() ) {
		return true;
	}

	return false;
}

/**
 * ID страниц, исключаемых из Rank Math page-sitemap.
 */
function proauc_get_sitemap_excluded_page_ids() {
	return array_merge( proauc_get_noindex_page_ids(), array( 60, 62, 70 ) );
}

function proauc_api_meta_field( $field ) {
	global $post;
	if ( ! $post || ! property_exists( $post, 'api_meta' ) || ! is_object( $post->api_meta ) ) {
		return '';
	}
	return ! empty( $post->api_meta->$field ) ? $post->api_meta->$field : '';
}

function proauc_is_country_catalog_mark_model() {
	global $wp;
	return ( ! empty( $wp->query_vars['mark'] ) || ! empty( $wp->query_vars['model'] ) )
		&& ! empty( $wp->query_vars['country'] )
		&& in_array( $wp->query_vars['country'], array( 'china', 'korea', 'japan' ), true );
}

function proauc_build_catalog_auto_title() {
	global $wp;

	$auto_name = str_replace( '-', ' ', strtoupper( $wp->query_vars['mark'] ) );
	if ( ! empty( $wp->query_vars['model'] ) ) {
		$auto_name .= ' ' . str_replace( '-', ' ', strtoupper( $wp->query_vars['model'] ) );
	}

	$title = $auto_name;
	if ( 'china' === $wp->query_vars['country'] ) {
		$title .= ' из Китая';
	}
	if ( 'japan' === $wp->query_vars['country'] ) {
		$title .= ' из Японии';
	}
	if ( 'korea' === $wp->query_vars['country'] ) {
		$title .= ' из Кореи';
	}

	return $title . ' — купить ' . $auto_name . ' с пробегом';
}

function proauc_build_catalog_auto_description() {
	global $wp;

	$auto_name = str_replace( '-', ' ', strtoupper( $wp->query_vars['mark'] ) );
	if ( ! empty( $wp->query_vars['model'] ) ) {
		$auto_name .= ' ' . str_replace( '-', ' ', strtoupper( $wp->query_vars['model'] ) );
	}

	$description = 'Привозим ' . $auto_name . ' напрямую';
	if ( 'china' === $wp->query_vars['country'] ) {
		$description .= ' из Китая';
	}
	if ( 'japan' === $wp->query_vars['country'] ) {
		$description .= ' из Японии';
	}
	if ( 'korea' === $wp->query_vars['country'] ) {
		$description .= ' из Кореи';
	}

	return $description . ' под заказ! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.';
}

/**
 * Статические title/description для ключевых посадочных (P2 из SEO-аудита).
 */
function proauc_get_static_landing_seo() {
	return array(
		'/' => array(
			'title'       => 'Автомобили и спецтехника под заказ — Владивосток и Дальний Восток',
			'description' => 'Автомобили и спецтехника из Японии, Кореи и Китая под заказ: подбор, выкуп, таможня и доставка во Владивосток и по Дальнему Востоку. Прозрачные условия и сопровождение сделки.',
		),
		'/kak-chitat-aukczionnyj-list/' => array(
			'title'       => 'Как читать аукционный лист: оценки и расшифровка обозначений',
			'description' => 'Расшифровка аукционного листа японского авто: оценки кузова и салона, листы USS, JAA, TAA, обозначения A1–W3. Подбор и доставка авто с аукциона под ключ.',
		),
		'/blog/' => array(
			'title'       => 'Статьи о покупке авто с аукционов — Япония, Корея, Китай',
			'description' => 'Полезные материалы Proauc: как купить авто с аукциона, растаможка, цены, спецтехника и электромобили из Китая. Советы экспертов и ответы на частые вопросы.',
		),
		'/motorcycles/' => array(
			'title'       => 'Мотоциклы с аукциона Японии — под заказ с доставкой по ДВ',
			'description' => 'Мотоциклы Honda, Yamaha, Kawasaki и Harley с аукционов Японии под заказ: доставка во Владивосток, Хабаровск и другие города Дальнего Востока. Подбор, выкуп и оформление.',
		),
		'/avto-iz-kitaya/' => array(
			'description' => 'Автомобили из Китая напрямую под заказ с доставкой! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/avto-iz-kitaya/catalog/' => array(
			'title'       => 'Каталог авто из Китая: марки и модели под заказ',
			'description' => 'Каталог автомобилей из Китая: марки, модели и актуальные предложения под заказ. Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/avto-iz-korei/' => array(
			'title'       => 'Авто из Кореи под заказ — доставка во Владивосток и по ДВ',
			'description' => 'Автомобили из Кореи под заказ с доставкой во Владивосток, Хабаровск и другие города Дальнего Востока. Hyundai, Kia, Genesis — проверенная история и сопровождение сделки.',
		),
		'/avto-iz-korei/catalog/' => array(
			'title'       => 'Каталог авто из Кореи: марки и модели под заказ',
			'description' => 'Каталог автомобилей из Кореи: марки, модели и актуальные предложения под заказ. Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/avto-iz-yaponii/' => array(
			'title'       => 'Авто с аукциона Японии — доставка во Владивосток и по ДВ',
			'description' => 'Автомобили с аукционов Японии под заказ: доставка во Владивосток, Хабаровск, Благовещенск и другие города Дальнего Востока. Подбор, торги, таможня и сопровождение.',
		),
		'/avto-iz-yaponii/catalog/' => array(
			'title'       => 'Каталог авто из Японии: аукционы онлайн',
			'description' => 'Каталог автомобилей с аукционов Японии: марки, модели и актуальные лоты под заказ. Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/avto-iz-yaponii/statistika/' => array(
			'title'       => 'Статистика аукционов Японии: цены, пробеги и продажи',
			'description' => 'Статистика аукционов Японии: цены, пробеги и популярные модели! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/avtodoma/' => array(
			'title'       => 'Автодома — кемперы и кабины-кон из Японии и Кореи',
			'description' => 'Автодома и кемперы: кабины-кон, ван-кон и моторхомы из Японии и Кореи. Проверенная история, доставка по России и полное сопровождение сделки.',
		),
		'/avto-v-nalichii/' => array(
			'description' => 'Автодома и кемперы с проверенной историей и готовностью к покупке! Выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/kompaniya/' => array(
			'description' => 'О компании Proauc и поставке автомобилей из Японии, Кореи и Китая! Проверенная репутация, выгодные условия и полное сопровождение на всех этапах сделки.',
		),
		'/kontaktyi/' => array(
			'title'       => 'Контакты Proauc во Владивостоке — подбор авто с аукционов',
			'description' => 'Контакты Proauc во Владивостоке: консультация по авто из Японии, Кореи и Китая, доставка по Дальнему Востоку. Быстрый ответ и сопровождение сделки.',
		),
		'/spectehnika/' => array(
			'title'       => 'Спецтехника с аукционов — доставка во Владивосток и по России',
			'description' => 'Спецтехника с аукционов Японии, Китая и Кореи: экскаваторы, краны, самосвалы. Подбор, доставка в порт Владивосток и оформление документов.',
		),
		'/spectehnika/catalog/' => array(
			'description' => 'Каталог спецтехники с аукционов Японии, Китая и Кореи: автокраны, экскаваторы, погрузчики и другая техника под заказ. Подбор, выкуп, доставка и оформление документов.',
		),
		'/spectehnika/sedelnye-tyagachi/' => array(
			'title'       => 'Седельные тягачи под заказ из Японии, Китая и Кореи',
			'description' => 'Седельные тягачи с аукционов Японии, Китая и Кореи под заказ: подбор, выкуп, доставка и оформление документов.',
		),
		'/spectehnika/drygoe/' => array(
			'title'       => 'Прочая спецтехника с аукционов под заказ',
			'description' => 'Каталог прочей спецтехники с аукционов: подбор, выкуп, доставка и таможенное оформление по России.',
		),
	);
}

function proauc_get_static_landing_meta( $field ) {
	$path = proauc_request_path();
	$seo  = proauc_get_static_landing_seo();
	if ( ! isset( $seo[ $path ][ $field ] ) ) {
		return '';
	}
	return $seo[ $path ][ $field ];
}

function proauc_hdm_display_name() {
	global $post;
	if ( ! $post || ! property_exists( $post, 'api_meta' ) || ! is_object( $post->api_meta ) ) {
		return '';
	}
	if ( ! empty( $post->api_meta->name_ru ) ) {
		return $post->api_meta->name_ru;
	}
	if ( ! empty( $post->api_meta->h1 ) ) {
		return $post->api_meta->h1;
	}
	return '';
}

function proauc_build_hdm_auto_title() {
	$name = proauc_hdm_display_name();
	if ( ! $name ) {
		return '';
	}
	return $name . ' с аукциона — подбор, выкуп и доставка по России';
}

function proauc_build_hdm_auto_description() {
	$name = proauc_hdm_display_name();
	if ( ! $name ) {
		return '';
	}
	return $name . ' из Японии, Китая и Кореи под заказ: новая и б/у техника с аукционов. Подбор, выкуп, доставка и таможенное оформление.';
}

function proauc_is_hdm_catalog_page() {
	global $post, $wp;
	return $post && in_array( (int) $post->ID, array( 40, 41, 43 ), true ) && ! empty( $wp->query_vars['hdm-group'] );
}

function proauc_build_breadcrumb_list_schema( $items ) {
	if ( count( $items ) < 2 ) {
		return null;
	}

	$list = array();
	$pos  = 1;
	foreach ( $items as $item ) {
		if ( empty( $item['name'] ) || empty( $item['url'] ) ) {
			continue;
		}
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => $item['name'],
			'item'     => $item['url'],
		);
	}

	if ( count( $list ) < 2 ) {
		return null;
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
}

function proauc_get_catalog_breadcrumb_items() {
	global $wp;

	$items   = array();
	$items[] = array(
		'name' => 'Главная',
		'url'  => home_url( '/' ),
	);

	$country = isset( $wp->query_vars['country'] ) ? $wp->query_vars['country'] : '';
	$roots   = array(
		'japan' => array(
			'name'    => 'Авто из Японии',
			'url'     => home_url( '/avto-iz-yaponii/' ),
			'catalog' => home_url( '/avto-iz-yaponii/catalog/' ),
		),
		'korea' => array(
			'name'    => 'Авто из Кореи',
			'url'     => home_url( '/avto-iz-korei/' ),
			'catalog' => home_url( '/avto-iz-korei/catalog/' ),
		),
		'china' => array(
			'name'    => 'Авто из Китая',
			'url'     => home_url( '/avto-iz-kitaya/' ),
			'catalog' => home_url( '/avto-iz-kitaya/catalog/' ),
		),
	);

	if ( empty( $roots[ $country ] ) ) {
		return $items;
	}

	$root = $roots[ $country ];
	$items[] = array( 'name' => $root['name'], 'url' => $root['url'] );
	$items[] = array( 'name' => 'Каталог', 'url' => $root['catalog'] );

	if ( ! empty( $wp->query_vars['mark'] ) ) {
		$mark_url = $root['catalog'] . $wp->query_vars['mark'] . '/';
		$mark_name = str_replace( '-', ' ', strtoupper( $wp->query_vars['mark'] ) );
		$items[] = array( 'name' => $mark_name, 'url' => user_trailingslashit( $mark_url ) );

		if ( ! empty( $wp->query_vars['model'] ) ) {
			$model_url  = $mark_url . $wp->query_vars['model'] . '/';
			$model_name = $mark_name . ' ' . str_replace( '-', ' ', strtoupper( $wp->query_vars['model'] ) );
			$items[] = array( 'name' => trim( $model_name ), 'url' => user_trailingslashit( $model_url ) );
		}
	}

	return $items;
}

function proauc_get_hdm_breadcrumb_items() {
	global $wp, $post;

	$items   = array();
	$items[] = array(
		'name' => 'Главная',
		'url'  => home_url( '/' ),
	);
	$items[] = array(
		'name' => 'Спецтехника',
		'url'  => home_url( '/spectehnika/' ),
	);

	if ( empty( $wp->query_vars['hdm-group'] ) ) {
		return $items;
	}

	$group_name = str_replace( '-', ' ', $wp->query_vars['hdm-group'] );
	if ( property_exists( $post, 'api_meta' ) && is_object( $post->api_meta ) && ! empty( $post->api_meta->name_ru ) ) {
		$group_name = $post->api_meta->name_ru;
	}
	$items[] = array(
		'name' => $group_name,
		'url'  => home_url( '/spectehnika/' . $wp->query_vars['hdm-group'] . '/' ),
	);

	if ( ! empty( $wp->query_vars['hdm-type'] ) ) {
		$type_name = str_replace( '-', ' ', $wp->query_vars['hdm-type'] );
		$items[] = array(
			'name' => $type_name,
			'url'  => proauc_get_catalog_canonical_url() ?: home_url( proauc_request_path() ),
		);
	}

	return $items;
}

/**
 * FAQ для страницы про аукционный лист (P2: рост CTR в Google).
 */
function proauc_get_auction_list_faq_schema() {
	$page_url = home_url( '/kak-chitat-aukczionnyj-list/' );

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'@id'        => $page_url . '#faq',
		'url'        => $page_url,
		'inLanguage' => 'ru-RU',
		'mainEntity' => array(
			array(
				'@type'          => 'Question',
				'name'           => 'Что такое аукционный лист на авто из Японии?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Аукционный лист — документ японского аукциона с оценкой кузова и салона, пробегом, комплектацией и замечаниями инспектора. По нему покупатель понимает реальное состояние авто до торгов.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'Как расшифровать оценку в аукционном листе?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Оценка состоит из двух частей: первая цифра — кузов (от 1 до 5), вторая — салон (от A до D). Например, 4.5B означает хороший кузов и аккуратный салон; 3.5C — заметный износ.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'Что означают обозначения A1, W3 и U3 на листе?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'A1 — небольшая царапина, A3 — крупная царапина с возможным окрасом. W3 — заметный вторичный окрас со шпаклёвкой. U3 — большая вмятина, возможна рихтовка или замена детали.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'Чем отличаются листы USS, JAA и TAA?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Это форматы разных аукционных домов Японии (USS, JAA, TAA, CAA и др.). Смысл полей одинаковый — оценка, пробег, схема кузова и комментарии, но расположение блоков на листе отличается.',
				),
			),
		),
	);
}

add_filter( 'rank_math/frontend/title', function( $title ) {
	global $post;
	global $wp;
	global $wpdb;
    global $update_seo;

	if (($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) ){

		
		$mark = '';
		$country = '';
		if (array_key_exists('country', $wp->query_vars) && isset($wp->query_vars['country'])){ 
			$country = $wp->query_vars['country']; 
		}
		if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
			$mark = str_replace('-', ' ', strtoupper($wp->query_vars['mark'])); 
			$res = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, seo_title, seo_description, seo_h1, seo_text FROM wp_api_vendors WHERE vendor_label = %s AND country = %s',
					$mark,
					$country
				)
			);
			
			if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])){ 
				$model = str_replace('-', ' ', strtoupper($wp->query_vars['model'])); 
		
				if ( ! empty( $res ) ) {
					$result = $wpdb->get_row(
						$wpdb->prepare(
							'SELECT seo_title, seo_description, seo_h1, seo_text FROM wp_api_models WHERE model_label = %s AND country = %s AND vendor_id = %d',
							$model,
							$country,
							(int) $res->id
						)
					);
				} else {
					$result = $wpdb->get_row(
						$wpdb->prepare(
							'SELECT seo_title, seo_description, seo_h1, seo_text FROM wp_api_models WHERE model_label = %s AND country = %s',
							$model,
							$country
						)
					);
				}
			} else {
				$result = $res;
			}

			if ( $result){
				$post->api_meta = $result;
				$title = $post->api_meta->seo_title;
			}
		}

		
	} else if ($post->ID == 40){
		if (array_key_exists('hdm-group', $wp->query_vars) && isset($wp->query_vars['hdm-group'])){ 
			$hdmGroup = $wp->query_vars['hdm-group']; 
			if (array_key_exists('hdm-type', $wp->query_vars) && isset($wp->query_vars['hdm-type'])){ 	
				$hdmType = $wp->query_vars['hdm-type']; 	
				$result = $wpdb->get_row(
					$wpdb->prepare( 'SELECT * FROM wp_api_hdm_types WHERE slug = %s', $hdmType )
				);
			} else {
				$result = $wpdb->get_row(
					$wpdb->prepare( 'SELECT * FROM wp_api_hdm_groups WHERE slug = %s', $hdmGroup )
				);
			}
			if ( $result){
				$post->api_meta = $result;
			}			
		}
	} else if (($post->ID == 41)||($post->ID == 43)){
		if (array_key_exists('hdm-group', $wp->query_vars) && isset($wp->query_vars['hdm-group'])){ 
			$hdmGroup = $wp->query_vars['hdm-group']; 
			if (array_key_exists('hdm-type', $wp->query_vars) && isset($wp->query_vars['hdm-type'])){ 	
				$hdmType = $wp->query_vars['hdm-type']; 	
				$result = $wpdb->get_row(
					$wpdb->prepare( 'SELECT * FROM wp_api_hdm_types WHERE slug = %s', $hdmType )
				);
			} else {
				$result = $wpdb->get_row(
					$wpdb->prepare( 'SELECT * FROM wp_api_hdm_groups WHERE slug = %s', $hdmGroup )
				);
			}
			if ( $result){
				$post->api_meta = $result;
				if ( ! empty( $post->api_meta->seo_title ) ) {
					$title = $post->api_meta->seo_title;
				}
			}			
		}		
	}

	if ( proauc_is_hdm_catalog_page() && ! proauc_api_meta_field( 'seo_title' ) ) {
		$hdm_title = proauc_build_hdm_auto_title();
		if ( $hdm_title ) {
			$title = $hdm_title;
		}
	}

    if ( proauc_is_country_catalog_mark_model() && ! proauc_api_meta_field( 'seo_title' ) ) {
        $title = proauc_build_catalog_auto_title();
    }

	$static_title = proauc_get_static_landing_meta( 'title' );
	if ( ! $static_title && is_front_page() ) {
		$static_title = proauc_get_static_landing_seo()['/']['title'] ?? '';
	}
	if ( $static_title ) {
		$title = $static_title;
	}

    if(!empty($update_seo) && !empty($update_seo['title'])) {
        $title = $update_seo['title'];
		
    }
	
	if(!empty($update_seo) && !empty($update_seo['h1']) && property_exists( $post, 'api_meta' ) && is_object( $post->api_meta )) {
        $post->api_meta->h1 = $update_seo['h1'];
    }

	return $title;
});

add_filter( 'rank_math/frontend/description', function( $description ) {
	global $post, $wp, $update_seo;
	if (($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) ){
		if (property_exists ($post, 'api_meta') )
			$description = $post->api_meta->seo_description;
	}else if (($post->ID == 40)||($post->ID == 41)||($post->ID == 43)){
		if (property_exists ($post, 'api_meta') )
			$description = $post->api_meta->seo_description;
	}

	if ( proauc_is_hdm_catalog_page() && ! proauc_api_meta_field( 'seo_description' ) ) {
		$hdm_description = proauc_build_hdm_auto_description();
		if ( $hdm_description ) {
			$description = $hdm_description;
		}
	}

    if ( empty( $wp->query_vars['mark'] ) && empty( $wp->query_vars['model'] ) && ! empty( $wp->query_vars['country'] ) && $wp->query_vars['country'] === 'china' && empty( $description ) ) {
        $description = 'Каталог автомобилей из Китая: марки, модели и актуальные предложения под заказ. Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.';
    }

    if ( proauc_is_country_catalog_mark_model() && ! proauc_api_meta_field( 'seo_description' ) ) {
        $description = proauc_build_catalog_auto_description();
    }

	$static_description = proauc_get_static_landing_meta( 'description' );
	if ( ! $static_description && is_front_page() ) {
		$static_description = proauc_get_static_landing_seo()['/']['description'] ?? '';
	}
	if ( $static_description ) {
		$description = $static_description;
	}

    if(!empty($update_seo) && !empty($update_seo['description'])) {
        $description = $update_seo['description'];
    }

	if ( $post && (int) $post->ID === 41 && empty( $wp->query_vars['hdm-group'] ) && empty( $description ) ) {
		$landing = proauc_get_static_landing_seo();
		if ( ! empty( $landing['/spectehnika/catalog/']['description'] ) ) {
			$description = $landing['/spectehnika/catalog/']['description'];
		}
	}

	return $description;
});


add_filter( 'rank_math/frontend/canonical', function( $canonical ) {
	global $post;

	if ( proauc_is_catalog_page( $post ) ) {
		$catalog_canonical = proauc_get_catalog_canonical_url();
		if ( $catalog_canonical ) {
			return $catalog_canonical;
		}
	}

	return $canonical;
}, 20 );

add_filter( 'rank_math/frontend/robots', function( $robots ) {
	if ( proauc_should_noindex_request() ) {
		$robots['index'] = 'noindex';
	}

	return $robots;
}, 99 );

add_filter( 'wp_robots', function( $robots ) {
	if ( proauc_should_noindex_request() ) {
		$robots['noindex'] = true;
		unset( $robots['index'] );
	}

	return $robots;
}, 99 );

add_filter( 'rank_math/sitemap/entry', function( $url, $type, $object ) {
	if ( 'post' !== $type || empty( $object->ID ) ) {
		return $url;
	}

	if ( in_array( (int) $object->ID, proauc_get_sitemap_excluded_page_ids(), true ) ) {
		return false;
	}

	return $url;
}, 10, 3 );

add_action( 'wp_head', function() {
	$schema = null;

	if ( proauc_is_catalog_page() ) {
		$schema = proauc_build_breadcrumb_list_schema( proauc_get_catalog_breadcrumb_items() );
	} elseif ( proauc_is_hdm_catalog_page() ) {
		$schema = proauc_build_breadcrumb_list_schema( proauc_get_hdm_breadcrumb_items() );
	}

	if ( $schema ) {
		proauc_print_json_ld( $schema );
	}
}, 96 );

add_action( 'wp_head', function() {
	if ( ! proauc_is_catalog_page() ) {
		return;
	}

	$canonical = proauc_get_catalog_canonical_url();
	if ( ! $canonical ) {
		return;
	}

	$title = wp_get_document_title();
	$description = '';

	if ( class_exists( '\RankMath\Paper\Paper' ) ) {
		$paper = \RankMath\Paper\Paper::get();
		$title = $paper->get_title() ?: $title;
		$description = $paper->get_description();
	}

	$site_name = get_bloginfo( 'name' );
	$site_url  = home_url( '/' );

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
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
				'@type'     => 'CollectionPage',
				'@id'       => $canonical . '#webpage',
				'url'       => $canonical,
				'name'      => $title,
				'isPartOf'  => array( '@id' => $site_url . '#website' ),
			),
		),
	);

	if ( $description ) {
		$schema['@graph'][2]['description'] = $description;
	}

	echo '<script type="application/ld+json">';
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
	echo "</script>\n";
}, 99 );

/**
 * Вывод JSON-LD в head.
 */
function proauc_print_json_ld( $schema ) {
	echo '<script type="application/ld+json">';
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
	echo "</script>\n";
}

/**
 * Данные лота для schema (минимальный запрос к API каталога).
 */
function proauc_get_car_lot_for_schema() {
	if ( ! get_query_var( 'car-lot' ) ) {
		return null;
	}

	$country = get_query_var( 'country' );
	if ( ! in_array( $country, array( 'korea', 'china', 'japan' ), true ) ) {
		$country = 'korea';
	}

	$lot  = get_query_var( 'car-lot' );
	$stat = get_query_var( 'stat' ) ? 1 : 0;

	if ( 'japan' === $country ) {
		$url = home_url( '/api/get-cars-japan.php?code=' . rawurlencode( $lot ) . '&stat=' . $stat );
	} else {
		$url = home_url( '/api/get-cars-' . $country . '.php?lot=' . rawurlencode( $lot ) );
	}

	$response = wp_remote_get( $url, array( 'timeout' => 20 ) );
	if ( is_wp_error( $response ) ) {
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ) );
	if ( empty( $data->autos[0] ) ) {
		return null;
	}

	return $data->autos[0];
}

function proauc_get_lot_page_url() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}
	$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	if ( empty( $path ) ) {
		return '';
	}
	return user_trailingslashit( home_url( $path ) );
}

add_action( 'wp_head', function() {
	if ( ! get_query_var( 'car-lot' ) ) {
		return;
	}

	$car = proauc_get_car_lot_for_schema();
	$page_url = proauc_get_lot_page_url();
	if ( ! $car || ! $page_url ) {
		return;
	}

	$site_url  = home_url( '/' );
	$site_name = get_bloginfo( 'name' );
	$name      = trim( $car->marka_name . ' ' . $car->model_name );
	$image     = '';

	if ( ! empty( $car->images ) ) {
		$images = is_string( $car->images ) ? explode( ';', $car->images ) : array();
		if ( ! empty( $images[0] ) ) {
			$image = trim( $images[0] );
		}
	}

	$vehicle = array(
		'@type' => 'Vehicle',
		'@id'   => $page_url . '#vehicle',
		'name'  => $name,
		'url'   => $page_url,
		'brand' => array(
			'@type' => 'Brand',
			'name'  => $car->marka_name,
		),
		'model' => $car->model_name,
	);

	if ( ! empty( $car->year ) && '0000' !== substr( (string) $car->year, 0, 4 ) ) {
		$vehicle['vehicleModelDate'] = (string) $car->year;
	}
	if ( ! empty( $car->mileage ) ) {
		$vehicle['mileageFromOdometer'] = array(
			'@type'    => 'QuantitativeValue',
			'value'    => (int) $car->mileage,
			'unitCode' => 'KMT',
		);
	}
	if ( $image ) {
		$vehicle['image'] = $image;
	}
	if ( ! empty( $car->color ) ) {
		$vehicle['color'] = $car->color;
	}

	$vehicle['offers'] = array(
		'@type'         => 'Offer',
		'url'           => $page_url,
		'availability'  => 'https://schema.org/InStock',
		'itemCondition' => 'https://schema.org/UsedCondition',
		'seller'        => array( '@id' => $site_url . '#organization' ),
	);

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type' => 'Organization',
				'@id'   => $site_url . '#organization',
				'name'  => $site_name,
				'url'   => home_url( '/kompaniya/' ),
			),
			$vehicle,
		),
	);

	proauc_print_json_ld( $schema );
}, 98 );

add_action( 'wp_head', function() {
	if ( ! is_page( 'kontaktyi' ) ) {
		return;
	}

	$site_url  = home_url( '/' );
	$site_name = get_bloginfo( 'name' );
	$page_url  = home_url( '/kontaktyi/' );
	$title     = wp_get_document_title();

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type' => 'Organization',
				'@id'   => $site_url . '#organization',
				'name'  => $site_name,
				'url'   => home_url( '/kompaniya/' ),
			),
			array(
				'@type'       => 'ContactPage',
				'@id'         => $page_url . '#webpage',
				'url'         => $page_url,
				'name'        => $title,
				'isPartOf'    => array( '@id' => $site_url . '#website' ),
				'about'       => array( '@id' => $page_url . '#localbusiness' ),
				'inLanguage'  => 'ru-RU',
			),
			array(
				'@type'           => 'LocalBusiness',
				'@id'             => $page_url . '#localbusiness',
				'name'            => $site_name,
				'url'             => $page_url,
				'telephone'       => '+7-800-201-43-40',
				'email'           => 'proauc@mail.ru',
				'image'           => home_url( '/images/logo.svg' ),
				'address'         => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => 'ул. Маковского, 95, офис 200',
					'addressLocality' => 'Владивосток',
					'addressCountry'  => 'RU',
				),
				'openingHoursSpecification' => array(
					array(
						'@type'     => 'OpeningHoursSpecification',
						'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
						'opens'     => '10:00',
						'closes'    => '18:00',
					),
					array(
						'@type'     => 'OpeningHoursSpecification',
						'dayOfWeek' => 'Saturday',
						'opens'     => '10:00',
						'closes'    => '15:00',
					),
				),
				'sameAs' => array(
					'https://t.me/proauc',
					'https://vk.com/proautospec',
					'https://www.youtube.com/@ProSpectekhnika',
				),
			),
			array(
				'@type'     => 'WebSite',
				'@id'       => $site_url . '#website',
				'url'       => $site_url,
				'name'      => $site_name,
				'publisher' => array( '@id' => $site_url . '#organization' ),
			),
		),
	);

	proauc_print_json_ld( $schema );
}, 98 );

add_action( 'wp_head', function() {
	if ( proauc_request_path() !== '/kak-chitat-aukczionnyj-list/' ) {
		return;
	}

	proauc_print_json_ld( proauc_get_auction_list_faq_schema() );
}, 97 );

/**
 * Статьи блога: дополняем BlogPosting Rank Math или добавляем в граф; BreadcrumbList без дубля.
 */
add_filter( 'rank_math/json_ld', function( $data ) {
	if ( ! is_singular( 'post' ) || ! is_array( $data ) ) {
		return $data;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return $data;
	}

	$article_key = null;

	foreach ( $data as $key => $entity ) {
		if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
			continue;
		}

		$types = (array) $entity['@type'];
		if ( in_array( 'BreadcrumbList', $types, true ) ) {
			$GLOBALS['proauc_rank_math_breadcrumbs'] = true;
			continue;
		}

		if ( ! array_intersect( $types, array( 'Article', 'BlogPosting', 'NewsArticle' ) ) ) {
			continue;
		}

		$article_key = $key;
		if ( function_exists( 'proauc_enrich_blogposting_entity' ) ) {
			proauc_enrich_blogposting_entity( $data[ $key ], $post );
		}
		$GLOBALS['proauc_rank_math_blogposting'] = true;
	}

	if ( null === $article_key && function_exists( 'proauc_build_blogposting_schema' ) ) {
		$built = proauc_build_blogposting_schema( $post, false );
		if ( $built ) {
			$data['proauc-blogposting'] = $built;
			$GLOBALS['proauc_rank_math_blogposting'] = true;
		}
	}

	if ( empty( $GLOBALS['proauc_rank_math_breadcrumbs'] ) && function_exists( 'proauc_build_breadcrumb_list_schema' ) && function_exists( 'proauc_get_blog_breadcrumb_items' ) ) {
		$bc = proauc_build_breadcrumb_list_schema( proauc_get_blog_breadcrumb_items() );
		if ( $bc ) {
			$data['proauc-breadcrumb'] = $bc;
			$GLOBALS['proauc_rank_math_breadcrumbs'] = true;
		}
	}

	return $data;
}, 25 );

/**
 * Default Open Graph / Twitter image (1200×630).
 * File: theme images/og-default.jpg (center-crop from brand blog cover).
 * Singular posts keep featured/cover image when Rank Math already has one.
 */
function proauc_get_default_og_image_url() {
	$relative = '/images/og-default.jpg';
	$path     = get_template_directory() . $relative;
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return get_template_directory_uri() . $relative;
}

add_action( 'rank_math/opengraph/facebook/add_images', function( $images ) {
	if ( ! is_object( $images ) || ! method_exists( $images, 'has_images' ) || $images->has_images() ) {
		return;
	}
	$url = proauc_get_default_og_image_url();
	if ( $url && method_exists( $images, 'add_image_by_url' ) ) {
		$images->add_image_by_url( $url );
	}
}, 20 );

add_action( 'rank_math/opengraph/twitter/add_images', function( $images ) {
	if ( ! is_object( $images ) || ! method_exists( $images, 'has_images' ) || $images->has_images() ) {
		return;
	}
	$url = proauc_get_default_og_image_url();
	if ( $url && method_exists( $images, 'add_image_by_url' ) ) {
		$images->add_image_by_url( $url );
	}
}, 20 );

add_filter( 'rank_math/opengraph/facebook/image', function( $url ) {
	return $url ? $url : proauc_get_default_og_image_url();
}, 20 );

add_filter( 'rank_math/opengraph/twitter/image', function( $url ) {
	return $url ? $url : proauc_get_default_og_image_url();
}, 20 );

add_filter( 'rank_math/settings', function( $settings ) {
	$url = proauc_get_default_og_image_url();
	if ( ! $url || ! is_array( $settings ) ) {
		return $settings;
	}
	if ( empty( $settings['titles']['open_graph_image'] ) ) {
		$settings['titles']['open_graph_image'] = $url;
	}
	return $settings;
} );

add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );

add_filter( 'rank_math/metabox/priority', function( $priority ) {
	return 'low';
});


add_filter( 'rank_math/sitemap/index', function( $xml ) {
	$date = date(DATE_ATOM,time());
	$xml .= '
		<sitemap>
			<loc>https://proauc.ru/sitemap_korea.xml</loc>
			<lastmod>'.$date.'</lastmod>
		</sitemap>
				<sitemap>
			<loc>https://proauc.ru/sitemap_japan.xml</loc>
			<lastmod>'.$date.'</lastmod>
		</sitemap>
		<sitemap>
			<loc>https://proauc.ru/sitemap_china.xml</loc>
			<lastmod>'.$date.'</lastmod>
		</sitemap>
		<sitemap>
			<loc>https://proauc.ru/sitemap_lots.xml</loc>
			<lastmod>'.$date.'</lastmod>
		</sitemap>
		<sitemap>
			<loc>https://proauc.ru/sitemap_hdm.xml</loc>
			<lastmod>'.$date.'</lastmod>
		</sitemap>';
		return $xml;
}, 11 );



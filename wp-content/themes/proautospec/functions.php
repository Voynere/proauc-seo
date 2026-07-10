<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


$picostrap_includes = array(
	'/theme-settings.php',                  // Initialize theme default settings.
	'/setup.php',                           // Theme setup and custom theme supports.
	'/widgets.php',                         // Register widget area.
	'/clean-head.php',							// Eliminates useless meta tags, emojis, etc            
	'/enqueues.php', 							// Enqueue scripts and styles.     
	'/template-tags.php',                   // Custom template tags for this theme.
	'/pagination.php',                      // Custom pagination for this theme.
	//'/hooks.php',                           // Custom hooks.
	//'/extras.php',                          // Custom functions that act independently of the theme templates.
	//'/custom-comments.php',                 // Custom Comments file.
	//'/jetpack.php',                         // Load Jetpack compatibility file.
	'/bootstrap-navwalker.php',    			// Load custom WordPress nav walker. 
	//'/woocommerce.php',                     // Load WooCommerce functions.
	'/editor.php',                          // Load Editor functions. 
	//'/customizer-assets/customizer.php',	//Defines Customizer options
	//'/picosass-compiler-integration.php',	//To interface the Customizer with the SCSS js compiler
	//'/scssphp-legacy-compiler-integration.php', //To interface the Customizer with the SCSS php compiler
	//'/options-page.php',                  // Load theme options page. 
	'/content-filtering.php',				//for LC compatibility when shutting down plugin
	'/blog-seo.php',                        // Blog bootstrap, CTA, FAQ (P3).
	'/avto-display.php',                    // Avto price / motorhome display helpers.
	'/avtodoma-filter.php',                 // /avtodoma/ mark/model/year filter.
	'/seo-settings.php',                    // OAuth Метрики, SEO-настройки админки.
    //'/windpress-support.php'                    //for deep integration with the WindPress plugin, for optional use of TailWind
);

foreach ( $picostrap_includes as $file ) {
	require_once get_template_directory() . '/inc' . $file;
}


function set_user_ip_global() {
    // Check if the REMOTE_ADDR is set

    $ipHeaders = [
        'HTTP_CLIENT_IP',
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
	$user_ip_address = '0.0.0.0';
	
    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $user_ip_address = sanitize_text_field($_SERVER[$header]);
			break;
        }
    }
	define('USER_IP', $user_ip_address);

}
add_action('init', 'set_user_ip_global');

/**
 * Slug каталога (марка/модель) пригоден для URL.
 */
function proauc_is_valid_catalog_slug( $slug ) {
	return is_string( $slug ) && $slug !== '' && mb_strpos( $slug, '%' ) === false;
}

/**
 * Количество лотов в API каталога (та же логика, что и 404 на странице).
 * При ошибке запроса возвращает -1.
 */
function proauc_catalog_api_count( $country, $mark_slug, $model_slug = null, $timeout = 20 ) {
	$endpoints = array(
		'korea' => 'get-cars-korea.php',
		'china' => 'get-cars-china.php',
		'japan' => 'get-cars-japan.php',
	);

	if ( empty( $endpoints[ $country ] ) ) {
		return 0;
	}

	$mark = str_replace( '-', ' ', $mark_slug );
	$url  = home_url( '/api/' . $endpoints[ $country ] . '?marka_name=' . rawurlencode( $mark ) );
	
	if ( null !== $model_slug ) {
		/*if(strpos($model_slug, '-series') !== false) {
			$model = $model_slug;
		} else {
			$model = str_replace( '-', ' ', $model_slug );
		}
		$url  .= '&model_name=' . rawurlencode( $model );
		*/
		$url  .= '&model_name=' . $model;
	}
	
	$response = wp_remote_get( $url, array( 'timeout' => $timeout ) );
	if ( is_wp_error( $response ) ) {
		return -1;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || ! isset( $body['count'] ) ) {
		return -1;
	}
	
	return (int) $body['count'];
}

/**
 * Есть ли в каталоге хотя бы один лот (иначе страница отдаёт 404).
 */
function proauc_catalog_has_listings( $country, $mark_slug, $model_slug = null ) {
	$count = proauc_catalog_api_count( $country, $mark_slug, $model_slug );
	if ( $count < 0 ) {
		return true;
	}
	return $count > 0;
}

/**
 * Проверка лотов для генерации sitemap (короткий timeout API + кэш в рамках одного запроса).
 */
function proauc_sitemap_has_listings( $country, $mark_slug, $model_slug = null ) {
	static $cache = array();

	$key = $country . '|' . $mark_slug . '|' . ( null === $model_slug ? '' : $model_slug );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$count = proauc_catalog_api_count( $country, $mark_slug, $model_slug, 8 );
	if ( $count < 0 ) {
		$cache[ $key ] = true;
		return true;
	}

	$cache[ $key ] = $count > 0;
	return $cache[ $key ];
}

function proauc_http_get_body( $url, $timeout = 30, $retries = 3 ) {
	$context = stream_context_create(
		array(
			'http' => array(
				'timeout' => $timeout,
			),
			'ssl'  => array(
				'verify_peer'      => false,
				'verify_peer_name' => false,
			),
		)
	);

	for ( $attempt = 0; $attempt < $retries; $attempt++ ) {
		if ( $attempt > 0 ) {
			usleep( 750000 * $attempt );
		}

		$body = @file_get_contents( $url, false, $context ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( proauc_is_valid_json_body( $body ) ) {
			return $body;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => $timeout,
				'sslverify' => false,
			)
		);
		if ( is_wp_error( $response ) ) {
			continue;
		}

		$remote_body = wp_remote_retrieve_body( $response );
		if ( proauc_is_valid_json_body( $remote_body ) ) {
			return $remote_body;
		}
	}

	return '';
}

function proauc_fetch_cars_api_page( $country, $pn, $timeout = 45 ) {
	$url = home_url( '/api/get-cars-' . $country . '.php?pn=' . (int) $pn );

	for ( $attempt = 0; $attempt < 4; $attempt++ ) {
		if ( $attempt > 0 ) {
			usleep( 750000 * $attempt );
		}

		$body = proauc_http_get_body( $url, $timeout, 1 );
		if ( ! proauc_is_valid_json_body( $body ) ) {
			continue;
		}

		$data = json_decode( $body );
		if ( ! empty( $data->autos ) && is_array( $data->autos ) ) {
			return $data;
		}

		if ( 1 === (int) $pn ) {
			break;
		}
	}

	return null;
}

function proauc_is_valid_json_body( $body ) {
	if ( ! is_string( $body ) || strlen( $body ) < 20 ) {
		return false;
	}

	$data = json_decode( $body );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return false;
	}

	return is_object( $data ) || is_array( $data );
}

function proauc_sitemap_response_start( $label ) {
	if ( php_sapi_name() !== 'cli' ) {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
		}
		echo $label . "\n";
		if ( function_exists( 'ob_flush' ) ) {
			@ob_flush();
		}
		flush();
	}
}

function proauc_sitemap_response_finish( array $lines ) {
	if ( php_sapi_name() !== 'cli' ) {
		echo "\nГотово:\n";
		foreach ( $lines as $line ) {
			echo '- ' . $line . "\n";
		}
	}
	exit;
}

function proauc_catalog_label_to_slug( $label ) {
	return str_replace( array( '&', ' ' ), '-', strtolower( $label ) );
}

function proauc_request_path() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}
	$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	return $path ? user_trailingslashit( $path ) : '';
}

function proauc_match_request_path( $uri_path ) {
	if ( empty( $uri_path ) ) {
		return false;
	}
	$uri_path = str_replace( 'https://proauc.ru', '', $uri_path );
	return proauc_request_path() === user_trailingslashit( $uri_path );
}

function proauc_build_lot_slug( $marka, $model, $grade = '', $country = 'korea' ) {
	if ( 'japan' === $country && $grade ) {
		$name = $marka . '-' . $model . '-' . $grade;
	} else {
		$name = $marka . ' ' . $model;
	}
	$name = preg_replace( '/[^a-zA-Z0-9\s-]/', '', $name );
	$name = trim( $name );
	$name = preg_replace( '/\s+/', '-', $name );
	$name = preg_replace( '/-+/', '-', $name );
	return strtolower( $name );
}

function proauc_build_lot_url( $country, $lot, $car ) {
	$slug  = proauc_build_lot_slug(
		$car->marka_name,
		$car->model_name,
		isset( $car->grade ) ? $car->grade : '',
		$country
	);
	$bases = array(
		'korea' => 'avto-iz-korei',
		'china' => 'avto-iz-kitaya',
		'japan' => 'avto-iz-yaponii',
	);
	if ( empty( $bases[ $country ] ) || empty( $lot ) || empty( $slug ) ) {
		return '';
	}
	return 'https://proauc.ru/' . $bases[ $country ] . '/' . rawurlencode( $lot ) . '-' . $slug . '/';
}

function check_maker_model_available() {
	global $wp;
	global $wp_query;

	$country = isset( $wp->query_vars['country'] ) ? $wp->query_vars['country'] : '';

	if ( ! empty( $wp->query_vars['mark'] ) && empty( $wp->query_vars['model'] ) ) {
		if ( in_array( $country, array( 'korea', 'china', 'japan' ), true )
			&& ! proauc_catalog_has_listings( $country, $wp->query_vars['mark'] ) ) {
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			get_footer();
			exit();
		}
	}

	if ( ! empty( $wp->query_vars['mark'] ) && ! empty( $wp->query_vars['model'] ) ) {
		
		if ( in_array( $country, array( 'korea', 'china', 'japan' ), true )
			&& ! proauc_catalog_has_listings( $country, $wp->query_vars['mark'], $wp->query_vars['model'] ) ) {
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			get_footer();
			exit();
		}
	}

	if ( ! empty( $wp->query_vars['model'] ) && mb_strpos( $wp->query_vars['model'], '%' ) !== false ) {
		$wp_query->set_404();
		status_header( 404 );
		get_template_part( 404 );
		get_footer();
		exit();
	}
}
add_action('wp', 'check_maker_model_available');



//PURELY OPT-IN FEATURES ////////////////

//OPTIONAL: DISABLE WORDPRESS COMMENTS
//if (get_theme_mod("singlepost_disable_comments") ) 
	
require_once locate_template('/inc/opt-in/disable-comments.php'); 

/*
//OPTIONAL: BACK TO TOP
if (get_theme_mod("enable_back_to_top") ) require_once locate_template('/inc/opt-in/back-to-top.php');

//OPTIONAL: OPEN MENU ON HOVER  
if (get_theme_mod("enable_open_menu_on_hover") ) require_once locate_template('/inc/opt-in/open-menu-on-hover.php');

//OPTIONAL: LIGHTBOX  
if (get_theme_mod("enable_lightbox") ) require_once locate_template('/inc/opt-in/lightbox.php');
	
//OPTIONAL: TOOLTIPS  
if (get_theme_mod("enable_tooltips") ) require_once locate_template('/inc/opt-in/initialize-tooltips.php');
	
//OPTIONAL: DETECT PAGE SCROLL
if (get_theme_mod("enable_detect_page_scroll") ) require_once locate_template('/inc/opt-in/detect-page-scroll.php');

//OPTIONAL: DISABLE GUTENBERG  
if (get_theme_mod("disable_gutenberg") ) require_once locate_template('/inc/opt-in/disable-gutenberg.php');
	
//OPTIONAL: DISABLE WIDGETS BLOCK EDITOR  
if (get_theme_mod("disable_widgets_block_editor") ) require_once locate_template('/inc/opt-in/disable-widgets-block-editor.php');
	
//OPTIONAL: DISABLE XML/RPC
if (get_theme_mod("disable_xml_rpc") ) require_once locate_template('/inc/opt-in/disable-xml-rpc.php');
	*/
	
add_filter( 'user_can_richedit', 'disable_for_cpt' );

function disable_for_cpt( $default ) {
    global $post;
    if ( 'page' == get_post_type( $post ) )
        return false;
    return $default;
}

/*
add_filter( 'rank_math/frontend/title', function( $title ) {
	return str_replace("&nbsp;", " ", $title);
});*/

add_filter('wpcf7_autop_or_not', '__return_false');


/*
	
add_action( 'init', function() {
   // remove_post_type_support( 'post', 'editor' );
    remove_post_type_support( 'page', 'editor' );
}, 99);	
	*/
	
	
add_filter( 'register_block_type_args', function( $args, $block_type ) { if ( $block_type !== 'rank-math/rich-snippet' ) { return $args; } $args['style_handles'] = []; return $args; }, 10, 2 ); add_action( 'rank_math/snippet/after_schema_content', function() { wp_enqueue_style( 'rank-math-review-snippet', untrailingslashit( rank_math()->plugin_url() ) . '/includes/modules/schema/blocks/schema/assets/css/schema.css', null, rank_math()->version );
} );



/*

add_action( 'wpcf7_before_send_mail', 'wpcf7_do_something_else_with_the_data', 90, 1 );
    
function wpcf7_do_something_else_with_the_data( $WPCF7_ContactForm ){

	// Submission object, that generated when the user click the submit button.
	$submission = WPCF7_Submission :: get_instance();

	if ( $submission ){
		$posted_data = $submission->get_posted_data();      
		if ( empty( $posted_data ) ){ return; }
		
		
		$message_data = $posted_data['your-phone'];

		// Do my code with this name
		//$changed_name = 'something';
		$message_data = str_replace( array( '(', ')', ' ', '-'), '', $message_data);
		//if (strpos($posted_data['contact-method'][0], 'Whats')){
		$html = "\nСсылки (на всякий случай обе):\nhttps://wa.me/".$message_data."\nhttps://t.me/+".$message_data;
		//}else{
		
		//}
		// Got e-mail text
		$mail = $WPCF7_ContactForm->prop( 'mail' );

		// Replace "[s2-name]" field inside e-mail text
		$new_mail = str_replace( '[contact-method]', "[contact-method] ".$html, $mail );

		// Set
		$WPCF7_ContactForm->set_properties( array( 'mail' => $new_mail ) );
		
		return $WPCF7_ContactForm;
	}
}

*/


//flush_rewrite_rules();


function prefix_locations_rewrite_rule() {
	add_rewrite_rule( '^avto-iz-yaponii/catalog/?$', 'index.php?page_id=45&country=japan', 'top' );
	add_rewrite_rule( '^avto-iz-yaponii/catalog/([^/]+)/?$', 'index.php?page_id=45&mark=$matches[1]&country=japan', 'top' );
	add_rewrite_rule( '^avto-iz-yaponii/catalog/([^/]+)/([^/]+)/?$', 'index.php?page_id=45&mark=$matches[1]&model=$matches[2]&country=japan', 'top' );
	

	
	
	
	
	add_rewrite_rule( '^avto-iz-yaponii/statistika/?$', 'index.php?page_id=46&country=japan', 'top' );
	add_rewrite_rule( '^avto-iz-yaponii/statistika/([^/]+)/?$', 'index.php?page_id=46&mark=$matches[1]&country=japan', 'top' );
	add_rewrite_rule( '^avto-iz-yaponii/statistika/([^/]+)/([^/]+)/?$', 'index.php?page_id=46&mark=$matches[1]&model=$matches[2]&country=japan', 'top' );
	
	
	add_rewrite_rule( '^avto-iz-korei/catalog/?$', 'index.php?page_id=48&country=korea', 'top' );
	add_rewrite_rule( '^avto-iz-korei/catalog/([^/]+)/?$', 'index.php?page_id=48&mark=$matches[1]&country=korea', 'top' );
	add_rewrite_rule( '^avto-iz-korei/catalog/([^/]+)/([^/]+)/?$', 'index.php?page_id=48&mark=$matches[1]&model=$matches[2]&country=korea', 'top' );
	
	add_rewrite_rule( '^avto-iz-kitaya/catalog/?$', 'index.php?page_id=51&country=china', 'top' );
	add_rewrite_rule( '^avto-iz-kitaya/catalog/([^/]+)/?$', 'index.php?page_id=51&mark=$matches[1]&country=china', 'top' );
	add_rewrite_rule( '^avto-iz-kitaya/catalog/([^/]+)/([^/]+)/?$', 'index.php?page_id=51&mark=$matches[1]&model=$matches[2]&country=china', 'top' );

	
    add_rewrite_rule( '^avto-iz-korei/([0-9]{8,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=korea', 'top' );
    add_rewrite_rule( '^avto-iz-kitaya/([0-9]{8,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=china', 'top' );



	add_rewrite_rule( '^avto-iz-yaponii/statistika/-/([^-]+)-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan&stat=1', 'top' );


	add_rewrite_rule( '^avto-iz-yaponii/([^_]+)_(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[2]&car-slug=$matches[1]&country=japan', 'top' );
    add_rewrite_rule( '^avto-iz-yaponii/([^-]+)-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan', 'top' );
	
	


	//add_rewrite_rule( '^spectehnika/([^_]+)_(.+)/?$', 'index.php?pagename=hdm-lot&hdm-slug=$matches[1]&hdm-lot=$matches[2]&country=japan', 'top' );	
	
	
	
    //add_rewrite_rule( '^spectehnika/([0-9]{1,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan', 'top' );
    add_rewrite_rule( '^motorcycles/([0-9]{4,12})-(.+)/?$', 'index.php?pagename=moto-lot&moto-lot=$matches[1]&moto-slug=$matches[2]&country=japan', 'top' );
	
	
	//add_rewrite_rule( '^spectehnika/([0-9]{1,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan', 'top' );
	/*
	$cats = get_categories(  array( 'parent' => 6 ) ); // Спецтехника
	foreach ($cats as $cat){
		$catSlugs[] = $cat->slug;

	}
	echo $catSlugList = implode ('|', $catSlugs);
	exit;
	*/
	
	//add_rewrite_rule
	
    add_rewrite_rule( '^spectehnika/(avtovyishka|avtodom-333|avtokran|benzovoz|betononasos|betononasosyi|betonosmesitel|bortovoj-gruzovik|bortovoj-gruzovik-s-kmu|buldozer|burilno-kranovaya|dorozhno-stroitelnaya|izotermicheskij-furgon|manipulyator|metallovoz|mini-ekskavator|samosval|sedelnyij-tyagach|universalnyij-ekskavator|furgon|furgon-babochka|furgon-refrizherator|evakuator|ekskavator|ekskavator-planirovshhik){1}/?$', 'index.php?category_name=$matches[1]', 'top' );
	
	add_rewrite_rule( '^spectehnika/(avtovyishka|avtodom-333|avtokran|benzovoz|betononasos|betononasosyi|betonosmesitel|bortovoj-gruzovik|bortovoj-gruzovik-s-kmu|buldozer|burilno-kranovaya|dorozhno-stroitelnaya|izotermicheskij-furgon|manipulyator|metallovoz|mini-ekskavator|samosval|sedelnyij-tyagach|universalnyij-ekskavator|furgon|furgon-babochka|furgon-refrizherator|evakuator|ekskavator|ekskavator-planirovshhik){1}/(.+)/?$', 'index.php?pagename=hdm-lot&hdm-cat=$matches[1]&hdm-slug=$matches[2]', 'top' );
	
	
		

	add_rewrite_rule( '^spectehnika/([^_]+)_(.+)/?$', 'index.php?pagename=hdm-lot&hdm-slug=$matches[1]&hdm-lot=$matches[2]&country=japan', 'top' );	

	add_rewrite_rule( '^spectehnika/gruzoviki/?$', 'index.php?page_id=43&hdm-group=gruzoviki', 'top' );
	add_rewrite_rule( '^spectehnika/gruzoviki/([^/]+)/?$', 'index.php?page_id=43&hdm-group=gruzoviki&mark=$matches[1]&country=japan', 'top' );
	add_rewrite_rule( '^spectehnika/gruzoviki/([^/]+)/([^/]+)/?$', 'index.php?page_id=43&hdm-group=gruzoviki&mark=$matches[1]&model=$matches[2]&country=japan', 'top' );		

	
	add_rewrite_rule( '^spectehnika/catalog/?$', 'index.php?page_id=41', 'top' );
	add_rewrite_rule( '^spectehnika/([^/]+)/?$', 'index.php?page_id=41&hdm-group=$matches[1]', 'top' );
	add_rewrite_rule( '^spectehnika/([^/]+)/([^/]+)/?$', 'index.php?page_id=41&hdm-group=$matches[1]&hdm-type=$matches[2]', 'top' );




    

}
add_action( 'init', 'prefix_locations_rewrite_rule' );


function prefix_register_query_var( $vars ) {
    $vars[] = 'hdm-group';
    $vars[] = 'hdm-type';
    $vars[] = 'mark';
    $vars[] = 'model';
    $vars[] = 'country';
	$vars[] = 'car-lot';
    $vars[] = 'car-slug';
    $vars[] = 'moto-lot';
    $vars[] = 'moto-slug';
	$vars[] = 'hdm-slug';
	$vars[] = 'hdm-lot';
	$vars[] = 'hdm-cat';
	$vars[] = 'stat';
    return $vars;
}
add_filter( 'query_vars', 'prefix_register_query_var' );

function prefix_url_rewrite_templates() {

    if ( get_query_var( 'car-lot' ) ) {
        add_filter( 'template_include', function() {
            return get_template_directory() . '/page-car-lot.php';
        });
    }else if ( get_query_var( 'moto-lot' ) ) {
        add_filter( 'template_include', function() {
            return get_template_directory() . '/page-moto-lot.php';
        });
    }else if ( get_query_var( 'hdm-lot' ) ) {
        add_filter( 'template_include', function() {
            return get_template_directory() . '/page-hdm-lot.php';
        });
    }
	
	if ( get_query_var( 'hdm-group' ) == "gruzoviki" ) {
        add_filter( 'template_include', function() {
            return get_template_directory() . '/page-gruzoviki.php';
        });
    }			
	
	if (is_page(46) || is_page(48) || is_page(51)){
		add_filter( 'template_include', function() {
            return get_template_directory() . '/page-48.php';
        });
	}
	
	
}

add_action( 'template_redirect', 'prefix_url_rewrite_templates' );

/**
 * 301 redirect legacy motorhome listing URL to /avtodoma/.
 */
function proautospec_avtodoma_legacy_redirect() {
	$path = trim( (string) parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( $path === 'avto-v-nalichii' ) {
		wp_redirect( home_url( '/avtodoma/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'proautospec_avtodoma_legacy_redirect', 0 );



add_filter('bcn_add_post_type_arg', 'my_add_post_type_arg_filt', 10, 3);
function my_add_post_type_arg_filt($add_query_arg, $type, $taxonomy)
{
	return false;
}

add_filter('bcn_breadcrumb_url', 'my_breadcrumb_url_changer', 3, 10);
function my_breadcrumb_url_changer($url, $type, $id)
{
    /*if(in_array('category', $type) && (int) $id === )
    {
        $url = get_permalink(PAGEID);
    }*/
	//var_dump($type);
	//var_dump($url);
	//var_dump($id);
    return $url;
}




add_filter( 'wpseo_canonical', '__return_false', 100 );








add_action('wp_head','preloadCarsData');

function preloadCarsData() {
	//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
	//delete_transient( 'manufacturers' );
	if ( is_page('avto-iz-korei') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
		if ( false === $manufacturersStored ) {
			parseData('korea');
		}
	}else if ( mb_strpos($_SERVER['REQUEST_URI'], 'avto-iz-korei') ){
        global $wp_filesystem;
        //delete_transient( 'manufacturers' );
        $manufacturersStored = get_transient( 'manufacturers_korea' );
        if ( false === $manufacturersStored ) {
            parseData('korea');
        }
    }else if ( is_page('avto-iz-kitaya') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
		if ( false === $manufacturersStored ) {
			parseData('china');
		}
	}else if ( mb_strpos($_SERVER['REQUEST_URI'], 'avto-iz-kitaya') ){
        global $wp_filesystem;
        //delete_transient( 'manufacturers' );
        $manufacturersStored = get_transient( 'manufacturers' );
        if ( false === $manufacturersStored ) {
            parseData('china');
        }
    }else if ( is_page('avto-iz-yaponii') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
		if ( false === $manufacturersStored ) {
			parseData('japan');
		}
	}else if ( mb_strpos($_SERVER['REQUEST_URI'], 'avto-iz-yaponii') ){
        global $wp_filesystem;
        //delete_transient( 'manufacturers' );
        $manufacturersStored = get_transient( 'manufacturers_japan' );
        if ( false === $manufacturersStored ) {
            parseData('japan');
        }
    }else if ( is_page('motorcycles') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
		if ( false === $manufacturersStored ) {
			parseData('bike');
		}
	}else if ( mb_strpos($_SERVER['REQUEST_URI'], 'motorcycles') ){
        global $wp_filesystem;
        //delete_transient( 'manufacturers' );
        $manufacturersStored = get_transient( 'manufacturers_bike' );
        if ( false === $manufacturersStored ) {
            parseData('bike');
        }
    }
	if ( is_page('spectehnika') ){
		global $wp_filesystem;
		//delete_transient( 'hdm' );
		$manufacturersStored = get_transient( 'hdm' );
		if ( false === $manufacturersStored ) {
			 parseHDM();
		}
	}
	
	
}
function updateSql(){
	global $wp_filesystem;
	global $wpdb;
	$table_name = 'wp_api_hdm_types';
	$models = json_decode( $wp_filesystem->get_contents( get_home_path().'/api/cache/translated_array.json'), JSON_UNESCAPED_UNICODE ) ;
	foreach ($models as $item){
	
		
		$result = $wpdb->update($table_name, $item, array('id' => $item['id']) );
		//var_dump($result);
		
	}

	
}

function parseHDM(){
	global $wp_filesystem;
	global $wpdb;
	$models_filename = 'hdm_type';
	if ( ! is_dir( get_home_path() . '/api/cache/' ) ) {
		wp_mkdir_p( get_home_path() . '/api/cache/' );
	}	
	$marks_filename = "hdm_type";
	try {
		$response = wp_remote_get("http://78.46.90.228/api/hdm_type");
		
		
		if (is_object($response) || wp_remote_retrieve_response_code( $response ) != 200){ throw new Exception('api error');}


		
		$wp_filesystem->put_contents( get_home_path().'/api/cache/'.$marks_filename.'.txt', $response['body']);
		$arr = preg_split('/\r\n|\r|\n/', $response['body']);
		
		set_transient( 'hdm', date("Y-m-d H:i:s"), 2 * HOUR_IN_SECONDS );
		
	} catch (Exception $e) {
		$file = $wp_filesystem->get_contents( get_home_path().'/api/cache/'.$marks_filename.'.txt');

		$arr = preg_split('/\r\n|\r|\n/',  $response['body']);
		
		//echo 'Caught exception: ',  $e->getMessage(), "\n";
	} 

	
	$newModels = array();
	
	foreach ($arr as $item){
		$data = [];
		$item = preg_replace('/$\R?^/m', '', $item);
		$item = explode(';', $item);
		$table_name = 'wp_api_hdm_types';
		
		if (array_key_exists(3, $item)){

			$slug = strtolower( preg_replace( '/-+/', '-',  preg_replace( '/\s+/', '-', trim ( preg_replace( '/[^a-zA-Z0-9\s-]/','', $item[3]) )) ) );
			
			$data = array( 
				'id' => $item[0], 
				
				'name_en' => $item[3],
				'name_ru' => $item[4],
				'slug' => $slug,
				'has_items' => 0
			);

			
			$result = $wpdb->update($table_name, $data, array('name_en' => $item[3]));
			//If nothing found to update, it will try and create the record.
			if ($result === FALSE || $result < 1) {
				$result = $wpdb->insert($table_name, $data);
			}
		
			$data['text'] = $item[3];
			$newModels[] = $data;
		}
	}	
	usort($newModels, "cmp");
	$wp_filesystem->put_contents( get_home_path().'/api/cache/'.$models_filename.'.js', 'const models_hdm = \''.json_encode($newModels, JSON_UNESCAPED_UNICODE ).'\';' );
	

	//full list of categories from limited api translated table loaded. now go to cats, which has items from api itself, and put them in table with id 1000++
	
	
	try {
		$response = wp_remote_get((empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-hdm-categories.php");
	} catch (Exception $e) {
		
	}
	
	
	
	$liveTypes = json_decode($response['body'])->cats;
	//echo 'Сейчас в базе категорий по лотам: '.count($liveTypes).'<br>';

	$i = 0; $j = 1000;
	foreach ($liveTypes as $item){
		if ($item != ''){
			$itemName = strtolower($item);

			$type = $wpdb->get_row('SELECT * FROM wp_api_hdm_types WHERE lcase(`name_en`) = "'.$itemName.'"');

			if ($type){
		
				
				$i++;
				$data = array( 
					'has_items' => 1
				);
				$result = $wpdb->update( 'wp_api_hdm_types', $data, array('id' => $type->id));	
				
			}else{
				$j++;

				$slug = strtolower( preg_replace( '/-+/', '-',  preg_replace( '/[)(\/\s+)]/', '-', trim ( preg_replace( '/[^a-zA-Z0-9\s-]/','', $item) )) ) );

				$data = array( 
					'id' => $j, 
					'has_items' => 1,
					'name_en' => $item,
					'name_ru' => $item,
					'slug' => $slug,
					'from_api' => 0
				);
					

				$result = $wpdb->update( 'wp_api_hdm_types', $data, array('name_en' => $item));	
				echo "trying update untranslated item";
				
				if ($result === FALSE || $result < 1) {
					//unset($data['id']);
					$last_id = $wpdb->get_var( "SELECT MAX(id) FROM wp_api_hdm_types " );
					$data['id'] = $last_id + 1;
					$data['group_id'] = 0;
					$result = $wpdb->insert( 'wp_api_hdm_types', $data);
		
					echo "inserting new untranslated item";
					var_dump($data);
					echo $wpdb->last_error;
					
				}
				var_dump($data);
				
			}
		}
		
	}
	
	//and now generate full filename for js api
	$newTypes = array();
	$groups =  $wpdb->get_results('SELECT * FROM wp_api_hdm_groups ORDER by name_ru');
	$types = $wpdb->get_results('SELECT * FROM wp_api_hdm_types WHERE has_items = 1 ORDER by name_ru');
	foreach ($groups as $group){
		$thisGroupTypes = array();
		foreach ($types as $type){
			//var_dump($type);
			if ($type->group_id == $group->id){
				//unset ($type->group_id);
				$tmpType = $type;
				//unset ($tmpType->group_id);
				$tmpType->text = $tmpType->name_ru;
				//TODO: make unset works. var by ref or php bug
				$thisGroupTypes[] = $tmpType;
			}
		}	
		//$thisGroupTypes = usort($thisGroupTypes, "cmp");
		
		$newTypes[] = ['id' => $group->id, 'text' => $group->name_ru, 'slug'=> $group->slug, 'types' => $thisGroupTypes];
	}
	//
	$wp_filesystem->put_contents( get_home_path().'/api/cache/hdm_new_types.js', 'const models_hdm = \''.json_encode($newTypes, JSON_UNESCAPED_UNICODE ).'\';' );
		
	
	set_transient( 'hdm', date("Y-m-d H:i:s"), 2 * HOUR_IN_SECONDS );
	//exit;
}



function parseData($country = 'korea'){
	global $wp_filesystem;
	
	if ( ! is_dir( get_home_path() . '/api/cache/' ) ) {
		wp_mkdir_p( get_home_path() . '/api/cache/' );
	}	
	
	if ($country == 'japan'){
		$marks_filename = 'MANUF_ST';
		$models_filename = 'MODEL_ST';
		$pinned = array('TOYOTA','NISSAN','MAZDA','MITSUBISHI','HONDA','SUZUKI','SUBARU','ISUZU','DAIHATSU','MITSUOKA','LEXUS');
	}else if ($country == 'korea'){
		$marks_filename = "manuf_kr";
		$models_filename = "model_kr";
		$pinned = array('HYUNDAI','KIA','SSANGYONG','DAEWOO','SAMSUNG');
	}else if ($country == 'china'){
		$marks_filename = "manuf_che";
		$models_filename = "model_che";
		$pinned = array('BYD','CHANGAN','CHERY','GEELY','HAVAL', 'VOYAH');
	}else if ($country == 'bike'){
		$marks_filename = "manuf_bike";
		$models_filename = "model_bike";
		$pinned = array();
	}/*else if ($country == 'hdm'){
		$marks_filename = "manuf_hdm";
		$models_filename = "model_hdm";
		$pinned = array();
	}*/
	
	try {
		$response = wp_remote_get("http://78.46.90.228/".$marks_filename);
		
		if (is_object($response) || wp_remote_retrieve_response_code( $response ) != 200){ throw new Exception('api error');}

		$wp_filesystem->put_contents( get_home_path().'/api/cache/'.$marks_filename.'.txt', $response['body']);
		$arr = explode(';', $response['body']);
	} catch (Exception $e) {
		$file = $wp_filesystem->get_contents( get_home_path().'/api/cache/'.$marks_filename.'.txt');

		$arr = explode(';', $file);
		
		//echo 'Caught exception: ',  $e->getMessage(), "\n";
		
	} 

	
	if ($country != 'bike'){
		$vendors = array_map(function($item) use ($pinned) {
			$tmp = explode(':', $item);
			if (in_array($tmp[1], $pinned)){ $order = array_search($tmp[1], $pinned);} else { $order = 1000;}
			return array(
				'id' => $tmp[0],
				'text' => $tmp[1],
				'order' => $order
			);
		  }, $arr);
	}else{
		$vendors = array_map(function($item) use ($pinned) {
			$tmp = explode(':', $item);
			if (in_array($tmp[1], $pinned)){ $order = array_search($tmp[1], $pinned);} else { $order = 1000;}
			return array(
				'id' => $tmp[0],
				'text' => preg_replace('/\(.*\)/', '', $tmp[1]),
				'order' => $order
			);
		  }, $arr);
	}
	//usort($vendors, "cmp");  
	usort($vendors, "cmpByOrder");
	
	global $wpdb;
	foreach ($vendors as $item){
		
		$item = (object) $item;
		$table_name = 'wp_api_vendors';
		$data = array( 
			'uid' => $item->id, 
			'vendor_label' => $item->text,
			'country' => $country,
			'sort_order' => $item->order
		);
		
		$result = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE uid = $item->id AND country = '$country' LIMIT 1");
			
		try{
			if (count($result)>0){
				$result = $wpdb->update($table_name, $data, array('uid' => $item->id, 'country' => $country));
			}else{
				$wpdb->insert($table_name, $data);
			}
			
			//If nothing found to update, it will try and create the record.
			/*echo "trying update item";
			var_dump($result);
			var_dump($wpdb->last_error);
			var_dump($wpdb->last_query);	
				*/	
			/*if ($result === FALSE || $result < 1) {
				echo "update fails, inserting";
				
				$data['uid'] = $item->id;
				$data['country'] = $country;
				$wpdb->insert($table_name, $data);
				
				var_dump($result);
				var_dump($wpdb->last_error);
				var_dump($wpdb->last_query);				
				
			}*/
		
		}catch (Exception $e){
			//var_dump($e);
		}


	}
	$wp_filesystem->put_contents( get_home_path().'/api/'.$marks_filename.'.txt', json_encode($vendors) );
	//set_transient( 'manufacturers', date("Y-m-d H:i:s"), 3600 );
	
	
	
	$api_url = "http://78.46.90.228/".$models_filename;
	try {
		$response = wp_remote_get($api_url);
		//var_dump($response);
		
		if (is_object($response) || wp_remote_retrieve_response_code( $response ) != 200){ throw new Exception('api error');}
		$wp_filesystem->put_contents( get_home_path().'/api/cache/'.$models_filename.'.txt', $response['body']);		
		$arr = explode(';', $response['body']);
	} catch (Exception $e) {
		$file = $wp_filesystem->get_contents( get_home_path().'/api/cache/'.$models_filename.'.txt');
		//var_dump($file);
		$arr = explode(';', $file);
		//echo 'Caught exception: ',  $e->getMessage(), "\n";
		
	} 		
	
	$models = array_map(function($item){
		
		$tmp = explode(':', $item);

			@$model_text = preg_replace('/ \((.+?)\)/i', '', $tmp[2]);
			return @array(
				'marka_id' => $tmp[0],
				'id' => $tmp[1],
				'text' => $model_text
			);
		
	  }, $arr);
	usort($models, "cmp");
	
	//return;
	
	foreach ($models as $item){
		
		$item = (object) $item;
		$table_name = 'wp_api_models';
		$data = array( 
			'uid' => $item->id, 
			'vendor_id' => $item->marka_id,
			'model_label' => $item->text,
		
			'country' => $country
		);
		
		$result = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE uid = $item->id AND country = '$country' LIMIT 1");
			
		try{
			if (count($result)>0){
				$result = $wpdb->update($table_name, $data, array('uid' => $item->id, 'country' => $country));
			}else{
				$wpdb->insert($table_name, $data);
			}
			
			//If nothing found to update, it will try and create the record.
			/*echo "trying update item";
			var_dump($result);
			var_dump($wpdb->last_error);
			var_dump($wpdb->last_query);	
				*/	
			/*if ($result === FALSE || $result < 1) {
				echo "update fails, inserting";
				
				$data['uid'] = $item->id;
				$data['country'] = $country;
				$wpdb->insert($table_name, $data);
				
				var_dump($result);
				var_dump($wpdb->last_error);
				var_dump($wpdb->last_query);				
				
			}*/
		
		}catch (Exception $e){
			//var_dump($e);
		}
		
		
	}
	
	
	$newModels = array();
	foreach ($vendors as $vendor){
		//echo '<br><br>'.$vendor['text'].'<br>';
		$thisVendorModels = array();
		foreach ($models as $model){
			if ($model['marka_id'] == $vendor['id']){
				unset ($model['marka_id']);
				$model['slug'] = str_replace(' ', '-', mb_strtolower($model['text']));
				$thisVendorModels[] = $model;
			}
		}
		$newModels[] = ['id' => $vendor['id'], 'text' => $vendor['text'], 'pinned' => ($vendor['order'] == 1000 ? 0 : 1), 'models' => $thisVendorModels];
		
	}
	$wp_filesystem->put_contents( get_home_path().'/api/cache/'.$models_filename.'.js', 'const models_'.$country.' = \''.json_encode($newModels).'\';' );
	set_transient( 'manufacturers_' . $country, date("Y-m-d H:i:s"), 2 * HOUR_IN_SECONDS );
	set_transient( 'manufacturers', date("Y-m-d H:i:s"), 2 * HOUR_IN_SECONDS );
}


function cmp($a, $b)
{
    return strcmp($a["text"], $b["text"]);
}
function cmpByOrder($a, $b)
{
    return ($a["order"] <=> $b["order"]);
}




function debug_404_rewrite_dump( &$wp ) {
    global $wp_rewrite;

    echo '<h2>rewrite rules</h2>';
    echo var_export( $wp_rewrite->wp_rewrite_rules(), true );

    echo '<h2>permalink structure</h2>';
    echo var_export( $wp_rewrite->permalink_structure, true );

    echo '<h2>page permastruct</h2>';
    echo var_export( $wp_rewrite->get_page_permastruct(), true );

    echo '<h2>matched rule and query</h2>';
    echo var_export( $wp->matched_rule, true );

    echo '<h2>matched query</h2>';
    echo var_export( $wp->matched_query, true );

    echo '<h2>request</h2>';
    echo var_export( $wp->request, true );

    global $wp_the_query;
    echo '<h2>the query</h2>';
    echo var_export( $wp_the_query, true );
}
if ( WP_DEBUG == true){
	add_action( 'parse_request', 'debug_404_rewrite_dump' );
}





if(function_exists('add_db_table_editor')){

  add_db_table_editor(array(
    'title'=>'HDM subtypes',
    'table'=>'hdm_subtypes',
	'id_column'=>"id"
  ));

}









function my_is_post_to_create( $continue_import, $data, $import_id )
{
    // Unless you want this code to execute for every import, check the import id
    // if ($import_id == 5) { ... }
	if ($import_id == 3) {
	var_dump($data);

	global $wpdb;
	
		
		$item = (object) $data;
		$table_name = 'wp_api_models';
		$data = array( 
			'seo_title' => $item->seo_title,
			'seo_description' => $item->seo_description,
			'seo_h1' => $item->seo_h1,
			'seo_text' => $item->seo_text
			
		);
		$result = $wpdb->update($table_name, $data, array('id' => $item->id, 'country' => $item->country));
		//If nothing found to update, it will try and create the record.
		if ($result === FALSE || $result < 1) {
			//$wpdb->insert($table_name, $data);
			//no creation yet
		}
	}
	if ($import_id == 4) {
	var_dump($data);

	global $wpdb;
	
		
		$item = (object) $data;
		$table_name = 'wp_api_vendors';
		$data = array( 
			'seo_title' => $item->seo_title,
			'seo_description' => $item->seo_description,
			'seo_h1' => $item->seo_h1,
			'seo_text' => $item->seo_text
		);
		$result = $wpdb->update($table_name, $data, array('uid' => $item->uid, 'country' => $item->country));
		//If nothing found to update, it will try and create the record.
		if ($result === FALSE || $result < 1) {
			//$wpdb->insert($table_name, $data);
			//no creation yet
		}
	}	
    return false;
}

add_filter('wp_all_import_is_post_to_create', 'my_is_post_to_create', 10, 3);




add_filter( 'pmxi_single_category', 'wpai_pmxi_single_category', 10, 2 ); 

function wpai_pmxi_single_category( $term_into, $tx_name ) {
	//var_dump($data);	
    return false;
}






function generateSitemap($urls) {
	$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset/>');
	$xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

	foreach ($urls as $url) {
		$urlTag = $xml->addChild('url');
		$urlTag->addChild('loc', $url['loc']);
		//$urlTag->addChild('lastmod', $url['lastmod']);
		$urlTag->addChild('changefreq', $url['changefreq']);
		$urlTag->addChild('priority', $url['priority']);
	}

	return $xml->asXML();
}



function generate_sitemap(){
	global $wpdb;
	global $wp_filesystem;

	@set_time_limit( 600 );
	@ini_set( 'max_execution_time', '600' );

	proauc_sitemap_response_start( 'Генерация карт сайта (марки/модели, лоты, спецтехника). Обычно 1–2 минуты…' );

	if ( empty( $wp_filesystem ) ) {
		WP_Filesystem();
	}
	generate_sitemap_lots();
	generate_sitemap_hdm();

	$countries =  [ 'china' => (object) ['slug' => "avto-iz-kitaya", 'filename' => 'sitemap_china.xml'], 
					  'korea' => (object) ['slug' => "avto-iz-korei", 'filename' => 'sitemap_korea.xml'], 
					  'japan' => (object) ['slug' => "avto-iz-yaponii", 'filename' => 'sitemap_japan.xml']
				  ];
	$siteUrl = "https://proauc.ru/";
	$urls = [];
	$date = date(DATE_ATOM,time());

	foreach ($countries as $country => $options){
		$xmlString = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$xmlString .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'."\n";		
		$vendors =  $wpdb->get_results('SELECT * FROM wp_api_vendors WHERE country = "'.$country.'" AND seo_title IS NOT NULL ORDER by country, sort_order');
		
		foreach ($vendors as $vendor){
			$vendor->slug = proauc_catalog_label_to_slug( $vendor->vendor_label );
			if ( ! proauc_is_valid_catalog_slug( $vendor->slug ) ) {
				continue;
			}
			if ( ! proauc_sitemap_has_listings( $country, $vendor->slug ) ) {
				continue;
			}

			$xmlString .= '<url>'."\n";
			$xmlString .= '<loc>'. $siteUrl. $options->slug."/catalog/".$vendor->slug.'/'.'</loc>'."\n";
			$xmlString .= '<lastmod>'.$date.'</lastmod>'."\n";
			$xmlString .= '<changefreq>daily</changefreq>'."\n";
			$xmlString .= '<priority>.9</priority>'."\n";
			$xmlString .= '</url>'."\n";
			
			$models = $wpdb->get_results('SELECT * FROM wp_api_models WHERE country = "'.$country.'" AND vendor_id = '. $vendor->id .' AND seo_title IS NOT NULL ORDER by model_label');
			foreach ($models as $model){
				$model->slug = proauc_catalog_label_to_slug( $model->model_label );
				if ( ! proauc_is_valid_catalog_slug( $model->slug ) ) {
					continue;
				}
				if ( ! proauc_sitemap_has_listings( $country, $vendor->slug, $model->slug ) ) {
					continue;
				}

				$xmlString .= '<url>'."\n";
				$xmlString .= '<loc>'. $siteUrl.$options->slug."/catalog/".$vendor->slug.'/'. $model->slug.'/</loc>'."\n";
				$xmlString .= '<lastmod>'.$date.'</lastmod>'."\n";
				$xmlString .= '<changefreq>daily</changefreq>'."\n";
				$xmlString .= '<priority>.8</priority>'."\n";
				$xmlString .= '</url>'."\n";				
				
			}
		}
		$xmlString .= '</urlset>'."\n";
		$wp_filesystem->put_contents( get_home_path().'/'.$options->filename, $xmlString);

	}

	$root = get_home_path();
	$lots_count = substr_count( @file_get_contents( $root . 'sitemap_lots.xml' ), '<loc>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$hdm_count  = substr_count( @file_get_contents( $root . 'sitemap_hdm.xml' ), '<loc>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	proauc_sitemap_response_finish(
		array(
			'sitemap_korea.xml',
			'sitemap_china.xml',
			'sitemap_japan.xml',
			'sitemap_lots.xml (' . (int) $lots_count . ' URL)',
			'sitemap_hdm.xml (' . (int) $hdm_count . ' URL)',
		)
	);
}

function generate_sitemap_lots( $max_per_country = 340 ) {
	global $wp_filesystem;

	@set_time_limit( 600 );

	if ( empty( $wp_filesystem ) ) {
		WP_Filesystem();
	}

	if ( ! empty( $_GET['sitemap-lots-create'] ) ) {
		proauc_sitemap_response_start( 'Генерация sitemap_lots.xml…' );
	}

	$site_url  = 'https://proauc.ru/';
	$date      = date( DATE_ATOM );
	$xml       = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml      .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	$page_size = 20;
	$max_pages = max( 1, (int) ceil( $max_per_country / $page_size ) );

	foreach ( array( 'korea', 'china', 'japan' ) as $country ) {
		$seen  = array();
		$added = 0;

		for ( $pn = 1; $pn <= $max_pages && $added < $max_per_country; $pn++ ) {
			if ( $pn > 1 ) {
				usleep( 400000 );
			}

			$data = proauc_fetch_cars_api_page( $country, $pn, 45 );
			if ( null === $data ) {
				break;
			}

			foreach ( $data->autos as $car ) {
				if ( empty( $car->lot ) ) {
					continue;
				}
				$lot_key = $country . ':' . $car->lot;
				if ( isset( $seen[ $lot_key ] ) ) {
					continue;
				}
				$seen[ $lot_key ] = true;

				$loc = proauc_build_lot_url( $country, $car->lot, $car );
				if ( ! $loc ) {
					continue;
				}
				$xml .= '<url>' . "\n";
				$xml .= '<loc>' . esc_url( $loc ) . '</loc>' . "\n";
				$xml .= '<lastmod>' . $date . '</lastmod>' . "\n";
				$xml .= '<changefreq>daily</changefreq>' . "\n";
				$xml .= '<priority>0.6</priority>' . "\n";
				$xml .= '</url>' . "\n";
				$added++;

				if ( $added >= $max_per_country ) {
					break;
				}
			}

			if ( count( $data->autos ) < $page_size ) {
				break;
			}
		}
	}

	$xml .= '</urlset>' . "\n";
	$wp_filesystem->put_contents( get_home_path() . '/sitemap_lots.xml', $xml );

	if ( ! empty( $_GET['sitemap-lots-create'] ) ) {
		$url_count = substr_count( $xml, '<loc>' );
		proauc_sitemap_response_finish(
			array(
				'sitemap_lots.xml (' . $url_count . ' URL)',
			)
		);
	}

	return substr_count( $xml, '<loc>' );
}

function generate_sitemap_hdm() {
	global $wpdb;
	global $wp_filesystem;

	@set_time_limit( 120 );

	if ( empty( $wp_filesystem ) ) {
		WP_Filesystem();
	}

	if ( ! empty( $_GET['sitemap-hdm-create'] ) ) {
		proauc_sitemap_response_start( 'Генерация sitemap_hdm.xml…' );
	}

	$site_url = 'https://proauc.ru/';
	$date     = date( DATE_ATOM );
	$xml      = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml     .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	$groups = $wpdb->get_results( 'SELECT id, slug FROM wp_api_hdm_groups ORDER BY name_ru' );
	foreach ( $groups as $group ) {
		if ( empty( $group->slug ) ) {
			continue;
		}
		$xml .= '<url>' . "\n";
		$xml .= '<loc>' . esc_url( $site_url . 'spectehnika/' . $group->slug . '/' ) . '</loc>' . "\n";
		$xml .= '<lastmod>' . $date . '</lastmod>' . "\n";
		$xml .= '<changefreq>daily</changefreq>' . "\n";
		$xml .= '<priority>0.8</priority>' . "\n";
		$xml .= '</url>' . "\n";

		$types = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT slug FROM wp_api_hdm_types WHERE group_id = %d AND has_items = 1 ORDER BY name_ru',
				$group->id
			)
		);
		foreach ( $types as $type ) {
			if ( empty( $type->slug ) ) {
				continue;
			}
			$xml .= '<url>' . "\n";
			$xml .= '<loc>' . esc_url( $site_url . 'spectehnika/' . $group->slug . '/' . $type->slug . '/' ) . '</loc>' . "\n";
			$xml .= '<lastmod>' . $date . '</lastmod>' . "\n";
			$xml .= '<changefreq>daily</changefreq>' . "\n";
			$xml .= '<priority>0.7</priority>' . "\n";
			$xml .= '</url>' . "\n";
		}
	}

	$xml .= '</urlset>' . "\n";
	$wp_filesystem->put_contents( get_home_path() . '/sitemap_hdm.xml', $xml );

	if ( ! empty( $_GET['sitemap-hdm-create'] ) ) {
		$url_count = substr_count( $xml, '<loc>' );
		proauc_sitemap_response_finish(
			array(
				'sitemap_hdm.xml (' . $url_count . ' URL)',
			)
		);
	}
}

add_action(
	'init',
	function () {
		if ( ! empty( $_GET['sitemap-create'] ) ) {
			generate_sitemap();
		}
		if ( ! empty( $_GET['sitemap-lots-create'] ) ) {
			generate_sitemap_lots();
			exit;
		}
		if ( ! empty( $_GET['sitemap-hdm-create'] ) ) {
			generate_sitemap_hdm();
			exit;
		}
	},
	1
);

function get_ip_address(){
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip); // just to be safe

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ip;
                }
            }
        }
    }
}

$update_seo = [];
if(file_exists(get_template_directory() . "/seo.csv")) {
    $csvFile = file(get_template_directory() . "/seo.csv");
    $data = [];
    foreach ($csvFile as $line) {
        $data = str_getcsv($line);
        if(!empty($data[0])) {
            $data[0] = str_replace("https://proauc.ru", "", $data[0]);
            if ( proauc_match_request_path( $data[0] ) ) {
                $update_seo['title'] = $data[1];
                $update_seo['description'] = $data[2];
                $update_seo['h1'] = $data[3];
            }
        }
    }
}
if(file_exists(get_template_directory() . "/spec.csv")) {
    $csvFile = file(get_template_directory() . "/spec.csv");
    $data = [];
    foreach ($csvFile as $line) {
        $data = explode(";", $line);
        if(!empty($data[0])) {
            $data[0] = str_replace("https://proauc.ru", "", $data[0]);
            if ( proauc_match_request_path( $data[0] ) ) {
                $update_seo['title'] = $data[2];
                $update_seo['description'] = $data[3];
                $update_seo['h1'] = $data[1];
            }
        }
    }
}
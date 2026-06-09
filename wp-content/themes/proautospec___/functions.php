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
    //'/windpress-support.php'                    //for deep integration with the WindPress plugin, for optional use of TailWind
);

foreach ( $picostrap_includes as $file ) {
	require_once get_template_directory() . '/inc' . $file;
}

//PURELY OPT-IN FEATURES ////////////////

//OPTIONAL: DISABLE WORDPRESS COMMENTS
if (get_theme_mod("singlepost_disable_comments") ) require_once locate_template('/inc/opt-in/disable-comments.php'); 

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


flush_rewrite_rules();


function prefix_locations_rewrite_rule() {
	add_rewrite_rule( '^avto-iz-yaponii/catalog/(.+)/?$', 'index.php?page_id=46&mark=$matches[1]&country=japan', 'top' );
	add_rewrite_rule( '^avto-iz-yaponii/catalog/(.+)/(.+)/?$', 'index.php?page_id=46&mark=$matches[1]&model=$matches[2]&country=japan', 'top' );
	
	add_rewrite_rule( '^avto-iz-korei/catalog/(.+)/?$', 'index.php?page_id=48&mark=$matches[1]&country=korea', 'top' );
		add_rewrite_rule( '^avto-iz-korei/catalog/(.+)/(.+)/?$', 'index.php?page_id=46&mark=$matches[1]&model=$matches[2]&country=korea', 'top' );
	
	add_rewrite_rule( '^avto-iz-kitaya/catalog/(.+)/?$', 'index.php?page_id=51&mark=$matches[1]&country=china', 'top' );

	
    add_rewrite_rule( '^avto-iz-korei/([0-9]{8,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=korea', 'top' );
    add_rewrite_rule( '^avto-iz-kitaya/([0-9]{8,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=china', 'top' );
    add_rewrite_rule( '^avto-iz-yaponii/statistika/([^-]+)-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan&stats=1', 'top' );
    add_rewrite_rule( '^avto-iz-yaponii/([^-]+)-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan', 'top' );
    add_rewrite_rule( '^spectehnika/([0-9]{1,9})-(.+)/?$', 'index.php?pagename=car-lot&car-lot=$matches[1]&car-slug=$matches[2]&country=japan', 'top' );
    add_rewrite_rule( '^motorcycles/([0-9]{4,12})-(.+)/?$', 'index.php?pagename=moto-lot&moto-lot=$matches[1]&moto-slug=$matches[2]&country=japan', 'top' );
	/*
	$cats = get_categories(  array( 'parent' => 6 ) ); // Спецтехника
	foreach ($cats as $cat){
		$catSlugs[] = $cat->slug;

	}
	echo $catSlugList = implode ('|', $catSlugs);
	exit;
	*/
    add_rewrite_rule( '^spectehnika/(avtovyishka|avtodom-333|avtokran|benzovoz|betononasos|betononasosyi|betonosmesitel|bortovoj-gruzovik|bortovoj-gruzovik-s-kmu|buldozer|burilno-kranovaya|dorozhno-stroitelnaya|izotermicheskij-furgon|manipulyator|metallovoz|mini-ekskavator|samosval|sedelnyij-tyagach|universalnyij-ekskavator|furgon|furgon-babochka|furgon-refrizherator|evakuator|ekskavator|ekskavator-planirovshhik){1}/?$', 'index.php?category_name=$matches[1]', 'top' );
	add_rewrite_rule( '^spectehnika/(avtovyishka|avtodom-333|avtokran|benzovoz|betononasos|betononasosyi|betonosmesitel|bortovoj-gruzovik|bortovoj-gruzovik-s-kmu|buldozer|burilno-kranovaya|dorozhno-stroitelnaya|izotermicheskij-furgon|manipulyator|metallovoz|mini-ekskavator|samosval|sedelnyij-tyagach|universalnyij-ekskavator|furgon|furgon-babochka|furgon-refrizherator|evakuator|ekskavator|ekskavator-planirovshhik){1}/(.+)/?$', 'index.php?pagename=hdm-lot&hdm-cat=$matches[1]&hdm-slug=$matches[2]', 'top' );
    add_rewrite_rule( '^spectehnika/([^-]+)-(.+)/?$', 'index.php?pagename=hdm-lot&hdm-lot=$matches[1]&hdm-slug=$matches[2]&country=japan', 'top' );

}
add_action( 'init', 'prefix_locations_rewrite_rule' );


function prefix_register_query_var( $vars ) {
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
	$vars[] = 'stats';
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
}

add_action( 'template_redirect', 'prefix_url_rewrite_templates' );



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
	
	if ( is_page('avto-iz-korei') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
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
	}else if ( is_page('avto-iz-yaponii') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
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
	}
	/*
	else if ( is_page('spectehnika') ){
		global $wp_filesystem;
		//delete_transient( 'manufacturers' );
		$manufacturersStored = get_transient( 'manufacturers' );
		if ( false === $manufacturersStored ) {
			parseData('hdm');
		}
	}
	*/
	
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
		$pinned = array('BYD','CHANGAN','CHERY','GEELY','HAVAL');
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

	
	$vendors = array_map(function($item) use ($pinned) {
		$tmp = explode(':', $item);
		if (in_array($tmp[1], $pinned)){ $order = array_search($tmp[1], $pinned);} else { $order = 1000;}
		return array(
			'id' => $tmp[0],
			'text' => $tmp[1],
			'order' => $order
		);
	  }, $arr);
	//usort($vendors, "cmp");  
	usort($vendors, "cmpByOrder");

	//$wp_filesystem->put_contents( get_home_path().'/api/MANUF_ST.js', json_encode($vendors) );
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
	
	$newModels = array();
	foreach ($vendors as $vendor){
		//echo '<br><br>'.$vendor['text'].'<br>';
		$thisVendorModels = array();
		foreach ($models as $model){
			if ($model['marka_id'] == $vendor['id']){
				unset ($model['marka_id']);
				$thisVendorModels[] = $model;
			}
		}
		$newModels[] = ['id' => $vendor['id'], 'text' => $vendor['text'], 'pinned' => ($vendor['order'] == 1000 ? 0 : 1), 'models' => $thisVendorModels];
	}
	$wp_filesystem->put_contents( get_home_path().'/api/cache/'.$models_filename.'.js', 'const models_'.$country.' = \''.json_encode($newModels).'\';' );
	//set_transient( 'manufacturers', date("Y-m-d H:i:s"), 2 * HOUR_IN_SECONDS );
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
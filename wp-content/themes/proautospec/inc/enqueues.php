<?php
/**
 * Enqueue the CSS and JS files
 *
 * @package picostrap5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/*
add_action( 'all', function ( $tag ) {
    static $hooks = array();
    // Only do_action / do_action_ref_array hooks.
    if ( did_action( $tag ) ) {
        $hooks[] = $tag;
    }
    if ( 'shutdown' === $tag ) {
        print_r( $hooks );
    }
} );
*/



 
 
//SUPPORT FUNCTIONS FOR DETERMINING THE RIGHT CSS BUNDLE FILENAME AND LOCATION
function picostrap_get_css_url (){
    //onboarding: if no CSS custom bundle was created, serve the default one
    if (get_theme_mod("css_bundle_version_number", 0) == 0) return get_stylesheet_directory_uri() . '/'. picostrap_get_css_optional_subfolder_name() . picostrap_get_base_css_filename(); 

    //standard case
    return get_stylesheet_directory_uri() . '/' . picostrap_get_css_optional_subfolder_name() . picostrap_get_complete_css_filename(); 

}

if (!function_exists('picostrap_get_css_optional_subfolder_name')):
    function picostrap_get_css_optional_subfolder_name() { return "css-output/"; }
endif;

if (!function_exists('picostrap_get_base_css_filename')):
    function picostrap_get_base_css_filename() { return "bundle.css"; }
endif;

if (!function_exists('picostrap_get_complete_css_filename')):
    function picostrap_get_complete_css_filename() { 
        $filename = picostrap_get_base_css_filename();
        if (is_multisite()) $filename = str_replace('.', '-' . get_current_blog_id() . '.', $filename );
        return $filename;
    }
endif;

//HELPER FUNCTION TO GET CSS BUNDLE VERSION
function picostrap_get_css_version(){ 
    return(get_theme_mod ('css_bundle_version_number'));
}


remove_action( 'wp_head', 'wp_print_auto_sizes_contain_css_fix', 1 );

//ADD THE MAIN CSS FILE
add_action( 'wp_enqueue_scripts',  function  () {

    //ENQUEUE THE CSS FILE
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-blocks-style' ); // Remove WooCommerce block CSS
    //wp_enqueue_style( 'picostrap-styles', picostrap_get_css_url() . '#handlecsserror', array(), picostrap_get_css_version()); 
    wp_enqueue_style( 'fonts', get_stylesheet_directory_uri().'/css/fonts.css', array(), null);
    wp_enqueue_style( 'bs', get_stylesheet_directory_uri().'/css/bootstrap.min.css', array(), null);
    wp_enqueue_style( 'swiper', get_stylesheet_directory_uri().'/js/swiper/swiper-bundle.min.css', array(), null);
    wp_enqueue_style( 'select2', get_stylesheet_directory_uri().'/js/select2/css/select2.min.css', array(), null);
    wp_enqueue_style( 'select2-dark', get_stylesheet_directory_uri().'/js/select2/css/select2-bootstrap5-dark.min.css', array(), null);
    wp_enqueue_style( 'fancybox', get_stylesheet_directory_uri().'/js/fancybox/fancybox.min.css', array(), null);
    wp_enqueue_style( 'lg', get_stylesheet_directory_uri().'/js/lightgallery/lightgallery.min.css', array(), null);
    wp_enqueue_style( 'app', get_stylesheet_directory_uri().'/css/app.css', array());   
});

///ADD THE MAIN JS FILES
//enqueue js in footer, async
add_action( 'wp_enqueue_scripts', function() {

    //MAIN BOOTSTRAP JS
    //want to override file in child theme? use get_stylesheet_directory_uri in place of get_template_directory_uri 
    //this was done for compatibility reasons towards older child themes
	wp_enqueue_script( 'jquery', get_template_directory_uri() . "/js/jquery.min.js", array(), null, array( 'in_footer' => true) );
    wp_enqueue_script( 'bootstrap5', get_template_directory_uri() . "/js/bootstrap.bundle.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    wp_enqueue_script( 'popper', get_template_directory_uri() . "/js/popper.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    wp_enqueue_script( 'phone-mask', get_template_directory_uri() . "/js/jquery.mask.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    
 
    wp_enqueue_script( 'lg', get_template_directory_uri() . "/js/lightgallery/lightgallery-all.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    wp_enqueue_script( 'select2', get_template_directory_uri() . "/js/select2/js/select2.full.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    wp_enqueue_script( 'swiper', get_template_directory_uri() . "/js/swiper/swiper-bundle.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    wp_enqueue_script( 'fancybox', get_template_directory_uri() . "/js/fancybox/fancybox.umd.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    wp_enqueue_script( 'hover-carousel', get_template_directory_uri() . "/js/jquery.hover-carousel.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );


    wp_enqueue_script( 'bootstrap5', get_template_directory_uri() . "/js/bootstrap.bundle.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
	
	wp_enqueue_script( 'js-router', get_template_directory_uri() . "/js/pathparser.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
    
	
	
	
	
	wp_enqueue_script( 'onload-all', get_template_directory_uri() . "/js/onload.all.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );
	
	if (is_page('kontaktyi')){
		wp_enqueue_script( "yamaps-dist", "https://api-maps.yandex.ru/2.1/?apikey=44798667-4902-42bf-990a-7fc7a39f8685&lang=ru_RU", array(), null, array( 'in_footer' => true) );
		wp_enqueue_script( "yamaps-init", get_template_directory_uri() . "/js/onload.about.js", array(), null, array( 'in_footer' => true) );
		
	}else if (is_single()){
		if ( get_post_type( get_the_ID() ) == 'reviews' ) {
			wp_enqueue_script( "onload-single", get_template_directory_uri() . "/js/onload.single-reviews.js", array(), null, array( 'in_footer' => true) );//if is true
		}else if ( get_post_type( get_the_ID() ) == 'avto' ) {
			wp_enqueue_script( "imagesloaded", get_template_directory_uri() . "/js/imagesloaded.pkgd.min.js", array(), null, array( 'in_footer' => true) );
			wp_enqueue_script( "onload-single", get_template_directory_uri() . "/js/onload.single-auto.js", array(), null, array( 'in_footer' => true) );
			
			$id = get_the_ID();
			$oldSlug = get_post_meta($id, 'old-slug')[0];
			if ($oldSlug){
				wp_redirect('/'.$oldSlug.'/',301);
			}
			
			//exit;
			
		}
	}else if (is_page('car-lot') || is_page('moto-lot') || is_page('hdm-lot')){
		wp_enqueue_script( "imagesloaded", get_template_directory_uri() . "/js/imagesloaded.pkgd.min.js", array(), null, array( 'in_footer' => true) );
		wp_enqueue_script( "onload-single", get_template_directory_uri() . "/js/onload.single-auto.js", array(), null, array( 'in_footer' => true) );
	}else if (is_post_type_archive('reviews')){
		wp_enqueue_script( "onload-reviews", get_template_directory_uri() . "/js/onload.reviews.js", array(), null, array( 'in_footer' => true) );
	}else if ( is_page( 'avtodoma' ) ) {
		wp_enqueue_script( 'onload-avtodoma', get_template_directory_uri() . '/js/onload.avtodoma.js', array(), null, array( 'in_footer' => true ) );
	}
	
	if (is_page('car-lot') || is_page('moto-lot') || is_page('hdm-lot')){
		remove_action('wp_head', 'rel_canonical');
	}
	
	
	
	
} ,100);


add_filter( 'wp_default_scripts', 'remove_jquery_migrate' );
function remove_jquery_migrate( $scripts ) {
	if ( empty( $scripts->registered['jquery'] ) || is_admin() ) {
		return;
	}
	$deps = & $scripts->registered['jquery']->deps;
	$deps = array_diff( $deps, [ 'jquery-migrate' ] );
}

//ADD THE CUSTOM HEADER CODE (SET IN CUSTOMIZER)
//add_action( 'wp_head', 'picostrap_add_header_code' );
function picostrap_add_header_code() {
    if (!get_theme_mod("picostrap_fonts_header_code_disable")) {
        echo  get_theme_mod("picostrap_fonts_header_code")." ";
    }
    echo get_theme_mod("picostrap_header_code");
}

//ADD THE CUSTOM FOOTER CODE (SET IN CUSTOMIZER)
//add_action( 'wp_footer', 'picostrap_add_footer_code' );
function picostrap_add_footer_code() {
	  //if (!current_user_can('administrator'))
      echo get_theme_mod("picostrap_footer_code");
}

//ADD THE CUSTOM CHROME COLOR TAG (SET IN CUSTOMIZER)
//add_action( 'wp_head', 'picostrap_add_header_chrome_color' );
function picostrap_add_header_chrome_color() {
	 if (get_theme_mod('picostrap_header_chrome_color')!=""):
        ?><meta name="theme-color" content="<?php echo get_theme_mod('picostrap_header_chrome_color'); ?>" />
	<?php endif;
}

//CSS error handling ENQUEUE: if CSS bundle file is not found, trigger recompile
function picostrap_add_css_error_handling($url){
    if ( strpos( $url, '#handlecsserror') === false )
        return $url;
    else if ( !current_user_can('administrator') or isset($_GET['compile_sass']))
        return str_replace( '#handlecsserror', '', $url );
    else
	return str_replace( '#handlecsserror', '', $url )."' onerror='alert(\"CSS bundle not found. Rebuilding.\");location.href=\"?compile_sass=1&sass_nocache=1\""; 
    }
//add_filter( 'clean_url', 'picostrap_add_css_error_handling', 11, 1 );

//UNRENDER-BLOCK CSS 
// as per https://www.phpied.com/faster-wordpress-rendering-with-3-lines-of-configuration/

function picostrap_get_headers(){
    //add link to preload CSS bundle
    $headers = "link: <".picostrap_get_css_url()."?ver=".picostrap_get_css_version().">; rel=preload; as=style";
    //if relevant, add the CSS for Gutenberg blocks
    if (!get_theme_mod("disable_gutenberg") OR
        ( function_exists('lc_plugin_option_is_set') && lc_plugin_option_is_set('gtblocks') )
        ) $headers.=", <".includes_url()."css/dist/block-library/style.min.css?ver=".get_bloginfo( 'version' ).">; rel=preload; as=style";
    return $headers;
}

if(!function_exists('picostrap_hints')):
    function picostrap_hints() {  
        header(picostrap_get_headers());
    }
endif;

if  (!get_theme_mod("disable_bootstrap")) {
    //add_action('send_headers', 'picostrap_hints'); 
}




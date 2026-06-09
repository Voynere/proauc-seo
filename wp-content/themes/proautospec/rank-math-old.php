<?php

add_filter( 'rank_math/frontend/title', function( $title ) {
	global $post;
	global $wp;
	global $wpdb;

	if (($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) ){

		
		$mark = '';
		if (array_key_exists('country', $wp->query_vars) && isset($wp->query_vars['country'])){ 
			$country = $wp->query_vars['country']; 
		}
		if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
			$mark = str_replace('-', ' ', strtoupper($wp->query_vars['mark'])); 
			$query =  "SELECT seo_title, seo_description, seo_h1, seo_text FROM wp_api_vendors WHERE vendor_label = '$mark' AND country = '$country'" ;
			
			if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])){ 
				$model = str_replace('-', ' ', strtoupper($wp->query_vars['model'])); 
		
				$query = "SELECT seo_title, seo_description, seo_h1, seo_text FROM wp_api_models WHERE model_label = '$model' AND country = '$country'";
			}
			
			$result = $wpdb->get_row( $query );

			if ( $result){
				$post->api_meta = $result;
				$title = $post->api_meta->seo_title;
			}
		}

		
	} else if ($post->ID == 40){
		if (array_key_exists('hdm-group', $wp->query_vars) && isset($wp->query_vars['hdm-group'])){ 
			$hdmGroup = $wp->query_vars['hdm-group']; 
			$query =  "SELECT * FROM wp_api_hdm_groups WHERE slug = '$hdmGroup'" ;
			if (array_key_exists('hdm-type', $wp->query_vars) && isset($wp->query_vars['hdm-type'])){ 	
				$hdmType = $wp->query_vars['hdm-type']; 	
				$query =  "SELECT * FROM wp_api_hdm_types WHERE slug = '$hdmType'" ;
			}
			$result = $wpdb->get_row( $query );
			if ( $result){
				$post->api_meta = $result;
			}			
		}
	} else if (($post->ID == 41)||($post->ID == 43)){
		if (array_key_exists('hdm-group', $wp->query_vars) && isset($wp->query_vars['hdm-group'])){ 
			$hdmGroup = $wp->query_vars['hdm-group']; 
			$query =  "SELECT * FROM wp_api_hdm_groups WHERE slug = '$hdmGroup'" ;
			if (array_key_exists('hdm-type', $wp->query_vars) && isset($wp->query_vars['hdm-type'])){ 	
				$hdmType = $wp->query_vars['hdm-type']; 	
				$query =  "SELECT * FROM wp_api_hdm_types WHERE slug = '$hdmType'" ;
			}
			$result = $wpdb->get_row( $query );
			if ( $result){
				$post->api_meta = $result;
				$title = $post->api_meta->seo_title;
			}			
		}		
	}
	return $title;
});

add_filter( 'rank_math/frontend/description', function( $description ) {
	global $post;
	if (($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) ){
		if (property_exists ($post, 'api_meta') )
			$description = $post->api_meta->seo_description;
	}else if (($post->ID == 41)||($post->ID == 43)){
		if (property_exists ($post, 'api_meta') )
			$description = $post->api_meta->seo_description;
	}
	return $description;
});


add_filter( 'rank_math/frontend/canonical', function( $canonical ) {
	global $post;
	
	if ($post){
		if (($post->ID == 41) || ($post->ID == 43) || ($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) || ($post->ID == 70) ){

			return false;
		
		}		
	}
	
	return $canonical;
});

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
		</sitemap>';
		return $xml;
}, 11 );



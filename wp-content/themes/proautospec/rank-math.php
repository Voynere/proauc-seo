<?php

add_filter( 'rank_math/frontend/title', function( $title ) {
	global $post;
	global $wp;
	global $wpdb;
    global $update_seo;

	if (($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) ){

		
		$mark = '';
		if (array_key_exists('country', $wp->query_vars) && isset($wp->query_vars['country'])){ 
			$country = $wp->query_vars['country']; 
		}
		if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
			$mark = str_replace('-', ' ', strtoupper($wp->query_vars['mark'])); 
			$query =  "SELECT id, seo_title, seo_description, seo_h1, seo_text FROM wp_api_vendors WHERE vendor_label = '$mark' AND country = '$country'" ;
			
			$res = $wpdb->get_row( $query );
			
			if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])){ 
				$model = str_replace('-', ' ', strtoupper($wp->query_vars['model'])); 
		
				$query = "SELECT seo_title, seo_description, seo_h1, seo_text FROM wp_api_models WHERE model_label = '$model' AND country = '$country'";
				if(!empty($res)) {
					$query .= " AND vendor_id = '".$res->id."'";
				}
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

    if((!empty($wp->query_vars['mark']) || !empty($wp->query_vars['model'])) && (!empty($wp->query_vars['country']) && in_array($wp->query_vars['country'], ['china', 'korea', 'japan'])) ) {
        $auto_name = str_replace('-', ' ', strtoupper($wp->query_vars['mark']) );
        if(!empty($wp->query_vars['model'])) {
            $auto_name .= " " . str_replace('-', ' ', strtoupper($wp->query_vars['model']) );
        }

        $title = $auto_name;
        if($wp->query_vars['country'] == "china") {
            $title .= " из Китая";
        }
        if($wp->query_vars['country'] == "japan") {
            $title .= " из Японии";
        }
        if($wp->query_vars['country'] == "korea") {
            $title .= " из Кореи";
        }

        $title .= " — купить " . $auto_name . " с пробегом";
    }

    if(!empty($update_seo) && !empty($update_seo['title'])) {
        $title = $update_seo['title'];
		
    }
	
	if(!empty($update_seo) && !empty($update_seo['h1'])) {
        $post->api_meta->h1 = $update_seo['h1'];
    }

	return $title;
});

add_filter( 'rank_math/frontend/description', function( $description ) {
	global $post, $wp, $update_seo;
	if (($post->ID == 45) || ($post->ID == 46) || ($post->ID == 48) || ($post->ID == 51) ){
		if (property_exists ($post, 'api_meta') )
			$description = $post->api_meta->seo_description;
	}else if (($post->ID == 41)||($post->ID == 43)){
		if (property_exists ($post, 'api_meta') )
			$description = $post->api_meta->seo_description;
	}

    if((!empty($wp->query_vars['mark']) || !empty($wp->query_vars['model'])) && (!empty($wp->query_vars['country']) && in_array($wp->query_vars['country'], ['china', 'korea', 'japan'])) ) {
        $auto_name = str_replace('-', ' ', strtoupper($wp->query_vars['mark']) );
        if(!empty($wp->query_vars['model'])) {
            $auto_name .= " " . str_replace('-', ' ', strtoupper($wp->query_vars['model']) );
        }

        $description = "Привозим " . $auto_name . " напрямую";
        if($wp->query_vars['country'] == "china") {
            $description .= " из Китая";
        }
        if($wp->query_vars['country'] == "japan") {
            $description .= " из Японии";
        }
        if($wp->query_vars['country'] == "korea") {
            $description .= " из Кореи";
        }

        $description .= " под заказ! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.";
    }

    if(!empty($update_seo) && !empty($update_seo['description'])) {
        $description = $update_seo['description'];
    }
	
	if($_SERVER['REQUEST_URI'] == "/motorcycles/") {
		$description = "Мотоциклы с аукционов Японии под заказ с доставкой! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/avto-iz-kitaya/") {
		$description = "Автомобили из Китая напрямую под заказ с доставкой! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/avto-iz-korei/") {
		$description = "Автомобили из Кореи напрямую под заказ с доставкой! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/avto-iz-yaponii/") {
		$description = "Автомобили из Японии напрямую под заказ с доставкой! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/avto-iz-yaponii/statistika/") {
		$description = "Статистика аукционов Японии: цены, пробеги и популярные модели! Проверенная история, выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/avto-v-nalichii/") {
		$description = "Автомобили и спецтехника в наличии с проверенной историей и готовностью к покупке! Выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/kompaniya/") {
		$description = "О компании Proauc и поставке автомобилей из Японии, Кореи и Китая! Проверенная репутация, выгодные условия и полное сопровождение на всех этапах сделки.";
	}
	if($_SERVER['REQUEST_URI'] == "/kontaktyi/") {
		$description = "Контакты Proauc для связи и консультации по подбору авто! Быстрый ответ, выгодные условия и сопровождение на всех этапах сделки.";
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



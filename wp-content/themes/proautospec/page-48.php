<?php
// korea
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $wp;
global $wpdb;
global $post;

get_header();
?>


<?php

$country = "korea";
$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-cars-korea.php?client_ip=".USER_IP;
$h1 = 'Каталог автомобилей из Кореи под заказ';
if (array_key_exists('country', $wp->query_vars) && isset($wp->query_vars['country'])){ 
	
	$country = $wp->query_vars['country']; 
	$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-cars-".$country.".php?client_ip=".USER_IP;
	$h1 = 'Каталог автомобилей '.$options->{$country}->labelFrom.' под заказ';
}
$options = $options->{$country};
$options->country = $country;

if (property_exists ($post, 'api_meta') ){
	$post->api_meta->isModel = false;
	$post->api_meta->isVendor = false;
}else{
	$post->api_meta = new StdClass();
}

if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
	$mark = str_replace('-', '+', strtoupper($wp->query_vars['mark'])); 
	$carsApiUrl = $carsApiUrl.'&marka_name='.$mark;
	
	$post->api_meta->isVendor = true;
	$post->api_meta->vendorLabel = str_replace('-', ' ', strtoupper($wp->query_vars['mark']) );
	if (property_exists ($post, 'api_meta') ){
		$h1 = $post->api_meta->seo_h1;
	}

    $auto_name = $post->api_meta->vendorLabel;

    if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])) {
		if(strpos(strtoupper($wp->query_vars['model']), '-SERIES') !== false) {
			$model = strtoupper($wp->query_vars['model']);
		} else {
			$model = str_replace('-', '+', strtoupper($wp->query_vars['model'])); 
		}
		
		$carsApiUrl = $carsApiUrl.'&model_name='.$model;
		if (property_exists ($post, 'api_meta') ){
			$h1 = $post->api_meta->seo_h1;
			$post->api_meta->isModel = true;
			$post->api_meta->modelLabel = str_replace('-', ' ', strtoupper($wp->query_vars['model']) );

            $auto_name .= " " . $post->api_meta->modelLabel;
		}
	}
	
	if ($post->ID == 46){
		$carsApiUrl = $carsApiUrl.'&stat=1';
		$h1 = 'Статистика (средние цены) на '.$h1;
	}

    $h1 = $auto_name;

    if($country == "china") {
        $h1 .= " из Китая";
    }
    if($country == "korea") {
        $h1 .= " из Кореи";
    }
    if($country == "japan") {
        $h1 .= " из Японии";
    }

}else{
	if ($post->ID == 46){
		$carsApiUrl = $carsApiUrl.'?stat=1';
		$h1 = 'Статистика (средние цены) продаж автомобилей из Японии';
	}
}


if(!empty($update_seo) && !empty($update_seo['h1'])) {
    $h1 = $update_seo['h1'];
}

$cars = json_decode(file_get_contents( $carsApiUrl ) );
	//var_dump($carsApiUrl);
	//var_dump($cars);
	//exit;
?>

<section class="pb-0">
<div class="container">
	<h1 class="mb-4"><?php echo $h1;?></h1>
	<div class="b-cars-catalog-filter b-cars-catalog-filter--goto" data-test="1">
		<form method="get">
			<input type="hidden" name="pn" value="1">
			<?php
			get_template_part( 'template-parts/catalog-form-filter' );
			?>			
		</form>
	</div>
</div>
</section>
<?php

$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/'.$options->jsModelsFile);

echo "<script>var course = ".$options->course.";</script>";
echo "<script>".$models."</script>";
?>
<?php 

?>



<script src="/wp-content/themes/proautospec/js/pagination/pagination.js"></script>

<link href="/wp-content/themes/proautospec/js/pagination/pagination.css" rel="stylesheet">
<script>

</script>
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script>
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-<?php echo $options->country;?>.js"></script>

<script src="/wp-content/themes/proautospec/js/api/cars-catalog.js?v=1"></script>

<section id="cars-listing" class="container mt-0 pt-4">
	<div class="d-flex align-items-between mb-3">
		<p class="cars-listing__total">Всего по запросу найдено <var><?php echo $cars->count;?></var> автомобилей:</p>
		<p class="ms-auto">Курс: 1 <?php echo $options->sign.' = '.$options->course;?> ₽</p>
	</div>
	<div class="row">
		<?php foreach ($cars->autos as $car):?>
			<?php
				$nameInUrl = $car->marka_name .' '. $car->model_name;

				$grade = preg_replace_callback(
									'/&#(\d+);/',
									function ($matches) {
										return "";
									},
									$car->grade
								);	
				$nameInUrl =  $car->marka_name .'-'. $car->model_name .'-'.$grade;
				$nameInUrl = preg_replace( '/[^a-zA-Z0-9\s-]/','', $nameInUrl);
				$nameInUrl = trim ($nameInUrl);
				$nameInUrl = preg_replace( '/\s+/', '-', $nameInUrl);
				$nameInUrl = preg_replace( '/-+/', '-', $nameInUrl);
				$nameInUrl = strtolower($nameInUrl);
				if ($post->ID != 46){
					$canonicalUrl = '/'.$options->baseSlug.'/'. $car->id . '-' .$nameInUrl . '/';
				}else{
					$canonicalUrl = '/'.$options->baseSlug.'/statistika/-/'. $car->id . '-' .$nameInUrl . '/';
				}
				$car->canonicalUrl = $canonicalUrl;	
				$car->title = $car->marka_name.' '.$car->model_name;
				$car->images = str_replace('8.ajes.com', '7.ajes.com', $car->images);
				$images = explode("#", $car->images);	
				$fullurl = str_replace( '&h=50', '', $images[0]);
				$thumbimg = $fullurl."&w=320";
				
											
			?>
			<div class="col-lg-3 car-loaded">
				<div class="car-item">
					<div class="car-item__pic">
						<a class="car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank"><img class="car-img" src="<?php echo $thumbimg;?>" alt="<?php echo esc_attr( $car->title ); ?>"></a>
					</div>
					<div class="car-item__desc">
						<h3><a href="<?php echo $car->canonicalUrl;?>" class="car-model-name car-link">
						<?php echo $car->title;?>
						</a>
						<span class="car-model-specification"><?php echo $car->grade;?></span> </h3>
						<dl class="car-item__params">
							<dt>Год</dt><dd><?php echo $car->year;?></dd>	
							<?php if ($car->kpp):?>
									<dt>Привод</dt><dd><?php echo $car->kpp;?></dd>
							<?php endif;?>				
						    <?php if ($car->eng_v):?>
									<dt>Объём</dt><dd><?php echo $car->eng_v;?> см<sup>3</sup></dd>
							<?php endif;?>	
							<dt>Пробег</dt><dd><?php echo number_format($car->mileage, 0, '.', ' ' );?> км</dd>							
						</dl>
						<a class="btn car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank">Подробнее</a>
						<div class="car-item__price">
								<span>Цена</span>
								<var class="car-price-value">
								<?php if ($car->finish):?>								
									<?php echo number_format( $car->finish * $options->course, 0, '.', ' ' );?> ₽ <?php // echo $currencySign->{$country}; ?>
								<?php else: ?>
									По запросу
								<?php endif; ?>
								</var>
						</div>
						
					</div>
				</div>			
			</div>
		
		<?php endforeach; ?>
	
		<div class="col-lg-3 d-none" id="car-item">
			<div class="car-item">
				<div class="car-item__pic">
					<a class="car-link" href="" target="_blank"><img class="car-img" src="" alt="Купить"></a>
				</div>
				<div class="car-item__desc">
					<h3><a href="" class="car-model-name car-link"></a>
					<span class="car-model-specification"></span> </h3>
					<dl class="car-item__params">


					</dl>
					<a class="btn car-link" href="#" target="_blank">Подробнее</a>
					<div class="car-item__price">
						<span>Цена</span> <var class="car-price-value"><?php //echo number_format( $car->cena1, 0, '.', ' ' );?></var>
					</div>
					
				</div>
			</div>
		</div>
	</div>
	<div class="row mt-lg-4"><div class="col text-center w-100"  id="car-listing-pagination">


	</div></div>

</section>

<?php
$request = explode("?", $_SERVER['REQUEST_URI']);
$current_url = "https://proauc.ru" . $request[0];
$seo_text = '';
$seo_title = '';
while( have_rows('seo_texts', 'option') ) {
	the_row();
    $seo_url = preg_replace('/\s+/', '', get_sub_field('seo_texts_url'));
	$pattern = "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
	preg_match($pattern, get_sub_field('seo_texts_url'), $s_url);
	if(!empty($s_url[0]) && $s_url[0] == $current_url) {
		$seo_title = get_sub_field('seo_texts_title');
		$seo_text = get_sub_field('seo_texts_text');
	}
}

if(!empty($seo_title) && !empty($seo_text)) { ?>

<section class="mt-4">
	<div class="container">
		<h2><?php echo $seo_title; ?></h2>
		<?php echo $seo_text; ?>
	</div>
</section>
	
<?php } ?>

<section class="container">
	

	
	
		
<?php
	if (($post->api_meta->isVendor) || ($post->api_meta->isModel)) {

		$vendor = $wpdb->get_row('SELECT * FROM wp_api_vendors WHERE country = "'.$country.'" AND UPPER(vendor_label) = "'.$post->api_meta->vendorLabel.'" group by vendor_label');
        ?>
		<div class="section-title">
			<h2>Автомобили <?php echo $vendor->vendor_label;?> с пробегом <?php echo $options->labelFrom;?></h2><p>Каталог по моделям</p>
		</div>
		<div class="b-vendors-list">
		<?php
		$items = $wpdb->get_results('SELECT * FROM wp_api_models WHERE country = "'.$country.'" AND vendor_id = '. $vendor->uid . '  group by model_label ORDER BY model_label ');
		foreach ($items as $item){
            if(mb_strpos($item->model_label, "&#") === false) {
                echo '<a href="/' . $options->baseSlug . '/catalog/' . str_replace(' ', '-', strtolower($vendor->vendor_label)) . '/' . str_replace(' ', '-', strtolower($item->model_label)) . '/" title="' . $item->seo_h1 . '">' . $item->model_label . '</a>';
            }
		}
		?>
		</div>	
		<?php
	}else{
		?>
		<div class="section-title">
			<h2>Автомобили с пробегом <?php echo $options->labelFrom;?></h2><p>Каталог по производителям</p>
		</div>
		<div class="b-vendors-list">
		<?php
		$items = $wpdb->get_results('SELECT * FROM wp_api_vendors WHERE country = "'.$country.'"  group by vendor_label ORDER BY vendor_label ');
		foreach ($items as $item){
			echo '<a href="/'.$options->baseSlug.'/catalog/'.str_replace(' ','-',strtolower($item->vendor_label)).'/" title="'.$item->seo_h1.'">'.$item->vendor_label.'</a>';
		}	
		?>
		</div>	
		<?php
	}

	
	
?>	

</section>	

<?php get_footer();
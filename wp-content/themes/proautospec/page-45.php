<?php
// korea
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>
<?php 

global $post;


global $wp;
global $wpdb;
global $post;
$country = "japan";
$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-cars-japan.php?client_ip=".USER_IP;
$h1 = 'Аукционы Японии онлайн';
if (array_key_exists('country', $wp->query_vars) && isset($wp->query_vars['country'])){ 
	$country = $wp->query_vars['country']; 
	$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-cars-".$country.".php?client_ip=".USER_IP;
	$h1 = 'Каталог автомобилей '.$options->{$country}->labelFrom.' под заказ';
}
$options = $options->{$country};
$options->country = $country;

if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
	$mark = str_replace('-', '+', strtoupper($wp->query_vars['mark'])); 
	$carsApiUrl = $carsApiUrl.'&marka_name='.$mark;

	if (property_exists ($post, 'api_meta') ){
		$h1 = $post->api_meta->seo_h1;
	}else{
		$post->api_meta = new StdClass();
	}
	if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])){ 
		$model = str_replace('-', '+', strtoupper($wp->query_vars['model'])); 
		$carsApiUrl = $carsApiUrl.'&model_name='.$model;
		if (property_exists ($post, 'api_meta') ){
			$h1 = $post->api_meta->seo_h1;
		}
	}	

}

$cars = json_decode(file_get_contents( $carsApiUrl ) );

?>
<section class="pb-0">
	<div class="container">
		<h1 class="mb-4"><?php echo $h1;?></h1>
		<div class="b-cars-catalog-filter b-cars-catalog-filter--goto">
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


<script>var statistics = 0; var renderingType = 'table';</script> 
<script src="/wp-content/themes/proautospec/js/pagination/pagination.js"></script>
<link href="/wp-content/themes/proautospec/js/pagination/pagination.css" rel="stylesheet">
<script>

</script> 
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script> 
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-japan.js?v=1.1"></script>
<script src="/wp-content/themes/proautospec/js/api/cars-catalog.js?v=1.1"></script>
<section id="cars-listing" class="container mt-0 pt-4 cars-listing--table">
	<div class="d-flex align-items-between mb-3">
		<p class="cars-listing__total">Всего по запросу найдено <var><?php echo $cars->count;?></var> автомобилей:</p>
		<p class="ms-auto">Курс: 1 <?php echo $options->sign.' = '.$options->course;?> ₽</p>
	</div>
	<div class="row">
		<div class="col-12">
			<div class="car-item car-item--row car-item--row__headings">
				<div class="car-item__pic">
					Фото
				</div>
				<div class="car-item__lot">
					Номер лота <br>
					Модель
				</div>
				<div class="car-item__auctions">
					Аукцион
				</div>
				<div class="car-item__condition">
					Год, кузов<br>
					Серия
				</div>
				<div class="car-item__complectation">
					Объём&nbsp;двигателя<br>
					Комплектация
				</div>
				<div class="car-item__rate">
					Пробег<br>
					Оценка
				</div>
				<div class="car-item__price">
					Стартовая цена<br>
					= Цена на аукционе<br>
					~ Средняя цена
				</div>
			</div>
		</div>
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
				$canonicalUrl = '/'.$options->baseSlug.'/'.$car->id . '-' .$nameInUrl . '/';
				$car->canonicalUrl = $canonicalUrl;	
				$car->title = $car->marka_name.' '.$car->model_name;	
				$car->images = str_replace('8.ajes.com', '7.ajes.com', $car->images);
				
				$images = explode("#", $car->images);	
				$fullurl = str_replace( '&h=50', '', $images[0]);
				$thumbimg = $fullurl."&w=320";
				
											
			?>
			<div class="col-12 car-loaded">
				<div class="car-item car-item--row">
					<div class="car-item__pic">
						<a class="car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank"><img class="car-img" src="<?php echo $thumbimg;?>" alt="Купить"></a>
					</div>
					<div class="car-item__lot">
						<a class="car-link" href="<?php echo $car->canonicalUrl;?>"><span class="car-lot"><?php echo $car->lot;?></span></a> <a class="car-link" href="<?php echo $car->canonicalUrl;?>">
						<h3 class="car-model-name"><?php echo $car->title;?></h3>
						</a>
					</div>
					<div class="car-item__auctions">
						<p class="car-auction"><?php echo $car->auction;?></p>
						<p class="car-auction-date"><?php echo $car->auction_date;?></p>
					</div>
					<div class="car-item__condition">
						<span class="car-year"></span> <span class="car-kuzov"></span>
						<p class="car-grade"><?php echo $car->grade;?></p>
					</div>
					<div class="car-item__complectation">
						<p class="car-eng"><?php if ($car->eng_v):?>
									<?php echo $car->eng_v;?> см<sup>3</sup>
										<?php endif;?>	
						</p>
									
						<p class="car-kpp">
							<?php if ($car->kpp):?>
									<?php echo $car->kpp;?>
							<?php endif;?>		
						</p>	
						<p class="car-color"><?php echo $car->color;?></p>
					</div>
					<div class="car-item__rate">
						<span class="car-mileage"><?php echo number_format($car->mileage, 0, '.', ' ' );?> км</span> <span class="car-rate"></span>
					</div>
					<div class="car-item__price">
						<div class="car-price-start">
						</div>
						<div class="car-price-finish">
						<?php if ($car->finish):?>								
									<?php echo number_format( $car->finish * $options->course, 0, '.', ' ' );?> ₽ <?php // echo $currencySign->{$country}; ?>
						<?php endif;?>			
						</div>
						<div class="car-price-average">
						</div>
						<div class="car-price-value">
						</div>
					</div>
				</div>				
				
			
			</div>
		
		<?php endforeach; ?>
		
		<div class="col-12 d-none" id="car-item">
			<div class="car-item car-item--row">
				<div class="car-item__pic">
					<a class="car-link" href="" target="_blank"><img class="car-img" src="" alt="Купить"></a>
				</div>
				<div class="car-item__lot">
					<a class="car-link d-block mb-1"><span class="car-lot"></span></a> <a class="car-link">
					<h3 class="car-model-name mb-0" style="color:#e84e0e"></h3>
					<p class="car-grade" style="color:#fff;"></p>
					</a>
				</div>
				<div class="car-item__auctions">
					<p class="car-auction"></p>
					<p class="car-auction-date"></p>
				</div>
				<div class="car-item__condition">
					<span class="car-year"></span> <span class="car-kuzov"></span>
					
				</div>
				<div class="car-item__complectation">
					<p class="car-eng"></p>
					<p class="car-kpp"></p>
					<p class="car-color"></p>
				</div>
				<div class="car-item__rate">
					<span class="car-mileage"></span> <span class="car-rate"></span>
				</div>
				<div class="car-item__price">
					<div class="car-price-start">
					</div>
					<div class="car-price-finish">
					</div>
					<div class="car-price-average">
					</div>
					<div class="car-price-value">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row mt-lg-4">
		<div class="col text-center w-100"  id="car-listing-pagination">
		</div>
	</div>
	
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
	
	<div style="margin: 20px 0;">
	<?php

	if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
		$mark = str_replace('-', '+', strtoupper($wp->query_vars['mark'])); 
		$carsApiUrl = $carsApiUrl.'&marka_name='.$mark;
		
		$post->api_meta->isVendor = true;
		$post->api_meta->vendorLabel = str_replace('-', ' ', strtoupper($wp->query_vars['mark']) );
		
		if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])){
			$model = str_replace('-', '+', strtoupper($wp->query_vars['model'])); 
			$carsApiUrl = $carsApiUrl.'&model_name='.$model;
			if (property_exists ($post, 'api_meta') ){
				$post->api_meta->isModel = true;
				$post->api_meta->modelLabel = str_replace('-', ' ', strtoupper($wp->query_vars['model']) );

				$auto_name .= " " . $post->api_meta->modelLabel;
			}
		}
	}
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
</div>
</section>
<?php
get_footer();

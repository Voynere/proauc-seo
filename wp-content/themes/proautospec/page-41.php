<?php
// korea
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

global $wp;
global $wpdb;
global $post;


$country = 'hdm';



$options = $options->{$country};


$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-hdm.php?client_ip=".USER_IP;
$h1 = "Спецтехника из Японии и Кореи под заказ";

if (array_key_exists('hdm-group', $wp->query_vars) && isset($wp->query_vars['hdm-group'])){ 
	$hdmGroup = $wp->query_vars['hdm-group']; 
	if (property_exists ($post, 'api_meta') ){
		$h1 = $post->api_meta->h1;
	}else{
		$post->api_meta = new StdClass();
	}

	if (array_key_exists('hdm-type', $wp->query_vars) && isset($wp->query_vars['hdm-type'])){ 
		$hdmType = str_replace('-', '+', strtoupper($wp->query_vars['hdm-type']));  ; 

		$carsApiUrl = $carsApiUrl.'&category='.$hdmType;

		if (property_exists ($post, 'api_meta') ){
			$h1 =  $post->api_meta->h1;
		}
	}	

}

$cars = json_decode(file_get_contents( $carsApiUrl ) );




	
?>
<section class="pb-0">
<div class="container">
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
	</div>
			
	<h1 class="mb-4"><?php echo $h1;?></h1>		
	<div class="b-cars-catalog-filter b-hdm-tags d-none">

		
	<h2>Тяжёлая строительная техника</h2>	
<a href="/spectehnika/?category=vehicle">Автомобиль</a>
<a href="/spectehnika/?category=bulldozer">Бульдозер</a>
<a href="/spectehnika/?category=forklift">Вилочный погрузчик</a>
<a href="/spectehnika/?category=generator&amp;compressor">Генератор и компрессор</a>
<a href="/spectehnika/?category=grader">Грейдер</a>
<a href="/spectehnika/?category=crawler%20lift">Гусеничная рабочая платформа</a>
<a href="/spectehnika/?category=crawler%20crane">Гусеничный кран</a>
<a href="/spectehnika/?category=crawler%20carrier">Гусеничный транспортер
<a href="/spectehnika/?category=parts%20and%20attachment">Запчасти и навесное оборудование</a>
<a href="/spectehnika/?category=combined%20roller">Каток</a>
<a href="/spectehnika/?category=mini%20wheel%20loader">Колесный погрузчик</a>
<a href="/spectehnika/?category=crane">Кран</a>
<a href="/spectehnika/?category=rough%20terrain%20crane">Кран для труднопроходимой местности</a>
<a href="/spectehnika/?category=other">Прочее</a>
<a href="/spectehnika/?category=excavator">Экскаватор</a>
<a href="/spectehnika/?category=road%20equipment">Тип не указан</a> 
		<h2>Промышленное оборудование</h2>	
<a href="/spectehnika/?category=generator">Генератор</a> 
<a href="/spectehnika/?category=welder%20generator">Сварочный генератор</a> 
		<h2>Сельхозтехника</h2>	
<a href="/spectehnika/?category=lawn%20mover">Газонокосилка</a>
<a href="/spectehnika/?category=combine">Комбайн</a>
<a href="/spectehnika/?category=moving&20machine">Косилка</a>
<a href="/spectehnika/?category=speed%20sprayer">Опрыскиватель</a>
<a href="/spectehnika/?category=tiller">Культиватор</a>
<a href="/spectehnika/?category=cultivator">Культиватор</a>
<a href="/spectehnika/?category=rice%20transplanter">Посадочная машина для риса</a>
<a href="/spectehnika/?category=">Пресс-подборщик</a>
<a href="/spectehnika/?category=">Прочее</a>
<a href="/spectehnika/?category=power%20sprayer">Распылитель</a>
<a href="/spectehnika/?category=">Снегоуборщик</a>
<a href="/spectehnika/?category=tractor">Трактор</a>
<a href="/spectehnika/?category=">Тягач</a>
		<h2>После ремонта и т.п.</h2>
<a href="/spectehnika/?category=aerial%20work%20platform">Автовышка</a>
<a href="/spectehnika/?category=">Асфальтоотделочная машина</a>
<a href="/spectehnika/?category=">Вилка</a>
<a href="/spectehnika/?category=">Гусеничный кран мини</a>
<a href="/spectehnika/?category=">Дробилка</a>
<a href="/spectehnika/?category=">Дробильный ковш</a>
<a href="/spectehnika/?category=">Думпер (гусуничный самосвал)</a>
<a href="/spectehnika/?category=">Захват</a>
<a href="/spectehnika/?category=">Карьерный погрузчик</a>
<a href="/spectehnika/?category=">Ковш</a>
<a href="/spectehnika/?category=">Компрессор</a>
<a href="/spectehnika/?category=">Культиватор</a>
<a href="/spectehnika/?category=">Моечная машина высокого давления</a>
<a href="/spectehnika/?category=">Погрузчик с бортовым поворотом</a>
<a href="/spectehnika/?category=">Подметальная машина</a>
<a href="/spectehnika/?category=">Посадочная машина для риса</a>
<a href="/spectehnika/?category=">Пресс-подборщик</a>
<a href="/spectehnika/?category=">Прожектор</a>
<a href="/spectehnika/?category=">Прочие</a>
<a href="/spectehnika/?category=">Резак</a>
<a href="/spectehnika/?category=">Резак для бетона</a>
<a href="/spectehnika/?category=">Снегоуборщик</a>
<a href="/spectehnika/?category=">Тягач</a>
<a href="/spectehnika/?category=">Универсальный вилочный погрузчик</a>
<a href="/spectehnika/?category=">Электрический вилочный погрузчик</a>
	
	</div>
</div>
</section>
	<section class="mt-0 pt-0">
		<div class="container">
		<div class="b-cars-catalog-filter b-cars-catalog-filter--goto">
			<h2>Найдите нужный тип спецтехники самостоятельно</h2>
			<form method="get" action="/spectehnika/">
				<div class="row form-row">
					<div class="col-lg-4">
						<label for="group-id">Группа техники</label>
						<select class="" name="group-id" id="group-id">
							<option value="">Выберите группу</option>
						</select>
					</div>
					<div class="col-lg-5">
						<label for="type-id">Категория техники</label>
						<select class="" name="type-id" id="type-id">
							<option value="">Выберите категорию</option>
						</select>
					</div>
					<div class="col-lg-3">
						<label>&nbsp;</label>
						<button class="btn btn-submit">Подобрать</button>
					</div>
				</div>
			</form>
		</div></div>
	</section>	

				
<?php
/*
$response = wp_remote_get((empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-hdm-categories.php");
$liveTypes = json_decode($response['body'])->cats;
foreach ($liveTypes as $item){
	if ($item != ''){
		$itemName = strtolower($item);

		$type = $wpdb->get_row('SELECT * FROM wp_api_hdm_types WHERE has_items = 1');	
		
	}
}
*/
?>
	
	
<script src="/wp-content/themes/proautospec/js/pagination/pagination.js"></script>

<link href="/wp-content/themes/proautospec/js/pagination/pagination.css" rel="stylesheet">
<?php

$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/hdm_new_types.js');

//echo "<script>var course = ".$options->course.";</script>";
echo "<script>".$models."</script>";
?>	

<script src="/wp-content/themes/proautospec/js/api/hdm-catalog-filter.js"></script>
	
	
	
	
<script src="/wp-content/themes/proautospec/js/api/hdm-catalog.js"></script>

<section id="cars-listing" class="container mt-0 pt-4 hdm-listing">
	<p class="cars-listing__total mb-3">Всего найдено: <var></var></p>
	<div class="row">
		
		<?php foreach ($cars->autos as $car):?>
			<?php
				$nameInUrl = $car->marka_name .' '. $car->model_name;

				$grade = $car->grade;
									
				$nameInUrl =  $car->marka_name .'-'. $car->model_name .'-'.$grade;
				$nameInUrl = preg_replace( '/[^a-zA-Z0-9\s-]/','', $nameInUrl);
				$nameInUrl = trim ($nameInUrl);
				$nameInUrl = preg_replace( '/\s+/', '-', $nameInUrl);
				$nameInUrl = preg_replace( '/-+/', '-', $nameInUrl);
				$nameInUrl = strtolower($nameInUrl);
				$canonicalUrl = '/'.$options->baseSlug.'/'. $nameInUrl  . '_' . $car->id . '/';
				
				$car->canonicalUrl = $canonicalUrl;	
				$car->title = $car->marka_name.' '.$car->model_name;	
				$images = explode("#", $car->images);	
				$fullurl = str_replace( ['&h=50',"&w=320"], '', $images[0]);
				$thumbimg = $fullurl."&w=320";
				
											
			?>

			<div class="col-lg-3 car-loaded">
				<div class="car-item">
					<div class="car-item__pic">
						<a class="car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank"><img class="car-img" src="<?php echo $thumbimg;?>" alt="Купить"></a>
					</div>
					<div class="car-item__desc">
						<h3><a href="<?php echo $car->canonicalUrl;?>" class="car-model-name car-link">
						<?php echo $car->title;?>
						</a>
						</h3>
						<dl class="car-item__params">
							<dt>Год</dt><dd><?php echo $car->year;?></dd>	
							<?php if ($car->kpp):?>
									<dt>Привод</dt><dd><?php echo $car->kpp;?></dd>
							<?php endif;?>				
							<?php if (($car->mileage) && is_numeric($car->mileage)):?>		
							<dt>Пробег</dt><dd><?php echo number_format($car->mileage, 0, '.', ' ' );?> км</dd>	
							<?php endif;?>
						</dl>
						<a class="btn car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank">Подробнее</a>
						<div class="car-item__price">
								<span>Цена</span>
								<var class="car-price-value">
								<?php if (($car->finish) && is_numeric($car->finish)):?>								
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
<?php $hdmGroup = $wpdb->get_row('SELECT * FROM wp_api_hdm_groups WHERE slug = "'. $hdmGroup. '"');?>

<?php 
?>
	

	
	
<?php if (isset($hdmGroup)):?>
<?php 

	
	if (property_exists ($post, 'api_meta') ){
		echo '<section class="container">'.$post->api_meta->seo_text.'</section>';
	}
		
?>

<?php	
	
	$categories = get_categories(
		array(
			'parent'     => 6,
			'orderby'    => 'id',
			'order'      => 'DESC',
			'hide_empty' => 1,
			'meta_query' => array(
				array(
					'key'     => 'new-group',     // Adjust to your needs!
					'value'   => $hdmGroup->id,   // Adjust to your needs!
					'compare' => '=',         // Default
				)
			)
		)
	);
	if ($categories):
		$catArr = array();
		//var_dump($categories);
		foreach ($categories as $item){
			$catArr[] = $item->cat_ID;
		}
		$args = array(
			'post_type' => 'avto',
			'posts_per_page' => -1,
			'category__in' => $catArr 
		);


		$the_query = new WP_Query( $args );


	?>	
	<?php if ( $the_query->have_posts() ) : ?>
	<section class="">
		<div class="container">
		<h2 class="mb-4"><?php echo $hdmGroup->name_ru;?>, что недавно привезли </h2>

		<div class="row">	

				<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
					<div class="col-lg-3">
						<?php get_template_part('template-parts/loops/avto');?>
					</div>
				<?php endwhile; ?>

				<?php wp_reset_postdata(); ?>
		</div>	
			</div>
	</section>		
			<?php 
			endif;


			?>


	<?php endif;?>
	<?php endif;?>
<?php

	//$category = get_category_by_slug( get_query_var( 'category_name' ) );
	if (isset($hdmType)):
	$category = get_category_by_slug( $hdmType );

?>	

    <?php 
	if ($category):
		$args = array(
			'post_type' => 'avto',
			'posts_per_page' => -1,
			'category__in' => $category->cat_ID
		);

		$the_query = new WP_Query( $args );
		
		?>

		<?php if ( $the_query->have_posts() ) : ?>
<section class="">
	<div class="container">
	<h2 class="mb-4">Недавно купленная спецтехника</h2>
	
    <div class="row">	

			<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
				<div class="col-lg-3">
					<?php get_template_part('template-parts/loops/avto');?>
				</div>
			<?php endwhile; ?>

			<?php wp_reset_postdata(); ?>
    </div>	
		</div>
</section>		
        <?php 
        endif;
        endif;
        endif;
        ?>
	
	<?php if (isset($hdmGroup)):?>
<section class="container b-hdm-tags">
	<h2 class="mb-4">Смежные категории спецтехники:</h2>
<?php 
	$hdmTypes = $wpdb->get_results('SELECT * FROM wp_api_hdm_types WHERE group_id = "'. $hdmGroup->id. '" AND has_items = 1');

	foreach ($hdmTypes as $type){
		echo '<a href="/spectehnika/'.$hdmGroup->slug.'/'.$type->slug.'/">'.$type->name_ru.'</a>';
	}
?>	
</section>		
<?php endif;?>	

<?php get_footer();
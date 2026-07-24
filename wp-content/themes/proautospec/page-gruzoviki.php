<?php
// korea
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

global $wp;
global $wpdb;
global $post;


$country = 'japan';


echo "<script>var course = ".$options->japan->course.";</script>";
$options = $options->{$country};


$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-trucks.php?client_ip=".USER_IP;
$h1 = "Грузовики из Японии под заказ";

if (array_key_exists('hdm-group', $wp->query_vars) && isset($wp->query_vars['hdm-group'])){ 

	$hdmGroup = $wp->query_vars['hdm-group'];
	$hdmGroup = "gruzoviki";
	if (property_exists ($post, 'api_meta') ){
		$h1 = $post->api_meta->name_ru . ' с аукционов';
	}
	
	if (array_key_exists('mark', $wp->query_vars) && isset($wp->query_vars['mark'])){ 
		
		$mark = str_replace('-', '+', strtoupper($wp->query_vars['mark'])); 
		$carsApiUrl = $carsApiUrl.'&marka_name='.$mark;

		if (property_exists ($post, 'api_meta') ){
			$h1 = $post->api_meta->seo_h1;
		}
		if (array_key_exists('model', $wp->query_vars) && isset($wp->query_vars['model'])){ 
			$model = str_replace('-', '+', strtoupper($wp->query_vars['model'])); 
			$carsApiUrl = $carsApiUrl.'&model_name='.$model;
			if (property_exists ($post, 'api_meta') ){
				$h1 = $post->api_meta->seo_h1;
			}
		}	
	}	
	
}

$cars = json_decode(file_get_contents( $carsApiUrl ) );

$truckCategories = [
	'TOYOTA' => ['DYNA', 'TOYOACE', 'TOWN ACE TRUCK'],
	'NISSAN' => ['ATLAS', 'TRUCK', 'CLIPPER TRUCK'],
	'SUZUKI' => ['CARRY TRUCK'],
	'DAIHATSU'	=> ['HIJET TRUCK'],
	'MAZDA' => ['BONGO', 'TITAN'],
	'ISUZU'	 => ['CABIN', 'ELF', 'ELF TRUCK', 'FORWARD', 'GIGA', 'JUSTON', 'TRUCK'],
	'HINO' => ['DUTRO', 'PROFIA', 'RANGER', 'TRUCK'],
	'MITSUBISHI' => ['CANTER']
];

///$groups =  $wpdb->get_results('SELECT * FROM wp_api_hdm_groups ORDER by name_ru');
//$types = $wpdb->get_results('SELECT * FROM wp_api_hdm_types WHERE has_items = 1 ORDER by name_ru');




foreach ($truckCategories as $key=>$models){
	$vendor = (object) $wpdb->get_row("SELECT * FROM wp_api_vendors WHERE vendor_label = '".$key."' AND country = 'japan'");
	//var_dump($vendor);
	
	//echo ('SELECT * FROM wp_api_models WHERE vendor_id = '.$vendor->uid." AND model_label IN ('" . implode ( "', '", $models) . "') AND country = 'japan'");
	$models = $wpdb->get_results('SELECT * FROM wp_api_models WHERE vendor_id = '.$vendor->uid." AND model_label IN ('" . implode ( "', '", $models) . "') AND country = 'japan' AND seo_title IS NOT NULL");
	//var_dump($models);
	$thisVendorModels = array();
	foreach ($models as $model){
		$thisVendorModels[] = [ 'id' =>  $model->uid, 'text' => $model->model_label];
		
	}	
	//$thisGroupTypes = usort($thisGroupTypes, "cmp");
	
	$newVendors[] = ['id' => $vendor->uid, 'pinned' => 0, 'text' => $vendor->vendor_label, 'models' => $thisVendorModels];



}
$wp_filesystem->put_contents( get_home_path().'/api/cache/truck_models.js', 'const models_trucks = \''.json_encode($newVendors, JSON_UNESCAPED_UNICODE ).'\';' );

//exit;

?>
<section class="pb-0">
<div class="container">
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
	</div>
			
	<h1 class="mb-4"><?php echo $h1;?></h1>		

</div>
</section>
	<section class="mt-0 pt-0">
		<div class="container">
		<div class="b-cars-catalog-filter b-cars-catalog-filter--goto">
			<h2>Найдите нужный грузовик самостоятельно</h2>
			<form method="get">
				<input type="hidden" name="pn" value="1">
				<?php
				get_template_part( 'template-parts/catalog-form-filter' );
				?>			
			</form>
		</div>
		</div>

	</section>	

				

	
	
<script src="/wp-content/themes/proautospec/js/pagination/pagination.js"></script>

<link href="/wp-content/themes/proautospec/js/pagination/pagination.css" rel="stylesheet">
<?php

$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/truck_models.js');

//echo "<script>var course = ".$options->course.";</script>";
echo "<script>".$models."</script>";




?>	

<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script>




	
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-japan.js"></script>	
<script src="/wp-content/themes/proautospec/js/api/cars-catalog.js"></script>


<section id="cars-listing" class="container mt-0 pt-4 hdm-listing">
	<p class="cars-listing__total mb-3">Всего найдено: <var></var></p>
	<div class="row">
		
		<?php foreach ($cars->autos as $car):?>
			<?php
				$nameInUrl = $car->marka_name .' '. $car->model_name;

				$grade = $car->grade;
				if (!empty($grade))
					$nameInUrl =  $car->marka_name .'-'. $car->model_name .'-'.$grade;
				else 
					$nameInUrl =  $car->marka_name .'-'. $car->model_name;
		
		
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
						<a class="car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank"><img class="car-img" src="<?php echo $thumbimg;?>" alt="<?php echo esc_attr( $car->title ); ?>"></a>
					</div>
					<div class="car-item__desc">
						<h3><a href="<?php echo $car->canonicalUrl;?>" class="car-model-name car-link">
						<?php echo $car->title;?><span class="d-block" style="display:block;color:#fff;font-size:.85rem"><?php echo $car->grade;?></span>
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



<?php if ( function_exists( 'proauc_render_catalog_blog_sidebar' ) ) { proauc_render_catalog_blog_sidebar(); }
get_footer();
<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
$country = 'hdm';
echo "<script>var course = ".$options->japan->course.";</script>";
$options = $options->{$country};


?>

<?php

$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/hdm_new_types.js');

//echo "<script>var course = ".$options->course.";</script>";
echo "<script>".$models."</script>";
?>	


<script src="/wp-content/themes/proautospec/js/api/hdm-catalog-filter.js"></script>	
<?php /*
<script src="/wp-content/themes/proautospec/js/api/hdm-catalog.js?v=1"></script>
*/
?>
	
	<article>
	<section class="b-intro">
		<div class="container h-100">
			<div class="row h-100 align-items-center">
				<div class="col-12 col-md-12 col-lg-6">
					<h1>Грузовики<br>
						и спецтехника<br>
						с аукционов</h1>
					<div class="b-intro__tags">
						
						<a href="/spectehnika/catalog/">#Спецтехника</a>
						<a href="/spectehnika/gruzoviki/">#Грузовики</a>
					</div>
					<p class="b-intro__feature-list">Б/у спецтехника из Японии и Кореи — это качественные машины в отличном техническом состоянии по цене значительно ниже новой. В этих странах технику обслуживают вовремя, эксплуатируют бережно и часто меняют ещё до серьёзного износа. «Проспецавто» поможет привезти надёжную технику под ваши задачи — с полной проверкой, документами и доставкой по всей России.</p>
					<a class="btn btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog">Бесплатная консультация</a>
				</div>
				<div class="col-lg-6 align-self-end">
					<img src="/images/gruzoviki-i-spectehnika-iz-yaponii.png" class="b-intro__img" alt="Грузовики и спецтехника из Кореи">
				</div>
			</div>
		</div>
	</section>
	<section class="mt-0 pt-0">
		<div class="container">
		<div class="b-cars-catalog-filter b-cars-catalog-filter--goto">
			<h2>Найдите нужный тип спецтехники самостоятельно</h2>
			<p>Или выбирайте из подборок ниже</p>
			<form method="get" action="/spectehnika/catalog/">
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
	
$pinnedCategories = [ 
	'Грузовики' => (object) ['slugs' => [], 'url' => '/spectehnika/gruzoviki/'],
	'Экскаваторы' => (object) ['slugs' => ['Mini Excavator', 'Excavator'], 'url' => '/spectehnika/ekskavatory/'], 
	'Бульдозеры' => (object)  ['slugs' => ['Bulldozer'], 'url' => '/spectehnika/buldozery/'], 
	'Погрузчики' => (object)  ['slugs' => ['Forklift', 'Shovel Loader', 'Reach Forklift'], 'url' => '/spectehnika/pogruzchiki/'],
	
	'Дорожная техника' => (object) ['slugs' => ['Asphalt Finisher', 'GRADER', 'Crawler Carrier', 'Tandem Roller', 'Road Equipment', 'Road Cutter', 'Sweeper', 'mobile crusher', 'tire roller', 'mcadam roller', 'wheel carrier', 'concrete cutter', 'hand guided roller'], 'url' => '/spectehnika/dorozhnay-tehnika/'],
	'Сельхозтехника' => (object) ['slugs' => ['Moving Machine', 'Plough', 'MOWING MACHINE', 'BOOM SPRAYER A', 'HARROW', 'RICE TRANSPLANTER'], 'url' => '/spectehnika/selhoztehnika/']
	
] ;
		
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
		

foreach ($pinnedCategories as $catName => $values):

		if ($catName == 'Грузовики'){
			$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] ."/api/get-trucks.php";		
		}else{
			$catGetParams = implode('&', array_map(function ($data) { return 'category[]='.urlencode($data); }, $values->slugs));

			$carsApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] ."/api/get-hdm.php?".$catGetParams;		
		}	
		$cars = json_decode(file_get_contents( $carsApiUrl ) );
		
?>		
<section id="cars-listing" class="mt-0 pt-4">		
		
		<div class="container">
				<div class="d-flex align-items-center mb-4">
				<h2 class=""><?php echo $catName;?></h2>
				<a class="ms-4" href="<?php echo $values->url;?>">Полный каталог &rarr;</a>
				</div>
				<div class="recently-bought-slider__wrapper position-relative">
					<div class="swiper recently-bought-slider">
						<div class="swiper-wrapper row">
		
		<?php foreach ($cars->autos as $car):?>
			<?php
				
				$grade = preg_replace_callback(
									'/&#(\d+);/',
									function ($matches) {
										return "";
									},
									$car->grade
								);	
				if (!empty($grade))
					$nameInUrl =  $car->marka_name .'-'. $car->model_name .'-'.$grade;
				else 
					$nameInUrl =  $car->marka_name .'-'. $car->model_name;
				$nameInUrl = preg_replace( '/[^a-zA-Z0-9\s-]/','', $nameInUrl);
				$nameInUrl = trim ($nameInUrl);
				$nameInUrl = preg_replace( '/\s+/', '-', $nameInUrl);
				$nameInUrl = preg_replace( '/-+/', '-', $nameInUrl);
				$nameInUrl = strtolower($nameInUrl);
				if ($catName != 'Грузовики'){
					$canonicalUrl = '/'.$options->baseSlug.'/'. $nameInUrl  . '_' . $car->id . '/';
				}else{
					$canonicalUrl = '/avto-iz-yaponii/'.$car->id .'-' . $nameInUrl  . '/';
				}
				
				$car->canonicalUrl = $canonicalUrl;	
				$car->title = $car->marka_name.' '.$car->model_name;	

				if (!property_exists ($car, 'category')){
					$car->category = $catName;
				}
											
			?>

			<div class="col-lg-3 swiper-slide car-loaded">
				<div class="car-item">
					<div class="car-item__pic">
						<a class="car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank">
						<?php
						$images = explode("#", $car->images);	
						if ($catName != 'Грузовики'){
							array_shift($images); //remove first image in hdm, cause typically it's a list
						}
						if (count($images) > 1){
							foreach ($images as $image){
								$thumbimg = str_replace( ['&h=50',"&w=320"], '', $image)."&w=320";
								echo '<img class="car-img" src="'.$thumbimg.'" alt="Купить">';
							}
						}
						?>
							
							
							
						</a>
					</div>
					<div class="car-item__desc">
						<h3 style="height:auto"><a href="<?php echo $car->canonicalUrl;?>" class="car-model-name car-link">
						<?php echo $car->title;?><span class="d-block" style="display:block;color:#fff;font-size:.85rem"><?php echo $car->grade;?></span>
						</a>
						
						</h3>
						<strong><?php echo $car->category;?></strong>
						<dl class="car-item__params">
							<?php if (isset( $car->year) and ($car->year != 0) ):?>
									<dt>Год</dt><dd><?php echo $car->year;?></dd>	
							<?php endif;?>		
							
							<?php if (isset( $car->kpp) && ($car->kpp != '') ):?>
									<dt>Привод</dt><dd><?php echo $car->kpp;?></dd>
							<?php endif;?>				
							<?php if (($car->mileage) && is_numeric($car->mileage)):?>		
								<dt>Пробег</dt><dd><?php echo number_format($car->mileage, 0, '.', ' ' );?> км</dd>	
							<?php endif;?>
							<?php if (isset($car->eng_v)):?>
									<dt>Объём</dt><dd><?php echo $car->eng_v;?></dd>
							<?php endif;?>
							<?php if (isset($car->kuzov)):?>
									<dt>Кузов</dt><dd><?php echo $car->kuzov;?></dd>
							<?php endif;?>
							<?php if (($car->auction)):?>		
								<dt>Аукцион</dt><dd><?php echo $car->auction;?></dd>	
							<?php endif;?>
			
						
						</dl>
						<a class="btn car-link" href="<?php echo $car->canonicalUrl;?>" target="_blank">Подробнее</a>
						<div class="car-item__price">
								<span>Цена</span>
								<var class="car-price-value">
								по запросу
								
								<?php /* if (($car->finish) && is_numeric($car->finish)):?>								
									<?php echo number_format( $car->finish * $options->course, 0, '.', ' ' );?> ₽ <?php // echo $currencySign->{$country}; ?>
								<?php else: ?>
									по запросу
								<?php endif; */ ?>
								</var>
						</div>
						
					</div>
				</div>			
			</div>
		
		<?php endforeach; ?>
		</div>		</div>
		<div class="swiper-navigation">
						<div class="swiper-scrollbar-wrapper">
							<div class="swiper-scrollbar">
							</div>
						</div>
						<div class="swiper-button-prev">
						</div>
						<div class="swiper-button-next">
						</div>
					</div>	
		</div>
		</div>
</section>		
<?php endforeach;?>	

	

	
	<section class="content-plain pt-0">
	<div class="container">
	<h2>Поставка спецтехники из Японии и Южной Кореи</h2>
	<p>Грузовые автомобили и специализированная техника из Японии и Южной Кореи — это выгодное решение для тех, кто ценит надежность, долговечность и высокий ресурс машин. Мы предлагаем услуги по профессиональному подбору, закупке и доставке спецтехники от ведущих производителей с прямыми поставками из этих стран. В наличии и под заказ — самосвалы, экскаваторы, погрузчики, автокраны, буровые установки, трактора, комбайны, манипуляторы и другая техника для строительства, сельского хозяйства и коммунальных работ.</p>
		

		<h2 class="my-3">Почему стоит выбирать спецтехнику из Японии и Кореи?</h2>

	<div class="row row-cols-3">
		<div class="col-12 col-lg-4">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Безупречное техническое состояние</h3>
					<p>Японцы и корейцы известны своим бережным отношением к технике. Даже техника с пробегом часто находится в состоянии, близком к новой: своевременное ТО, аккуратная эксплуатация, отсутствие перегрузок.</p>
				</div>
			</div>
		</div>
		<div class="col-12 col-lg-4">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Высокое качество сборки и комплектующих</h3>
					<p>Такие бренды, как Komatsu, Hitachi, Kobelco, Hyundai, Doosan и Kato, производят технику, которая рассчитана на длительную эксплуатацию в самых тяжёлых условиях. Это оборудование работает десятилетиями без серьёзных вложений в ремонт.</p>
				</div>
			</div>
		</div>
				<div class="col-12 col-lg-4">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Техника, адаптированная к суровым климатическим условиям</h3>
					<p>Многие модели, особенно с японского рынка, прекрасно переносят холодные зимы и температурные перепады — что делает их идеальными для эксплуатации в российских регионах.</p>
				</div>
			</div>
		</div>
						<div class="col-12 col-lg-6">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Доступная цена по сравнению с европейскими аналогами</h3>
					<p>Даже с учётом логистики и растаможки, техника из Японии и Кореи обходится дешевле — при этом по качеству зачастую превосходит новые китайские аналоги.</p>
				</div>
			</div>
		</div>
		<div class="col-12 col-lg-6">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Широкий выбор редких и специализированных моделей</h3>
					<p>На аукционах Японии и у корейских дилеров можно найти технику, которая практически не представлена в РФ — буровые установки, мини-погрузчики, коммунальные машины, краны-манипуляторы и многое другое.</p>
				</div>
			</div>
		</div>
	</div>
	</section>

</article>	
<noindex>
	<section class="mt-4">
		<?php
		get_template_part( 'template-parts/landing/b-getting-auto' );
		?>
	</section>
	<section class="">
		<?php
		get_template_part( 'template-parts/landing/b-cta-whatsapp' );
		?>
	</section>
	<section class="">
		<?php
		get_template_part( 'template-parts/landing/b-testimonials' );
		?>
	</section>
	<section class="mb-0">
		<?php
		get_template_part( 'template-parts/landing/b-team' );
		?>
	</section>
	<div class="container">
		<div class="b-form-consultation">
			<h2>Нужна консультация?</h2>
			<p>Оставьте бесплатную заявку, и мы свяжемся с вами в ближайшее время и подробно всё расскажем.</p>
			<?php echo apply_shortcodes( '[contact-form-7 id="8ea05cf" title="Бесплатная консультация"]' ); ?>
			<p class="text-policy mt-4">Нажимая на кнопку, Вы соглашаетесь с <a href="/privacy/" target="_blank">политикой обработки персональных данных</a></p>
		</div>
	</div>
	
</noindex>
		

<section class="container b-hdm-tags">
	<h2 class="mb-4">Полный каталог спецтехники по тегам:</h2>
<?php 
	$hdmGroups = $wpdb->get_results('SELECT * FROM wp_api_hdm_groups');
	
	foreach ($hdmGroups as $hdmGroup){
		//echo '<a style="margin-top:1rem" href="/spectehnika/'.$hdmGroup->slug.'/">'.$hdmGroup->name_ru.'</a>:<br>';
		$hdmTypes = $wpdb->get_results('SELECT * FROM wp_api_hdm_types WHERE group_id = "'. $hdmGroup->id. '" AND has_items = 1');

		foreach ($hdmTypes as $type){
			echo '<a href="/spectehnika/'.$hdmGroup->slug.'/'.$type->slug.'/">'.$type->name_ru.'</a>';
		}
	}
	
?>	
</section>		
		

<?php
get_footer();

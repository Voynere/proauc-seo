<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();


echo "<script>var course = ".$options->china->course.";</script>";
?>
<article>
	<section class="b-intro">
		<div class="container h-100">
			<div class="row h-100 align-items-center">
				<div class="col-12 col-md-12 col-lg-6">
					<h1>Автомобили<br>
						и спецтехника<br>
						из Китая</h1>
					<div class="b-intro__tags">
						<a href="/avtomobili-i-spectehnika-iz-kitaya/catalog/">#Китай</a>
					</div>
					<p class="b-intro__feature-list">Наша команда поможет вам в покупке автомобиля и спецтехники из Китая. Мы привозим автомобили из Китая и доставляем их по всей России. Авто проходит на территории РФ полную процедуру таможенной очистки и имеет все необходимые документы для беспрепятственной постановки на учет в органах Госавтоинспекции РФ.</p>
					<a class="btn btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog">Бесплатная консультация</a>
				</div>
				<div class="col-lg-6 align-self-end">
					<img src="/images/avtomobili-iz-kitaya.png" class="b-intro__img" alt="Автомобили и спецтехника из Китая">
				</div>
			</div>
		</div>
	</section>
	<section  id="cars-listing"  class="b-recently-bought b-car-items mb-0 pb-0">


			<div class="container">
				<div class="section-title">
					<h2>Купить автомобиль из Китая</h2>
				</div>



				<div class="recently-bought-slider__wrapper position-relative">
					<div class="swiper recently-bought-slider">
						<div class="swiper-wrapper row">

							<div class="col-lg-3 swiper-slide d-none" id="car-item">
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
					</div>
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
	</section>		
	<section class="mt-0 pt-0">
		<div class="container">
		<div class="b-cars-catalog-filter b-cars-catalog-filter--goto">
			<h2>Найдите нужный автомобиль самостоятельно</h2>
			<form method="get" action="/avto-iz-kitaya/catalog/">
				<?php
				get_template_part( 'template-parts/catalog-form-filter' );
				?>
			</form>
		</div></div>
	</section>	
	<section class="content-plain pt-0">
	<div class="container">
	<h2>Авто из Китая</h2>
	<p>В Китае можно купить автомобили различных марок с левым расположением руля, что считается самым удобным и правильным для нашего правостороннего движения. В данном случае нет автомобильных аукционов, где вы получите аукционный лист со всеми замечаниями и информацией об авто, заместо этого есть специальные площадки и рынки где размещаются автомобили для продажи, а также есть возможность купить технику прямиком с завода. Наша команда, которая находится в Китае, подберет нужный вам автомобиль под все ваши запросы и пожелания, приедет на осмотр авто, вы будете точно уверены и осведомлены что вы покупаете и решение о покупке всегда остается за вами, мы ничего вам не навязываем - мы лишь помогаем вам в покупке вашей мечты!</p>
	<h2>Не только китайские авто</h2>
	<p>Помимо популярных китайских автомобильных марок из Китая можно выгодно привезти американские, европейские и японские авто с левым рулем.</p>
	<div class="b-auto-marks">
		<a href="/avto-iz-kitaya/catalog/bmw/"><img src="/images/auto-logo-bmw.png" alt="BMW из Китая"></a> <a href="/avto-iz-kitaya/catalog/audi/"><img src="/images/auto-logo-audi.png" alt="Audi из Китая"></a> <a href="/avto-iz-kitaya/catalog/mercedes-benz/"><img src="/images/auto-logo-mercedes.png" alt="Mercedes из Китая"></a> <a href="/avto-iz-kitaya/catalog/ford/"><img src="/images/auto-logo-ford.png" alt="Ford из Китая"></a> <a href="/avto-iz-kitaya/catalog/volkswagen/"><img src="/images/auto-logo-volkswagen.png" alt="Volkswagen из Китая"></a> <a href="/avto-iz-kitaya/catalog/jeep/"><img src="/images/auto-logo-jeep.png" alt="Jeep из Китая"></a> <a href="/avto-iz-kitaya/catalog/honda/"><img src="/images/auto-logo-honda.png" alt="Honda из Китая"> </a><a href="/avto-iz-kitaya/catalog/land-rover/"><img src="/images/auto-logo-land-rover.png" alt="Land Rover из Китая"></a>
	</div>
	<div class="row">
		<div class="col-12 col-lg-6 ">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Выгоднее</h3>
					<p>Стоимость автомобилей из Китая будет значительно ниже, чем покупка таких же автомобилей на б/у рынках РФ. Большим плюсом является то, что можно купить технику с завода.</p>
				</div>
			</div>
		</div>
		<div class="col-12 col-lg-6">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Быстрее</h3>
					<p>Покупка техники из Китая происходит быстрее, чем в Японии, сроки подготовки авто и его отправки в РФ тоже происходят быстрее.</p>
				</div>
			</div>
		</div>
	</div>
	</div>
	</section>

</article>
<?php
$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/model_che.js');
echo "<script>".$models."</script>";
?>



<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script>	
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-china.js"></script>
<script src="/wp-content/themes/proautospec/js/api/cars-catalog.js?v=1"></script>
			
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
<?php
get_footer();

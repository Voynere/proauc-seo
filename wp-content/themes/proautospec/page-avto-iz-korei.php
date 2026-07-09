<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

echo "<script>var course = ".$options->korea->course.";</script>";
?>
<article>
	<section class="b-intro">
		<div class="container h-100">
			<div class="row h-100 align-items-center">
				<div class="col-12 col-md-12 col-lg-6">
					<h1>Автомобили<br>
						и спецтехника<br>
						из Кореи</h1>
					<div class="b-intro__tags">
						<a href="/avto-iz-korei/catalog/">#Корея</a>
					</div>
					<p class="b-intro__feature-list">Корейские машины отличаются высоким уровнем безопасности, экономичными и долговечными двигателями, а также богатой комплектацией уже в базовой версии. При этом стоимость авто на 10-30% ниже, чем на рынке РФ. Мы подбираем, проверяем и привозим технику с гарантией юридической чистоты.</p>
					<a class="btn btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog">Бесплатная консультация</a>
				</div>
				<div class="col-lg-6 align-self-end">
					<img src="/images/avtomobili-iz-korei.png" class="b-intro__img" alt="Автомобили и спецтехника из Кореи">
				</div>
			</div>
		</div>
	</section>
	<section  id="cars-listing"  class="b-recently-bought b-car-items mb-0 pb-0">


			<div class="container">
				<div class="section-title">
					<h2>Купить автомобиль из Кореи</h2>
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
			<form method="get" action="/avto-iz-korei/catalog/">
				<?php
				get_template_part( 'template-parts/catalog-form-filter' );
				?>
			</form>
		</div></div>
	</section>
	
	<section class="content-plain pt-0">
	<div class="container">
	<h2>Авто из Кореи</h2>
	<p>В Корее можно купить автомобили различных марок с левым расположением руля, что считается самым удобным и правильным для нашего правостороннего движения, покупка авто в Корее отличается от покупки авто в Японии. В данном случае нет автомобильных аукционов, где вы получите аукционный лист со всеми замечаниями и информацией об авто, заместо этого есть специальная площадка (аналог нашего drom.ru или auto.ru) где размещаются автомобили для продажи. Наша команда, которая находится в Корее, подберет нужный вам автомобиль под все ваши запросы и пожелания, приедет на осмотр авто, снимет вам полноценный видео отчет автоподбора, пройдется толщинометром по всему кузову авто, детально снимет видео и сфотографирует со всех сторон, вы будете точно уверены и осведомлены что вы покупаете и решение о покупке всегда остается за вами, мы ничего вам не навязываем - мы лишь помогаем вам в покупке вашей мечты!</p>
	<h2>Не только корейские авто</h2>
	<p>Помимо популярных корейских автомобильных марок из Кореи можно выгодно привезти американские и европейские авто с левым рулем.</p>
	<div class="b-auto-marks">
		<a href="/avto-iz-korei/catalog/bmw/"><img src="/images/auto-logo-bmw.png" alt="BMW из Кореи"></a> <a href="/avto-iz-korei/catalog/audi/"><img src="/images/auto-logo-audi.png" alt="Audi из Кореи"></a> <a href="/avto-iz-korei/catalog/mercedes-benz/"><img src="/images/auto-logo-mercedes.png" alt="Mercedes из Кореи"></a> <a href="/avto-iz-korei/catalog/ford/"><img src="/images/auto-logo-ford.png" alt="Ford из Кореи"></a> <a href="/avto-iz-korei/catalog/volkswagen/"><img src="/images/auto-logo-volkswagen.png" alt="Volkswagen из Кореи"></a> <a href="/avto-iz-korei/catalog/jeep/"><img src="/images/auto-logo-jeep.png" alt="Jeep из Кореи"></a> <a href="/avto-iz-korei/catalog/honda/"><img src="/images/auto-logo-honda.png" alt="Honda из Кореи"> </a><a href="/avto-iz-korei/catalog/land-rover/"><img src="/images/auto-logo-land-rover.png" alt="Land Rover из Кореи"></a>
	</div>
	<div class="row">
		<div class="col-12 col-lg-6 ">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Выгоднее</h3>
					<p>Стоимость американского и европейского авто из Кореи будет значительно ниже, чем из США или Европы.</p>
				</div>
			</div>
		</div>
		<div class="col-12 col-lg-6">
			<div class="content-panel">
				<div class="feature-item__text">
					<h3>Быстрее</h3>
					<p>Покупка техники из Кореи происходит быстрее чем в Японии, сроки подготовки авто и его отправи в РФ тоже гораздо меньше.</p>
				</div>
			</div>
		</div>
	</div>
	</section>

	<?php get_template_part( 'template-parts/landing/b-blog-links', null, array( 'cluster' => 'koreya' ) ); ?>

</article>
<?php
$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/model_kr.js');
echo "<script>".$models."</script>";
?>



<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script>	
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-korea.js?v=1"></script>
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

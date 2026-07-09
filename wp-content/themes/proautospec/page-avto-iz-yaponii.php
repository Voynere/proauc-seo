<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

echo "<script>var course = ".$options->japan->course.";</script>";
?>
<article>
<section class="b-intro">
	<div class="container h-100">
		<div class="row h-100 align-items-center">
			<div class="col-12 col-md-12 col-lg-6">
				<h1>Автомобили<br>
					и спецтехника<br>
					из Японии</h1>
				<div class="b-intro__tags">
					<a href="/avto-iz-yaponii/catalog/">#Япония</a>
				</div>
				<p class="b-intro__feature-list">Наша команда поможет вам в покупке автомобиля или спецтехники из Японии. Чаще всего покупают авто "под полную пошлину" - это целый автомобиль, прошедший на территории РФ процедуру таможенной очистки и имеющий все необходимые документы для постановки на учёт в органах Госавтоинспекции РФ.</p>
				<a class="btn btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog">Бесплатная консультация</a>
			</div>
			<div class="col-lg-6 align-self-end">
				<img src="/images/avtomobili-iz-yaponii.png" class="b-intro__img" alt="Автомобили и спецтехника из Японии">
			</div>
		</div>
	</div>
</section>
<section  id="cars-listing"  class="b-recently-bought b-car-items mb-0 pb-0">
<div class="container">
<div class="section-title">
	<h2>Купить автомобиль из Японии</h2>
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
						<h3><a href="" class="car-model-name car-link"></a> <span class="car-model-specification"></span> </h3>
						<dl class="car-item__params">
						</dl>
						<a class="btn car-link" href="#" target="_blank">Подробнее</a>
						<div class="car-item__price">
							<span>Цена</span> <var class="car-price-value">
							<?php //echo number_format( $car->cena1, 0, '.', ' ' );?>
							</var>
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
			<form method="get" action="/avto-iz-yaponii/catalog/">
				<?php
				get_template_part( 'template-parts/catalog-form-filter' );
				?>
			</form>
		</div>
	</div>
</section>
<section class="content-plain pt-0">
	<div class="container">
		<h2>Авто и спецтехника под полную пошлину</h2>
		<p>Авто под полную пошлину — это целый автомобиль, прошедший на территории РФ полную процедуру таможенной очистки и имеющий все необходимые документы для беспрепятственной постановки на учет в органах Госавтоинспекции РФ. Так же это возможность приобрести автомобиль уникальной конфигурации, которую не всегда получится найти в диллерских центрах, потому что автомобили для внутреннего японского рынка отличаются от нашего внутреннего рынка.</p>
		<h2>Проходной или непроходной, что это значит?</h2>
		<p>Это означает, что срок эксплуатации машины влияет на пошлину и конечную стоимость автомобиля.</p>
		<p>Проходные — это автомобили, у которых с момента выпуска с завода прошло от 3 до 5 лет. На них установленная минимальная пошлина.<br>
			Непроходные — автомобили старше 5 лет и младше 3 лет. Их привезти будет дороже, пошлина значительно выше в сравнении с проходными.</p>
		<p>По действующим правилам таможенного оформления, ввозимые из-за границы иномарки в зависимости от их срока эксплуатации, делятся на несколько групп: до трех лет, от трех до пяти лет, свыше пяти лет.</p>
		<p>Так же не стоит забывать, что, чем старше автомобиль и мощнее двигатель, тем больше вы заплатите за пошлину.</p>
		<div class="b-auto-marks">
			<a href="/avto-iz-yaponii/catalog/bmw/"><img src="/images/auto-logo-bmw.png" alt="BMW из Кореи"></a> <a href="/avto-iz-yaponii/catalog/audi/"><img src="/images/auto-logo-audi.png" alt="Audi из Кореи"></a> <a href="/avto-iz-yaponii/catalog/mercedes/"><img src="/images/auto-logo-mercedes.png" alt="Mercedes из Кореи"></a> <a href="/avto-iz-yaponii/catalog/ford/"><img src="/images/auto-logo-ford.png" alt="Ford из Кореи"></a> <a href="/avto-iz-yaponii/catalog/volkswagen/"><img src="/images/auto-logo-volkswagen.png" alt="Volkswagen из Кореи"></a> <a href="/avto-iz-yaponii/catalog/jeep/"><img src="/images/auto-logo-jeep.png" alt="Jeep из Кореи"></a> <a href="/avto-iz-yaponii/catalog/honda/"><img src="/images/auto-logo-honda.png" alt="Honda из Кореи"> </a><a href="/avto-iz-yaponii/catalog/land-rover/"><img src="/images/auto-logo-land-rover.png" alt="Land Rover из Кореи"></a>
		</div>
	</div>
</section>

<section class="content-plain">
<div class="container">
<h2>Конструктор</h2>
<p>Этот вариант подойдёт тем, кто привозит автомобиль для разбора на запчасти. Основное отличие от распила в том, чтоу конструктора кузов остаётся целым, а не пилится по частям. Автомобиль разбирается на 3 основные части: двигатель, кузов, ходовую часть. Из документов только ГТД, на учёт его поставить невозможно.
<p>Ввоз авто конструктором отличается тем, что машина не разбирается на мелкие детали и не распиливается по частям. Перед прохождением таможни отделяются кузов, ходовая часть и двигатель. Для каждой из трех частей оформляется отдельная таможенная декларация. Таким образом, можно растаможить кузов целиком. На руки человек получает ГДТ на кузов и двигатель автомобиля.</p>
<p>Когда таможенные процедуры будут пройдены, машина вновь собирается, что делает удобной её транспортировку по территории РФ до конечного покупателя.</p>
<p>Конструктор на сегодняшний день один из самых популярных типов ввоза запчастей на территорию РФ. Для рамных автомобилей конструктор используется для перестановки кузова и двигателя, в таком случае номер рамы остается прежним.</p>
	</section>

	<?php get_template_part( 'template-parts/landing/b-blog-links', null, array( 'cluster' => 'yaponiya' ) ); ?>
</article>
<?php
$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/MODEL_ST.js');
echo "<script>".$models."</script>";
?>
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script> 
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-japan.js?v=1"></script>
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
<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

?>
<!doctype html>
<html <?php language_attributes(); ?> prefix="og: http://ogp.me/ns# article: http://ogp.me/ns/article# fb: http://ogp.me/ns/fb#">
<head>

	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>	
	<?php wp_head(); ?>
	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
	<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
	<link rel="shortcut icon" href="/favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
	<meta name="apple-mobile-web-app-title" content="Автомобили с аукционов" />
	<meta name="robots" content="max-image-preview:large" />

<meta charset="utf-8">

</head>
<?php 

$cache_key = $_SERVER["DOCUMENT_ROOT"]."/rates.json";
$rates = json_decode(file_get_contents($cache_key));  

global $options;
$options = json_decode('{ 
"korea" : { "sign":  "₩", "labelFrom": "из Кореи", "course": ' .$rates->Valute->KRW->Value / $rates->Valute->KRW->Nominal . ', "jsModelsFile": "model_kr.js", "baseSlug": "avto-iz-korei"},
"japan" : { "sign":  "¥", "labelFrom": "из Японии", "course": ' .$rates->Valute->JPY->Value / $rates->Valute->JPY->Nominal. ', "jsModelsFile": "MODEL_ST.js", "baseSlug": "avto-iz-yaponii"},
"china" : { "sign":  "¥", "labelFrom": "из Китая", "course": ' .$rates->Valute->CNY->Value / $rates->Valute->CNY->Nominal. ', "jsModelsFile": "model_che.js", "baseSlug": "avto-iz-kitaya"},
"hdm" : { "sign":  "¥", "labelFrom": "из Японии", "course": ' .$rates->Valute->JPY->Value / $rates->Valute->JPY->Nominal. ', "jsModelsFile": "hdm_new_types.js", "baseSlug": "spectehnika"}
} ');

?>

<?php if ( is_page() ) { $cssSlug = 'page-'.get_queried_object()->post_name; } else { $cssSlug = ""; } ?>
<body <?php body_class( $cssSlug ); ?>>
<?php wp_body_open(); ?>
<header class="masthead fixed-top">
	<div class="container">
		<div class="brandline">
			<a class="navbar-brand" href="/"><img src="/images/logo.svg" alt="ProAutoSpec"></a>
			<div class="header-phone order-lg-2">
				<small>Офис в г.Владивосток</small> <a href="tel:88002014340" target="_blank">8 800 201-43-40</a>
			</div>
			<div class="header-request order-lg-3">
				<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog">Задать вопрос </a>
			</div>
			<button class="navbar-toggler order-lg-4" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-expanded="false" aria-label="Показать меню">
			<div class="hamburger">
			</div>
			</button>
		</div>
		<nav class="navbar navbar-expand-lg">
			<div class="collapse navbar-collapse" id="main-nav">
				<ul class="navbar-nav main-menu">
				<?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container' => false,
                        'menu_class' => '',
                        'fallback_cb' => '__return_false',
                        'items_wrap' => '<ul id="%1$s" class="main-menu navbar-nav mx-lg-auto %2$s">%3$s</ul>',
                        'walker' => new bootstrap_5_wp_nav_menu_walker()
                    )
                );
                ?>
				
				
				
				
					
					<li class="dropdown"><a class="nav-link dropdown-toggle" data-bs-hover="dropdown" aria-haspopup="true" data-bs-auto-close="outside" aria-expanded="false" href="/avto-iz-yaponii/">Из Японии</a>
						<ul class="dropdown-menu  depth_0">
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-266"><a href="/avto-iz-yaponii/" class="dropdown-item">Заказать авто</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-266"><a href="/avto-iz-yaponii/catalog/" class="dropdown-item">Аукционы онлайн</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-266"><a href="/avto-iz-yaponii/statistika/" class="dropdown-item">Статистика продаж</a></li>
						</ul>					
					
					</li>
					<li><a class="nav-link" href="/avto-iz-korei/">Из Кореи</a></li>
					<li><a class="nav-link" href="/avto-iz-kitaya/">Из Китая</a></li>
					<li><a class="nav-link" href="/motorcycles/">Мотоциклы</a></li>
					<li class="dropdown"><a class="nav-link dropdown-toggle" data-bs-hover="dropdown" aria-haspopup="true" data-bs-auto-close="outside" aria-expanded="false" href="/spectehnika/">Спецтехника</a>
						<ul class="dropdown-menu  depth_0 spectehnika">
							<li><a href="/spectehnika/" class="dropdown-item nav-link--highlighted">Полный каталог</a></li>
							<li><a href="/spectehnika/gruzoviki/" class="dropdown-item">Грузовики</a></li>
							
							<li><a href="/spectehnika/avtokrany/" class="dropdown-item">Автокраны</a></li>
							<li><a href="/spectehnika/buldozery/" class="dropdown-item">Бульдозеры</a></li>
							<li><a href="/spectehnika/generatory-i-kompressory/" class="dropdown-item">Генераторы и компрессоры</a></li>
							
							<li><a href="/spectehnika/gusenicniye-krany/" class="dropdown-item">Гусеничные краны</a></li>
							<li><a href="/spectehnika/dorozhnay-tehnika/" class="dropdown-item">Дорожная техника</a></li>
						
							<li><a href="/spectehnika/samosvaly/" class="dropdown-item">Самосвалы</a></li>
							<li><a href="/spectehnika/selhoztehnika/" class="dropdown-item">Сельхозтехника</a></li>
							<li><a href="/spectehnika/traktory/" class="dropdown-item">Тракторы и комбайны</a></li>
							<li><a href="/spectehnika/ekskavatory/" class="dropdown-item">Экскаваторы</a></li>
							<li><a href="/spectehnika/manipulyatory-kmu/" class="dropdown-item">Манипуляторы (КМУ)</a></li>
							<li><a href="/spectehnika/avtobetononasosy-shvingi/" class="dropdown-item">Автобетононасосы (швинги)</a></li>
							<li><a href="/spectehnika/furgony/" class="dropdown-item">Фургоны</a></li>
							
							<li><a href="/spectehnika/drygoe/" class="dropdown-item">... другое</a></li>
							
							<?php
							/*wp_nav_menu(
								array(
									'theme_location' => 'secondary',
									'container' => false,
									'menu_class' => '',
									'fallback_cb' => '__return_false',
									'items_wrap' => '%3$s',
									
								)
							);*/
							?>							
						</ul>
					</li>
					<li class="dropdown"><a href="#" class="nav-link  dropdown-toggle" data-bs-hover="dropdown" aria-haspopup="true" data-bs-auto-close="outside" aria-expanded="false">О нас</a>
						<ul class="dropdown-menu  depth_0">
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-266"><a href="/kompaniya/" class="dropdown-item">Компания</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-266"><a href="/kontaktyi/" class="dropdown-item">Контакты</a></li>
						</ul>
					</li>
					<li class="dropdown d-none"><a href="/about/"  class="nav-link  dropdown-toggle" data-bs-hover="dropdown" aria-haspopup="true" data-bs-auto-close="outside" aria-expanded="false">Онлайн-каталог</a>
						<ul class="dropdown-menu  depth_0">
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Аукционы Кореи</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Авто из Японии</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Спецтехника из Японии</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Мотоциклы из Японии</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Статистика продаж Японии</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Каталог авто из Кореи</a></li>
							<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item nav-item-271"><a href="/shema-raboty/" class="dropdown-item ">Каталог авто из Китая</a></li>
						</ul>
					</li>
					<li><a class="nav-link nav-link--highlighted" href="/avto-v-nalichii/">🔥 Авто в наличии</a></li>					
				</ul>
				<div class="ms-auto">
					<small>Мы в социальных сетях</small>
					<ul class="social-list">
						<li><a href="https://t.me/proauc" target="_blank" rel="external nofollow"> <ins class="social-list_tg"></ins> </a></li>
						<li><a href="https://vk.com/proautospec" target="_blank" rel="external nofollow"> <ins class="social-list_vk"></ins> </a></li>
						<li><a href="https://www.youtube.com/@proautospec" target="_blank" rel="external nofollow"> <ins class="social-list_yt"></ins> </a></li>
						<li><a href="https://www.instagram.com/pro_auto_spec/" target="_blank" rel="external nofollow"> <ins class="social-list_ig"></ins> </a></li>
					</ul>
				</div>
			</div>

		</nav>
	</div>
	<div class="header-menu" id="mega-menu">
		<div class="container h-100 d-flex flex-column">
			<div class="row mt-auto">

				<div class="col-lg-12"><strong class="h2">Привезём "под ключ"</strong></div>
				<div class="col-lg-5">
					<ul>
						
						<li><a href="/avto-iz-yaponii/">Автомобили из Японии</a></li>
						<li><a href="/avto-iz-korei/">Автомобили из Кореи</a></li>
						<li><a href="/avto-iz-kitaya/">Автомобили из Китая</a></li>
						<li><a href="/motorcycles/">Мотоциклы из Японии</a></li>

					</ul>
				</div>
				<div class="col-lg-7">
					<ul>
						<li><a href="/spectehnika/">Спецтехника и&nbsp;грузовые автомобили</a></li>
						
						<li><a href="/avto-v-nalichii/" style="color:#F9D87B">Автомобили в наличии 🔥 </a></li>
					</ul>
				</div>
			</div>
			<div class="row mt-5 mb-auto">
				<div class="col-lg-5">
					<strong class="h2">Для покупателя</strong>
					<ul>
						<li><a href="/kak-chitat-aukczionnyj-list/">Как читать аукционный лист</a></li>
						
					</ul>
				</div>
				
				
				<div class="col-lg-7">
					<strong class="h2">О нас</strong>
					<ul class="">
						<li><a href="/kompaniya/">Компания</a></li>
						<li><a href="/kontaktyi/">Контакты</a></li>
						<li><a href="/kompaniya/#komanda">Наша команда</a></li>

					</ul>
				</div>
			</div>
		</div>
	</div>
</header>
<main>

<?php
// korea
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="pb-0">
<div class="container">
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
	</div>
	<h1 class="mb-4">Мотоциклы из Японии под заказ</h1>
	<p class="mb-4">Подбираем мотоциклы Honda, Yamaha, Kawasaki и Harley с японских аукционов под заказ с доставкой во Владивосток и другие города Дальнего Востока. Проверенная история, выкуп на площадке и оформление «под ключ» — как для <a href="/avto-iz-yaponii/">авто из Японии</a>.</p>
	<div class="b-cars-catalog-filter">
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
$models = $wp_filesystem->get_contents( get_home_path().'/api/cache/model_bike.js');
echo "<script>".$models."</script>";
?>

<script src="/wp-content/themes/proautospec/js/pagination/pagination.js"></script>

<link href="/wp-content/themes/proautospec/js/pagination/pagination.css" rel="stylesheet">
<script>

</script>
<script src="/wp-content/themes/proautospec/js/api/cars-catalog-filter.js"></script>
<script src="/wp-content/themes/proautospec/js/api/motorcycles-catalog.js?v=1"></script>

<section id="cars-listing" class="container mt-0 pt-4 motorcycles-listing">
	<p class="cars-listing__total mb-3">Всего найдено мотоциклов: <var></var></p>
	<div class="row">
		<div class="col-lg-3 d-none" id="car-item">
			<div class="car-item">
				<div class="car-item__pic">
					<a class="car-link" href="" target="_blank"><img class="car-img" src="" alt="Купить"></a>
				</div>
				<div class="car-item__desc">
					<h3><a href="" class="car-model-name car-link" target="_blank"></a>
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

<?php get_template_part( 'template-parts/landing/b-blog-links', null, array( 'cluster' => 'mototsikly' ) ); ?>

<?php if ( function_exists( 'proauc_render_catalog_blog_sidebar' ) ) { proauc_render_catalog_blog_sidebar(); }
get_footer(); ?>
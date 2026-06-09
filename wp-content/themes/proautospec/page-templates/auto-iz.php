<?php
/**
 * Template Name: Авто из ...
 *
 * Template for displaying a page just with the header and footer area and a "naked" content area in between.
 * Good for landingpages and other types of pages where you want to add a lot of custom markup.
 *
 * @package UnderStrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
get_header();
?>








<article class="mb-lg-5">
	<?php 
		$imageXl = get_field('services-introimg');
		$imageSm = str_replace("_xl", "_sm", $imageXl);
		if (! file_exists($_SERVER['DOCUMENT_ROOT'].$imageSm)) $imageSm = null;
	
	?>
<section class="b-intro<?php echo $imageSm ? '':' fallback-position';?>">

	<div class="b-intro__img-holder">
		<picture>
			<source media="(min-width: 900px)" srcset="<?php echo $imageXl; ?>"> <?php // type="image/webp""?>
			<?php if ($imageSm) echo '<source srcset="'.$imageSm.'">'; ?>
			
			<img src="<?php echo $imageXl; ?>" alt="<?php echo get_the_title(); ?>"> </picture>

		<div class="breadcrumbs__outer-container">
			<div class="container breadcrumbs">
				<?php if(function_exists('bcn_display')) {
					bcn_display();
				}?>
			</div>	
		</div>
	</div>
	<div class="container">
		<div class="row">
			
			<div class="col-lg-7">
				<h1><?php echo (!empty(get_field('services-title-html')) ? nl2br(get_field('services-title-html')) : get_the_title());?> <span>на Пхукете</span></h1>
				<p class="b-intro__lead-1"><?php echo nl2br(get_field('services-lead')); ?></p>
				<a href="#" class="btn btn-lg" data-bs-toggle="modal" data-bs-target="#order-dialog" data-title="Записаться на <?php echo mb_strtolower(get_the_title());?>">Записаться на занятия</a>
			</div>
		</div>
	</div>
	<div class="waves-holder">
		<svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
			<defs>
				<path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"></path>
			</defs>
			<g class="parallax">
				<use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(204,246,255,0.5" ></use>
				<use xlink:href="#gentle-wave" x="48" y="2" fill="rgba(204,246,255,0.4)"></use>
				<use xlink:href="#gentle-wave" x="48" y="4" fill="rgba(204,246,255,0.3)"></use>
				<use xlink:href="#gentle-wave" x="48" y="0" fill="#FFFFFF" stroke-width="3" stroke="#ffffff"></use>
			</g>
		</svg>
	</div>
	<a href="#services" id="scroll-down"></a> </section>
<div class="b-service-content" id="services">	
	<div class="container">
		<div class="col-md-10 offset-md-1 py-5">
		<?php	
			the_content();
			?>
		</div>		
	</div>		
</div>	
<?php 
$attachments = get_field('services-gallery');

if ( $attachments ){ ?>
<section class="mb-5">
	<div class="container">
		<h2 class="h3">Галерея</h2>
		<div class="b-gallery__grid">
			<div class="b-gallery__grid-sizer">
			</div>
	<?php    
		$i = 1;
		foreach ($attachments as $attachment ) {
			if ($attachment['id'] == get_post_thumbnail_id()) continue;
			$caption = get_the_title( $attachment['id'] );
			$thumbimgurl = wp_get_attachment_image_url( $attachment['id'], 'gallery-thumb', false);
			$fullurl = wp_get_attachment_image_url($attachment['id'], 'gallery-full', true ) ;


			echo '<a href="'.$fullurl.'" class="b-gallery__grid-item gallery-item">';

			?>

			   <img src="<?php echo $thumbimgurl;?>" alt="<?php echo !empty($caption) ? get_the_title().', '.$caption : get_the_title().', фото '.$i; ?>">

			</a>
			<?
			$i++;
		}
	?>
	</div>
	</div>
</section>   		
<?php       
} ?>


<noindex>
<?php if ( wp_get_post_parent_id( get_the_ID() ) == 7 ):?>

<?php get_template_part('template-parts/b-shop', null, array('for' => 'kids'));?>

<?php else:?>
<?php get_template_part('template-parts/b-shop', null, array('for' => 'adults'));?>

<?php endif;?>
</noindex>



		
<div class="b-why-shark mt-3 mt-lg-5">
	<div class="container">
		<h2>Почему выбирают нашу школу?</h2>
		<?php if ( wp_get_post_parent_id( get_the_ID() ) == 7 ):?>

		<div class="row">
			<div class="col-lg-3">
				<h3>Выездные уроки по&nbsp;всему Пхукету</h3>
				<p>Занимайтесь там, где вам удобно – дома, в гостинице, кондоминиуме или на вилле – тогда, когда вам удобно: бронируйте занятия наперёд или по факту.</p>
			</div>	
			<div class="col-lg-3">	
				<h3>Опытные тренеры по&nbsp;плаванию с&nbsp;навыком педагогики</h3>
				<p>У нас в команде нет случайных людей. Все инструкторы школы Shark - сертифицированные специалисты с солидным опытом работы с детьми.</p>
			</div>
			<div class="col-lg-3">	
				<h3>Проверенные методики, плюс&nbsp;индивидуальный подход</h3>
				<p>Каждое занятие ведётся по программе, однако корректируется с учётом возраста, возможностей, самочувствия и реакции ребёнка.</p>
			</div>
			<div class="col-lg-3">	
				<h3>Комфортные условия с&nbsp;акцентом на&nbsp;безопасность</h3>
				<p>Привезём нужный инвентарь, подберём лучшее место с акцентом на безопасность: ваш ребёнок всегда под присмотром тренера.</p>
			</div>
		</div>
		<?php else:?>
		<div class="row">
			<div class="col-lg-3">
				<h3>Персональные программы</h3>
				<p>Индивидуальные занятия и групповые тренировки адаптированы под ваши цели.</p>
			</div>	
			<div class="col-lg-3"> 
				<h3>Профессиональные тренеры</h3>
				<p>Сертифицированные специалисты научат плавать с нуля в безопасных условиях.</p>
			</div>
			<div class="col-lg-3"> 
				<h3>Уроки по всему Пхукету</h3>
				<p>Тренировки проходят в бассейнах, на виллах и в кондоминиумах.</p>
			</div>
			<div class="col-lg-3">
				<h3>Гибкий график</h3>
				<p>Выбирайте удобное время для занятий в комфортных условиях.</p>
			</div>
		</div>		
		<?php endif;?>
	</div>
</div>
	
</article>

<noindex>
<?php get_template_part('template-parts/b-testimonials');?>
</noindex>
<?php
get_footer();

<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>


<article class="mb-4 mb-lg-5">

  <div class="container">
	<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
		<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
	</div>
  </div>
	<div class="container">
	 <h1 class="mb-3 mb-lg-4"><?php echo get_the_title();?></h1>
 
	<?php 
	
	if ( have_posts() ) : 

		while ( have_posts() ) : the_post();
			the_content();
		endwhile;
	else :
		_e( 'Ничего не найдено', 'textdomain' );
	endif;
	?>
	</div>
</article>
<section class="mt-0 pt-0">
	<div class="container">
		<div class="b-form-consultation">
			<h2>Нужна консультация?</h2>
			<p>Оставьте бесплатную заявку, и мы свяжемся с вами в ближайшее время и подробно всё расскажем.</p>
			<?php echo apply_shortcodes( '[contact-form-7 id="8ea05cf" title="Бесплатная консультация"]' ); ?>
			<p class="text-policy mt-4">Нажимая на кнопку, Вы соглашаетесь с <a href="/privacy/" target="_blank">политикой обработки персональных данных</a></p>
		</div>
	</div>
</section>

<?php get_footer();

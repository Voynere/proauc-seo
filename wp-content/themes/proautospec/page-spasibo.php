<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package picostrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
 
?>


<section class="b-intro">
	<div class="container h-100">
		<div class="row h-100 align-items-center">
			<div class="col-12 col-md-6 col-lg-6">
				<h1 style="font-size:2.1rem;margin-bottom:2rem">Ваша заявка успешно принята!</h1>
				<p>В ближайшее время мы с Вами свяжемся и обязательно подберём для вас именно то, что вам нужно, с подробным расчетом стоимости.</p>
				<div class="b-intro__buttons">
					<a class="btn btn-lg" href="/">Вернуться на главную</a>
				</div>
			</div>
		</div>
</section>


<?php
get_footer();

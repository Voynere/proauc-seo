<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>
  
	<section class="b-intro">
		<div class="container h-100">
			<div class="row h-100 align-items-center">
				<div class="col-12 col-md-12 col-lg-11">
					<h1>Автомобили<br>
						и&nbsp;спецтехника<br>
						под заказ </h1>
					<div class="b-intro__tags">
						<a href="/avto-iz-yaponii/">#Япония</a><a href="/avto-iz-korei/">#Корея</a><a href="/avto-iz-kitaya/">#Китай</a>
					</div>
					<ul class="b-intro__feature-list">
						<li>Cпецтехника с гарантией прохождения таможни и выдачей ЭПТС</li>
						<li>Возможность покупки любых автомобилей из стран Азии</li>
						<li>Фиксированная комиссия за работу, без скрытых накруток, платежей и доплат</li>
						<li>Доставка вашей техники по РФ и СНГ</li>
						<li>Более 10 лет работаем с физлицами и юрлицами</li>
					</ul>
					<a class="btn btn-lg" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog">Бесплатная консультация</a>
				</div>
			</div>
		</div>
	</section>
	<section class="b-company-welcome">
		<div class="container">
			<div class="row">
				<div class="col-lg-8">
					<?php setlocale(LC_ALL, "ru_RU.UTF-8"); ?>					
					<h2 class="section-title"><em><?php echo strftime("%A", time());?></em> лучший день для&nbsp;покупки автомобиля</h2>
					<p>С момента образования нашей компании мы стабильно растём и развиваемся, работаем над сервисом, скоростью и качеством предоставляемых услуг, создавая лучшие условия для покупки автомобилей
						и спецтехники из стран Азии.</p>
					<p>В нашей работе приоритетом является индивидуальный подход, соблюдение договорных условий и, как наивысшая цель, довольный заказчик.
						Мы стремимся сделать сотрудничество с нашей компанией комфортным,
						а обслуживание внимательным и заботливым.</p>
					<p>С уважением, генеральный директор, Хлопотов Виктор Денисович.</p>
				</div>
				<div class="col-lg-4">
					<img src="/images/proautospec-director.png" class="img-fluid">
				</div>
			</div>
			<div class="row" style="margin-top:-3rem">
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Качество</h3>
						<p>Нам не всё равно, какой автомобиль Вы купите и в каком виде Вы его получите. Наша задача - обеспечить вам грамотный сервис и купить Вам авто в отличном состоянии и с прозрачной историей.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Стоимость</h3>
						<p>Мы не скрываем стоимость за свои услуги, никуда не прячем дополнительные расходы, которые потом положим к себе в карман, - мы за прозрачность и честность работы.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Прозрачность во всём</h3>
						<p>Мы предоставляем отчётность со всеми расходами по вашему автомобилю. Наш договор гарантирует Вам безопасность. Наша команда несёт полную ответственность за авто перед Вами.</p>
					</div>
				</div>
			</div>
		</div>
	</section>
	<section class="b-recently-bought b-car-items">
		<div class="container">
			<div class="section-title">
				<h2>Недавно купленные авто</h2>
				<p>Указаны итоговые стоимости недавно привезённых нами автомобилей и спецтехники со всеми расходами в г. Владивосток.</p>
			</div>
			<div class="recently-bought-slider__wrapper position-relative">
				<div class="swiper recently-bought-slider">
					<div class="swiper-wrapper row">
						<?php
						$args = array(
							'post_type' => 'avto',
							'posts_per_page' => 12,
							'category__in' => 5
						);

						$the_query = new WP_Query( $args );

						?>

						<?php if ( $the_query->have_posts() ) : ?>

							<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
								<div class="swiper-slide col-lg-3">
									<?php get_template_part('template-parts/loops/avto');?>
								</div>
							<?php endwhile; ?>

							<?php wp_reset_postdata(); ?>

						<?php endif; ?>
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
			<?php
				get_template_part( 'template-parts/landing/b-cta-whatsapp' );
			?>

		</div>
	</section>
	<section class="b-why-us">
		<div class="container">
			<h2 class="section-title">Почему выбирают нас</h2>
			<div class="row">
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Вся техника официально ставится на учёт</h3>
						<p>У каждой машины есть электронный ПТС, она проходит испытательную лабораторию. Всё абсолютно легально.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Привезём любой вид техники</h3>
						<p>От грузовика и мини-экскаватора до башенного крана и бурильной установки, от легкового седана до полноценного дома на колесах.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Если на рынке нет нужной техники, мы её соберем</h3>
						<p>Соберём технику под вашу задачу, скомбинировав шасси и нужный модуль: КМУ, реф.фургон и т.д.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Не являемся посредниками ни на одном из этапов</h3>
						<p>От подбора и покупки техники до растаможки и постановки на учет - все делаем сами.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Работаем больше 15 лет,
							а&nbsp;именно с 2010 года</h3>
						<p>За это время привезли и продали огромное количество спецтехники и автомобилей, уже 15 лет делаем наших клиентов счастливыми.</p>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="content-panel">
						<h3>Прозрачная цепочка этапов&nbsp;поставки</h3>
						<p>В любой момент мы знаем, где находится техника и когда она перейдёт на следующий этап поставки.</p>
					</div>
				</div>
			</div>
		</div>
	</section>
	<section class="b-cta b-cta-fullwidth">
		<div class="container">
			<div class="row">
				<div class="col-lg-6 b-cta__title">
					<h2 class="fs-2">Подберём 3-5 вариантов техники</h2>
					<p>Пройдите небольшой опрос и мы пришлём варианты <br>
						с ценами и характеристиками.</p>
					<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" data-title="Подобрать варианты техники">Подобрать варианты</a>
				</div>
				<div class="col-lg-6">
					<img class="img-fluid" src="/images/cta-autos.png" alt="Подбор автомобилей и техники">
				</div>
			</div>
		</div>
	</section>
	<section>
		<div class="container b-getting-auto">
			<div class="section-title">
				<h2>Схема работы</h2>
			</div>
			<div class="row">
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Бесплатная консультация <small>(1 час)</small></h3>
						<p>Подробно проконсультируем. Мы всегда искренне стараемся решить проблему.</p>
						<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" data-title="Заказать консультацию">Заказать консультацию</a>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Подбор 3-5 вариантов <small>(3-4 дня)</small></h3>
						<p>Не всегда получается 3-5, иногда меньше. В таком случае предложим альтернативу.</p>
						<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" data-title="Заказать подбор">Заказать подбор</a>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Договор и аванс <small>(2 часа)</small></h3>
						<p>Заключаем договор, в котором прописана вся информация, далее вносите аванс. Оплата на расчетный счет компании, всё прозрачно и никаких скрытых платежей.</p>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Выкуп техники <small>(3-10 дней)</small></h3>
						<p>Самостоятельно ищем нужный вам вариант автомобиля или спецтехники, осматриваем его, чтобы вы получили наилучший желаемый вариант, далее выкупаем технику.</p>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Доставка техники <small>(2-4 недели)</small></h3>
						<p>Без привлечения клиента решаем все вопросы и трудности, связанные с доставкой во Владивосток. Стараемся максимально быстро доставить вашу технику&nbsp;в&nbsp;РФ. </p>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Растаможка <small>(1-2 недели)</small></h3>
						<p>Со 100% вероятностью проходим таможню и оплачиваем пошлину. Ваша спецтехника или автомобиль в надёжных руках, никаких проблем не возникет.</p>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Прохождение лаборатории и получение ЭПТС <small>(2 дня)</small></h3>
						<p>Проходим необходимые по законодательству процедуры.</p>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="b-getting-auto__item content-panel">
						<h3>Передача или&nbsp;отправка <small>(1-14 дней)</small></h3>
						<p>Передаем технику лично во Владивостоке, либо отправляем любым удобным для вас способом в любую точку РФ или СНГ.</p>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="b-form-consultation">
				<h2>Нужна консультация?</h2>
				<p>Оставьте бесплатную заявку, и мы свяжемся с вами в ближайшее время и подробно всё расскажем.</p>
				<?php echo apply_shortcodes( '[contact-form-7 id="8ea05cf" title="Бесплатная консультация"]' ); ?>
				<p class="text-policy mt-4">Нажимая на кнопку, Вы соглашаетесь с <a href="/privacy/" target="_blank">политикой обработки персональных данных</a></p>
			</div>
		</div>
	</section>
	<section class="b-faq">
		<div class="container">
			<div class="section-title">
				<h2>Часто задаваемые вопросы</h2>
				<p>Время - самый ценный ресурс, поэтому специально для вас мы собрали часто задаваемые вопросы и ответы к ним.</p>
			</div>
			<div class="faq-accordion">
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false">Есть ли техника в наличии</h3>
					<div class="accordion-collapse collapse">
						<p>Ежемесячно мы привозим несколько единиц техники для продажи из наличия. Список такой техники постоянно меняется, т.к. она довольно быстро распродается. По текущей технике в наличии вы можете уточнить, позвонив нам, или оставив заявку на сайте. Возможно, у нас есть то, что вам нужно. </p>
					</div>
				</div>
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false">Цены указаны с ПТС?</h3>
					<div class="accordion-collapse collapse">
						<p>Да, в указанную цену ходит сама техника, доставка в РФ, растаможка, пошлина, утилизационный сбор, прохождение лаборатории, получение электронного ПТС и постановка на учет. 
							Хотите получить цены по интересующей вас технике? Звоните или оставляйте заявку.</p>
					</div>
				</div>
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false">Как привезти технику через вас?</h3>
					<div class="accordion-collapse collapse">
						<p>Для начала от вас нужна заявка с указанием, что именно вам нужно  Мы подбираем под ваш запрос несколько вариантов подходящей техники и делаем просчет ее привоза в РФ. Обсуждаем с вами каждый вариант. Если есть техника, которая вам подходит, мы заключаем договор и осуществляем необходимые действия, чтобы привезти вам технику. 
							Если у вас есть интерес к определенной технике - оставьте нам заявку на подбор и мы посмотрим, что можем вам предложить. </p>
					</div>
				</div>
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false">Есть ли доставка техники по РФ и СНГ?</h3>
					<div class="accordion-collapse collapse">
						<p>Да, мы осуществляем доставку техники в любой город РФ и СНГ. Стоимость доставки можем посчитать по вашему запросу. Позвоните нам или оставьте заявку.</p>
					</div>
				</div>
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false"> В какие сроки поставляется техника? </h3>
					<div class="accordion-collapse collapse">
						<p>От 1 до 4-х месяцев. В зависимости от специфики техники и от того, откуда именно мы будем везти технику.</p>
					</div>
				</div>
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false">Работаете с НДС?</h3>
					<div class="accordion-collapse collapse">
						<p>Да, можем привезти для вас технику как с НДС, так и с УСН</p>
					</div>
				</div>
				<div class="faq-item">
					<h3 class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="false">Можно привезти в лизинг?</h3>
					<div class="accordion-collapse collapse">
						<p>Да, мы можем привезти для вас технику в лизинг. Но не вся техника подходит под лизинг. Если хотите узнать подробнее, позвоните нам или оставьте заявку - и мы проконсультируем вас.</p>
					</div>
				</div>
			</div>
		</div>
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
	<section class="pt-0 mt-0">
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

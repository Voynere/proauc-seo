<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

//$archiveId = get_field('old-id'); 


// Loop through posts if there are any
if (have_posts()):
		while (have_posts()): the_post(); 
			
			
			

	

			
			
			$terms = get_the_terms(get_the_ID(), 'category');
			$categories = [];
			$isArchive = false;
			if( $terms ) {          
				foreach ($terms as $category) {
					if ($category->slug == 'archive') {
						$isArchive = true;
						break;
					}
				}       
			}
			if ($isArchive){
				$archiveBaseUrl = "/uploads/posts/";
				$paramsString = get_post_meta($post->ID, 'old-params', true);
				$paramsArr = explode ('||', $paramsString);
				$car = (object)[];
				foreach ($paramsArr as $param){
					$paramArr = explode ('|', $param);
					$car->{$paramArr[0]} = $paramArr[1];
				}
				$postThumbnail = explode("&#124;",$car->image1)[0];
				if (property_exists($car, 'gallery')){
					$galArr =  explode (",", $car->gallery);
					$gallery = [];
					$gallery[] = $postThumbnail;
					foreach ($galArr as $item){
						$gallery[] = explode ('&#124;', $item)[0];
					}
				}else{
					$gallery = [];
					$gallery[] = $postThumbnail;
				}
				$car->gallery = $gallery;
				
				

			}
		?>	
		
		
		<div class="container" style="margin-top:.5rem;">
			<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
				<?php // if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
			</div>
			<article class="single-auto">
				<div class="d-flex align-items-baseline">
					<h1><?php the_title(); ?><span><?php echo get_field('year');?></span></h1>
				</div>
				<div class="row mb-5">
					<div class="col-lg-7">
						<div class="swiper single-auto__slider-gallery">
						    <div class="swiper-wrapper">
								<?php if (!$isArchive): ?>
									<?php
									$images = get_field('photos');
									$size = 'medium'; // (thumbnail, medium, large, full or custom size)
									if( !$images ):
										$image_id = get_post_thumbnail_id();
										$images = [ 0 => array('id' => $image_id)];
									endif;
									if( $images ): ?>
										
										<?php
											$i = 1;
											foreach ($images as $image ) {
												$class = "";
												$caption = get_the_title( $image['id'] );
												$thumbimg = wp_get_attachment_image( $image['id'], 'medium_large', true, array( 'alt' => get_the_title().', фото '.$i) );
												$fullurl = wp_get_attachment_image_url($image['id'], 'full', true ) ;
												echo '<a href="'.$fullurl.'" class="' . $class . ' swiper-slide" data-slide="'.$i.'" target="_blank">' . $thumbimg . '</a>';
												$i++;
											}
										?>
									<?php endif; ?>
								<?php else: ?>
								<?php 
										$i = 1;
										foreach ($car->gallery as $image ) {
											$class = "";
											echo '<a href="'.$archiveBaseUrl.$image.'" class="' . $class . ' swiper-slide" data-slide="'.$i.'" target="_blank"><img src="'.$archiveBaseUrl.$image.'" alt="'.get_the_title().', фото '.$i.'"></a>';
											$i++;
										}
									?>
								<?php endif; ?>
                            </div>
							<div class="swiper-button-next">
							</div>
							<div class="swiper-button-prev">
							</div>							
                        </div>
						<div class="gallery single-auto__slider-nav">
							<?php if (!$isArchive): ?>
								<?php 
								$images = get_field('photos');
								$size = 'medium'; // (thumbnail, medium, large, full or custom size)
								if( $images ): ?>
									<?php    
										$i = 1;
										foreach ($images as $image ) {
											$class = "";
											$caption = get_the_title( $image['id'] );
											$thumbimg = wp_get_attachment_image( $image['id'], 'medium', true, array( 'alt' => get_the_title().', фото '.$i) );
											$fullurl = wp_get_attachment_image_url($image['id'], 'full', true ) ;
											echo '<a href="'.$fullurl.'" class="' . $class . ' grid-item gallery-item" data-slide="'.$i.'">' . $thumbimg . '</a>';
											$i++;
										}
									?>
								<?php endif; ?>
							<?php else: ?>
							<?php 
									$i = 1;
									foreach ($car->gallery as $image ) {
										$class = "";
										echo '<a href="'.$archiveBaseUrl.$image.'" class="' . $class . ' grid-item gallery-item" data-slide="'.$i.'"><img src="'.$archiveBaseUrl.$image.'" alt="'.get_the_title().', фото '.$i.'"></a>';
										$i++;
									}
							?>
							<?php endif; ?>								
						</div>	
					</div>
					<div class="col-lg-5">
						<div class="single-auto__params">
							
							<?php if (!$isArchive): ?>
								<dl>
									<?php $props = get_field('properties'); ?>
									<dt>Год</dt><dd><?php echo $props['year'];?></dd>
									<dt>Объём</dt><dd><?php echo $props['capacity'];?> л</dd>
									<dt>Пробег</dt><dd><?php echo number_format((int) $props['mileage'], 0, '.', ' ' );?> км</dd>
									<dt>Тип двигателя</dt><dd><?php echo $props['engine-type']['label'];?></dd>
									<dt>Тип привода</dt><dd><?php echo $props['drive-type']['label'];?></dd>
									<?php if( have_rows('parameters') ):?>
										<dl>
										<?php while ( have_rows('parameters') ) : the_row();?>
											<dt><?php echo get_sub_field('param-name')['label'];?>:</dt><dd><?php echo get_sub_field('param-value');?></dd>
										<?php endwhile;?>
										</dl>
									<?php endif; ?>									
									<dt class="single-auto__price">Конечная<br>стоимость</dt><dd class="single-auto__price"><?php echo esc_html( proautospec_avto_price_html( $props['price'] ?? 0 ) ); ?></dd>
								</dl>
							<?php else: ?>
								<dl>
									<dt>Год</dt><dd><?php echo $car->god;?></dd>
									<dt>Объём</dt><dd><?php echo $car->ob;?> л</dd>
									<dt>Тип двигателя</dt><dd><?php echo $car->dvig;?></dd>
									<dt>Тип привода</dt><dd><?php echo $car->priv;?></dd>
									<dt>Пробег</dt><dd><?php echo number_format($car->probeg, 0, '.', ' ' );?> км</dd>
									<dt>Кузов</dt><dd><?php echo $car->kuzov;?></dd>
									<?php if( have_rows('parameters') ):?>
										<dl>
										<?php while ( have_rows('parameters') ) : the_row();?>
											<dt><?php echo get_sub_field('param-name')['label'];?>:</dt><dd><?php echo get_sub_field('param-value');?></dd>
										<?php endwhile;?>
										</dl>
									<?php endif; ?>									
									<dt class="single-auto__price">Конечная<br>стоимость</dt><dd class="single-auto__price"><small>от </small><?php echo number_format( $car->cena1, 0, '.', ' ' );?> ₽</dd>
								</dl>
							<?php endif; ?>

							<div class="single-auto__buttons">
								<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" class="btn btn-white" data-model="<?php the_title();?>">Хочу купить</a>
							</div>								
							
						</div>
						
					</div>
				</div>
			

			</article>
	</div>
				<?php
				endwhile;

			?>	





	
<?php 
	else :
		_e( 'Sorry, no posts matched your criteria.', 'picostrap5' );
	endif;
?>


<?php get_footer();
?>
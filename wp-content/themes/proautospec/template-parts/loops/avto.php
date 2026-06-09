<?php
	$car = (object)[];
	$archiveId = get_field('old-id'); 

?>


<?php 

	

	$cat = get_query_var( 'category_name' );
	
	$props = get_field('properties');
	$car->title = get_the_title();
	
	if ($archiveId){
		$archiveBaseUrl = "/";
		$img = explode("|", get_field('old-photos'))[0];
		if ($img)
			$car->image = "/".pathinfo($img)['dirname'] . "/thumb/" . pathinfo($img)['filename'] . ".jpg";
		else 
			$car->image = "/images/no-photo.png";
		//$car->image = "/".explode("|", get_field('old-photos'))[0];
		$car->capacity = $props['capacity'];
		$car->permalink = "/".get_field('old-slug')."/";
	} else {
		$car->image = get_the_post_thumbnail_url( get_the_ID(), 'card-thumbnail');
		$car->capacity = $props['capacity']."л";
		$car->permalink = get_the_permalink();
	}
?>

	<div class="car-item">
		<div class="car-item__pic">
			<a href="<?php echo $car->permalink;?>"><img src="<?php echo $car->image; ?>" alt="Купить <?php echo $car->title;?>"></a>
		</div>
		<div class="car-item__desc">
			<h3><a href="<?php echo $car->permalink;?>"><?php echo $car->title;?></a></h3>
			<dl class="car-item__params">
				<?php if ($props['year']):?>
					<dt>Год</dt>
					<dd><?php echo $props['year'];?></dd>
				<?php endif;?>
				<?php if ($props['engine-type']):?>
					<dt>Двигатель</dt>
					<dd><?php echo $props['engine-type']['label'];?></dd>
				<?php endif;?>				
				<?php if ($props['capacity']):?>
					<dt>Объём</dt>
					<dd><?php echo $car->capacity;?></dd>
				<?php endif;?>
				<?php if ($props['drive-type']):?>
					<dt>Привод</dt>
					<dd><?php echo $props['drive-type']['label'];?></dd>
				<?php endif;?>		
				<?php if ($props['mileage']):?>
					<dt>Пробег</dt>
					<dd><?php echo number_format( (int) $props['mileage'], 0, '', ' ' );?> км</dd>
				<?php endif;?>
				<?php if ($props['grade']):?>
					<dt>Оценка</dt> 
					<dd><?php echo $props['grade'];?></dd>
				<?php endif;?>
			</dl>
			<div class="car-item__price">
				<span>Цена в РФ</span> <var><?php echo number_format( $props['price'], 0, '.', ' ' );?> ₽</var>
			</div>
			<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" data-model="<?php echo $car->title;?>">Хочу похожий</a>
		</div>
	</div>

<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;




$car = (object)[];
$archiveId = get_field('old-id'); 




global $wp;

if (array_key_exists('hdm-lot', $wp->query_vars) && isset($wp->query_vars['hdm-lot'])){
	$lot = $wp->query_vars['hdm-lot'];
	$slug = $wp->query_vars['hdm-slug'];
	
	$carExists = 0;

	$content=file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-hdm.php?client_ip=".USER_IP."&code=".$lot);
	$data=json_decode($content);
	//var_dump($data);
	//exit;
	
	if (@!empty($data->autos[0])){
		
		
		$carExists = 1;
		
		$car = $data->autos[0];
		$car->fromAPI = true;
		$labelledProps = [  
			'Лот' => $car->lot,
			'Марка' => $car->marka_name,
			'Модель' => $car->model_name,	
			'Серия' => $car->grade,
			'Год' => $car->year,			
			'Аукцион' => $car->auction,
			'Дата аукциона'  => $car->auction_date,			
			'Группа' => $car->group,
			'Тип' => $car->category,


			'КПП' => $car->kpp,
			'Привод' => $car->priv,
			'Пробег' => $car->mileage,
			'Оценка' => $car->rate,
			'Серийный номер' => $car->serial
		];
		$car->price = number_format( (float) $car->price, 0, '.', ' ' ).' ¥';					
		/*			
			  'id' => string 'e0i7zheNPuZIU' (length=13)
			  'lot' => string '252K0468' (length=8)
			  'auction_date' => string '2025-02-28 04:16:00' (length=19)
			  'is_stat' => string '0' (length=1)
			  'auction' => string 'www.jencorp.net' (length=15)
			  'group' => string 'heavy construction machinery' (length=28)
			  'category' => string 'Other' (length=5)
			  'gg' => string '1' (length=1)
			  'cc' => string '87' (length=2)
			  'marka_id' => string '10' (length=2)
			  'marka_name' => string 'KOMATSU' (length=7)
			  'model_name' => string 'BZ200-1' (length=7)
			  'year' => string '' (length=0)
			  'grade' => string '' (length=0)
			  'kpp' => string '' (length=0)
			  'priv' => string '35054' (length=5)
			  'mileage' => string '' (length=0)
			  'rate' => string '' (length=0)
			  'volume' => string '' (length=0)
			  'price' => string '1750000' (length=7)
			  'finish' => string '' (length=0)
			  'status' => string '' (length=0)
			  'serial' => string '1037' (length=4)
			  'images' => string 'http://8.ajes.com/imgs/22eDxgm26jATfh2SbxpvYEeVBs6XxmxQQDoy4uEOzx0FbNmaLcuL7L7iwusHxUPVGB0KaLekU8FR-e0i7zheNPuZIU#http://8.ajes.com/imgs/22eDxgm26jATfh2SbxpvYEeVBs6XxmxQQDoy4uEOzx0FbNmaLcuL7L7iwusHxUPVGB0Kcu2oQ0Vp-e0i7zheNPuZIU#http://8.ajes.com/imgs/22eDxgm26jATfh2SbxpvYEeVBs6XxmxQQDoy4uEOzx0FbNmaLcuL7L7iwusHxUPVGB0KcwrSwlfu-e0i7zheNPuZIU#http://8.ajes.com/imgs/22eDxgm26jATfh2SbxpvYEeVBs6XxmxQQDoy4uEOzx0FbNmaLcuL7L7iwusHxUPVGB0KcySmcwzz-e0i7zheNPuZIU#http://8.ajes.com/imgs/22eDxgm26jATfh2SbxpvYEeVBs6XxmxQQ'... (length=7865)
			  'fordebug' => string '2025-02-20 05:40:52' (length=19)
			  'info' => string 'a:3:{s:7:"Feature";s:0:"";s:7:"Comment";N;s:13:"Delivery Yard";s:24:"Consignors Yard(Arrived)";}' (length=96)					

		*/
		
		
		//$content = file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-hdm.php?code=".$car->id);
		//$data = json_decode($content);
		//$car = $data->autos[0];

		$car->title = $car->marka_name.' '.$car->model_name;
		try {
			$historyLinks = json_decode(file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-lists.php?car-id=".$car->id."&list=history"));
			$inspectionLinks = json_decode(file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-lists.php?car-id=".$car->id."&list=inspection"));
		} catch (Exception $e) {
			$historyLinks = (object) array(	0 => '', 1 => '');
			$inspectionLinks = (object) array(	0 => '', 1 => '');
		}

		
		$nameInUrl = $car->marka_name .' '. $car->model_name;
		

		
		$images = str_replace('&h=50', '', $car->images);

		$images = str_replace('#', '|', $images);
		$car->images = $images;
		
		
		
		$grade = preg_replace_callback(
							'/&#(\d+);/',
							function ($matches) {
								return "";
							},
							$car->grade
						);	
		$nameInUrl = $car->marka_name .'-'. $car->model_name .'-'.$grade;
		$nameInUrl = preg_replace( '/[^a-zA-Z0-9\s-]/','', $nameInUrl);
		$nameInUrl = trim ($nameInUrl);
		$nameInUrl = preg_replace( '/\s+/', '-', $nameInUrl);
		$nameInUrl = preg_replace( '/-+/', '-', $nameInUrl);
		$nameInUrl = strtolower( rtrim( $nameInUrl, '-') );			
		
		$car->canonicalUrl = '/spectehnika/'. $nameInUrl . '_' . $lot . '/';
		
		$currentUrl = $_SERVER['REQUEST_URI'];
		if ($currentUrl != $car->canonicalUrl){
			$urlparts = wp_parse_url(home_url());
			header("HTTP/1.1 301 Moved Permanently"); 
			header("Location: ". $urlparts['scheme']."://".$urlparts['host'].$car->canonicalUrl); 
			exit(); 				
		}
				
		function regenerate_breadcrumbs( $trail ) {
				

		}
		
		add_action( 'bcn_after_fill', function($trail) use ( $car) {
			global $wpdb;
			$bc_home = array_pop($trail->breadcrumbs);
			$trail->breadcrumbs = [];
		
			$bc_item = new bcn_breadcrumb();
			$bc_item->set_title($car->title);
			$bc_item->set_url(site_url().$car->canonicalUrl);
			$bc_item->set_linked(false);
			$trail->breadcrumbs[] = $bc_item;
			
			$bc_item = new bcn_breadcrumb();
			$bc_item->set_title($car->category);
			
			$type = $wpdb->get_row('SELECT * FROM wp_api_hdm_types WHERE lcase(`name_en`) = "'.strtolower($car->category).'"');
			$group = $wpdb->get_row('SELECT * FROM wp_api_hdm_groups WHERE id = '.$type->group_id);
	
			
			$bc_item->set_url(site_url().'/spectehnika/'.$group->slug.'/'.strtolower( str_replace(' ', '-', $car->category).'/' ) );
			
			$bc_item->set_linked(true);

			$trail->breadcrumbs[] = $bc_item;
			
			$bc_item = new bcn_breadcrumb();
			$bc_item->set_title('Спецтехника');
			$bc_item->set_linked(true);
			$bc_item->set_url(site_url().'/spectehnika/');

			$trail->breadcrumbs[] = $bc_item;
			$trail->breadcrumbs[] =	$bc_home;		
				// Add as second item in list.
				//array_splice( $trail->breadcrumbs, 1, 0, array( $magazine_item ) );
			
			return $trail;			
			
		});


		
	}else{
		$carExists = 0;

		$car = (object) array(
			'marka_name' => 'Этот лот уже продан',
			'model_name' => '',
			'grade' => ''
		);
	}





	
}else if (array_key_exists('hdm-cat', $wp->query_vars) && isset($wp->query_vars['hdm-cat'])){
	$car->fromAPI = false;
	$cat = $wp->query_vars['hdm-cat'];
	$slug = $wp->query_vars['hdm-slug'];
	//$url = "spectehnika/".$cat."/".$slug."/";
	$carExists = 1;
	
	
	
	
	$args = array(
		'posts_per_page'    => -1,
		'post_type' => 'avto',
		'meta_query'    => array(
        array(
            'key'       => 'old-slug',
            'value'     => "spectehnika/".$cat."/".$slug,
            'compare'   => '='
        ))
	);

	$the_query = new WP_Query( $args );
	$the_query->the_post();
	
	
	$car->props = get_field('properties');
	$props = $car->props;
	
	$car->title = get_the_title();

	$car->images = get_field('old-photos');
	$car->price = number_format( (float) $car->props['price'], 0, '.', ' ' ).' ₽';

	/*
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: ". $urlparts['scheme']."://".$urlparts['host'].'avto-iz-korei'); 
	exit(); */
	$car->seoDesc = get_field('seo-description');
	$car->seoTitle = get_field('seo-title');
	

	
	
}



ob_start();
get_header();
$header = ob_get_clean();

$header = preg_replace('#<title>(.*?)<\/title>#', '<title>Купить '.$car->title.'</title>', $header);
echo $header;









// Loop through posts if there are any
if ($carExists == 1):

		?>	
		
		
		<div class="container" style="margin-top:.5rem;">
			<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
				<?php if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
			</div>
			<?php /*
			<div class="d-none">
			<pre>
			<?php print_r($car);?>
			</pre>
			
			</div>
			*/ ?>
			<article class="single-auto">
				<div class="d-flex align-items-baseline">
					<h1><?php echo $car->title;?></h1>
				</div>
				<div class="row mb-0">
					<div class="col-lg-6">
						<div class="swiper single-auto__slider-gallery">
						    <div class="swiper-wrapper">

									<?php
									$images = explode("|", $car->images);
									
									if( $images ): ?>
										
										<?php
											$i = 1;
											$image0 = array_shift($images);
											array_push($images, $image0);
											foreach ($images as $image ) {
												$class = "";
												if (! $car->fromAPI){
													$thumbimg = "/".pathinfo($image)['dirname'] . "/thumb/" . pathinfo($image)['filename'] . ".jpg";
											
													$fullimg = "/".$image;
												}else{
													$thumbimg = $image;
													$fullimg = $image;
												}
												echo '<a href="'.$fullimg.'" class="' . $class . ' swiper-slide" data-slide="'.$i.'" target="_blank"><img src="' . $thumbimg . '" alt=""></a>';
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

		
								<?php 
								
								
								if( $images ): ?>
									<?php    
										$i = 1;
										foreach ($images as $image ) {
											$class = "";

											//$thumbimg = str_replace( '&h=50', '&w=320', $image);
												if (! $car->fromAPI){
													$thumbimg = "/".pathinfo($image)['dirname'] . "/thumb/" . pathinfo($image)['filename'] . ".jpg";
											
													$fullimg = "/".$image;
												}else{
													$thumbimg = $image."&w=320";
													$fullimg = $image;
												}
											
											echo '<a href="'.$fullimg.'" class="' . $class . ' grid-item gallery-item" data-slide="'.$i.'"><img src="' . $thumbimg . '" alt=""></a>';
											$i++;
										}
									?>
								<?php endif; ?>
											
						</div>	
					</div>
					<div class="col-lg-6">
						<div class="single-auto__params">
							
			
							<dl>
								<?php if ($car->fromAPI):?>
									<?php foreach ($labelledProps as $label => $value):?>
										<?php if (!empty($value)):?>
											<dt><?php echo $label;?></dt>
											<dd><?php echo $value;?></dd>
										<?php endif;?>
									<?php endforeach; ?>
								<?php else: ?>
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
										<dd><?php echo $props['capacity'];?></dd>
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
									<?php if (property_exists($car, 'auction_date')):?>
										<dt>Дата торгов:</dt><dd><?php echo $car->auction_date;?></dd>
									<?php endif;?>		
								<?php endif; ?>
								<?php if ($car->price):?>								
									<dt class="single-auto__price">Стартовая цена</dt><dd class="single-auto__price"><?php echo $car->price;?></dd>
								<?php endif; ?>
								
								
								
							</dl>

							<div class="single-auto__buttons">
								<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" class="btn btn-white" data-model="<?php echo $car->title;?>">Хочу купить</a>
							</div>

						</div>
						
					</div>
				</div>
			</article>
	</div>
				<?php


			?>	


<?php 
	else :
		?>
	<section>
		<div class="container">
			<h1>Лот продан</h1>
			<p>Извините, эта позиция уже продана. Но у нас есть много других.</p>
		</div>
	</section>		
		<?php
	endif;
?>
<?php /*
	<script>
		const domLoaded = new Promise((resolve) => {
			document.addEventListener('DOMContentLoaded', resolve);
		});

		Promise.all([domLoaded]).then((results) => {
			jQuery('.btn-car-history').on('click', function (e) {
				e.preventDefault();
				jQuery.ajax({
					url: '/api/get-lists.php?car-id=<?php echo $car->id; ?>&list=history',
					method: 'get',
					dataType: 'html',
					success: function (data) {
						//var decoded_data = jQuery("<div/>").html(data).text();
						//console.log(decoded_data.length);
						if  (!jQuery.trim(data)) {


							jQuery('#history-raw-html').html('<p class="p-3">Извините, данных по истории нет.</p>')
						} else {
							const obj = JSON.parse(data);
							console.log(obj[0]);
							console.log(obj[1]);
							jQuery('#history-raw-html').html(data);
						}
					}
				})

			});
			jQuery('.btn-car-inspection').on('click', function (e) {
				e.preventDefault();
				jQuery.ajax({
					url: '/api/get-lists.php?car-id=<?php echo $car->id; ?>&list=inspection',
					method: 'get',
					dataType: 'html',
					success: function (data) {
						if (!jQuery.trim(data)) {
							jQuery('#inspection-raw-html').html('<p class="p-3">Извините, данных по инспекции нет.</p>')
						} else {
							const obj = JSON.parse(data);
							console.log(obj);
							jQuery('#inspection-raw-html').html(data);
						}
					}
				})

			});
		});

	</script>
 */
?>

<?php get_footer();?>
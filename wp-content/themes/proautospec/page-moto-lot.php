<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



global $wp;
if (array_key_exists('moto-lot', $wp->query_vars) && isset($wp->query_vars['moto-lot'])){
	$lot = $wp->query_vars['moto-lot'];
	$slug = $wp->query_vars['moto-slug'];

	$carExists = 0;

	$content=file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-motorcycles.php?client_ip=".USER_IP."&code=".$lot);
	$data=json_decode($content);
	//var_dump($data);
	//exit;
	
	if (@!empty($data->autos[0])){
		$car = $data->autos[0];
		$content = file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-motorcycles.php?client_ip=".USER_IP."&code=".$car->id);
		$data = json_decode($content);
		$car = $data->autos[0];
		$labelledProps = [  
			'Лот' => $car->lot,
			'Марка' => $car->marka_name,
			'Модель' => $car->model_name,	
			'Серия' => $car->grade,
			'Серийный номер' => $car->serial,
			'Цвет' => $car->color,
			'Год' => $car->year,			
			'Аукцион' => $car->auction,
			'Дата аукциона'  => $car->auction_date,			

			
			'Объём двигателя' => $car->eng_v,
			'КПП' => $car->kpp,
			'Привод' => $car->priv,
			'Пробег' => $car->mileage,
			'Общая оценка' => $car->rate,
			'Оценка рамы' => $car->rate_frame,		
			'Оценка электроники' => $car->rate_el,
						
			'Оценка внешнего вида' => $car->rate_ext,		
			'Оценка передняя часть' => $car->rate_front,
			'Оценка задняя часть' => $car->rate_rear,



			//'Описание' => stripcslashes($car->info)
		];
		
		
	/*	
		  ["id"]=>
  string(9) "774439784"
  ["auction_date"]=>
  string(19) "2025-03-05 00:00:00"
  ["lot_num"]=>
  string(4) "0210"
  ["auction"]=>
  string(22) "BDS Kantou (Wednesday)"
  ["auction_id"]=>
  string(1) "1"
  ["marka_id"]=>
  string(2) "50"
  ["model_id"]=>
  string(5) "10040"
  ["marka_name"]=>
  string(6) "Ducati"
  ["model_name"]=>
  string(13) "DUCATI 1198 S"
  ["year"]=>
  string(4) "2012"
  ["grade"]=>
  string(0) ""
  ["mileage"]=>
  string(4) "5932"
  ["mil_note"]=>
  string(6) "5,932K"
  ["eng_v"]=>
  string(4) "1198"
  ["color"]=>
  string(13) " WHITE | RED "
  ["rate_eng"]=>
  string(1) "3"
  ["rate_front"]=>
  string(1) "4"
  ["rate_ext"]=>
  string(1) "3"
  ["rate_rear"]=>
  string(1) "4"
  ["rate_el"]=>
  string(1) "5"
  ["rate_frame"]=>
  string(1) "5"
  ["rate"]=>
  string(1) "4"
  ["start"]=>
  string(1) "0"
  ["finish"]=>
  string(0) ""
  ["status"]=>
  string(9) "available"
  ["inspection"]=>
  string(7) "2069.01"
  ["serial"]=>
  string(17) "ZDMH704AA9B026277"
  ["images"]=>
  string(1109) "http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZejYKFS2HP3zZMAYy095LJ-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZeWWYrQGeeO2uhhnUjjJYs-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZfzVcdPkKEzsYLXNgtvpbb-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZgcTpZNZh5kUtgEcCDH5nU-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZgPRDLMDNu7lXLkBYNSJAD-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZhsPRxLijTRNsg22kY5oNm-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZi6O6jJWQjDeWKHqH9g516-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZiIMj6IBmJoGrfnQ4irJcO-7#http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZjlKwRHfT0a8VK5fpsDopx-7#http://8.ajes.com/imgs/Igw5IYO4ZLdDu2ORC5slOGkABEHCDhwEKbh3ZO1p0auhvpZui84ml3uV2tKOAs2PZesS15bK24Gu5fpHqWOsh-7"
  ["image0"]=>
  string(110) "http://8.ajes.com/imgs/4p8eFC4QLFMJXxwwJQNS9FpqF498JWzTAW75ZZ5NCRgDv2ZXyMez7o3nsJd9EXnZjYIKDFUpyVzqeKELCP4Cg-7"
  ["info"]=>
  string(4200) "{"BDS report ":" exterior  after market .ETC attaching . each place  scratch . paint .."," exhibition  shop ..":"","videos":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/movie_engine\/021020250305_r.mp4#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/movie_engine\/021020250305_l.mp4"," successful bid price range information ":". Ducati 1198S.4 point  past  one month . successful bid price range information  number of successful bid 0 pcs  the lowest price 0 thousand  jpy . the highest price 0 thousand  jpy ","rate":[{"txt":"E.G:3# engine - rust , cover - after market , oil leaks - under  large , radiator - rust . with cover , cab -, clutch -, cell -,-,-,-","img":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_010_01.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_010_02.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_010_03.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_010_04.jpg"},{"txt":"F Pair:4# outer .- sticker  crack , inner -, stem - rust . top bridge  scratch , steering wheel -, wheel - all paint , brake -, tire -,-,-,-","img":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_020_01.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_020_02.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_020_03.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_020_04.jpg"},{"txt":"Exterior:3# upper -, center -, under - scratch  many  paint ., side -, tanker - pad  attaching , seat -, tail - scratch , front  fender -, back  fender -, screen -","img":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_030_01.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_030_02.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_030_03.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_030_04.jpg"},{"txt":"R Pair:4# shock -, Swing Arm - scratch , chain -, sprocket -, wheel - all paint , brake -, tire -,-,-,-","img":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_040_01.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_040_02.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_040_03.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_040_04.jpg"},{"txt":"Electro:5# key -2, meter -, turn signal - scratch  installation defectiveness . stay  crack , light -, battery -, horn -, brake  lamp -, mirror - paint . discoloration , muffler - scratch , exhaust pipe - rust  many . cover  scratch","img":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_050_01.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_050_02.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_050_03.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_050_04.jpg"},{"txt":"Chassis:5# main  frame - scratch  small , down  tube -, stopper - dent , seat rail -, step - scratch , stand - scratch ,-,-,-,-","img":"https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_060_01.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_060_02.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_060_03.jpg#https:\/\/bdsc.jupiter.ac\/auctiondata\/bds\/disp\/bds\/20250305\/image_item\/021020250305_060_04.jpg"}],"list":"C - sticker  crack . D - all paint . E - rust . F - pad  attaching . G - paint . scratch . H - scratch  small . J - scratch  rust  many . cover  scratch . K - all paint"}"
}
*/		
		
		try {
			$historyLinks = json_decode(file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-lists.php?car-id=".$car->id."&list=history"));
			$inspectionLinks = json_decode(file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-lists.php?car-id=".$car->id."&list=inspection"));
		} catch (Exception $e) {
			$historyLinks = (object) array(	0 => '', 1 => '');
			$inspectionLinks = (object) array(	0 => '', 1 => '');
		}
		$carExists = 1;
		
		$nameInUrl = $car->marka_name .' '. $car->model_name;

		$grade = preg_replace_callback(
							'/&#(\d+);/',
							function ($matches) {
								return "";
							},
							$car->grade
						);	
		$nameInUrl =  $car->marka_name .'-'. $car->model_name .'-'.$grade;
		$nameInUrl = preg_replace( '/[^a-zA-Z0-9\s-]/','', $nameInUrl);
		$nameInUrl = trim ($nameInUrl);
		$nameInUrl = preg_replace( '/\s+/', '-', $nameInUrl);
		$nameInUrl = preg_replace( '/-+/', '-', $nameInUrl);
		$nameInUrl = strtolower( rtrim( $nameInUrl, '-') );			
		$canonicalUrl = '/motorcycles/'. $lot . '-' .$nameInUrl . '/';		
		
		$currentUrl = $_SERVER['REQUEST_URI'];
		
		if ( strstr ($car->model_name, $car->marka_name) ) {
			$car->title = $car->model_name;
		} else{
			$car->title = $car->marka_name . ' ' . $car->model_name;
		}
					

		$car->canonicalUrl = '/motorcycles/'. $lot . '-' .$nameInUrl . '/';
		
		add_action( 'bcn_after_fill', function($trail) use ( $car) {
			$bc_home = array_pop($trail->breadcrumbs);
			$trail->breadcrumbs = [];
		
			$bc_item = new bcn_breadcrumb();
			$bc_item->set_title($car->title);
			$bc_item->set_url(site_url().$car->canonicalUrl);
			$bc_item->set_linked(false);
			$trail->breadcrumbs[] = $bc_item;
			

			
			$bc_item = new bcn_breadcrumb();
			$bc_item->set_title('Мотоциклы из Японии');
			$bc_item->set_linked(true);
			$bc_item->set_url(site_url().'/motorcycles/');

			$trail->breadcrumbs[] = $bc_item;
			$trail->breadcrumbs[] =	$bc_home;		
				// Add as second item in list.
				//array_splice( $trail->breadcrumbs, 1, 0, array( $magazine_item ) );
			
			return $trail;			
			
		});



		
		if ($currentUrl != $canonicalUrl){
			$urlparts = wp_parse_url(home_url());
			header("HTTP/1.1 301 Moved Permanently"); 
			header("Location: ". $urlparts['scheme']."://".$urlparts['host'].$canonicalUrl); 
			exit(); 				
		}
	}else{
		$carExists = 0;

		$car = (object) array(
			'marka_name' => 'Этот лот уже продан',
			'model_name' => '',
			'title' => 'Мотоцикл продан',
			'grade' => ''
		);
	}





	
}else{
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: ". $urlparts['scheme']."://".$urlparts['host'].'avto-iz-korei'); 
	exit(); 
}



ob_start();
get_header();
$header = ob_get_clean();
$header = preg_replace('#<title>(.*?)<\/title>#', '<title>Заказать мотоцикл '.$car->title .' '. $car->grade.' из Японии</title>', $header);
echo $header;



// Loop through posts if there are any
if ($carExists == 1):

		?>	
		
		
		<div class="container" style="margin-top:.5rem;">
			<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
				<?php  if(function_exists('bcn_display')): echo bcn_display(true); endif; ?>
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
					<h1><?php echo $car->title;?><span><?php echo $car->grade;?></span></h1>
				</div>
				<div class="row mb-0">
					<div class="col-lg-6">
						<div class="swiper single-auto__slider-gallery">
						    <div class="swiper-wrapper">

									<?php
									$images = explode("#", $car->images);
									$images[] = $car->image0;
									if( $images ): ?>
										
										<?php
											$i = 1;
											foreach ($images as $image ) {
												$class = "";
												$thumbimg = str_replace( '&h=50', '', $image);
												$fullurl = str_replace( '&h=50', '', $image);
												echo '<a href="'.$fullurl.'" class="' . $class . ' swiper-slide" data-slide="'.$i.'" target="_blank"><img src="' . $thumbimg . '" alt="" onload="image_nofoto(this);" onerror="image_error(this);"></a>';
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
								$images = explode("#", $car->images);
								$images[] = $car->image0;
								if( $images ): ?>
									<?php    
										$i = 1;
										foreach ($images as $image ) {
											$class = "";

											//$thumbimg = str_replace( '&h=50', '&w=320', $image);
											$fullurl = str_replace( '&h=50', '', $image);
											
											$thumbimg = $fullurl."&w=320";
											
											echo '<a href="'.$fullurl.'" class="' . $class . ' grid-item gallery-item" data-slide="'.$i.'"><img src="' . $thumbimg . '" alt="" onload="image_nofoto(this);" onerror="image_error(this);"></a>';
											$i++;
										}
									?>
								<?php endif; ?>
											
						</div>	
					</div>
					<div class="col-lg-6">
						<div class="single-auto__params">
							
			
							<dl>
								
								
								
								
								<?php 
								foreach($labelledProps as $label=>$value):
								?>
									<?php if ($value):?>
									<dt><?php echo $label;?></dt>
									<dd><?php echo $value;?></dd>
									<?php endif;?>
								<?php endforeach; ?>
								
							
								
								<?php /*
								<dt>Модель</dt><dd><?php echo $car->marka_name.' '.$car->model_name;?></dd>
								<?php if ($car->grade):?>
								<dt>Комплектация</dt><dd><?php echo $car->grade;?></dd>
								<?php endif;?>
								
								<?php if ($car->year > 1900):?>
									<dt>Год выпуска</dt><dd><?php echo $car->year;?></dd>
								<?php endif;?>									
								<!-- <dt>Объём</dt><dd><?php echo get_field('capacity');?> л</dd>-->
								<dt>Пробег</dt><dd><?php echo number_format($car->mileage, 0, '.', ' ' );?> км</dd>
								<!-- <dt>Тип двигателя</dt><dd><?php echo get_field('engine-type')['label'];?></dd>
								<dt>Тип привода</dt><dd><?php echo get_field('drive-type')['label'];?></dd> -->
						

								<?php if ($car->eng_v):?>
									<dt>Объём</dt><dd><?php echo $car->eng_v;?> см<sup>3</sup></dd>
								<?php endif;?>	
									
								
								<?php if ($car->color):?>
									<dt>Цвет</dt><dd><?php echo $car->color;?></dd>
								<?php endif;?>	

								
								*/ ?>
								<?php if ($car->start):?>
									<dt>Стартовая цена</dt>
									<dd><?php echo number_format ($car->start * 1000, 0, '.', ' ');  ?> ¥</dd>
								<?php endif;?>		
								
								<?php if ($car->finish):?>								
									<dt class="single-auto__price">Цена</dt><dd class="single-auto__price"><?php echo number_format( $car->finish  * 1000, 0, '.', ' ' );?> ¥</dd>
								<?php else: ?>
									<dt class="single-auto__price">Цена в РФ</dt><dd class="single-auto__price">по запросу</dd>
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
			<h1>Данный мотоцикл уже продан</h1>
			<p>Извините, этот мотоцикл уже продан. Но у нас есть много других.</p>
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
<section>
<div class="container">
<h2>Система оценок на аукционах мотоциклов в Японии</h2>

<h3>Аукцион JBA</h3>
<p>S – новый мотоцикл в идеальном состоянии<br>
6 – практически новый мотоцикл, не требующий никакого ремонта<br>
5 – мотоцикл в отличном состоянии, почти не требующий ремонта<br>
4.5 – мотоцикл в очень хорошем состоянии<br>
4 – хороший мотоцикл, не требующий серьезного ремонта или перекраски<br>
3,5 – оценка, близкая к 4, за исключением некоторых погрешностей<br>
3 – необходим небольшой ремонт<br>
2 – необходим капитальный ремонт<br>
1 – мотоцикл в аварийном состоянии<br>
R – модель для запчастей</p>

<p>Иногда рядом с основной отметкой присутствуют дополнительные отметки, для более точной характеристики повреждений:<br>
A – повреждение краски – царапины, сколы<br>
B – вмятина<br>
H – дырка<br>
N – отсутствие детали<br>
P – наличие покраски<br>
R – следы ремонта<br>
S – коррозия, ржавчина<br>
T - поломка<br>
X – необходима замена<br>
W – следы восстановления</p>

<p>Рядом с буквами могут быть указаны цифры от 1 до 3. Это степени повреждения: 1 – незначительный дефект, 2 – деталь требует небольшого ремонта, 3 – деталь может требовать серьезного восстановления или полной замены</p>
<hr>
<h3>Аукцион BDS</h3>
<p>10 – новый мотоцикл, который ранее не стоял на учете или был поставлен на учет недавно<br>
9 – в идеальном состоянии, ремонт не требуется<br>
8-6 – незначительные повреждения, не требующие ремонта, в том числе и изношенности<br>
5 – мелкие замечания<br>
4 – наличие царапин<br>
3 – мотоцикл в среднем состоянии, требующий небольшого ремонта<br>
2 – мотоцикл со значительными повреждениями<br>
1 – мото не пригодное для езды, в аварийном состоянии или идущее на запчасти</p>
<hr>
<h3>Аукцион ARAI</h3>
<p>А – идеальное состояние<br>
В – хорошее состояние<br>
С – удовлетворительное состояние<br>
D – плохое состояние<br>
Е – очень плохое состояние<br>
Х – требуется замена детали или не подлежит восстановлению</p>

<p>Особенность аукциона – специфическое указывание даты. Указывается не год выпуска, а год первой регистрации. Соответственно мотоцикл может быть старше, чем показано в листе.</p>
</div>
</section>
<?php get_footer();?>
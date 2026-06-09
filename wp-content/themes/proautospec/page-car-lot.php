<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
	

global $wp;

if (array_key_exists('country', $wp->query_vars) && isset($wp->query_vars['country'])){ 
	$country = $wp->query_vars['country']; 
}else{
	$country = "korea";
}



if (array_key_exists('car-lot', $wp->query_vars) && isset($wp->query_vars['car-lot'])){
	$lot = $wp->query_vars['car-lot'];
	$slug = $wp->query_vars['car-slug'];
	

	
	
	
	if (array_key_exists('stat', $wp->query_vars) && isset($wp->query_vars['stat'])){ 
		$stat = 1; 
	}else{
		$stat = 0;
	}	

	$currencySign = (object) array( "korea" => "₩ <small>KRW</small>", "china" => "¥ <small>CNY</small>", "japan" => "¥ <small>JPY</small>" ) ;
	$countryFrom = (object) array( "korea" => "из Кореи", "china" => "из Китая", "japan" => "из Японии" ) ;
	$countryOver = (object) array( "korea" => "по Корее", "china" => "по Китаю", "japan" => "по Японии" ) ;
	
	$carExists = 0;
	if ($country == 'japan'){
		$content=file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-cars-" . $country . ".php?client_ip=".USER_IP."&code=".$lot.'&stat='.$stat."&proxiedip=".get_ip_address());
	}else{
		$content=file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-cars-" . $country . ".php?client_ip=".USER_IP."&lot=".$lot."&proxiedip=".get_ip_address());
	}


	$data=json_decode($content);
	//var_dump($data);
	//exit;
	if (@!empty($data->autos[0])){
		$car = $data->autos[0];
		$content = file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-cars-" . $country . ".php?client_ip=".USER_IP."&code=".$car->id.'&stat='.$stat);
		$data = json_decode($content);
		$car = $data->autos[0];
		$car->priceRu = 0; 
		$car->stat = $stat;
		$car->country = $country;
		$car->countryOver = $countryOver->{$country};;
		$car->countryFrom = $countryFrom->{$country};;
		$car->currencySign = $currencySign->{$country};
		
		/*try {
			$historyLinks = json_decode(file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-lists.php?car-id=".$car->id."&list=history"));
			$inspectionLinks = json_decode(file_get_contents( (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" . "/api/get-lists.php?car-id=".$car->id."&list=inspection"));
		} catch (Exception $e) {
			$historyLinks = (object) array(	0 => '', 1 => '');
			$inspectionLinks = (object) array(	0 => '', 1 => '');
		}*/

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
		$nameInUrl = strtolower($nameInUrl);
		$car->title = $car->marka_name.' '.$car->model_name;
				
		
		switch ($country){
			case 'china':
				$priceApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=china&year=" . $car->year . "&price=" . $car->finish . "&volume="  . $car->eng_v;
				if ($car->finish > 0 ){
					$priceApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=china&year=" . $car->year . "&price=" . $car->finish . "&volume="  . $car->eng_v;
				} 
				try {
					
					
					
					$price = json_decode(file_get_contents( $priceApiUrl ) );
					
					
					/*
					print_r($priceApiUrl );
					print_r($price);
			*/
					
					
					if (!empty ($price) ){
					
						$car->apiPrice = $price;
					
						$currencies = array ();
						array_walk(explode(';', $price->result->info[0]->currency ), function ($value, $key) use (&$currencies) {
							list($k, $v) = explode(':', $value);
							$currencies[$k] = $v;
							
						});
							
						$car->currencies = $currencies;
						
						$car->prices['auction-price'] = number_format($price->result->row[0]->tag1, 0, '.', ' ' );
						$car->prices['auction-price-ru'] = number_format($price->result->row[0]->tag1 / $currencies["USDCNY_system"] * $currencies["USDRUB_system"], 0, '.', ' ' );
						
						$car->prices['fraht'] = number_format($price->result->row[3]->tag2, 0, '.', ' ' );
						$car->prices['fraht-ru'] = number_format($price->result->row[3]->tag2 * $currencies["USDRUB_system"], 0, '.', ' ');
						
						$car->prices['country-expenses'] =  number_format($price->result->row[1]->tag1, 0, '.', ' ' );
						$car->prices['country-expenses-ru'] = number_format($price->result->row[1]->tag1 / $currencies["USDCNY_system"] * $currencies["USDRUB_system"], 0, '.', ' ');
						
						$car->prices['customs-services'] =  number_format($price->result->row[5]->tag3, 0, '.', ' ' );
						$car->prices['customs-services-ru'] = $car->prices['customs-services'];
						
				
						
						$car->prices['customs-duty'] = number_format($price->result->info[0]->fiz, 0, '.', ' ' );
						$car->prices['customs-duty-ru'] = number_format( $price->result->info[0]->fiz * $currencies["USDRUB_system"], 0, '.', ' ' );
						
						$car->prices['company-commission'] = number_format($price->result->row[2]->tag3, 0, '.', ' ' );
						$car->prices['company-commission-ru'] = $car->prices['company-commission'];
						
						
						
						if ($car->finish > 0 ){
							$car->priceRu = number_format($price->result->sum * $currencies["USDRUB_system"], 0, '.', ' ' )  . " ₽";
						}
					}
					
					
				
					
				} catch (Exception $e) {
					$car->priceRu = 0; 
				}	
								
				
				
				
				$canonicalUrl = '/avto-iz-kitaya/'. $lot . '-' .$nameInUrl . '/';		
				break;
			case 'korea':
				$priceApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=korea&year=" . $car->year . "&price=" . $car->finish . "&volume="  . $car->eng_v;
				
				if ($car->finish > 0 ){
					$priceApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=korea&year=" . $car->year . "&price=" . $car->finish . "&volume="  . $car->eng_v;
				} 
				try {
					
					
					
					$price = json_decode(file_get_contents( $priceApiUrl ) );
					
					
					/*
					print_r($priceApiUrl );
					print_r($price);
			*/
					
					
					if (!empty ($price) ){
					
						$car->apiPrice = $price;
					
						$currencies = array ();
						array_walk(explode(';', $price->result->info[0]->currency ), function ($value, $key) use (&$currencies) {
							list($k, $v) = explode(':', $value);
							$currencies[$k] = $v;
							
						});
							
						$car->currencies = $currencies;
						
						$car->prices['auction-price'] = number_format($price->result->row[0]->tag1, 0, '.', ' ' );
						$car->prices['auction-price-ru'] = number_format($price->result->row[0]->tag1 / $currencies["USDKRW_system"] * $currencies["USDRUB_system"], 0, '.', ' ' );
						
						$car->prices['fraht'] = number_format($price->result->row[3]->tag2, 0, '.', ' ' );
						$car->prices['fraht-ru'] = number_format($price->result->row[3]->tag2 * $currencies["USDRUB_system"], 0, '.', ' ');
						
						$car->prices['country-expenses'] =  number_format($price->result->row[1]->tag1, 0, '.', ' ' );
						$car->prices['country-expenses-ru'] = number_format($price->result->row[1]->tag1 / $currencies["USDKRW_system"] * $currencies["USDRUB_system"], 0, '.', ' ');
						
						$car->prices['customs-services'] =  number_format($price->result->row[5]->tag3, 0, '.', ' ' );
						$car->prices['customs-services-ru'] = $car->prices['customs-services'];
						
				
						
						$car->prices['customs-duty'] = number_format($price->result->info[0]->fiz, 0, '.', ' ' );
						$car->prices['customs-duty-ru'] = number_format( $price->result->info[0]->fiz * $currencies["USDRUB_system"], 0, '.', ' ' );
						
						$car->prices['company-commission'] = number_format($price->result->row[2]->tag3, 0, '.', ' ' );
						$car->prices['company-commission-ru'] = $car->prices['company-commission'];
						
						
						
						if ($car->finish > 0 ){
							$car->priceRu = number_format($price->result->sum * $currencies["USDRUB_system"], 0, '.', ' ' )  . " ₽";
						}
					}
					
					
				
					
				} catch (Exception $e) {
					$car->priceRu = 0; 
				}	
				
				
				
				
				$canonicalUrl = '/avto-iz-korei/'. $lot . '-' .$nameInUrl . '/';		
				break;
			case 'japan':
				$priceApiUrl = ( empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=japan";

				if ($car->finish > 0 ){
					$priceApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=japan&year=" . $car->year . "&price=" . $car->finish . "&volume="  . $car->eng_v;
				} else if ($car->avg_price > 0) {	
					$priceApiUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . "/api/get-price.php?client_ip=".USER_IP."&country=japan&year=" . $car->year . "&price=" . $car->avg_price . "&volume="  . $car->eng_v;
				}
				if ($stat == 1){
					$priceApiUrl = $priceApiUrl.'&stat=1';
				}
				
				try {
					
					
					
					$price = json_decode(file_get_contents( $priceApiUrl ) );
					
					
					
					
					//print_r($price);
			
					
					
					if (!empty ($price) ){
					
						$car->apiPrice = $price;
					
						$currencies = array ();
						array_walk(explode(';', $price->result->info[0]->currency ), function ($value, $key) use (&$currencies) {
							list($k, $v) = explode(':', $value);
							$currencies[$k] = $v;
							
						});
							
						$car->currencies = $currencies;
						
						$car->prices['auction-price'] = number_format($price->result->row[0]->tag1, 0, '.', ' ' );
						$car->prices['auction-price-ru'] = number_format($price->result->row[0]->tag1 / $currencies["USDJPY_system"] * $currencies["USDRUB_system"], 0, '.', ' ' );
						
						$car->prices['fraht'] = number_format($price->result->row[3]->tag2, 0, '.', ' ' );
						$car->prices['fraht-ru'] = number_format($price->result->row[3]->tag2 * $currencies["USDRUB_system"], 0, '.', ' ');
						
						$car->prices['country-expenses'] =  number_format($price->result->row[1]->tag1, 0, '.', ' ' );
						$car->prices['country-expenses-ru'] = number_format($price->result->row[1]->tag1 / $currencies["USDJPY_system"] * $currencies["USDRUB_system"], 0, '.', ' ');
						
						$car->prices['customs-services'] =  number_format($price->result->row[5]->tag3, 0, '.', ' ' );
						$car->prices['customs-services-ru'] = $car->prices['customs-services'];
						
				
						
						$car->prices['customs-duty'] = number_format($price->result->info[0]->fiz, 0, '.', ' ' );
						$car->prices['customs-duty-ru'] = number_format( $price->result->info[0]->fiz * $currencies["USDRUB_system"], 0, '.', ' ' );
						
						$car->prices['company-commission'] = number_format($price->result->row[2]->tag3, 0, '.', ' ' );
						$car->prices['company-commission-ru'] = $car->prices['company-commission'];
						
						
						
						if ($car->finish > 0 ){
							$car->priceRu = number_format($price->result->sum * $currencies["USDRUB_system"], 0, '.', ' ' )  . " ₽";
						} else if ($car->avg_price > 0) {
							$car->priceRu = "~ " . number_format($price->result->sum * $currencies["USDRUB_system"], 0, '.', ' ' )  . " ₽";
						}
					}
					
					
				
					
				} catch (Exception $e) {
					$car->priceRu = 0; 
				}	
				
				
				if ($stat == 0)
					$canonicalUrl = '/avto-iz-yaponii/'. $lot . '-' .$nameInUrl . '/';		
				else 
					$canonicalUrl = '/avto-iz-yaponii/statistika/-/'. $lot . '-' .$nameInUrl . '/';		
				break;
		}	
		$car->canonicalUrl = $canonicalUrl;
		
		add_action( 'bcn_after_fill', function($trail) use ( $car) {
			$bc_home = array_pop($trail->breadcrumbs);
			$trail->breadcrumbs = [];
		
			$bc_item = new bcn_breadcrumb();
			$bc_item->set_title($car->title);
			$bc_item->set_url(site_url().$car->canonicalUrl);
			$bc_item->set_linked(false);
			$trail->breadcrumbs[] = $bc_item;
			
			
			
			
			$bc_item = new bcn_breadcrumb();
			switch ($car->country){
				case 'china':
					$bc_item->set_url(site_url().'/avto-iz-kitaya/catalog/');	
					$bc_item->set_title('Автомобили из Китая');				
					break;
				case 'korea':
					$bc_item->set_url(site_url().'/avto-iz-korei/catalog/');	
					$bc_item->set_title('Автомобили из Кореи');					
					break;
				case 'japan':
					if ($car->stats == 0){
						$bc_item->set_url(site_url().'/avto-iz-yaponii/catalog/');
						$bc_item->set_title('Автомобили из Японии');			
					}else{
						$bc_item->set_url(site_url().'/avto-iz-yaponii/statistika/');
						$bc_item->set_title('Статистика продаж из Японии');		
					}
				break;
			}
			
			$bc_item->set_linked(true);
			

			
			
			
			

			$trail->breadcrumbs[] = $bc_item;
			$trail->breadcrumbs[] =	$bc_home;		
				// Add as second item in list.
				//array_splice( $trail->breadcrumbs, 1, 0, array( $magazine_item ) );
			
			return $trail;			
			
		});
	
	
	
		$currentUrl = $_SERVER['REQUEST_URI'];
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
$header = preg_replace('#<title>(.*?)<\/title>#', '<title>Заказать '.$car->marka_name.' '.$car->model_name .' '. $car->grade.'</title>', $header);
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
					<h1><?php echo $car->marka_name.' '.$car->model_name;?> <span><?php echo $car->grade;?>, <?php echo $car->year;?> года</span>
						
					<?php echo $countryFrom->{$country};?>
					</h1>
					
				</div>
				<div class="row mb-0">
					<div class="col-lg-6">
						<div class="swiper single-auto__slider-gallery">
						    <div class="swiper-wrapper">

									<?php
									$car->images = str_replace( '8.ajes.com', '7.ajes.com', $car->images);
									$images = explode("#", $car->images);
									
									if( $images ): ?>
										
										<?php
											$i = 1;
											foreach ($images as $image ) {
												$class = "";
												$thumbimg = str_replace( '&h=50', '', $image);
												$fullurl = str_replace( '&h=50', '', $image);
												echo '<a href="'.$fullurl.'" class="' . $class . ' swiper-slide" data-slide="'.$i.'" target="_blank"><img src="' . $thumbimg . '" alt=""></a>';
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
								
								if( $images ): ?>
									<?php    
										$i = 1;
										foreach ($images as $image ) {
											$class = "";

											//$thumbimg = str_replace( '&h=50', '&w=320', $image);
											$fullurl = str_replace( '&h=50', '', $image);
											
											$thumbimg = $fullurl."&w=320";
											
											echo '<a href="'.$fullurl.'" class="' . $class . ' grid-item gallery-item" data-slide="'.$i.'"><img src="' . $thumbimg . '" alt=""></a>';
											$i++;
										}
									?>
								<?php endif; ?>
											
						</div>	
					</div>
					<div class="col-lg-6">
						<div class="single-auto__params">
							
			
							<dl>
								<dt>Модель</dt><dd><?php echo $car->marka_name.' '.$car->model_name;?></dd>
								<dt>Комплектация</dt><dd><?php echo $car->grade;?><?php if ($car->equip):  echo "<br>".$car->equip; endif; ?></dd>
								<dt>Год выпуска</dt><dd><?php echo $car->year;?></dd>
								<!-- <dt>Объём</dt><dd><?php echo get_field('capacity');?> л</dd>-->
								<dt>Пробег</dt><dd><?php echo number_format($car->mileage, 0, '.', ' ' );?> км</dd>
								<!-- <dt>Тип двигателя</dt><dd><?php echo get_field('engine-type')['label'];?></dd>
								<dt>Тип привода</dt><dd><?php echo get_field('drive-type')['label'];?></dd> -->
						
								<?php if ($car->kuzov):?>
									<dt>Кузов</dt><dd><?php echo $car->kuzov;?><?php if ($car->serial):  echo "<br>".$car->serial; endif; ?></dd>
								<?php endif;?>
								<?php if ($car->eng_v):?>
									<dt>Объём</dt><dd><?php echo $car->eng_v;?> см<sup>3</sup></dd>
								<?php endif;?>	
								<?php if ($car->priv):?>
									
									<?php 
										switch ($car->priv){
											case "D":
											case "d":
												echo "<dt>Топливо</dt><dd>дизель</dd>";
												break;
											case "G":
											case "g":
												echo "<dt>Топливо</dt><dd>бензин</dd>";
												break;
										}
									?></dd>
								<?php endif; ?>										
								<?php if ($car->kpp):?>
									<dt>КПП</dt><dd><?php echo $car->kpp;?></dd>
								<?php endif;?>									
								<?php if ($car->color):?>
									<dt>Цвет</dt><dd><?php echo $car->color;?></dd>
								<?php endif;?>	
								
								<?php if ($car->start):?>
									<dt>Стартовая цена</dt>
									<dd><?php echo number_format ($car->start, 0, '.', ' ');  ?> <?php echo $currencySign->{$country}; ?></dd>
								<?php endif;?>		
								
								<?php if ($car->finish):?>								
									<dt>Цена</dt><dd><?php echo number_format( $car->finish, 0, '.', ' ' );?> <?php echo $currencySign->{$country}; ?></dd>
								<?php endif; ?>
								
								
								<?php if ($car->avg_price):?>
									<dt>Средняя цена</dt>
									<dd>~ <?php echo number_format ($car->avg_price, 0, '.', ' ');  ?> <?php echo $currencySign->{$country}; ?></dd>
								<?php endif;?>		
								
								
								<dt class="single-auto__price">Цена в РФ</dt>
								
								<dd class="single-auto__price">
									<?php if ($car->priceRu != 0 ):?>
									
									<?php echo $car->priceRu; ?>
									<?php else: ?>
									По запросу
									<?php endif;?>
								</dd>
								
							</dl>

							<div class="single-auto__buttons">
								<?php if ( ( $country == 'japan' ) && ( $car->priceRu != 0)  ): ?>
								
									<a class="btn btn-grey" href="#" data-bs-toggle="modal" data-bs-target="#estimate-dialog" class="btn btn-white" data-model="<?php echo $car->marka_name.' '.$car->model_name;?>">Подробный расчёт</a> 
									<div class="modal fade" id="estimate-dialog" tabindex="-1" aria-labelledby="estimate-dialog">
										<div class="modal-dialog modal-dialog-centered modal-xl">
											<div class="modal-content">
												<div class="modal-header">
													<div class="modal-title">
														Подробный расчёт стоимости в РФ
													</div>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
												</div>
												<div class="container estimation-details">
													<div class="row mb-4">
														<div class="col-lg-12">
															<h2 class="text-center">Стоимость авто на аукционе <var class="estimation-details__price-auc estimation-details__price-ru"><?php echo $car->prices['auction-price'];?> ¥ </var><var class="estimation-details__price-auc estimation-details__price-ru"><?php echo $car->prices['auction-price-ru'];?> ₽ </var></h2>
														</div>
													</div>
													<div class="row">
														<div class="col-lg-6">
															<div class="d-flex flex-row">
																<h2>Расходы по Японии </h2>
																<var class="estimation-details__price-jp"><?php echo $car->prices['country-expenses'];?> ¥</var><var class="estimation-details__price-ru"><?php echo $car->prices['country-expenses-ru'];?> ₽</var>
															</div>
															<ul>
																<li>Оформление экспортных документов</li>
																<li>Доставка до порта отправки в Японии</li>
																<li>Комиссия Японской компании на стоимость авто</li>
															</ul>
														</div>
														<div class="col-lg-6">
															<div class="d-flex flex-row">
																<h2>Фрахт до Владивостока </h2>
																<var class="estimation-details__price-us"><?php echo $car->prices['fraht'];?> $</var> <var class="estimation-details__price-ru"><?php echo $car->prices['fraht-ru'];?> ₽</var>
															</div>
															<p>Фрахт из Японии указан усредненным значением для предварительных и удобных расчетов. Данное значение может измениться плюс минус на 150 долларов в зависимости от перевозчика и порта отгрузки.</p>
														</div>
														<div class="col-lg-6">
															<div class="d-flex flex-row">
																<h2>Услуги по таможенному оформлению</h2>
																<var class="estimation-details__price-ru"><?php echo $car->prices['customs-services-ru'];?> ₽</var>
															</div>
															<ul>
																<li>Услуги таможенного брокера</li>
																<li>Экспертиза</li>
																<li>Хранение на СВХ 5 дней</li>
																<li>Получение СБКТС и ЭлПТС</li>
																<li>Фото опись и приёмка авто</li>
															</ul>
														</div>
														<div class="col-lg-6 d-flex flex-column">
															<div class="d-flex flex-row">
																<h2>Пошлина</h2>
																<var class="estimation-details__price-ru"><?php echo $car->prices['customs-duty-ru'];?> ₽</var>
															</div>
															<p>Актуальная пошлина рассчитывается таможней на день растаможивания.</p>
															
															
															<div class="d-flex flex-row mt-auto" style="margin-bottom:1rem;">
																<h2>Комиссия компании</h2>
																<var class="estimation-details__price-ru"><?php echo $car->prices['company-commission-ru'];?> ₽</var>
															</div>
														</div>
													
														<div class="col-lg-12 mt-4">
															<span class="h1">Итого <var class="estimation-details__total"><?php echo $car->priceRu;?></var> <sup>*</sup></span>
														</div>
													</div>
													<div class="estimation-details__footnote">
														<p>*Если авто является санкционным, то стоимость будет выше, уточняйте у наших менеджеров</p>
														<p>**Стоимость является ориентировочной, приводится исключительно в ознакомительных целях, включая все расходы в г.Владивосток. Расчёт может быть некорректным. Для более точного расчёта и по всем вопросам - оставьте заявку на бесплатную консультацию, наши специалисты с радостью вам помогут во всём разобраться!</p>
													</div>
												</div>
											</div>
										</div>
									</div>	
								
								<?php endif; ?>
								<?php if (( ( $country == 'korea' ) || ( $country == 'china' )) && ( $car->priceRu != 0)  ): ?>
								
									<a class="btn btn-grey" href="#" data-bs-toggle="modal" data-bs-target="#estimate-dialog" class="btn btn-white" data-model="<?php echo $car->marka_name.' '.$car->model_name;?>">Подробный расчёт</a> 
									<div class="modal fade" id="estimate-dialog" tabindex="-1" aria-labelledby="estimate-dialog">
										<div class="modal-dialog modal-dialog-centered modal-xl">
											<div class="modal-content">
												<div class="modal-header">
													<div class="modal-title">
														Подробный расчёт стоимости в РФ
													</div>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
												</div>
												<div class="container estimation-details">
													<div class="row mb-4">
														<div class="col-lg-12">
															<h2 class="text-center">Стоимость авто на аукционе <var class="estimation-details__price-auc estimation-details__price-ru"><?php echo $car->prices['auction-price'].' '.$car->currencySign;?> </var><var class="estimation-details__price-auc estimation-details__price-ru"><?php echo $car->prices['auction-price-ru'];?> ₽ </var></h2>
														</div>
													</div>
													<div class="row">
														<div class="col-lg-6">
															<div class="d-flex flex-row">
																<h2>Расходы <?php echo $car->countryOver;?> </h2>
																<var class="estimation-details__price-jp"><?php echo $car->prices['country-expenses'].' '.$car->currencySign;?></var><var class="estimation-details__price-ru"><?php echo $car->prices['country-expenses-ru'];?> ₽</var>
															</div>
															<ul>
																<li>Оформление экспортных документов</li>
																
																<li>Комиссия корейской компании на стоимость авто</li>
															</ul>
														</div>
														<div class="col-lg-6">
															<div class="d-flex flex-row">
																<h2>Фрахт <?php echo $car->countryFrom;?> до Владивостока </h2>
																<var class="estimation-details__price-us"><?php echo $car->prices['fraht'];?> $</var> <var class="estimation-details__price-ru"><?php echo $car->prices['fraht-ru'];?> ₽</var>
															</div>
															<p>Фрахт указан усредненным значением для предварительных и удобных расчетов. Данное значение может измениться плюс минус на 150 долларов в зависимости от перевозчика.</p>
														</div>
														<div class="col-lg-6">
															<div class="d-flex flex-row">
																<h2>Услуги по таможенному оформлению</h2>
																<var class="estimation-details__price-ru"><?php echo $car->prices['customs-services-ru'];?> ₽</var>
															</div>
															<ul>
																<li>Услуги таможенного брокера</li>
																<li>Экспертиза</li>
																<li>Хранение на СВХ 5 дней</li>
																<li>Получение СБКТС и ЭлПТС</li>
																<li>Фото опись и приёмка авто</li>
															</ul>
														</div>
														<div class="col-lg-6 d-flex flex-column">
															<div class="d-flex flex-row">
																<h2>Пошлина</h2>
																<var class="estimation-details__price-ru"><?php echo $car->prices['customs-duty-ru'];?> ₽</var>
															</div>
															<p>Актуальная пошлина рассчитывается таможней на день растаможивания.</p>
															
															
															<div class="d-flex flex-row mt-auto" style="margin-bottom:1rem;">
																<h2>Комиссия компании</h2>
																<var class="estimation-details__price-ru"><?php echo $car->prices['company-commission-ru'];?> ₽</var>
															</div>
														</div>
													
														<div class="col-lg-12 mt-4">
															<span class="h1">Итого <var class="estimation-details__total"><?php echo $car->priceRu;?></var> <sup>*</sup></span>
														</div>
													</div>
													<div class="estimation-details__footnote">
														<div class="row d-none">
															
															<div class="col-lg-4"><p>Курс доллара: </p></div>
															<div class="col-lg-4"><p></p></div>
															<div class="col-lg-4"><p></p></div>
														</div>
													</div>
													<div class="estimation-details__footnote">
														<p>*Если авто является санкционным, то стоимость будет выше, уточняйте у наших менеджеров</p>
														<p>**Стоимость является ориентировочной, приводится исключительно в ознакомительных целях, включая все расходы в г.Владивосток. Расчёт может быть некорректным. Для более точного расчёта и по всем вопросам - оставьте заявку на бесплатную консультацию, наши специалисты с радостью вам помогут во всём разобраться!</p>
													</div>
												</div>
											</div>
										</div>
									</div>	
								
								<?php endif; ?>								
								<a class="btn" href="#" data-bs-toggle="modal" data-bs-target="#order-dialog" class="btn btn-white" data-model="<?php echo $car->marka_name.' '.$car->model_name;?>">Хочу купить</a>
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
			<h1>Данный автомобиль уже продан</h1>
			<p>Извините, этот автомобиль уже продан. Но у нас есть много других.</p>
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
<?php if ($country ==  "japan"):?>
<noindex>
	<section class="mt-4">
		<?php
		get_template_part( 'template-parts/landing/b-list-legend' );
		?>
	</section>
</noindex>
<?php endif;?>
<?php get_footer();?>
if (typeof statistics === 'undefined'){
	var statistics = false;

}

if (typeof renderingType === 'undefined'){
	var renderingType = 'cards';
}

	
	function renderListing(data){
		console.log(data);
		jQuery('#cars-listing .car-loaded').remove();


		for (var i = 0; i < data.length; i++) {
			car = data[i];
			
			let itemHtml = jQuery('#car-item.d-none').clone().removeClass('d-none').removeAttr('id').addClass('car-loaded');
			let name = nameInUrl = car.marka_name + ' ' + car.model_name;
			if (car.grade) nameInUrl = nameInUrl + '-' + car.grade.replace(/&#(\d+);/g, function(match, number){/* console.log(match + ' ' + number); */return String.fromCharCode(number); });
			nameInUrl = nameInUrl
				.replace(/[^a-zA-Z0-9\s-]/g,'')
				.trim()
				.replace(/\s+/g, '-')
				.replace(/-+/g, '-')
				.toLowerCase();
			
			let itemDetailedUrl;
			
			
			
			
			if ( renderingType == 'cards'){
				if (! statistics){
					itemDetailedUrl = '/avto-iz-yaponii/'+ car.id + '-' + nameInUrl + '/';
				}else{
					itemDetailedUrl = '/avto-iz-yaponii/statistika/-/'+ car.id + '-' + nameInUrl + '/';
				}
				itemHtml.find('.car-model-name').html(name);
				let itemTitleFull = name;
				itemHtml.find('.car-link').attr({'href': itemDetailedUrl});
				if (car.grade){
					itemHtml.find('.car-model-specification').html(car.grade);
					itemTitleFull += ' ' + car.grade.replace(/&#(\d+);/g, function(match, number){ /* console.log(match + ' ' + number);*/ return String.fromCharCode(number); });
				}
				itemHtml.find('h3').attr('title', itemTitleFull);

				
				if ( (car.year) && (parseInt(car.year) > 0))
					itemHtml.find('.car-item__params').append('<dt>Год</dt><dd>' + car.year + '</dd>');


				if (car.kpp)
					itemHtml.find('.car-item__params').append('<dt>Привод</dt><dd>' + car.kpp + '</dd>');
				
				if ((car.eng_v) && (parseInt(car.eng_v) > 0))
					itemHtml.find('.car-item__params').append('<dt>Объём</dt><dd>' + car.eng_v + ' см<sup>3</sup></su></dd>');


				if ((car.mileage) && (parseInt(car.mileage) > 0))
					itemHtml.find('.car-item__params').append('<dt>Пробег</dt><dd>' + number_format(car.mileage, 0) + ' км</dd>');


				/*if (car.mileage)
					itemHtml.find('.car-item__params').append('<dt>Пробег</dt><dd>' + number_format(car.mileage, 0) + ' км</dd>');
				*/


				//itemHtml.find('.car-price-value').html('~ '+number_format(car.finish, 0) + ' ₩')
				if (car.finish > 0){
					itemHtml.find('.car-price-value').html(''+number_format(car.finish * course, 0) + ' ₽')
				}else if (car.avg_price > 0){
					itemHtml.find('.car-price-value').html('~ '+number_format(car.avg_price * course, 0) + ' ₽')
				}else if (car.start > 0){
					itemHtml.find('.car-price-value').html('от '+number_format(car.start * course, 0) + ' ₽')
				}else{ 
					itemHtml.find('.car-price-value').html('по запросу')
				}
				if (window.location.href.indexOf("gruzoviki") != 0){
					itemHtml.find('.car-price-value').html('по запросу')
				}



				images = car.images.split('#');
				images = [...new Set(images)];
				if(images.length > 1){
					
					let $imgHtml = jQuery(itemHtml).find('.car-img');
					jQuery(itemHtml).find('.car-img').first().remove();
					for (let j=0; j < ( 5 < images.length ? 5 : images.length); j++){
						let $curItem = $imgHtml.clone();
						$curItem.attr({'src': images[j].replace('http://', 'https://').replace('&h=50', '').replace('8.ajes.com', '7.ajes.com') + "&w=320"});
						$curItem.attr({'onerror': "this.src='/images/no-photo.png'"});
						
						
						jQuery(itemHtml).find('.car-item__pic .car-link').append($curItem);
					}
					//console.log($div.prop('outerHTML'));
					
					
					//jQuery(itemHtml.find('.cart-img-2')[0]).css({'background-image': 'url("'+images[1].replace('&h=50', '')+'")'});
				}else{
					jQuery(itemHtml.find('.car-img')).attr({'src': images[0].replace('&h=50', '' + "&w=320")});
					
					//jQuery(cart.find('.card-line__img')[1]).remove();
				}
				


			}else{
				itemDetailedUrl = '/avto-iz-yaponii/'+ car.id + '-' + nameInUrl + '/';
				itemHtml.find('.car-link').attr({'href': itemDetailedUrl});
				
				if (car.lot){
					itemHtml.find('.car-lot').html(car.lot);
				}
				itemHtml.find('.car-model-name').html(name);
				itemHtml.find('.car-auction').html(car.auction);
				let aucDate = new Date(car.auction_date);
				itemHtml.find('.car-auction-date').html(aucDate.toLocaleDateString('ru') + ' [' + aucDate.toLocaleTimeString('ru', {  hour: "2-digit", minute: "2-digit", hour12: false}) + ']');
				
				itemHtml.find('.car-year').html(car.year);
				itemHtml.find('.car-kuzov').html(car.kuzov);
				itemHtml.find('.car-grade').html(car.grade);
				
				itemHtml.find('.car-mileage').html(''+car.mileage + ' км');
				itemHtml.find('.car-rate').html(car.rate);
				if (['4', '4.5', '5', '6', 'S'].includes(car.rate))
					itemHtml.find('.car-rate').addClass('car-rate--good');
				else if (['3','3.5'].includes(car.rate))
					itemHtml.find('.car-rate').addClass('car-rate--average');
				else if (['G', 'R', 'RA', 'RB', 'X', '1', '2'].includes(car.rate))
					itemHtml.find('.car-rate').addClass('car-rate--warning')
				
				itemHtml.find('.car-kpp').html(car.kpp);
				itemHtml.find('.car-color').html(car.color);
				if (car.eng_v > 0 )
					itemHtml.find('.car-eng').html(car.eng_v + ' см<sup>3</sup>');
				
				
				
				if (car.finish > 0){
					itemHtml.find('.car-price-finish').html('= '+number_format(car.finish * course, 0) + ' ₽')
				}
				if (car.avg_price > 0){
					itemHtml.find('.car-price-average').html('~ '+number_format(car.avg_price * course, 0) + ' ₽')
				}
				if (car.start > 0){
					itemHtml.find('.car-price-start').html('  '+number_format(car.start * course, 0) + ' ₽')
				}
				//itemHtml.find('.car-price-value').html('по запросу')
				images = car.images.split('#');
				if(images.length > 1){

					let $imgHtml = jQuery(itemHtml).find('.car-img');
					jQuery(itemHtml).find('.car-img').first().remove();
					for (let j=0; j < 2; j++){
						let $curItem = $imgHtml.clone();
						$curItem.attr({'src': images[j].replace('http://', 'https://').replace('&h=50', '') + "&w=320"});
						$curItem.attr({'onerror': "this.src='/images/no-photo.png'"});


						jQuery(itemHtml).find('.car-item__pic .car-link').append($curItem);
					}
					//console.log($div.prop('outerHTML'));


					//jQuery(itemHtml.find('.cart-img-2')[0]).css({'background-image': 'url("'+images[1].replace('&h=50', '')+'")'});
				}else{
					jQuery(itemHtml.find('.car-img')).attr({'src': images[0].replace('&h=50', '' + "&w=320")});

					//jQuery(cart.find('.card-line__img')[1]).remove();
				}
						

			}

			let $newEl = jQuery('#cars-listing .row').first().append(itemHtml);
				


		}
		
		if ( renderingType == 'cards'){
			jQuery('.car-loaded .car-item__pic .car-link').HoverMouseCarousel();
		}
		


	}
